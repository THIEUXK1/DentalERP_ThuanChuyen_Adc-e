<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Expense;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientDebt;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function dashboardKpis(?int $branchId = null): array
    {
        $today = today('Asia/Ho_Chi_Minh');

        $todayAppts = Appointment::whereDate('scheduled_at', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $todayRevenue = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereDate('patient_payments.payment_date', $today)
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0)
            ->sum('patient_payments.amount');

        $totalOutstanding = PatientDebt::whereIn('status', ['pending', 'partial'])
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId)))
            ->sum('remaining');

        $newLeads = Lead::where('created_at', '>=', $today->copy()->subDays(7))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $activePatients = Patient::where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        return compact('todayAppts', 'todayRevenue', 'totalOutstanding', 'newLeads', 'activePatients');
    }

    public function revenueTrend(string $from, string $to, ?int $branchId = null): array
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $dateExpr = $isSqlite
            ? 'DATE(patient_payments.payment_date)'
            : "DATE(patient_payments.payment_date AT TIME ZONE 'Asia/Ho_Chi_Minh')";

        $rows = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->select(
                DB::raw("{$dateExpr} as day"),
                DB::raw('SUM(CASE WHEN patient_payments.amount > 0 THEN patient_payments.amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN patient_payments.amount < 0 THEN ABS(patient_payments.amount) ELSE 0 END) as refunds')
            )
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('day')
            ->get();

        return $rows->map(fn ($r) => [
            'day' => $r->day,
            'revenue' => (int) $r->revenue,
            'refunds' => (int) $r->refunds,
            'net' => (int) $r->revenue - (int) $r->refunds,
        ])->toArray();
    }

    public function appointmentBreakdown(?int $branchId = null): array
    {
        return Appointment::select('status', DB::raw('count(*) as count'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'count' => $r->count])
            ->toArray();
    }

    public function leadFunnel(?string $from = null, ?string $to = null): array
    {
        return Lead::select('status', DB::raw('count(*) as count'))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to.' 23:59:59'))
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'count' => $r->count])
            ->toArray();
    }

    public function revenue(string $from, string $to, ?int $branchId = null): array
    {
        $byDay = $this->revenueTrend($from, $to, $branchId);

        $byMethod = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->select('patient_payments.method', DB::raw('SUM(patient_payments.amount) as total'))
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0)
            ->groupBy('patient_payments.method')
            ->get()
            ->toArray();

        return ['byDay' => $byDay, 'byMethod' => $byMethod];
    }

    public function appointmentReport(string $from, string $to, ?int $branchId = null): array
    {
        return Appointment::with(['doctor'])
            ->select('status', 'doctor_id', DB::raw('count(*) as count'))
            ->whereBetween('scheduled_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('status', 'doctor_id')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'doctor_id' => $r->doctor_id,
                'doctor_name' => $r->doctor?->full_name ?? 'Chưa gán',
                'count' => $r->count,
            ])
            ->toArray();
    }

    public function treatmentPlanConversion(?int $branchId = null): array
    {
        $q = TreatmentPlan::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $total = $q->count();
        $approved = (clone $q)->whereIn('status', ['approved', 'in_progress', 'completed'])->count();
        $rate = $total > 0 ? round($approved / $total * 100, 1) : 0;

        return compact('total', 'approved', 'rate');
    }

    public function revenueByDoctor(string $from, string $to, ?int $branchId = null): array
    {
        return PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->join('treatment_plans', 'patient_invoices.treatment_plan_id', '=', 'treatment_plans.id')
            ->join('employees', 'treatment_plans.doctor_id', '=', 'employees.id')
            ->select('employees.id', 'employees.full_name', DB::raw('SUM(patient_payments.amount) as revenue'))
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->where('patient_payments.amount', '>', 0)
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->groupBy('employees.id', 'employees.full_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->full_name, 'revenue' => (int) $r->revenue])
            ->toArray();
    }

    public function revenueByService(string $from, string $to, ?int $branchId = null): array
    {
        return TreatmentPlanItem::join('treatment_plans', 'treatment_plan_items.treatment_plan_id', '=', 'treatment_plans.id')
            ->select('treatment_plan_items.name', DB::raw('SUM(treatment_plan_items.subtotal) as revenue'))
            ->whereIn('treatment_plans.status', ['approved', 'in_progress', 'completed'])
            ->whereBetween('treatment_plans.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('treatment_plans.branch_id', $branchId))
            ->groupBy('treatment_plan_items.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'revenue' => (int) $r->revenue])
            ->toArray();
    }

    public function revenueBySource(string $from, string $to, ?int $branchId = null): array
    {
        return PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->join('treatment_plans', 'patient_invoices.treatment_plan_id', '=', 'treatment_plans.id')
            ->join('patients', 'treatment_plans.patient_id', '=', 'patients.id')
            ->select('patients.source', DB::raw('SUM(patient_payments.amount) as revenue'))
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->where('patient_payments.amount', '>', 0)
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->groupBy('patients.source')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['source' => $r->source ?? 'other', 'revenue' => (int) $r->revenue])
            ->toArray();
    }

    public function crmConversion(?string $from = null, ?string $to = null): array
    {
        $q = Lead::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to.' 23:59:59'));
        $total = $q->count();
        $contacted = (clone $q)->where('status', 'contacted')->count();
        $qualified = (clone $q)->where('status', 'qualified')->count();
        $converted = (clone $q)->where('status', 'converted')->count();
        $lost = (clone $q)->where('status', 'lost')->count();

        return compact('total', 'contacted', 'qualified', 'converted', 'lost');
    }

    public function leadsBySource(?string $from = null, ?string $to = null): array
    {
        return Lead::select(
            'source',
            DB::raw('count(*) as total'),
            DB::raw("COUNT(CASE WHEN status = 'converted' THEN 1 END) as converted")
        )
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to.' 23:59:59'))
            ->groupBy('source')
            ->get()
            ->map(fn ($r) => [
                'source' => $r->source ?? 'other',
                'total' => (int) $r->total,
                'converted' => (int) $r->converted,
                'rate' => $r->total > 0 ? round($r->converted / $r->total * 100, 1) : 0,
            ])
            ->toArray();
    }

    public function cashbook(string $from, string $to, ?int $branchId = null): array
    {
        $income = (int) PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0)
            ->sum('patient_payments.amount');

        $refunds = (int) abs(PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '<', 0)
            ->sum('patient_payments.amount'));

        $expenses = (int) Expense::whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        return [
            'income' => $income,
            'refunds' => $refunds,
            'expenses' => $expenses,
            'net' => $income - $refunds - $expenses,
        ];
    }

    public function profitLoss(string $from, string $to, ?int $branchId = null): array
    {
        $cashbook = $this->cashbook($from, $to, $branchId);

        $expensesByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'total' => (int) $r->total])
            ->toArray();

        $revenueByDay = $this->revenueTrend($from, $to, $branchId);

        return [
            ...$cashbook,
            'expensesByCategory' => $expensesByCategory,
            'revenueByDay' => $revenueByDay,
        ];
    }

    public function debtAging(?int $branchId = null): array
    {
        $today = today();

        return PatientDebt::with('patient')
            ->whereIn('status', ['pending', 'partial'])
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId)))
            ->get()
            ->map(function ($d) use ($today) {
                $days = $d->due_date ? $today->diffInDays($d->due_date, false) * -1 : 0;
                $bucket = $days <= 0 ? 'current' : ($days <= 30 ? '1-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+')));

                return [
                    'patient' => $d->patient->full_name,
                    'remaining' => $d->remaining,
                    'due_date' => $d->due_date?->format('d/m/Y'),
                    'days_overdue' => max(0, $days),
                    'bucket' => $bucket,
                    'invoice_id' => $d->invoice_id,
                ];
            })
            ->toArray();
    }
}
