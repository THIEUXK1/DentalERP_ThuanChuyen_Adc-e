<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\DebtStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PatientDebt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('cashier.view');

        // Auto-fix inconsistent records: remaining=0 but status not updated
        PatientDebt::whereIn('status', ['pending', 'partial'])->where('remaining', '<=', 0)
            ->update(['status' => DebtStatus::Paid->value]);

        $query = PatientDebt::with(['patient', 'invoice'])
            ->whereIn('status', ['pending', 'partial'])
            ->when($request->patient_search, function ($q, $v) {
                // ILIKE is Postgres-only; SQLite/MySQL use case-insensitive LIKE instead.
                $op = \Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' ? 'like' : 'ilike';
                $q->whereHas('patient', fn ($pq) => $pq->where('full_name', $op, "%{$v}%")->orWhere('phone', $op, "%{$v}%"));
            })
            ->when($request->branch_id, fn ($q, $v) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $v)))
            // "overdue" is derived from the due date, the other two are the stored status.
            ->when($request->status === 'overdue', fn ($q) => $q->whereNotNull('due_date')->whereDate('due_date', '<', now()))
            ->when(in_array($request->status, ['pending', 'partial'], true), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('remaining');

        $summary = PatientDebt::whereIn('status', ['pending', 'partial'])->sum('remaining');

        $perPage = in_array((int) $request->per_page, [20, 50, 100], true) ? (int) $request->per_page : 20;

        return Inertia::render('Cashier/Debts/Index', [
            // withQueryString keeps the active filters on the page links.
            'debts' => $query->paginate($perPage)->withQueryString()->through(fn ($d) => [
                'id' => $d->id,
                'patient' => $d->patient->full_name,
                'patient_id' => $d->patient_id,
                'phone' => $d->patient->phone,
                'invoice_code' => $d->invoice->code,
                'invoice_id' => $d->invoice_id,
                'amount' => $d->amount,
                'paid' => $d->paid_amount,
                'remaining' => $d->remaining,
                'due_date' => $d->due_date?->format('d/m/Y'),
                'overdue' => $d->due_date && $d->due_date->isPast(),
                'status' => $d->status->value,
                'status_label' => $d->status->label(),
                'status_color' => $d->status->color(),
            ]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'summary' => [
                // Cùng phạm vi với total_outstanding (toàn bộ công nợ chưa thu), không theo bộ lọc.
                'total_outstanding' => $summary,
                'count' => PatientDebt::whereIn('status', ['pending', 'partial'])->count(),
            ],
            'filters' => $request->only(['patient_search', 'branch_id', 'status', 'per_page']),
        ]);
    }
}
