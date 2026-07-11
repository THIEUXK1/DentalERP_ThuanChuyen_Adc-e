<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\TreatmentPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PendingDeletion;
use App\Models\PriceList;
use App\Models\TreatmentPlan;
use App\Services\InvoiceService;
use App\Services\TreatmentPlanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            'statuses' => collect(TreatmentPlanStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'doctors'  => Employee::doctors()->where('is_active', true)->get()->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('treatment_plans.view');

        $issueIds = \DB::table('treatment_plan_items')
            ->whereRaw('amount != (quantity * unit_price - discount)')
            ->pluck('treatment_plan_id')
            ->unique()
            ->flip();

        // Plain query-builder select with joins instead of Eloquent + with(): at 50k+ plans,
        // eager-loading patient/doctor/branch via whereIn([...ids]) plus per-row model
        // hydration and Carbon::format() calls is dramatically slower (see PatientInvoiceController::data()).
        $rows = \DB::table('treatment_plans as tp')
            ->leftJoin('patients as p', 'p.id', '=', 'tp.patient_id')
            ->leftJoin('employees as d', 'd.id', '=', 'tp.doctor_id')
            ->leftJoin('branches as b', 'b.id', '=', 'tp.branch_id')
            // Defaults the page to this year's plans so the common case doesn't fetch/render the
            // entire (50k+) table; pass ?year=all to see everything.
            ->when($request->input('year', 'all') !== 'all', function ($q) use ($request) {
                $year = (int) $request->input('year');
                $q->where('tp.created_at', '>=', "{$year}-01-01 00:00:00")
                  ->where('tp.created_at', '<', ($year + 1) . '-01-01 00:00:00');
            })
            ->orderByDesc('tp.id')
            ->select(array_merge([
                'tp.id', 'tp.code', 'tp.patient_id', 'tp.doctor_id', 'tp.branch_id',
                'tp.status', 'tp.total_amount', 'tp.discount_amount', 'tp.payment_schedule', 'tp.notes',
            ], $this->dateColumns(), [
                'p.full_name as patient_name',
                'd.full_name as doctor_name',
                'b.name as branch_name',
            ]))
            ->get();

        $plans = $rows->map(function ($p) use ($issueIds) {
            $schedule = $p->payment_schedule ? json_decode($p->payment_schedule, true) : [];
            $status   = TreatmentPlanStatus::from($p->status);

            return [
                'id'           => $p->id,
                'code'         => $p->code,
                'patient'      => $p->patient_name,
                'patient_id'   => $p->patient_id,
                'doctor'       => $p->doctor_name ?? '—',
                'doctor_id'    => $p->doctor_id,
                'branch'       => $p->branch_name,
                'branch_id'    => $p->branch_id,
                'status'       => $status->value,
                'status_label' => $status->label(),
                'status_color' => $status->color(),
                'total_amount' => $p->total_amount,
                'net_total'    => $p->total_amount - $p->discount_amount,
                'payment_schedule_total' => collect($schedule)->sum('amount'),
                'payment_schedule_count' => count($schedule),
                'has_data_issue' => isset($issueIds[$p->id]),
                'notes'        => $p->notes ?? '',
                'start_date'     => $p->start_date ?? '—',
                'start_date_raw' => $p->start_date_raw ?? '',
                'created_at'   => $p->created_at,
                'created_at_raw' => $p->created_at_raw,
            ];
        });

        return response()->json($plans);
    }

    /** Driver-portable date formatting for the raw select() above (Postgres to_char vs SQLite strftime). */
    private function dateColumns(): array
    {
        if (\DB::getDriverName() === 'sqlite') {
            return [
                \DB::raw("strftime('%d/%m/%Y', tp.start_date) as start_date"),
                \DB::raw("strftime('%Y-%m-%d', tp.start_date) as start_date_raw"),
                \DB::raw("strftime('%d/%m/%Y', tp.created_at) as created_at"),
                \DB::raw("strftime('%Y-%m-%d', tp.created_at) as created_at_raw"),
            ];
        }

        return [
            \DB::raw("to_char(tp.start_date, 'DD/MM/YYYY') as start_date"),
            \DB::raw("to_char(tp.start_date, 'YYYY-MM-DD') as start_date_raw"),
            \DB::raw("to_char(tp.created_at, 'DD/MM/YYYY') as created_at"),
            \DB::raw("to_char(tp.created_at, 'YYYY-MM-DD') as created_at_raw"),
        ];
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
            'items.*.responsible_doctor_id' => 'nullable|exists:employees,id',
            'items.*.assistant_doctor_id' => 'nullable|exists:employees,id',
        ]);

        $plan = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $planStatus = $data['status'] ?? TreatmentPlanStatus::Draft->value;
            
            $plan = TreatmentPlan::createWithCode([
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
                    'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'amount' => $itemData['amount'],
                    'estimated_sessions' => $itemData['estimated_sessions'] ?? null,
                    'stage_name' => $itemData['stage_name'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                    'responsible_doctor_id' => $itemData['responsible_doctor_id'] ?? null,
                    'assistant_doctor_id' => $itemData['assistant_doctor_id'] ?? null,
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

        // Map installment_index → invoice info for installments that have payments
        $installmentInvoiceMap = $treatmentPlan->invoices()
            ->whereNotNull('installment_index')
            ->get(['id', 'installment_index', 'status', 'amount_paid', 'total'])
            ->keyBy('installment_index')
            ->map(fn ($inv) => [
                'id'           => $inv->id,
                'status'       => $inv->status->value,
                'status_label' => $inv->status->label(),
                'status_color' => $inv->status->color(),
                'amount_paid'  => $inv->amount_paid,
                'total'        => $inv->total,
                'locked'       => $inv->amount_paid > 0,
            ]);

        $primaryInvoice = $treatmentPlan->invoices()
            ->whereNull('installment_index')
            ->first(['id']);

        return Inertia::render('Clinical/TreatmentPlans/Show', [
            'plan' => [
                'id'               => $treatmentPlan->id,
                'code'             => $treatmentPlan->code,
                'patient'          => $treatmentPlan->patient->full_name,
                'patient_id'       => $treatmentPlan->patient_id,
                'doctor'           => $treatmentPlan->doctor?->full_name ?? '—',
                'doctor_id'        => $treatmentPlan->doctor_id,
                'consultant'       => $treatmentPlan->consultant?->full_name ?? '—',
                'consultant_id'    => $treatmentPlan->consultant_id,
                'branch'           => $treatmentPlan->branch->name,
                'status'           => $treatmentPlan->status->value,
                'status_label'     => $treatmentPlan->status->label(),
                'status_color'     => $treatmentPlan->status->color(),
                'is_editable'      => $treatmentPlan->status->isEditable(),
                'items_editable'   => $treatmentPlan->status->isItemsEditable(),
                'total_amount'     => $treatmentPlan->total_amount,
                'discount_amount'  => $treatmentPlan->discount_amount,
                'deposit_amount'   => $treatmentPlan->deposit_amount,
                'net_total'        => $treatmentPlan->net_total,
                'notes'            => $treatmentPlan->notes,
                'payment_notes'    => $treatmentPlan->payment_notes,
                'approved_at'      => $treatmentPlan->approved_at?->format('d/m/Y H:i'),
                'payment_schedule' => $treatmentPlan->payment_schedule ?? [],
                'installment_invoice_map' => $installmentInvoiceMap,
                'created_at'       => $treatmentPlan->created_at->format('d/m/Y'),
                'diagnosis'        => $treatmentPlan->diagnosis,
                'chief_complaint'  => $treatmentPlan->chief_complaint,
                'treatment_goal'   => $treatmentPlan->treatment_goal,
                'start_date'       => $treatmentPlan->start_date?->format('d/m/Y'),
                'start_date_raw'   => $treatmentPlan->start_date?->format('Y-m-d'),
                'expected_end_date'=> $treatmentPlan->expected_end_date?->format('d/m/Y'),
                'estimated_sessions'=> $treatmentPlan->estimated_sessions,
                'frequency'        => $treatmentPlan->frequency,
                'priority'         => $treatmentPlan->priority,
                'has_payments'        => $treatmentPlan->hasPayments(),
                'primary_invoice_id'  => $primaryInvoice?->id,
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
            'doctors'     => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
        ]);
    }

    public function edit(TreatmentPlan $treatmentPlan): \Inertia\Response
    {
        $this->authorize('treatment_plans.edit');

        $treatmentPlan->load('items');

        return $this->form($treatmentPlan);
    }

    public function update(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        if (! $treatmentPlan->status->isEditable() && ! in_array($request->input('action'), ['update_staff', 'update_date'])) {
            return back()->with('error', 'Không thể sửa kế hoạch đã duyệt.');
        }

        $data = $request->validate([
            'patient_id'         => 'sometimes|exists:patients,id',
            'branch_id'          => 'sometimes|exists:branches,id',
            'doctor_id'          => 'nullable|exists:employees,id',
            'consultant_id'      => 'nullable|exists:employees,id',
            'appointment_id'     => 'nullable|exists:appointments,id',
            'discount_amount'    => 'integer|min:0',
            'deposit_amount'     => 'integer|min:0',
            'total_amount'       => 'integer|min:0',
            'notes'              => 'nullable|string',
            'diagnosis'          => 'nullable|string|max:255',
            'chief_complaint'    => 'nullable|string|max:1000',
            'treatment_goal'     => 'nullable|string|max:255',
            'start_date'         => 'nullable|date',
            'expected_end_date'  => 'nullable|date',
            'estimated_sessions' => 'nullable|integer|min:1',
            'frequency'          => 'nullable|string|max:255',
            'priority'           => 'nullable|string|max:50',
            'status'             => 'nullable|string|max:50',
            'action'             => 'nullable|string',

            'items'                       => 'nullable|array',
            'items.*.service_id'          => 'required|exists:dental_services,id',
            'items.*.tooth_number'        => 'nullable|string|max:50',
            'items.*.diagnosis'           => 'nullable|string|max:255',
            'items.*.quantity'            => 'required|integer|min:1',
            'items.*.unit_price'          => 'required|integer|min:0',
            'items.*.discount'            => 'nullable|integer|min:0',
            'items.*.amount'              => 'required|integer|min:0',
            'items.*.estimated_sessions'  => 'nullable|integer|min:1',
            'items.*.stage_name'          => 'nullable|string|max:255',
            'items.*.notes'              => 'nullable|string|max:1000',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $treatmentPlan) {
            $treatmentPlan->update(\Illuminate\Support\Arr::except($data, ['items', 'action']));

            if (array_key_exists('items', $data)) {
                $treatmentPlan->items()->delete();
                foreach ($data['items'] ?? [] as $itemData) {
                    $service = \App\Models\DentalService::find($itemData['service_id']);
                    $treatmentPlan->items()->create([
                        'service_id'         => $itemData['service_id'],
                        'name'               => $service->name,
                        'tooth_number'       => $itemData['tooth_number'] ?? null,
                        'diagnosis'          => $itemData['diagnosis'] ?? null,
                        'quantity'           => $itemData['quantity'],
                        'unit_price'         => $itemData['unit_price'],
                        'subtotal'           => $itemData['quantity'] * $itemData['unit_price'],
                        'discount'           => $itemData['discount'] ?? 0,
                        'amount'             => $itemData['amount'],
                        'estimated_sessions' => $itemData['estimated_sessions'] ?? null,
                        'stage_name'         => $itemData['stage_name'] ?? null,
                        'notes'              => $itemData['notes'] ?? null,
                        'status'             => 'pending',
                    ]);
                }
            }

            // Auto-create invoice when editing a plan into approved state
            $newStatus = ($data['status'] ?? null);
            if ($newStatus === TreatmentPlanStatus::Approved->value && ! $treatmentPlan->invoices()->exists()) {
                $this->invoiceSvc->fromTreatmentPlan($treatmentPlan->fresh());
            }
        });

        $action = $data['action'] ?? 'show';
        if ($action === 'appointment') {
            return redirect()->route('schedule.appointments.create', [
                'patient_id' => $treatmentPlan->patient_id,
                'branch_id'  => $treatmentPlan->branch_id,
            ])->with('success', 'Đã cập nhật kế hoạch. Tiếp tục đặt lịch hẹn.');
        }
        if ($action === 'consent') {
            return redirect()->route('patients.show', $treatmentPlan->patient_id . '#consent')
                ->with('success', 'Đã cập nhật kế hoạch. Tiếp tục ký phiếu đồng ý.');
        }
        if ($action === 'payment') {
            return redirect()->route('cashier.invoices.index', ['patient_id' => $treatmentPlan->patient_id])
                ->with('success', 'Đã cập nhật kế hoạch. Tiếp tục thanh toán.');
        }

        return redirect()->route('clinical.treatment-plans.show', $treatmentPlan)
            ->with('success', 'Đã cập nhật kế hoạch điều trị.');
    }

    public function destroy(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $request->validate(['reason' => 'required|string|max:500']);

        $hasPayments = $treatmentPlan->hasPayments();

        if ($hasPayments) {
            return back()->withErrors(['reason' => 'Không thể xóa kế hoạch điều trị đã có lịch sử thanh toán.']);
        }

        PendingDeletion::create([
            'deletable_type' => TreatmentPlan::class,
            'deletable_id'   => $treatmentPlan->id,
            'reason'         => $request->reason,
            'user_id'        => auth()->id(),
            'label'          => $treatmentPlan->code,
            'execute_at'     => now()->addMinutes(10),
        ]);

        return redirect()->route('patients.show', $treatmentPlan->patient_id)
            ->with('success', 'Kế hoạch sẽ bị xóa sau 10 phút. Bạn có thể hoàn tác trong thời gian này.');
    }

    public function transition(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data      = $request->validate([
            'status'                => 'required|string',
            'force_complete_items'  => 'sometimes|boolean',
        ]);
        $newStatus = TreatmentPlanStatus::from($data['status']);

        try {
            $this->svc->transition($treatmentPlan, $newStatus, (bool) ($data['force_complete_items'] ?? false));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = 'Đã cập nhật trạng thái.';

        // Auto-create invoices when entering in_progress or completed without existing invoices
        if (in_array($newStatus, [TreatmentPlanStatus::InProgress, TreatmentPlanStatus::Completed, TreatmentPlanStatus::Approved])) {
            $fresh = $treatmentPlan->fresh();
            if (! $fresh->invoices()->exists()) {
                try {
                    $this->invoiceSvc->fromTreatmentPlan($fresh);
                    $msg = 'Đã cập nhật trạng thái. Tự động tạo hóa đơn.';
                } catch (\RuntimeException $e) {
                    return back()->with('success', $msg)->with('warning', 'Không thể tạo hóa đơn: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', $msg);
    }

    public function approve(TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.approve');

        try {
            $this->svc->approve($treatmentPlan);
            $this->invoiceSvc->fromTreatmentPlan($treatmentPlan);
            $msg = 'Đã duyệt kế hoạch điều trị. Hóa đơn đã được tạo tự động.';
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $msg);
    }

    public function savePaymentSchedule(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        if (in_array($treatmentPlan->status, [TreatmentPlanStatus::Completed, TreatmentPlanStatus::Cancelled])) {
            return back()->with('error', 'Không thể cập nhật kế hoạch đã đóng.');
        }

        $request->validate([
            'schedule'           => 'nullable|array',
            'schedule.*.due_date'=> 'nullable|date',
            'schedule.*.amount'  => 'required|integer|min:0',
            'schedule.*.note'    => 'nullable|string|max:255',
        ]);

        $schedule = $request->input('schedule') ?? [];

        // Lịch thanh toán chỉ là nhắc hẹn — không tạo/đồng bộ hóa đơn theo đợt.
        $treatmentPlan->update(['payment_schedule' => $schedule]);

        return back()->with('success', 'Đã lưu lịch thanh toán.');
    }

    public function savePaymentNotes(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate(['payment_notes' => 'nullable|string|max:2000']);
        $treatmentPlan->update(['payment_notes' => $data['payment_notes'] ?? null]);

        return back()->with('success', 'Đã lưu ghi chú thanh toán.');
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
                'id'                 => $plan->id,
                'code'               => $plan->code,
                'patient_id'         => $plan->patient_id,
                'branch_id'          => $plan->branch_id,
                'doctor_id'          => $plan->doctor_id,
                'consultant_id'      => $plan->consultant_id,
                'appointment_id'     => $plan->appointment_id,
                'notes'              => $plan->notes,
                'diagnosis'          => $plan->diagnosis,
                'chief_complaint'    => $plan->chief_complaint,
                'treatment_goal'     => $plan->treatment_goal,
                'start_date'         => $plan->start_date?->format('Y-m-d'),
                'expected_end_date'  => $plan->expected_end_date?->format('Y-m-d'),
                'estimated_sessions' => $plan->estimated_sessions,
                'frequency'          => $plan->frequency,
                'priority'           => $plan->priority,
                'status'             => $plan->status->value,
                'total_amount'       => $plan->total_amount,
                'discount_amount'    => $plan->discount_amount,
                'items'              => $plan->items->map(fn ($i) => [
                    'service_id'         => $i->service_id,
                    'tooth_number'       => $i->tooth_number,
                    'diagnosis'          => $i->diagnosis,
                    'quantity'           => $i->quantity,
                    'unit_price'         => $i->unit_price,
                    'discount'           => $i->discount,
                    'amount'             => $i->amount,
                    'estimated_sessions' => $i->estimated_sessions,
                    'stage_name'         => $i->stage_name,
                    'notes'              => $i->notes,
                ])->values()->all(),
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
