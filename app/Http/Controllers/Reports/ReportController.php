<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $svc) {}

    public function revenue(Request $request): Response
    {
        $this->authorize('reports.financial');

        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;

        $data = $this->svc->revenue($from, $to, $branchId);

        $totalRevenue = array_sum(array_column($data['byDay'], 'revenue'));
        $totalRefunds = array_sum(array_column($data['byDay'], 'refunds'));

        return Inertia::render('Reports/Revenue', [
            'byDay' => $data['byDay'],
            'byMethod' => $data['byMethod'],
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'netRevenue' => $totalRevenue - $totalRefunds,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => compact('from', 'to', 'branchId'),
        ]);
    }

    public function appointments(Request $request): Response
    {
        $this->authorize('reports.view');

        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;

        $rows = $this->svc->appointmentReport($from, $to, $branchId);

        return Inertia::render('Reports/Appointments', [
            'rows' => $rows,
            'total' => array_sum(array_column($rows, 'count')),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => compact('from', 'to', 'branchId'),
        ]);
    }

    public function profitLoss(Request $request): Response
    {
        $this->authorize('reports.financial');

        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;

        $data = $this->svc->profitLoss($from, $to, $branchId);

        return Inertia::render('Reports/ProfitLoss', [
            ...$data,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => compact('from', 'to', 'branchId'),
        ]);
    }

    public function crm(Request $request): Response
    {
        $this->authorize('reports.view');

        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        return Inertia::render('Reports/Crm', [
            'conversion' => $this->svc->crmConversion($from, $to),
            'bySource' => $this->svc->leadsBySource($from, $to),
            'filters' => compact('from', 'to'),
        ]);
    }

    public function debt(Request $request): Response
    {
        $this->authorize('reports.financial');

        $branchId = $request->branch_id;
        $rows = $this->svc->debtAging($branchId);

        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        foreach ($rows as $r) {
            $buckets[$r['bucket']] = ($buckets[$r['bucket']] ?? 0) + $r['remaining'];
        }

        return Inertia::render('Reports/Debt', [
            'rows' => $rows,
            'buckets' => $buckets,
            'totalOutstanding' => array_sum(array_column($rows, 'remaining')),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => ['branch_id' => $branchId],
        ]);
    }
}
