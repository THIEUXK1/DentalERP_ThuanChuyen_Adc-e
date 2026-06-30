<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FundAccount;
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

    public function dailySchedule(Request $request): Response
    {
        $this->authorize('reports.view');

        $today    = now()->toDateString();
        $from     = $request->from     ?? ($request->date ?? $today);
        $to       = $request->to       ?? $from;
        $branchId = $request->branch_id ? (int) $request->branch_id : null;
        $doctorId = $request->doctor_id ? (int) $request->doctor_id : null;
        $status   = $request->status   ?? null;

        // Cap range to 90 days to avoid memory issues
        if ($to < $from) $to = $from;

        $apts = Appointment::with(['patient', 'doctor', 'service', 'chair'])
            ->whereBetween('scheduled_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($status,   fn ($q) => $q->where('status', $status))
            ->orderBy('scheduled_at')
            ->orderBy('doctor_id')
            ->get()
            ->map(fn ($a) => [
                'id'               => $a->id,
                'code'             => $a->code,
                'date'             => $a->scheduled_at->format('Y-m-d'),
                'date_label'       => $a->scheduled_at->format('d/m/Y'),
                'patient'          => $a->patient?->full_name ?? '—',
                'patient_phone'    => $a->patient?->phone ?? '',
                'doctor'           => $a->doctor?->full_name ?? 'Chưa gán',
                'doctor_id'        => $a->doctor_id,
                'service'          => $a->service?->name ?? '—',
                'chair'            => $a->chair?->name ?? '—',
                'scheduled_at'     => $a->scheduled_at->format('H:i'),
                'ends_at'          => $a->ends_at->format('H:i'),
                'duration_minutes' => $a->duration_minutes,
                'status'           => $a->status->value,
                'status_label'     => $a->status->label(),
                'notes'            => $a->notes ?? '',
            ]);

        $isRange = $from !== $to;

        if ($isRange) {
            // Group by date → then by doctor within each date
            $byDate = $apts->groupBy('date')->map(fn ($dateRows, $date) => [
                'date'       => $date,
                'date_label' => $dateRows->first()['date_label'],
                'total'      => $dateRows->count(),
                'byDoctor'   => $dateRows->groupBy('doctor')->map(fn ($rows, $name) => [
                    'name'  => $name,
                    'rows'  => $rows->values()->all(),
                    'total' => $rows->count(),
                ])->values()->all(),
            ])->values()->all();
            $byDoctor = null;
        } else {
            // Single day: group by doctor only
            $byDate = null;
            $byDoctor = $apts->groupBy('doctor')->map(fn ($rows, $name) => [
                'name'  => $name,
                'rows'  => $rows->values()->all(),
                'total' => $rows->count(),
            ])->values()->all();
        }

        return Inertia::render('Reports/DailySchedule', [
            'byDoctor' => $byDoctor,
            'byDate'   => $byDate,
            'isRange'  => $isRange,
            'total'    => $apts->count(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'doctors'  => Employee::doctors()->where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'statuses' => collect(AppointmentStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'filters'  => compact('from', 'to', 'branchId', 'doctorId', 'status'),
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

    public function cashflow(Request $request): Response
    {
        $this->authorize('reports.financial');

        $from          = $request->from ?? now()->startOfMonth()->toDateString();
        $to            = $request->to ?? now()->toDateString();
        $fundAccountId = $request->fund_account_id;

        $rows = $this->svc->cashflowByDay($from, $to, $fundAccountId);

        $totalIncome  = array_sum(array_column($rows, 'income'));
        $totalExpense = array_sum(array_column($rows, 'expense'));

        $fundAccounts = FundAccount::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'type_label' => $f->typeLabel(), 'current_balance' => $f->currentBalance()]);

        return Inertia::render('Reports/Cashflow', [
            'rows'          => $rows,
            'totalIncome'   => $totalIncome,
            'totalExpense'  => $totalExpense,
            'net'           => $totalIncome - $totalExpense,
            'fundAccounts'  => $fundAccounts,
            'filters'       => compact('from', 'to', 'fundAccountId'),
        ]);
    }

    public function performance(Request $request): Response
    {
        $this->authorize('reports.financial');

        $period   = $request->period ?? now()->format('Y-m');
        $branchId = $request->branch_id;

        $rows = $this->svc->employeePerformance($period, $branchId);

        return Inertia::render('Reports/Performance', [
            'rows'     => $rows,
            'period'   => $period,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'  => compact('period', 'branchId'),
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

    public function vatReport(Request $request): Response
    {
        $this->authorize('accounting.view');

        $from     = $request->from ?? now()->startOfMonth()->toDateString();
        $to       = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;

        $data = $this->svc->vatReport($from, $to, $branchId);

        return Inertia::render('Reports/VatReport', [
            ...$data,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'  => compact('from', 'to', 'branchId'),
        ]);
    }

    public function generalLedger(Request $request): Response
    {
        $this->authorize('accounting.view');

        $from     = $request->from ?? now()->startOfMonth()->toDateString();
        $to       = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;

        $data = $this->svc->generalLedger($from, $to, $branchId);

        return Inertia::render('Reports/GeneralLedger', [
            ...$data,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'  => compact('from', 'to', 'branchId'),
        ]);
    }

    public function reconciliation(Request $request): Response
    {
        $this->authorize('reports.financial');

        $period   = $request->period ?? now()->format('Y-m');
        $branchId = $request->branch_id;

        $revenue  = $this->svc->revenueReconciliation($period, $branchId);
        $payroll  = $this->svc->payrollReconciliation($period, $branchId);

        return Inertia::render('Reports/Reconciliation', [
            'revenue'  => $revenue,
            'payroll'  => $payroll,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'  => compact('period', 'branchId'),
        ]);
    }

    public function kpiSummary(Request $request): Response
    {
        $this->authorize('reports.financial');

        $period   = $request->period ?? now()->format('Y-m');
        $branchId = $request->branch_id;

        $data = $this->svc->kpiSummary($period, $branchId);

        return Inertia::render('Reports/KpiSummary', [
            ...$data,
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters'  => compact('period', 'branchId'),
        ]);
    }
}
