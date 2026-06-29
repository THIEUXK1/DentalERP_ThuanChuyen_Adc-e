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

    public function index(): \Inertia\Response
    {
        $this->authorize('treatment_plans.view');

        return Inertia::render('Clinical/TreatmentPlans/Index', [
            'all_plans' => TreatmentPlan::with(['patient', 'doctor', 'branch'])
                ->orderByDesc('id')
                ->get()
                ->map(fn ($p) => [
                    'id'           => $p->id,
                    'code'         => $p->code,
                    'patient'      => $p->patient->full_name,
                    'patient_id'   => $p->patient_id,
                    'doctor'       => $p->doctor?->full_name ?? '—',
                    'branch'       => $p->branch->name,
                    'branch_id'    => $p->branch_id,
                    'status'       => $p->status->value,
                    'status_label' => $p->status->label(),
                    'status_color' => $p->status->color(),
                    'total_amount' => $p->total_amount,
                    'net_total'    => $p->net_total,
                    'notes'        => $p->notes ?? '',
                    'created_at'   => $p->created_at->format('d/m/Y'),
                ]),
            'statuses' => collect(TreatmentPlanStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    public function create(Request $request): \Inertia\Response
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
            'diagnosis' => 'nullable|string|max:255',
            'chief_complaint' => 'nullable|string|max:1000',
            'treatment_goal' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date',
            'estimated_sessions' => 'nullable|integer|min:1',
            'frequency' => 'nullable|string|max:255',
            'priority' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'total_amount' => 'required|integer',
            'discount_amount' => 'required|integer',
            'action' => 'nullable|string',
            
            'items' => 'nullable|array',
            'items.*.service_id' => 'required|exists:dental_services,id',
            'items.*.tooth_number' => 'nullable|string|max:50',
            'items.*.diagnosis' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.discount' => 'nullable|integer|min:0',
            'items.*.amount' => 'required|integer|min:0',
            'items.*.estimated_sessions' => 'nullable|integer|min:1',
            'items.*.stage_name' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        $plan = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $planStatus = $data['status'] ?? TreatmentPlanStatus::Draft->value;
            
            $plan = TreatmentPlan::create([
                'code' => TreatmentPlan::generateCode(),
                'patient_id' => $data['patient_id'],
                'branch_id' => $data['branch_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'consultant_id' => $data['consultant_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'status' => $planStatus,
                'total_amount' => $data['total_amount'],
                'discount_amount' => $data['discount_amount'],
                'notes' => $data['notes'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'treatment_goal' => $data['treatment_goal'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'expected_end_date' => $data['expected_end_date'] ?? null,
                'estimated_sessions' => $data['estimated_sessions'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] ?? [] as $itemData) {
                $service = \App\Models\DentalService::find($itemData['service_id']);
                $plan->items()->create([
                    'service_id' => $itemData['service_id'],
                    'name' => $service->name,
                    'tooth_number' => $itemData['tooth_number'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['amount'],
                    'discount' => $itemData['discount'] ?? 0,
                    'amount' => $itemData['amount'],
                    'estimated_sessions' => $itemData['estimated_sessions'] ?? null,
                    'stage_name' => $itemData['stage_name'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            if ($planStatus === TreatmentPlanStatus::Approved->value) {
                $this->invoiceSvc->fromTreatmentPlan($plan);
            }

            return $plan;
        });

        $action = $data['action'] ?? 'show';
        if ($action === 'appointment') {
            return redirect()->route('schedule.appointments.create', [
                'patient_id' => $plan->patient_id,
                'branch_id' => $plan->branch_id,
            ])->with('success', "Đã tạo kế hoạch điều trị {$plan->code}. Tiếp tục đặt lịch hẹn.");
        }

        if ($action === 'consent') {
            return redirect()->route('patients.show', $plan->patient_id . '#consent')
                ->with('success', "Đã tạo kế hoạch điều trị {$plan->code}. Tiếp tục ký phiếu đồng ý.");
        }

        if ($action === 'payment') {
            return redirect()->route('cashier.invoices.index', [
                'patient_id' => $plan->patient_id,
            ])->with('success', "Đã tạo kế hoạch điều trị {$plan->code} và tự động tạo hóa đơn. Tiếp tục thanh toán.");
        }

        return redirect()->route('clinical.treatment-plans.show', $plan)
            ->with('success', "Đã tạo kế hoạch điều trị {$plan->code}.");
    }

    public function show(TreatmentPlan $treatmentPlan): \Inertia\Response
    {
        $this->authorize('treatment_plans.view');

        $treatmentPlan->load(['patient', 'doctor', 'consultant', 'branch', 'items.service', 'items.responsibleDoctor', 'items.assistantDoctor']);
        $allowed = $treatmentPlan->status->allowedTransitions();

        return Inertia::render('Clinical/TreatmentPlans/Show', [
            'plan' => [
                'id'               => $treatmentPlan->id,
                'code'             => $treatmentPlan->code,
                'patient'          => $treatmentPlan->patient->full_name,
                'patient_id'       => $treatmentPlan->patient_id,
                'doctor'           => $treatmentPlan->doctor?->full_name ?? '—',
                'consultant'       => $treatmentPlan->consultant?->full_name ?? '—',
                'branch'           => $treatmentPlan->branch->name,
                'status'           => $treatmentPlan->status->value,
                'status_label'     => $treatmentPlan->status->label(),
                'status_color'     => $treatmentPlan->status->color(),
                'is_editable'      => $treatmentPlan->status->isEditable(),
                'total_amount'     => $treatmentPlan->total_amount,
                'discount_amount'  => $treatmentPlan->discount_amount,
                'deposit_amount'   => $treatmentPlan->deposit_amount,
                'net_total'        => $treatmentPlan->net_total,
                'notes'            => $treatmentPlan->notes,
                'approved_at'      => $treatmentPlan->approved_at?->format('d/m/Y H:i'),
                'payment_schedule' => $treatmentPlan->payment_schedule ?? [],
                'created_at'       => $treatmentPlan->created_at->format('d/m/Y'),
                'diagnosis'        => $treatmentPlan->diagnosis,
                'chief_complaint'  => $treatmentPlan->chief_complaint,
                'treatment_goal'   => $treatmentPlan->treatment_goal,
                'start_date'       => $treatmentPlan->start_date?->format('d/m/Y'),
                'expected_end_date'=> $treatmentPlan->expected_end_date?->format('d/m/Y'),
                'estimated_sessions'=> $treatmentPlan->estimated_sessions,
                'frequency'        => $treatmentPlan->frequency,
                'priority'         => $treatmentPlan->priority,
            ],
            'items' => $treatmentPlan->items->map(fn ($i) => [
                'id'           => $i->id,
                'service_name' => $i->name,
                'tooth_number' => $i->tooth_number,
                'quantity'     => $i->quantity,
                'unit_price'   => $i->unit_price,
                'subtotal'     => $i->subtotal,
                'status'       => $i->status->value,
                'status_label' => $i->status->label(),
                'status_color' => $i->status->color(),
                'notes'        => $i->notes,
                'diagnosis'    => $i->diagnosis,
                'discount'     => $i->discount,
                'amount'       => $i->amount,
                'estimated_sessions' => $i->estimated_sessions,
                'stage_name'         => $i->stage_name,
                'responsible_doctor_id' => $i->responsible_doctor_id,
                'assistant_doctor_id'   => $i->assistant_doctor_id,
                'doctor_name'           => $i->responsibleDoctor?->full_name,
                'assistant_name'        => $i->assistantDoctor?->full_name,
            ]),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'selling_price' => $s->selling_price]),
            'priceLists' => PriceList::where('is_active', true)->get()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'transitions' => collect($allowed)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'canApprove'  => auth()->user()?->can('treatment_plans.approve'),
            'doctors'     => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
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
        $patientId = request('patient_id') ? (int) request('patient_id') : null;
        $selectedPatient = $patientId ? Patient::find($patientId) : null;

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
            'selected_patient_id' => $plan ? $plan->patient_id : $patientId,
            'selected_branch_id' => $plan ? $plan->branch_id : ($selectedPatient ? $selectedPatient->branch_id : null),
            'patients' => Patient::where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone, 'code' => $p->code, 'branch_id' => $p->branch_id]),
            'doctors' => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'consultants' => Employee::where('role_type', 'consultant')->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'selling_price' => $s->selling_price]),
        ]);
    }
}
