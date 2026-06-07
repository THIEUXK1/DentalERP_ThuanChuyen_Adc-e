<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\TreatmentPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PriceList;
use App\Models\TreatmentPlan;
use App\Services\InvoiceService;
use App\Services\TreatmentPlanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class TreatmentPlanController extends Controller
{
    public function __construct(
        private TreatmentPlanService $svc,
        private InvoiceService $invoiceSvc
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $this->authorize('treatment_plans.view');

        $query = TreatmentPlan::with(['patient', 'doctor', 'branch'])
            ->when($request->search, fn ($q, $v) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'ilike', "%{$v}%")))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('id');

        return Inertia::render('Clinical/TreatmentPlans/Index', [
            'plans' => $query->paginate(20)->through(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'patient' => $p->patient->full_name,
                'patient_id' => $p->patient_id,
                'doctor' => $p->doctor?->full_name ?? '—',
                'branch' => $p->branch->name,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'status_color' => $p->status->color(),
                'total_amount' => $p->total_amount,
                'net_total' => $p->net_total,
                'created_at' => $p->created_at->format('d/m/Y'),
            ]),
            'statuses' => collect(TreatmentPlanStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function create(): \Inertia\Response
    {
        $this->authorize('treatment_plans.create');

        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('treatment_plans.create');

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'consultant_id' => 'nullable|exists:employees,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'notes' => 'nullable|string',
        ]);

        $plan = TreatmentPlan::create([
            ...$data,
            'code' => TreatmentPlan::generateCode(),
            'status' => TreatmentPlanStatus::Draft->value,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('clinical.treatment-plans.show', $plan)
            ->with('success', "Đã tạo kế hoạch điều trị {$plan->code}.");
    }

    public function show(TreatmentPlan $treatmentPlan): \Inertia\Response
    {
        $this->authorize('treatment_plans.view');

        $treatmentPlan->load(['patient', 'doctor', 'consultant', 'branch', 'items.service']);
        $allowed = $treatmentPlan->status->allowedTransitions();

        return Inertia::render('Clinical/TreatmentPlans/Show', [
            'plan' => [
                'id' => $treatmentPlan->id,
                'code' => $treatmentPlan->code,
                'patient' => $treatmentPlan->patient->full_name,
                'patient_id' => $treatmentPlan->patient_id,
                'doctor' => $treatmentPlan->doctor?->full_name ?? '—',
                'consultant' => $treatmentPlan->consultant?->full_name ?? '—',
                'branch' => $treatmentPlan->branch->name,
                'status' => $treatmentPlan->status->value,
                'status_label' => $treatmentPlan->status->label(),
                'status_color' => $treatmentPlan->status->color(),
                'is_editable' => $treatmentPlan->status->isEditable(),
                'total_amount' => $treatmentPlan->total_amount,
                'discount_amount' => $treatmentPlan->discount_amount,
                'deposit_amount' => $treatmentPlan->deposit_amount,
                'net_total' => $treatmentPlan->net_total,
                'notes' => $treatmentPlan->notes,
                'approved_at' => $treatmentPlan->approved_at?->format('d/m/Y H:i'),
                'payment_schedule' => $treatmentPlan->payment_schedule ?? [],
            ],
            'items' => $treatmentPlan->items->map(fn ($i) => [
                'id' => $i->id,
                'service_name' => $i->name,
                'tooth_number' => $i->tooth_number,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'subtotal' => $i->subtotal,
                'status' => $i->status->value,
                'status_label' => $i->status->label(),
                'status_color' => $i->status->color(),
                'notes' => $i->notes,
            ]),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'selling_price' => $s->selling_price]),
            'priceLists' => PriceList::where('is_active', true)->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'transitions' => collect($allowed)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'canApprove' => auth()->user()?->can('treatment_plans.approve'),
        ]);
    }

    public function edit(TreatmentPlan $treatmentPlan): \Inertia\Response
    {
        $this->authorize('treatment_plans.edit');

        return $this->form($treatmentPlan);
    }

    public function update(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        if (! $treatmentPlan->status->isEditable()) {
            return back()->with('error', 'Không thể sửa kế hoạch đã duyệt.');
        }

        $data = $request->validate([
            'doctor_id' => 'nullable|exists:employees,id',
            'consultant_id' => 'nullable|exists:employees,id',
            'discount_amount' => 'integer|min:0',
            'deposit_amount' => 'integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $treatmentPlan->update($data);

        return back()->with('success', 'Đã cập nhật kế hoạch.');
    }

    public function destroy(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        if ($treatmentPlan->status !== TreatmentPlanStatus::Draft) {
            return back()->with('error', 'Chỉ có thể xóa kế hoạch ở trạng thái Nháp.');
        }

        $treatmentPlan->delete();

        return redirect()->route('clinical.treatment-plans.index')->with('success', 'Đã xóa kế hoạch.');
    }

    public function transition(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate(['status' => 'required|string']);

        try {
            $this->svc->transition($treatmentPlan, TreatmentPlanStatus::from($data['status']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }

    public function approve(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.approve');

        try {
            $this->svc->approve($treatmentPlan);
            // Auto-create invoice
            $this->invoiceSvc->fromTreatmentPlan($treatmentPlan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã duyệt kế hoạch điều trị. Hóa đơn đã được tạo tự động.');
    }

    public function savePaymentSchedule(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        if (in_array($treatmentPlan->status, [TreatmentPlanStatus::Completed, TreatmentPlanStatus::Cancelled])) {
            return back()->with('error', 'Không thể cập nhật kế hoạch đã đóng.');
        }

        $request->validate(['schedule' => 'nullable|array']);
        $treatmentPlan->update(['payment_schedule' => $request->input('schedule') ?? []]);

        return back()->with('success', 'Đã lưu lịch thanh toán.');
    }

    public function pdf(TreatmentPlan $treatmentPlan): Response
    {
        $this->authorize('treatment_plans.view');

        $treatmentPlan->load(['patient', 'doctor', 'branch', 'items.service']);

        $pdf = Pdf::loadView('pdf.treatment-plan', compact('treatmentPlan'));

        return $pdf->download("KHDT-{$treatmentPlan->code}.pdf");
    }

    private function form(?TreatmentPlan $plan = null): \Inertia\Response
    {
        return Inertia::render('Clinical/TreatmentPlans/Form', [
            'plan' => $plan ? [
                'id' => $plan->id,
                'code' => $plan->code,
                'patient_id' => $plan->patient_id,
                'branch_id' => $plan->branch_id,
                'doctor_id' => $plan->doctor_id,
                'consultant_id' => $plan->consultant_id,
                'appointment_id' => $plan->appointment_id,
                'notes' => $plan->notes,
            ] : null,
            'patients' => Patient::where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone, 'code' => $p->code]),
            'doctors' => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'consultants' => Employee::where('role_type', 'consultant')->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }
}
