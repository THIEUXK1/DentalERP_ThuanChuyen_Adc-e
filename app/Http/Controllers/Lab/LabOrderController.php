<?php

namespace App\Http\Controllers\Lab;

use App\Enums\LabOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lab;
use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('labo.view');

        $query = LabOrder::with(['lab', 'patient', 'branch'])
            ->when($request->search, fn ($q, $v) => $q->where('code', 'ilike', "%{$v}%"))
            ->when($request->lab_id, fn ($q, $v) => $q->where('lab_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('id');

        return Inertia::render('Lab/Orders/Index', [
            'orders'   => $query->paginate(20)->through(fn ($o) => $this->listDto($o)),
            'filters'  => $request->only(['search', 'lab_id', 'status']),
            'labs'     => Lab::where('is_active', true)->orderBy('name')->get()->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]),
            'statuses' => collect(LabOrderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('labo.manage');
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('labo.manage');

        $data = $this->validated($request);
        $order = LabOrder::createWithCode([
            ...$data,
            'status'     => LabOrderStatus::Draft,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('lab.orders.show', $order)->with('success', 'Đã tạo đơn labo.');
    }

    public function show(LabOrder $order): Response
    {
        $this->authorize('labo.view');

        $order->load(['lab', 'patient', 'branch', 'treatmentPlan', 'warranties']);

        return Inertia::render('Lab/Orders/Show', [
            'order'      => $this->detailDto($order),
            'statuses'   => collect(LabOrderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'transitions' => collect($order->status->allowedTransitions())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function update(Request $request, LabOrder $order): RedirectResponse
    {
        $this->authorize('labo.manage');

        if (!in_array($order->status, [LabOrderStatus::Draft, LabOrderStatus::Sent])) {
            return back()->with('error', 'Chỉ có thể sửa đơn ở trạng thái Nháp hoặc Đã gửi.');
        }

        $order->update($this->validated($request));

        return back()->with('success', 'Đã cập nhật đơn labo.');
    }

    public function transition(Request $request, LabOrder $order): RedirectResponse
    {
        $this->authorize('labo.manage');

        $request->validate(['status' => 'required|string']);
        $newStatus = LabOrderStatus::from($request->status);

        if (!in_array($newStatus, $order->status->allowedTransitions())) {
            return back()->with('error', 'Chuyển trạng thái không hợp lệ.');
        }

        $updates = ['status' => $newStatus];
        if ($newStatus === LabOrderStatus::Sent)     $updates['sent_date']     = now()->toDateString();
        if ($newStatus === LabOrderStatus::Received) $updates['received_date'] = now()->toDateString();

        $order->update($updates);

        return back()->with('success', "Đã chuyển sang: {$newStatus->label()}");
    }

    public function destroy(LabOrder $order): RedirectResponse
    {
        $this->authorize('labo.manage');

        if ($order->status !== LabOrderStatus::Draft) {
            return back()->with('error', 'Chỉ xóa được đơn ở trạng thái Nháp.');
        }

        $order->delete();

        return redirect()->route('lab.orders.index')->with('success', 'Đã xóa đơn labo.');
    }

    private function form(?LabOrder $order = null): Response
    {
        return Inertia::render('Lab/Orders/Form', [
            'order'    => $order ? $this->formDto($order) : null,
            'labs'     => Lab::where('is_active', true)->orderBy('name')->get()->map(fn ($l) => [
                'id'          => $l->id,
                'name'        => $l->name,
                'price_items' => $l->priceItems->map(fn ($p) => ['service_name' => $p->service_name, 'unit_price' => $p->unit_price])->values()->all(),
            ]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    public function payables(Request $request): Response
    {
        $this->authorize('labo.view');

        $rows = LabOrder::with('lab')
            ->where('estimated_cost', '>', 0)
            ->when($request->lab_id, fn ($q, $v) => $q->where('lab_id', $v))
            ->orderByDesc('id')
            ->get()
            ->map(fn ($o) => [
                'id'             => $o->id,
                'code'           => $o->code,
                'lab'            => $o->lab->name,
                'lab_id'         => $o->lab_id,
                'status'         => $o->status->value,
                'status_label'   => $o->status->label(),
                'estimated_cost' => $o->estimated_cost,
                'cost_paid'      => $o->cost_paid,
                'remaining'      => max(0, $o->estimated_cost - $o->cost_paid),
                'created_at'     => $o->created_at->format('d/m/Y'),
            ]);

        $summary = LabOrder::where('estimated_cost', '>', 0)
            ->selectRaw('lab_id, SUM(estimated_cost) as total_cost, SUM(cost_paid) as total_paid')
            ->groupBy('lab_id')
            ->with('lab')
            ->get()
            ->map(fn ($o) => [
                'lab_id'      => $o->lab_id,
                'lab'         => $o->lab->name,
                'total_cost'  => (int) $o->total_cost,
                'total_paid'  => (int) $o->total_paid,
                'remaining'   => max(0, (int) $o->total_cost - (int) $o->total_paid),
            ]);

        return Inertia::render('Lab/Payables/Index', [
            'rows'    => $rows,
            'summary' => $summary,
            'labs'    => Lab::where('is_active', true)->orderBy('name')->get()->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]),
            'filters' => $request->only(['lab_id']),
        ]);
    }

    public function recordPayment(Request $request, LabOrder $order): RedirectResponse
    {
        $this->authorize('labo.manage');

        $data = $request->validate([
            'estimated_cost' => 'required|integer|min:0',
            'cost_paid'      => 'required|integer|min:0',
        ]);

        $order->update($data);

        return back()->with('success', 'Đã cập nhật thanh toán labo.');
    }

    private function listDto(LabOrder $o): array
    {
        return [
            'id'             => $o->id,
            'code'           => $o->code,
            'lab'            => $o->lab->name,
            'patient'        => $o->patient->full_name,
            'branch'         => $o->branch?->name,
            'status'         => $o->status->value,
            'status_label'   => $o->status->label(),
            'status_color'   => $o->status->color(),
            'total_amount'   => $o->total_amount,
            'estimated_cost' => $o->estimated_cost,
            'cost_paid'      => $o->cost_paid,
            'expected_date'  => $o->expected_date?->format('d/m/Y'),
            'created_at'     => $o->created_at->format('d/m/Y'),
        ];
    }

    private function detailDto(LabOrder $o): array
    {
        return [
            ...$this->listDto($o),
            'lab_id'           => $o->lab_id,
            'patient_id'       => $o->patient_id,
            'treatment_plan_id' => $o->treatment_plan_id,
            'notes'            => $o->notes,
            'sent_date'        => $o->sent_date?->format('d/m/Y'),
            'received_date'    => $o->received_date?->format('d/m/Y'),
            'items'            => $o->items ?? [],
            'warranties'       => $o->warranties->map(fn ($w) => [
                'id'           => $w->id,
                'service_name' => $w->service_name,
                'tooth_number' => $w->tooth_number,
                'start_date'   => $w->start_date->format('d/m/Y'),
                'end_date'     => $w->end_date->format('d/m/Y'),
                'status'       => $w->status->value,
                'status_label' => $w->status->label(),
                'status_color' => $w->status->color(),
            ])->values()->all(),
        ];
    }

    private function formDto(LabOrder $o): array
    {
        return [
            'id'               => $o->id,
            'lab_id'           => $o->lab_id,
            'patient_id'       => $o->patient_id,
            'branch_id'        => $o->branch_id,
            'treatment_plan_id' => $o->treatment_plan_id,
            'items'            => $o->items ?? [],
            'notes'            => $o->notes,
            'expected_date'    => $o->expected_date?->format('Y-m-d'),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'lab_id'            => 'required|exists:labs,id',
            'patient_id'        => 'required|exists:patients,id',
            'branch_id'         => 'nullable|exists:branches,id',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'items'             => 'nullable|array',
            'items.*.service_name' => 'required|string',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|integer|min:0',
            'total_amount'      => 'nullable|integer|min:0',
            'notes'             => 'nullable|string',
            'expected_date'     => 'nullable|date',
        ]);
    }
}
