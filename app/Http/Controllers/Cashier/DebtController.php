<?php

namespace App\Http\Controllers\Cashier;

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

        $query = PatientDebt::with(['patient', 'invoice'])
            ->whereIn('status', ['pending', 'partial'])
            ->when($request->patient_search, fn ($q, $v) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'ilike', "%{$v}%")->orWhere('phone', 'ilike', "%{$v}%")))
            ->when($request->branch_id, fn ($q, $v) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $v)))
            ->orderByDesc('remaining');

        $summary = PatientDebt::whereIn('status', ['pending', 'partial'])->sum('remaining');

        return Inertia::render('Cashier/Debts/Index', [
            'debts' => $query->paginate(20)->through(fn ($d) => [
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
                'total_outstanding' => $summary,
                'count' => $query->count(),
            ],
            'filters' => $request->only(['patient_search', 'branch_id']),
        ]);
    }
}
