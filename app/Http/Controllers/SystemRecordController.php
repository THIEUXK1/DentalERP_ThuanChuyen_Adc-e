<?php

namespace App\Http\Controllers;

use App\Enums\LeadSource;
use App\Enums\PaymentMethod;
use App\Enums\RoleType;
use App\Enums\TreatmentItemStatus;
use App\Exports\SystemRecordExport;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\DentalService;
use App\Models\ServiceCategory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Read-only "unified transaction log" — the live-data counterpart to the legacy
 * Admin\ClinicRecordController import table, but built from the system's current
 * treatment-plan items (services delivered) and patient payments, instead of an
 * imported spreadsheet.
 */
class SystemRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // No date/year filter picked yet (fresh page load): default to today only, rather than
        // scanning/showing the entire history every time someone opens this page.
        if (! $request->filled('date_from') && ! $request->filled('date_to') && ! $request->filled('year')) {
            $today = now()->toDateString();
            $request->merge(['date_from' => $today, 'date_to' => $today]);
        }

        $union = $this->buildUnionQuery($request, $user);
        $union->orderByDesc('record_date')->orderByDesc('row_id');

        $perPageInput = $request->per_page ?? '50';
        $perPage = min(max((int) $perPageInput, 1), 1000);

        $records = $union->paginate($perPage)->withQueryString();
        $records->getCollection()->transform(fn ($row) => $this->normalizeRow($row));

        // Sums over the whole filtered result set (not just the current page) — built from a
        // fresh copy of the union query since $union above has already been mutated (order by,
        // pagination) by the time we get here.
        // "amount" mixes unrelated things: service revenue net of discount, cash actually
        // collected, and — within payments — collections vs refunds netting each other out
        // (a day with 20.2M collected and 10.4M refunded would otherwise just show "9.8M",
        // hiding that 10.4M went back out). Keep every one of these apart.
        $totals = DB::query()
            ->fromSub($this->buildUnionQuery($request, $user), 'u')
            ->selectRaw("
                COALESCE(SUM(quantity), 0) as quantity,
                COALESCE(SUM(discount), 0) as discount,
                COALESCE(SUM(quantity * unit_price), 0) as gross,
                COALESCE(SUM(amount) FILTER (WHERE record_type = 'service'), 0) as service_amount,
                COALESCE(SUM(amount) FILTER (WHERE record_type = 'payment' AND amount > 0), 0) as payment_collected,
                COALESCE(SUM(amount) FILTER (WHERE record_type = 'payment' AND amount < 0), 0) as payment_refunded
            ")
            ->first();

        return Inertia::render('SystemRecords/Index', [
            'records' => $records,
            'totals' => [
                'quantity' => (int) $totals->quantity,
                'discount' => (int) $totals->discount,
                'gross' => (int) $totals->gross,
                'service_amount' => (int) $totals->service_amount,
                'payment_collected' => (int) $totals->payment_collected,
                'payment_refunded' => (int) $totals->payment_refunded,
            ],
            'filters' => $request->only([
                'search', 'record_type', 'branch_id', 'date_from', 'date_to', 'year', 'per_page',
                'patient_name', 'doctor_id', 'consultant_id', 'assistant_id', 'amount_min', 'amount_max', 'reference_code',
                'group_id', 'category_id', 'source', 'status', 'service_id',
            ]),
            'branches' => $user->hasRole(['owner', 'admin'])
                ? Branch::where('is_active', true)->get(['id', 'name'])
                : [],
            'years' => $this->availableYears(),
            'doctors' => Employee::doctors()->where('is_active', true)->orderBy('full_name')
                ->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'consultants' => Employee::where('role_type', RoleType::Consultant->value)->where('is_active', true)->orderBy('full_name')
                ->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'assistants' => Employee::where('role_type', RoleType::Assistant->value)->where('is_active', true)->orderBy('full_name')
                ->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            // "groups" (service_groups) intentionally not sent — no UI control for it right now
            // (see comment in SystemRecords/Index.vue); the group_id filter itself still works
            // if a request ever passes it directly.
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'group_id']),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get(['id', 'name', 'category_id']),
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'statuses' => collect(TreatmentItemStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
                ->concat([
                    ['value' => 'paid', 'label' => 'Đã thu (thanh toán)'],
                    ['value' => 'refund', 'label' => 'Hoàn tiền (thanh toán)'],
                ]),
        ]);
    }

    /** Excel export requires an explicit date range so we never dump the whole unbounded history at once. */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        $user = auth()->user();

        $union = $this->buildUnionQuery($request, $user);
        $union->orderByDesc('record_date')->orderByDesc('row_id')->limit(50000);

        $rows = $union->get()->map(fn ($row) => $this->normalizeRow($row))->all();

        $filename = "du-lieu-he-thong_{$request->date_from}_{$request->date_to}.xlsx";

        return Excel::download(new SystemRecordExport($rows), $filename);
    }

    /** @return Builder Union of service + payment sub-queries, filtered per the request and the user's branch scope. */
    private function buildUnionQuery(Request $request, $user): Builder
    {
        $branchId = null;
        if (! $user->hasRole(['owner', 'admin']) && $user->branch_id) {
            $branchId = $user->branch_id;
        }
        if ($request->filled('branch_id') && $user->hasRole(['owner', 'admin'])) {
            $branchId = (int) $request->branch_id;
        }

        $search = $request->string('search')->trim()->value() ?: null;
        $type = $request->string('record_type')->value() ?: null;
        $dateFrom = $request->date_from ?: null;
        $dateTo = $request->date_to ?: null;
        $year = $request->filled('year') ? (int) $request->year : null;

        // "Trạng thái" spans two unrelated domains: treatment-item status (service rows) and
        // paid/refund (payment rows, derived from amount sign — see paymentQuery()). Figure out
        // up front which domain the picked value belongs to, so each side of the union knows
        // whether to filter on it or exclude itself entirely.
        $status = $request->string('status')->trim()->value() ?: null;
        $statusDomain = null;
        if ($status) {
            $statusDomain = in_array($status, array_column(TreatmentItemStatus::cases(), 'value'), true)
                ? 'service'
                : (in_array($status, ['paid', 'refund'], true) ? 'payment' : null);
        }

        $advanced = [
            'patient_name' => $request->string('patient_name')->trim()->value() ?: null,
            'doctor_id' => $request->filled('doctor_id') ? (int) $request->doctor_id : null,
            'consultant_id' => $request->filled('consultant_id') ? (int) $request->consultant_id : null,
            'assistant_id' => $request->filled('assistant_id') ? (int) $request->assistant_id : null,
            'reference_code' => $request->string('reference_code')->trim()->value() ?: null,
            'amount_min' => $request->filled('amount_min') ? (int) $request->amount_min : null,
            'amount_max' => $request->filled('amount_max') ? (int) $request->amount_max : null,
            'group_id' => $request->filled('group_id') ? (int) $request->group_id : null,
            'category_id' => $request->filled('category_id') ? (int) $request->category_id : null,
            'service_id' => $request->filled('service_id') ? (int) $request->service_id : null,
            'source' => $request->string('source')->trim()->value() ?: null,
            'status' => $status,
            'status_domain' => $statusDomain,
        ];

        $queries = [];
        if ($type !== 'payment') {
            $queries[] = $this->serviceQuery($branchId, $search, $dateFrom, $dateTo, $year, $advanced);
        }
        if ($type !== 'service') {
            $queries[] = $this->paymentQuery($branchId, $search, $dateFrom, $dateTo, $year, $advanced);
        }

        $union = array_shift($queries);
        foreach ($queries as $q) {
            $union->unionAll($q);
        }

        return $union;
    }

    private function availableYears(): array
    {
        // This scans both tables in full (no sargable index for EXTRACT(YEAR FROM ...)), but
        // the set of years barely ever changes, so an hourly cache avoids paying that cost
        // on every page load.
        return Cache::remember('system-records:available-years', 3600, function () {
            $serviceYears = DB::table('treatment_plan_items as ti')
                ->join('treatment_plans as tp', 'ti.treatment_plan_id', '=', 'tp.id')
                ->whereIn('tp.status', ['approved', 'in_progress', 'completed'])
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM COALESCE(ti.completed_at, ti.started_at, tp.approved_at, tp.created_at))::int as y')
                ->pluck('y');

            $paymentYears = DB::table('patient_payments')
                ->selectRaw('DISTINCT EXTRACT(YEAR FROM payment_date)::int as y')
                ->pluck('y');

            return $serviceYears->merge($paymentYears)->unique()->sortDesc()->values()->all();
        });
    }

    private function serviceQuery(?int $branchId, ?string $search, ?string $dateFrom, ?string $dateTo, ?int $year, array $advanced = []): Builder
    {
        return DB::table('treatment_plan_items as ti')
            ->join('treatment_plans as tp', 'ti.treatment_plan_id', '=', 'tp.id')
            ->join('patients as p', 'tp.patient_id', '=', 'p.id')
            ->leftJoin('employees as item_doc', 'ti.responsible_doctor_id', '=', 'item_doc.id')
            ->leftJoin('employees as plan_doc', 'tp.doctor_id', '=', 'plan_doc.id')
            ->leftJoin('employees as consult', 'tp.consultant_id', '=', 'consult.id')
            ->leftJoin('employees as asst', 'ti.assistant_doctor_id', '=', 'asst.id')
            ->leftJoin('branches as br', 'tp.branch_id', '=', 'br.id')
            ->leftJoin('dental_services as svc', 'ti.service_id', '=', 'svc.id')
            ->leftJoin('service_categories as svc_cat', 'svc.category_id', '=', 'svc_cat.id')
            ->whereIn('tp.status', ['approved', 'in_progress', 'completed'])
            ->when($branchId, fn ($q) => $q->where('tp.branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->whereRaw(
                'COALESCE(ti.completed_at, ti.started_at, tp.approved_at, tp.created_at) >= ?', [$dateFrom]
            ))
            ->when($dateTo, fn ($q) => $q->whereRaw(
                'COALESCE(ti.completed_at, ti.started_at, tp.approved_at, tp.created_at) <= ?', [$dateTo.' 23:59:59']
            ))
            ->when($year, fn ($q) => $q->whereRaw(
                'EXTRACT(YEAR FROM COALESCE(ti.completed_at, ti.started_at, tp.approved_at, tp.created_at)) = ?', [$year]
            ))
            ->when($search, fn ($q) => $q->where(function ($w) use ($search) {
                $like = "%{$search}%";
                $w->where('p.full_name', 'ilike', $like)
                    ->orWhere('p.code', 'ilike', $like)
                    ->orWhere('p.phone', 'ilike', $like)
                    ->orWhere('ti.name', 'ilike', $like)
                    ->orWhere('tp.code', 'ilike', $like);
            }))
            ->when($advanced['patient_name'] ?? null, fn ($q, $v) => $q->where('p.full_name', 'ilike', "%{$v}%"))
            ->when($advanced['doctor_id'] ?? null, fn ($q, $v) => $q->whereRaw(
                'COALESCE(item_doc.id, plan_doc.id) = ?', [$v]
            ))
            ->when($advanced['consultant_id'] ?? null, fn ($q, $v) => $q->where('consult.id', $v))
            ->when($advanced['assistant_id'] ?? null, fn ($q, $v) => $q->where('asst.id', $v))
            ->when($advanced['reference_code'] ?? null, fn ($q, $v) => $q->where('tp.code', 'ilike', "%{$v}%"))
            ->when($advanced['amount_min'] ?? null, fn ($q, $v) => $q->whereRaw('(ti.subtotal - ti.discount) >= ?', [$v]))
            ->when($advanced['amount_max'] ?? null, fn ($q, $v) => $q->whereRaw('(ti.subtotal - ti.discount) <= ?', [$v]))
            ->when($advanced['group_id'] ?? null, fn ($q, $v) => $q->where('svc_cat.group_id', $v))
            ->when($advanced['category_id'] ?? null, fn ($q, $v) => $q->where('svc.category_id', $v))
            ->when($advanced['service_id'] ?? null, fn ($q, $v) => $q->where('svc.id', $v))
            ->when($advanced['source'] ?? null, fn ($q, $v) => $q->where('p.source', $v))
            ->when($advanced['status'] ?? null, fn ($q, $v) => ($advanced['status_domain'] ?? null) === 'service'
                ? $q->where('ti.status', $v)
                : $q->whereRaw('1 = 0'))
            ->select([
                DB::raw("'service' as record_type"),
                'ti.id as row_id',
                DB::raw('CAST(COALESCE(ti.completed_at, ti.started_at, tp.approved_at, tp.created_at) AS date) as record_date'),
                'p.id as patient_id',
                'p.code as patient_code',
                'p.full_name as patient_name',
                'p.phone as phone',
                'ti.name as description_raw',
                'ti.quantity as quantity',
                'ti.unit_price as unit_price',
                'ti.discount as discount',
                DB::raw('(ti.subtotal - ti.discount) as amount'),
                'ti.status as status_raw',
                DB::raw('COALESCE(item_doc.full_name, plan_doc.full_name) as doctor_name'),
                'consult.full_name as consultant_name',
                'asst.full_name as assistant_name',
                'tp.branch_id as branch_id',
                'br.name as branch_name',
                'tp.code as reference_code',
                DB::raw("'treatment_plan' as reference_type"),
                'tp.id as reference_id',
            ]);
    }

    private function paymentQuery(?int $branchId, ?string $search, ?string $dateFrom, ?string $dateTo, ?int $year, array $advanced = []): Builder
    {
        return DB::table('patient_payments as pay')
            ->join('patient_invoices as inv', 'pay.invoice_id', '=', 'inv.id')
            ->join('patients as p', 'inv.patient_id', '=', 'p.id')
            ->leftJoin('branches as br', 'inv.branch_id', '=', 'br.id')
            ->when($branchId, fn ($q) => $q->where('inv.branch_id', $branchId))
            ->when($dateFrom, fn ($q) => $q->where('pay.payment_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('pay.payment_date', '<=', $dateTo))
            ->when($year, fn ($q) => $q->whereRaw('EXTRACT(YEAR FROM pay.payment_date) = ?', [$year]))
            ->when($search, fn ($q) => $q->where(function ($w) use ($search) {
                $like = "%{$search}%";
                $w->where('p.full_name', 'ilike', $like)
                    ->orWhere('p.code', 'ilike', $like)
                    ->orWhere('p.phone', 'ilike', $like)
                    ->orWhere('inv.code', 'ilike', $like)
                    ->orWhere('pay.reference', 'ilike', $like);
            }))
            ->when($advanced['patient_name'] ?? null, fn ($q, $v) => $q->where('p.full_name', 'ilike', "%{$v}%"))
            // Payments have no doctor/consultant/assistant — filtering by any of these should exclude payment rows entirely.
            ->when($advanced['doctor_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['consultant_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['assistant_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['reference_code'] ?? null, fn ($q, $v) => $q->where('inv.code', 'ilike', "%{$v}%"))
            ->when($advanced['amount_min'] ?? null, fn ($q, $v) => $q->where('pay.amount', '>=', $v))
            ->when($advanced['amount_max'] ?? null, fn ($q, $v) => $q->where('pay.amount', '<=', $v))
            // Payments aren't tied to a service — filtering by group/category/service should exclude them entirely.
            ->when($advanced['group_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['category_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['service_id'] ?? null, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($advanced['source'] ?? null, fn ($q, $v) => $q->where('p.source', $v))
            ->when($advanced['status'] ?? null, fn ($q, $v) => ($advanced['status_domain'] ?? null) === 'payment'
                ? $q->whereRaw("(CASE WHEN pay.amount < 0 THEN 'refund' ELSE 'paid' END) = ?", [$v])
                : $q->whereRaw('1 = 0'))
            ->select([
                DB::raw("'payment' as record_type"),
                'pay.id as row_id',
                'pay.payment_date as record_date',
                'p.id as patient_id',
                'p.code as patient_code',
                'p.full_name as patient_name',
                'p.phone as phone',
                'pay.method as description_raw',
                DB::raw('CAST(NULL AS integer) as quantity'),
                DB::raw('CAST(NULL AS bigint) as unit_price'),
                DB::raw('CAST(NULL AS bigint) as discount'),
                'pay.amount as amount',
                DB::raw("(CASE WHEN pay.amount < 0 THEN 'refund' ELSE 'paid' END) as status_raw"),
                DB::raw('CAST(NULL AS varchar) as doctor_name'),
                DB::raw('CAST(NULL AS varchar) as consultant_name'),
                DB::raw('CAST(NULL AS varchar) as assistant_name'),
                'inv.branch_id as branch_id',
                'br.name as branch_name',
                'inv.code as reference_code',
                DB::raw("'invoice' as reference_type"),
                'inv.id as reference_id',
            ]);
    }

    private function normalizeRow(object $row): array
    {
        $isService = $row->record_type === 'service';

        return [
            'id' => "{$row->record_type}-{$row->row_id}",
            'record_date' => $row->record_date,
            'record_type' => $row->record_type,
            'record_type_label' => $isService ? 'Thủ thuật' : 'Thanh toán',
            'patient_id' => $row->patient_id,
            'patient_code' => $row->patient_code,
            'patient_name' => $row->patient_name,
            'phone' => $row->phone,
            'description' => $isService
                ? $row->description_raw
                : (PaymentMethod::tryFrom($row->description_raw)?->label() ?? $row->description_raw),
            'quantity' => $row->quantity,
            'unit_price' => $row->unit_price,
            'discount' => $row->discount,
            'amount' => (int) $row->amount,
            'status_label' => $isService
                ? (TreatmentItemStatus::tryFrom($row->status_raw)?->label() ?? $row->status_raw)
                : ($row->status_raw === 'refund' ? 'Hoàn tiền' : 'Đã thu'),
            'doctor_name' => $row->doctor_name,
            'consultant_name' => $row->consultant_name,
            'assistant_name' => $row->assistant_name,
            'branch_name' => $row->branch_name,
            'reference_code' => $row->reference_code,
            'reference_type' => $row->reference_type,
            'reference_id' => $row->reference_id,
        ];
    }
}
