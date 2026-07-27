<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServerStatusController extends Controller
{
    public function index(): Response
    {
        $this->authorize('settings.view');

        return Inertia::render('Admin/ServerStatus/Index');
    }

    public function data(): JsonResponse
    {
        $this->authorize('settings.view');

        return response()->json([
            'cpu' => $this->cpuStats(),
            'memory' => $this->memoryStats(),
            'disk' => $this->diskStats(),
            'uptime' => $this->uptimeSeconds(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->format('H:i:s d/m/Y'),
        ]);
    }

    private function cpuStats(): array
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $cores = $this->cpuCoreCount();

        return [
            'load' => $load ?: null,
            'cores' => $cores,
            'percent' => $load ? round(min(100, ($load[0] / max($cores, 1)) * 100), 1) : null,
        ];
    }

    private function cpuCoreCount(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');

            return max(1, substr_count((string) $cpuinfo, 'processor'));
        }

        return max(1, (int) (getenv('NUMBER_OF_PROCESSORS') ?: 1));
    }

    private function memoryStats(): ?array
    {
        if (! is_file('/proc/meminfo')) {
            return null;
        }

        $meminfo = [];
        foreach (explode("\n", (string) file_get_contents('/proc/meminfo')) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) {
                $meminfo[$m[1]] = (int) $m[2];
            }
        }

        $totalKb = $meminfo['MemTotal'] ?? 0;
        $availableKb = $meminfo['MemAvailable'] ?? ($meminfo['MemFree'] ?? 0);
        $usedKb = max(0, $totalKb - $availableKb);

        return [
            'total_mb' => round($totalKb / 1024, 1),
            'used_mb' => round($usedKb / 1024, 1),
            'percent' => $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : null,
        ];
    }

    private function diskStats(): array
    {
        $path = base_path();
        $total = @disk_total_space($path) ?: 0;
        $free = @disk_free_space($path) ?: 0;
        $used = max(0, $total - $free);

        return [
            'total_gb' => round($total / 1024 ** 3, 1),
            'used_gb' => round($used / 1024 ** 3, 1),
            'percent' => $total > 0 ? round(($used / $total) * 100, 1) : null,
        ];
    }

    private function uptimeSeconds(): ?int
    {
        if (! is_file('/proc/uptime')) {
            return null;
        }

        $contents = (string) file_get_contents('/proc/uptime');

        return (int) floatval(explode(' ', $contents)[0]);
    }
}
