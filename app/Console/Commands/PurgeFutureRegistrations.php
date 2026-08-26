<?php

namespace App\Console\Commands;

use App\Models\ScheduleRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Đăng ký khám chỉ thuộc về đúng ngày hôm đó — ngày mai không được có sẵn danh sách.
 * Đặt trước cho ngày khác là việc của Lịch hẹn.
 *
 * Dọn các đăng ký lỡ tạo cho ngày tương lai (do luồng cũ cho phép). Xoá mềm nên
 * khôi phục lại được; lịch hẹn gốc vẫn còn nguyên, đến ngày bệnh nhân tới thì
 * lễ tân đăng ký lại trong ngày.
 */
class PurgeFutureRegistrations extends Command
{
    protected $signature = 'registrations:purge-future
                            {--dry-run : Chỉ đếm và hiển thị, không xoá}';

    protected $description = 'Xoá mềm các đăng ký khám bị tạo cho ngày ở tương lai';

    public function handle(): int
    {
        $today = today()->toDateString();

        $query = ScheduleRegistration::query()
            ->where('registration_date', '>', $today);

        $rows = (clone $query)->with('patient')
            ->orderBy('registration_date')->orderBy('visit_time')->get();

        if ($rows->isEmpty()) {
            $this->info('Không có đăng ký nào ở ngày tương lai.');

            return self::SUCCESS;
        }

        $this->table(
            ['Ngày khám', 'Giờ', 'Bệnh nhân', 'Lịch hẹn gốc'],
            $rows->map(fn ($r) => [
                $r->registration_date->format('d/m/Y'),
                $r->visit_time ? substr($r->visit_time, 0, 5) : '—',
                $r->patient?->full_name ?? '?',
                $r->appointment_id ?? '— (tạo tay)',
            ])
        );

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] Sẽ xoá {$rows->count()} đăng ký ở ngày tương lai.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        Log::channel('daily')->info('Dọn đăng ký khám ngày tương lai', [
            'after'   => $today,
            'deleted' => $deleted,
            'codes'   => $rows->pluck('code')->all(),
        ]);

        $this->info("Đã xoá {$deleted} đăng ký ở ngày tương lai (xoá mềm, khôi phục được).");

        return self::SUCCESS;
    }
}
