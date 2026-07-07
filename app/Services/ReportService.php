<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CommissionTransaction;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\FundAccount;
use App\Models\HkdExpenseEntry;
use App\Models\HkdRevenueEntry;
use App\Models\KpiAllocation;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientDebt;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\Payroll;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * $date scopes only the "today's" cards (appointments/revenue for that day) so the
     * dashboard can look back at a past day; the other KPIs below are current-state
     * snapshots (outstanding debt, active patients) or a rolling window from the real
     * "now", not per-day figures, so they intentionally stay pinned to today regardless.
     */
    public function dashboardKpis(?int $branchId = null, ?\Carbon\Carbon $date = null): array
    {
        $today = today('Asia/Ho_Chi_Minh');
        $date ??= $today;

        $todayAppts = Appointment::whereDate('scheduled_at', $date)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $todayRevenue = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereDate('patient_payments.payment_date', $date)
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

    public function cashflowByDay(string $from, string $to, ?int $fundAccountId = null): array
    {
        $incomeByDay = PatientPayment::whereBetween('payment_date', [$from, $to])
            ->where('amount', '>', 0)
            ->when($fundAccountId, fn ($q) => $q->where('fund_account_id', $fundAccountId))
            ->select(DB::raw('DATE(payment_date) as day'), DB::raw('SUM(amount) as income'))
            ->groupBy('day')
            ->get()->keyBy('day');

        $expenseByDay = Expense::whereBetween('expense_date', [$from, $to])
            ->when($fundAccountId, fn ($q) => $q->where('fund_account_id', $fundAccountId))
            ->select(DB::raw('DATE(expense_date) as day'), DB::raw('SUM(amount) as expense'))
            ->groupBy('day')
            ->get()->keyBy('day');

        $allDays = collect($incomeByDay->keys())->merge($expenseByDay->keys())
            ->unique()->sort()->values();

        $running = 0;
        return $allDays->map(function ($day) use ($incomeByDay, $expenseByDay, &$running) {
            $income  = (int) ($incomeByDay->get($day)?->income ?? 0);
            $expense = (int) ($expenseByDay->get($day)?->expense ?? 0);
            $running += $income - $expense;
            return compact('day', 'income', 'expense', 'running');
        })->values()->toArray();
    }

    public function employeePerformance(string $period, ?int $branchId = null): array
    {
        [$year, $month] = explode('-', $period);
        $from = "{$period}-01";
        $to   = date('Y-m-t', mktime(0, 0, 0, (int)$month, 1, (int)$year));

        $revenueRows = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->join('treatment_plans', 'patient_invoices.treatment_plan_id', '=', 'treatment_plans.id')
            ->join('employees', 'treatment_plans.doctor_id', '=', 'employees.id')
            ->select(
                'employees.id',
                'employees.full_name',
                'employees.code',
                'employees.role_type',
                DB::raw('SUM(CASE WHEN patient_payments.amount > 0 THEN patient_payments.amount ELSE 0 END) as revenue'),
                DB::raw('COUNT(DISTINCT patient_invoices.treatment_plan_id) as case_count')
            )
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->where('patient_payments.amount', '>', 0)
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->groupBy('employees.id', 'employees.full_name', 'employees.code', 'employees.role_type')
            ->get()->keyBy('id');

        $commissionByEmployee = CommissionTransaction::where('period', $period)
            ->select('employee_id', DB::raw('SUM(amount) as commission'))
            ->groupBy('employee_id')
            ->get()->keyBy('employee_id');

        return Employee::where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('full_name')->get()
            ->map(function ($emp) use ($revenueRows, $commissionByEmployee) {
                $row = $revenueRows->get($emp->id);
                return [
                    'employee_id'   => $emp->id,
                    'employee'      => $emp->full_name,
                    'code'          => $emp->code,
                    'role_type'     => $emp->role_type,
                    'revenue'       => (int) ($row?->revenue ?? 0),
                    'case_count'    => (int) ($row?->case_count ?? 0),
                    'commission'    => (int) ($commissionByEmployee->get($emp->id)?->commission ?? 0),
                ];
            })->toArray();
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

    public function vatReport(string $from, string $to, ?int $branchId = null): array
    {
        // Input VAT — from purchase invoices (VAT paid to suppliers)
        $inputVat = \App\Models\PurchaseInvoice::whereBetween('invoice_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('SUM(subtotal) as subtotal, SUM(vat_amount) as vat_amount, SUM(total) as total')
            ->first();

        $inputItems = \App\Models\PurchaseInvoice::with('supplier')
            ->whereBetween('invoice_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled'])
            ->where('vat_amount', '>', 0)
            ->orderBy('invoice_date')
            ->get()
            ->map(fn ($i) => [
                'date'         => $i->invoice_date->format('d/m/Y'),
                'code'         => $i->code,
                'supplier'     => $i->supplier->name,
                'supplier_tax' => $i->supplier->tax_code,
                'subtotal'     => $i->subtotal,
                'vat_amount'   => $i->vat_amount,
                'total'        => $i->total,
            ])->toArray();

        // Output VAT — dental services in VN are typically VAT-exempt (0%)
        // We compute a notional output VAT by treating patient invoice total as VAT-inclusive at 10%
        $outputRevenue = (int) PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0)
            ->sum('patient_payments.amount');

        return [
            'input' => [
                'subtotal'   => (int) ($inputVat->subtotal ?? 0),
                'vat_amount' => (int) ($inputVat->vat_amount ?? 0),
                'total'      => (int) ($inputVat->total ?? 0),
                'items'      => $inputItems,
            ],
            'output' => [
                'revenue'    => $outputRevenue,
                'vat_exempt' => true,
            ],
            'net_vat_payable' => (int) ($inputVat->vat_amount ?? 0),
            'period' => compact('from', 'to'),
        ];
    }

    public function generalLedger(string $from, string $to, ?int $branchId = null): array
    {
        // Patient income entries
        $income = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->select(
                DB::raw("DATE(patient_payments.payment_date) as date"),
                DB::raw("'Thu tiền bệnh nhân' as description"),
                DB::raw('patient_payments.amount as debit'),
                DB::raw('0 as credit'),
                DB::raw("'patient_payment' as entry_type"),
                'patient_payments.id as ref_id'
            )
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0);

        // Expense entries
        $expenses = \App\Models\Expense::select(
                DB::raw("DATE(expense_date) as date"),
                DB::raw("description"),
                DB::raw('0 as debit'),
                DB::raw('amount as credit'),
                DB::raw("'expense' as entry_type"),
                'id as ref_id'
            )
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        // Purchase invoice payments
        $purchases = \App\Models\PurchaseInvoice::select(
                DB::raw("DATE(invoice_date) as date"),
                DB::raw("CONCAT('Hóa đơn mua hàng', '') as description"),
                DB::raw('0 as debit'),
                DB::raw('paid_amount as credit'),
                DB::raw("'purchase' as entry_type"),
                'id as ref_id'
            )
            ->whereBetween('invoice_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('paid_amount', '>', 0);

        $entries = $income->union($expenses)->union($purchases)
            ->orderBy('date')
            ->get()
            ->map(fn ($e) => [
                'date'        => $e->date,
                'description' => $e->description,
                'debit'       => (int) $e->debit,
                'credit'      => (int) $e->credit,
                'entry_type'  => $e->entry_type,
            ])->toArray();

        $totalDebit  = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        return [
            'entries'      => $entries,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'net'          => $totalDebit - $totalCredit,
            'period'       => compact('from', 'to'),
        ];
    }

    // ─── Phase E: Reconciliation Reports ─────────────────────────────────────

    /**
     * Đối chiếu doanh thu: treatment plan totals vs tiền đã thu vs HKD revenue entries.
     */
    public function revenueReconciliation(string $period, ?int $branchId = null): array
    {
        [$from, $to] = $this->periodBounds($period);

        $invoiceTotal = PatientInvoice::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $collected = PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->whereBetween('patient_payments.payment_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('patient_invoices.branch_id', $branchId))
            ->where('patient_payments.amount', '>', 0)
            ->sum('patient_payments.amount');

        $hkdRevenue = HkdRevenueEntry::where('period', $period)
            ->when($branchId, fn ($q) => $q->whereHas('profile', fn ($pq) => $pq->where('branch_id', $branchId)))
            ->sum('amount');

        $outstanding = PatientDebt::whereIn('status', ['pending', 'partial'])
            ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId)))
            ->sum('remaining');

        return [
            'invoice_total' => (int) $invoiceTotal,
            'collected'     => (int) $collected,
            'outstanding'   => (int) $outstanding,
            'hkd_revenue'   => (int) $hkdRevenue,
            'gap'           => (int) $collected - (int) $hkdRevenue,
            'period'        => $period,
        ];
    }

    /**
     * Đối chiếu bảng lương: Payroll total_net_salary vs HKD expense entries (source_type=payroll).
     */
    public function payrollReconciliation(string $period, ?int $branchId = null): array
    {
        [$year, $month] = explode('-', $period);

        $payrollRows = Payroll::where('year', (int) $year)->where('month', (int) $month)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get(['id', 'code', 'branch_id', 'total_net_salary', 'status']);

        $payrollTotal = $payrollRows->sum('total_net_salary');

        $hkdSalary = HkdExpenseEntry::where('period', $period)
            ->where('source_type', 'payroll')
            ->when($branchId, fn ($q) => $q->whereHas('profile', fn ($pq) => $pq->where('branch_id', $branchId)))
            ->sum('amount');

        $expenseSalary = Expense::where('category', 'salary')
            ->whereBetween('expense_date', [$period . '-01', $period . '-31'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        return [
            'payrolls'      => $payrollRows->map(fn ($p) => [
                'code'           => $p->code,
                'net_salary'     => (int) $p->total_net_salary,
                'status'         => $p->status,
            ])->toArray(),
            'payroll_total'  => (int) $payrollTotal,
            'hkd_salary'     => (int) $hkdSalary,
            'expense_salary' => (int) $expenseSalary,
            'gap'            => (int) $payrollTotal - (int) $hkdSalary,
            'period'         => $period,
        ];
    }

    /**
     * KPI summary tháng theo nhân viên — số lượng, tổng tiền, breakdown theo trạng thái.
     */
    public function kpiSummary(string $period, ?int $branchId = null): array
    {
        $rows = KpiAllocation::where('period', $period)
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('branch_id', $branchId)))
            ->with('employee:id,full_name,code')
            ->get();

        $byEmployee = $rows->groupBy('employee_id')->map(function ($allocations, $empId) {
            $emp = $allocations->first()->employee;
            return [
                'employee_id'   => $empId,
                'employee_name' => $emp?->full_name ?? '—',
                'employee_code' => $emp?->code ?? '',
                'total_kpi'     => (int) $allocations->sum('final_kpi_amount'),
                'approved'      => (int) $allocations->where('status', 'approved')->sum('final_kpi_amount'),
                'pending'       => (int) $allocations->where('status', 'pending_approval')->sum('final_kpi_amount'),
                'paid'          => (int) $allocations->where('status', 'paid')->sum('final_kpi_amount'),
                'count'         => $allocations->count(),
            ];
        })->values()->sortByDesc('total_kpi')->values()->toArray();

        return [
            'by_employee'  => $byEmployee,
            'total_kpi'    => (int) $rows->sum('final_kpi_amount'),
            'total_paid'   => (int) $rows->where('status', 'paid')->sum('final_kpi_amount'),
            'total_pending' => (int) $rows->whereIn('status', ['pending_approval', 'accrued'])->sum('final_kpi_amount'),
            'period'       => $period,
        ];
    }

    private function periodBounds(string $period): array
    {
        $from = $period . '-01';
        $to   = date('Y-m-t', strtotime($from));
        return [$from, $to];
    }
}
