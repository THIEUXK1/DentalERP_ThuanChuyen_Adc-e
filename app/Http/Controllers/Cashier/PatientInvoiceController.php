<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PatientInvoice;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class PatientInvoiceController extends Controller
{
    public function __construct(private InvoiceService $svc) {}

    public function index(Request $request): \Inertia\Response
    {
        $this->authorize('cashier.view');

        $invoices = PatientInvoice::with(['patient', 'branch', 'treatmentPlan'])
            ->orderByRaw('due_date ASC NULLS LAST, id DESC')
            ->get()
            ->map(function ($inv) {
                $noSchedule = $inv->installment_index === null
                    && $inv->treatment_plan_id !== null
                    && empty($inv->treatmentPlan?->payment_schedule);

                return [
                    'id'                  => $inv->id,
                    'code'                => $inv->code,
                    'patient'             => $inv->patient->full_name,
                    'patient_id'          => $inv->patient_id,
                    'patient_phone'       => $inv->patient->phone ?? '',
                    'branch'              => $inv->branch->name,
                    'branch_id'           => $inv->branch_id,
                    'status'              => $inv->status->value,
                    'status_label'        => $noSchedule ? 'Chưa làm đợt TT' : $inv->status->label(),
                    'status_color'        => $noSchedule ? 'amber' : $inv->status->color(),
                    'total'               => $inv->total,
                    'amount_paid'         => $inv->amount_paid,
                    'amount_due'          => $inv->amountDue(),
                    'due_date'            => $inv->due_date?->format('d/m/Y'),
                    'due_date_raw'        => $inv->due_date?->toDateString(),
                    'installment_index'   => $inv->installment_index,
                    'plan_id'             => $inv->treatment_plan_id,
                    'has_no_schedule'     => $noSchedule,
                    'treatment_plan_code' => $inv->treatmentPlan?->code,
                    'created_at'          => $inv->created_at->format('d/m/Y'),
                    'created_at_raw'      => $inv->created_at->toDateString(),
                ];
            });

        return Inertia::render('Cashier/Invoices/Index', [
            'invoices'        => $invoices,
            'statuses'        => collect(InvoiceStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches'        => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'init_patient_id' => $request->patient_id ? (int) $request->patient_id : null,
            'init_plan_id'    => $request->plan_id    ? (int) $request->plan_id    : null,
        ]);
    }

    public function show(PatientInvoice $invoice): \Inertia\Response
    {
        $this->authorize('cashier.view');

        $invoice->load(['patient', 'branch', 'treatmentPlan.items.service', 'payments.creator', 'debt']);

        $plan = $invoice->treatmentPlan;

        return Inertia::render('Cashier/Invoices/Show', [
            'invoice' => [
                'id'               => $invoice->id,
                'code'             => $invoice->code,
                'patient'          => $invoice->patient->full_name,
                'patient_id'       => $invoice->patient_id,
                'patient_phone'    => $invoice->patient->phone,
                'branch'           => $invoice->branch->name,
                'status'           => $invoice->status->value,
                'status_label'     => $invoice->status->label(),
                'status_color'     => $invoice->status->color(),
                'subtotal'         => $invoice->subtotal,
                'discount'         => $invoice->discount,
                'total'            => $invoice->total,
                'amount_paid'      => $invoice->amount_paid,
                'amount_due'       => $invoice->amountDue(),
                'due_date'         => $invoice->due_date?->format('d/m/Y'),
                'notes'            => $invoice->notes,
                'cancel_reason'    => $invoice->cancel_reason,
                'installment_index'=> $invoice->installment_index,
                'plan_code'        => $plan?->code,
                'plan_id'          => $invoice->treatment_plan_id,
                'plan_status'      => $plan?->status->label(),
                'plan_doctor'      => $plan?->doctor?->full_name,
                'plan_net_total'          => $plan?->net_total,
                'plan_payment_schedule'  => $plan?->payment_schedule ?? [],
            ],
            'plan_items' => $plan ? $plan->items->map(fn ($i) => [
                'id'           => $i->id,
                'service_name' => $i->name,
                'tooth_number' => $i->tooth_number,
                'quantity'     => $i->quantity,
                'unit_price'   => $i->unit_price,
                'discount'     => $i->discount,
                'amount'       => $i->amount,
                'notes'        => $i->notes,
                'status_label' => $i->status->label(),
                'status_color' => $i->status->color(),
            ])->values()->all() : [],
            'payments' => $invoice->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'method' => $p->method->value,
                'method_label' => $p->method->label(),
                'method_color' => $p->method->color(),
                'payment_date' => $p->payment_date->format('d/m/Y'),
                'reference' => $p->reference,
                'notes' => $p->notes,
                'creator' => $p->creator->name,
            ]),
            'debt' => $invoice->debt ? [
                'amount' => $invoice->debt->amount,
                'paid' => $invoice->debt->paid_amount,
                'remaining' => $invoice->debt->remaining,
                'status' => $invoice->debt->status->value,
                'status_label' => $invoice->debt->status->label(),
                'status_color' => $invoice->debt->status->color(),
            ] : null,
            'methods' => collect(PaymentMethod::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
            'canRefund' => auth()->user()?->can('cashier.approve_refund'),
            'canDiscount' => auth()->user()?->can('cashier.approve_discount'),
        ]);
    }

    public function discount(Request $request, PatientInvoice $invoice): RedirectResponse
    {
        $this->authorize('cashier.approve_discount');

        $data = $request->validate(['discount' => 'required|integer|min:0|max:'.$invoice->subtotal]);

        try {
            $this->svc->applyDiscount($invoice, $data['discount']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã áp dụng giảm giá.');
    }

    public function cancel(Request $request, PatientInvoice $invoice): RedirectResponse
    {
        $this->authorize('cashier.manage');

        $data = $request->validate([
            'cancel_reason' => 'required|string|max:1000',
        ]);

        try {
            $this->svc->cancel($invoice);
            $invoice->update(['cancel_reason' => $data['cancel_reason']]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hủy hóa đơn.');
    }

    public function pdf(PatientInvoice $invoice): Response
    {
        $this->authorize('cashier.view');

        $invoice->load(['patient', 'branch', 'treatmentPlan.items', 'payments']);

        $pdf = Pdf::loadView('pdf.patient-receipt', compact('invoice'));

        return $pdf->download("{$invoice->code}.pdf");
    }
}
