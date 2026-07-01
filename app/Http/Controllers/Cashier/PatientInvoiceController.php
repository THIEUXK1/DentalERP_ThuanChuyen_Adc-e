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
            ->map(fn ($inv) => [
                'id'                  => $inv->id,
                'code'                => $inv->code,
                'patient'             => $inv->patient->full_name,
                'patient_id'          => $inv->patient_id,
                'patient_phone'       => $inv->patient->phone ?? '',
                'branch'              => $inv->branch->name,
                'branch_id'           => $inv->branch_id,
                'status'              => $inv->status->value,
                'status_label'        => $inv->status->label(),
                'status_color'        => $inv->status->color(),
                'total'               => $inv->total,
                'amount_paid'         => $inv->amount_paid,
                'amount_due'          => $inv->amountDue(),
                'due_date'            => $inv->due_date?->format('d/m/Y'),
                'due_date_raw'        => $inv->due_date?->toDateString(),
                'installment_index'   => $inv->installment_index,
                'treatment_plan_code' => $inv->treatmentPlan?->code,
                'created_at'          => $inv->created_at->format('d/m/Y'),
                'created_at_raw'      => $inv->created_at->toDateString(),
            ]);

        return Inertia::render('Cashier/Invoices/Index', [
            'invoices'        => $invoices,
            'statuses'        => collect(InvoiceStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches'        => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'init_patient_id' => $request->patient_id ? (int) $request->patient_id : null,
        ]);
    }

    public function show(PatientInvoice $invoice): \Inertia\Response
    {
        $this->authorize('cashier.view');

        $invoice->load(['patient', 'branch', 'treatmentPlan.items.service', 'payments.creator', 'debt']);

        return Inertia::render('Cashier/Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'code' => $invoice->code,
                'patient' => $invoice->patient->full_name,
                'patient_id' => $invoice->patient_id,
                'patient_phone' => $invoice->patient->phone,
                'branch' => $invoice->branch->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'status_color' => $invoice->status->color(),
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'total' => $invoice->total,
                'amount_paid' => $invoice->amount_paid,
                'amount_due' => $invoice->amountDue(),
                'due_date' => $invoice->due_date?->format('d/m/Y'),
                'notes' => $invoice->notes,
                'plan_code' => $invoice->treatmentPlan?->code,
                'plan_id' => $invoice->treatment_plan_id,
            ],
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

    public function cancel(PatientInvoice $invoice): RedirectResponse
    {
        $this->authorize('cashier.manage');

        try {
            $this->svc->cancel($invoice);
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
