<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TreatmentItemStatus;
use App\Exports\SystemRecordExport;
use App\Models\Branch;
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

        $union = $this->buildUnionQuery($request, $user);
        $union->orderByDesc('record_date')->orderByDesc('row_id');

        $perPageInput = $request->per_page ?? '50';
        $perPage = min(max((int) $perPageInput, 1), 1000);

        $records = $union->paginate($perPage)->withQueryString();
        $records->getCollection()->transform(fn ($row) => $this->normalizeRow($row));

        return Inertia::render('SystemRecords/Index', [
            'records' => $records,
            'filters' => $request->only(['search', 'record_type', 'branch_id', 'date_from', 'date_to', 'year', 'per_page']),
            'branches' => $user->hasRole(['owner', 'admin'])
                ? Branch::where('is_active', true)->get(['id', 'name'])
                : [],
            'years' => $this->availableYears(),
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

        $queries = [];
        if ($type !== 'payment') {
            $queries[] = $this->serviceQuery($branchId, $search, $dateFrom, $dateTo, $year);
        }
        if ($type !== 'service') {
            $queries[] = $this->paymentQuery($branchId, $search, $dateFrom, $dateTo, $year);
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

    private function serviceQuery(?int $branchId, ?string $search, ?string $dateFrom, ?string $dateTo, ?int $year): Builder
    {
        return DB::table('treatment_plan_items as ti')
            ->join('treatment_plans as tp', 'ti.treatment_plan_id', '=', 'tp.id')
            ->join('patients as p', 'tp.patient_id', '=', 'p.id')
            ->leftJoin('employees as doc', 'tp.doctor_id', '=', 'doc.id')
            ->leftJoin('employees as consult', 'tp.consultant_id', '=', 'consult.id')
            ->leftJoin('employees as asst', 'ti.assistant_doctor_id', '=', 'asst.id')
            ->leftJoin('branches as br', 'tp.branch_id', '=', 'br.id')
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
                'doc.full_name as doctor_name',
                'consult.full_name as consultant_name',
                'asst.full_name as assistant_name',
                'tp.branch_id as branch_id',
                'br.name as branch_name',
                'tp.code as reference_code',
                DB::raw("'treatment_plan' as reference_type"),
                'tp.id as reference_id',
            ]);
    }

    private function paymentQuery(?int $branchId, ?string $search, ?string $dateFrom, ?string $dateTo, ?int $year): Builder
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
