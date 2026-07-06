<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PatientInvoice;
use App\Models\PendingDeletion;
use App\Models\TreatmentPlan;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PatientInvoiceController extends Controller
{
    public function __construct(private InvoiceService $svc) {}

    public function index(Request $request): \Inertia\Response
    {
        $this->authorize('cashier.view');

        return Inertia::render('Cashier/Invoices/Index', [
            'statuses'        => collect(InvoiceStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches'        => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'init_patient_id' => $request->patient_id ? (int) $request->patient_id : null,
            'init_plan_id'    => $request->plan_id    ? (int) $request->plan_id    : null,
        ]);
    }

    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('cashier.view');

        // Bypasses Eloquent model hydration entirely: with 50k+ invoices, building an Eloquent
        // model (casts, magic accessors) plus Carbon::format() per row for every invoice is the
        // dominant cost (tens of seconds). A plain query-builder select with dates formatted in
        // SQL and stdClass rows cuts this down by ~10x.
        $rows = DB::table('patient_invoices as pi')
            ->leftJoin('patients as p', 'p.id', '=', 'pi.patient_id')
            ->leftJoin('branches as b', 'b.id', '=', 'pi.branch_id')
            ->leftJoin('treatment_plans as tp', 'tp.id', '=', 'pi.treatment_plan_id')
            // Defaults the page to this year's invoices so the common case doesn't fetch/render
            // the entire (50k+) table; pass ?year=all to see everything.
            ->when($request->input('year', 'all') !== 'all', function ($q) use ($request) {
                $year = (int) $request->input('year');
                $q->where('pi.created_at', '>=', "{$year}-01-01 00:00:00")
                  ->where('pi.created_at', '<', ($year + 1) . '-01-01 00:00:00');
            })
            ->orderByRaw('pi.due_date ASC NULLS LAST, pi.id DESC')
            ->select(
                'pi.id', 'pi.code', 'pi.patient_id', 'pi.branch_id', 'pi.treatment_plan_id',
                'pi.status', 'pi.total', 'pi.amount_paid', 'pi.installment_index',
                DB::raw("to_char(pi.due_date, 'DD/MM/YYYY') as due_date"),
                DB::raw("to_char(pi.due_date, 'YYYY-MM-DD') as due_date_raw"),
                DB::raw("to_char(pi.created_at, 'DD/MM/YYYY') as created_at"),
                DB::raw("to_char(pi.created_at, 'YYYY-MM-DD') as created_at_raw"),
                'p.full_name as patient_name', 'p.phone as patient_phone',
                'b.name as branch_name',
                'tp.code as plan_code', 'tp.payment_schedule as plan_payment_schedule',
            )
            ->get();

        $invoices = $rows->map(function ($inv) {
            $schedule   = $inv->plan_payment_schedule ? json_decode($inv->plan_payment_schedule, true) : null;
            $noSchedule = $inv->installment_index === null
                && $inv->treatment_plan_id !== null
                && empty($schedule);

            $status = InvoiceStatus::from($inv->status);

            return [
                'id'                  => $inv->id,
                'code'                => $inv->code,
                'patient'             => $inv->patient_name,
                'patient_id'          => $inv->patient_id,
                'patient_phone'       => $inv->patient_phone ?? '',
                'branch'              => $inv->branch_name,
                'branch_id'           => $inv->branch_id,
                'status'              => $status->value,
                'status_label'        => $noSchedule ? 'Chưa làm đợt TT' : $status->label(),
                'status_color'        => $noSchedule ? 'amber' : $status->color(),
                'total'               => $inv->total,
                'amount_paid'         => $inv->amount_paid,
                'amount_due'          => max(0, $inv->total - $inv->amount_paid),
                'due_date'            => $inv->due_date,
                'due_date_raw'        => $inv->due_date_raw,
                'installment_index'   => $inv->installment_index,
                'plan_id'             => $inv->treatment_plan_id,
                'has_no_schedule'     => $noSchedule,
                'treatment_plan_code' => $inv->plan_code,
                'created_at'          => $inv->created_at,
                'created_at_raw'      => $inv->created_at_raw,
            ];
        });

        return response()->json($invoices);
    }

    public function show(PatientInvoice $invoice): \Inertia\Response
    {
        $this->authorize('cashier.view');

        $invoice->load(['patient', 'branch', 'treatmentPlan.items.service', 'payments.creator', 'debt']);

        $plan = $invoice->treatmentPlan;

        $planPendingDeletion = null;
        if ($plan) {
            $pending = PendingDeletion::where('deletable_type', TreatmentPlan::class)
                ->where('deletable_id', $plan->id)
                ->whereNull('cancelled_at')
                ->whereNull('executed_at')
                ->where('execute_at', '>', now())
                ->first();
            if ($pending) {
                $planPendingDeletion = [
                    'id'         => $pending->id,
                    'execute_at' => $pending->execute_at->toIso8601String(),
                ];
            }
        }

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
                'overpaid_amount'  => $invoice->overpaidAmount(),
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
            'plan_pending_deletion' => $planPendingDeletion,
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
