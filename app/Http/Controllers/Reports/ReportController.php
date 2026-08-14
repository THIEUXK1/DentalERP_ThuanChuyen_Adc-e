<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AppointmentStatus;
use App\Exports\DailyScheduleExport;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FundAccount;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        [$from, $to] = $this->scheduleRange($request);

        return Inertia::render('Reports/DailySchedule', [
            'appointments' => $this->scheduleRows($from, $to)->all(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'doctors'  => Employee::doctors()->where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'statuses' => collect(AppointmentStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'filters'  => compact('from', 'to'),
        ]);
    }

    /**
     * Excel export of the schedule report — same rows as the screen, but every filter the
     * page applies client-side is re-applied here so the file matches what the user sees.
     */
    public function dailyScheduleExport(Request $request): BinaryFileResponse
    {
        $this->authorize('reports.view');

        [$from, $to] = $this->scheduleRange($request);

        $branchId = $request->branch_id ? (int) $request->branch_id : null;
        $doctorId = $request->doctor_id ? (int) $request->doctor_id : null;
        $status   = $request->status ?: null;
        $search   = trim((string) $request->search);

        $rows = $this->scheduleRows($from, $to)
            ->when($branchId, fn ($c) => $c->where('branch_id', $branchId))
            ->when($doctorId, fn ($c) => $c->where('doctor_id', $doctorId))
            ->when($status, fn ($c) => $c->where('status', $status))
            ->when($search !== '', fn ($c) => $c->filter(fn ($r) => str_contains(
                mb_strtolower(implode(' ', [$r['patient'], $r['patient_phone'], $r['patient_code'], $r['code'], $r['doctor'], $r['service']])),
                mb_strtolower($search)
            )))
            ->values();

        $labels = array_filter([
            $branchId ? 'Chi nhánh: '.(Branch::find($branchId)?->name ?? $branchId) : null,
            $doctorId ? 'Bác sĩ: '.(Employee::find($doctorId)?->full_name ?? $doctorId) : null,
            $status   ? 'Trạng thái: '.(AppointmentStatus::tryFrom($status)?->label() ?? $status) : null,
            $search !== '' ? 'Tìm: '.$search : null,
        ]);

        $meta = [
            'range' => $from === $to
                ? 'Ngày '.\Carbon\Carbon::parse($from)->format('d/m/Y')
                : 'Từ '.\Carbon\Carbon::parse($from)->format('d/m/Y').' đến '.\Carbon\Carbon::parse($to)->format('d/m/Y'),
            'filters' => ($labels ? implode('  |  ', $labels).'  |  ' : '').'Xuất lúc '.now()->format('H:i d/m/Y'),
        ];

        $statusCounts = $rows->groupBy('status_label')->map->count()->sortDesc()->all();

        $filename = $from === $to
            ? "lich-hen_{$from}.xlsx"
            : "lich-hen_{$from}_den_{$to}.xlsx";

        return Excel::download(new DailyScheduleExport($rows->all(), $meta, $statusCounts), $filename);
    }

    /** @return array{0:string,1:string} [from, to] — `to` never precedes `from`. */
    private function scheduleRange(Request $request): array
    {
        $from = $request->from ?? ($request->date ?? now()->startOfMonth()->toDateString());
        $to   = $request->to ?? now()->endOfMonth()->toDateString();

        return [$from, $to < $from ? $from : $to];
    }

    /**
     * Appointment rows for the schedule report, carrying the full patient record
     * (DOB / age / gender / contact / medical flags) that the Excel export prints.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function scheduleRows(string $from, string $to): \Illuminate\Support\Collection
    {
        $genders = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];
        $weekdays = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

        return Appointment::with(['patient', 'doctor', 'service', 'chair', 'branch'])
            ->whereBetween('scheduled_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('scheduled_at')
            ->orderBy('doctor_id')
            ->get()
            ->map(function ($a) use ($genders, $weekdays) {
                $p = $a->patient;

                return [
                    'id'               => $a->id,
                    'code'             => $a->code,
                    'date'             => $a->scheduled_at->format('Y-m-d'),
                    'date_label'       => $a->scheduled_at->format('d/m/Y'),
                    'weekday'          => $weekdays[$a->scheduled_at->dayOfWeek],
                    'patient'          => $p?->full_name ?? '—',
                    'patient_code'     => $p?->code ?? '',
                    'patient_phone'    => $p?->phone ?? '',
                    'patient_email'    => $p?->email ?? '',
                    'patient_gender'   => $p?->gender ? ($genders[$p->gender] ?? $p->gender) : '',
                    'patient_dob'      => $p?->dob?->format('d/m/Y') ?? '',
                    'patient_birth_year' => $p?->dob?->format('Y') ?? '',
                    'patient_age'      => $p?->dob?->age,
                    'patient_address'  => $p?->address ?? '',
                    'patient_source'   => $p?->source ?? '',
                    'patient_allergies' => $p?->allergies ?? '',
                    'patient_medical_history' => $p?->medical_history ?? '',
                    'patient_emergency_contact' => $p?->emergency_contact ?? '',
                    'doctor'           => $a->doctor?->full_name ?? 'Chưa gán',
                    'doctor_id'        => $a->doctor_id,
                    'branch_id'        => $a->branch_id,
                    'branch'           => $a->branch?->name ?? '',
                    'service'          => $a->service?->name ?? '—',
                    'chair'            => $a->chair?->name ?? '—',
                    'scheduled_at'     => $a->scheduled_at->format('H:i'),
                    'ends_at'          => $a->ends_at->format('H:i'),
                    'duration_minutes' => $a->duration_minutes,
                    'status'           => $a->status->value,
                    'status_label'     => $a->status->label(),
                    'notes'            => $a->notes ?? '',
                ];
            })
            ->values();
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
