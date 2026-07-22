<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\TreatmentItemStatus;
use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Services\TreatmentPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TreatmentPlanItemController extends Controller
{
    public function __construct(private TreatmentPlanService $svc) {}

    public function store(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse|JsonResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'service_id'           => 'required|exists:dental_services,id',
            'tooth_number'         => 'nullable|string|max:20',
            'quantity'             => 'required|integer|min:1',
            'price_list_id'        => 'nullable|exists:price_lists,id',
            'unit_price'           => 'nullable|integer|min:0',
            'discount'             => 'nullable|integer|min:0',
            'notes'                => 'nullable|string|max:500',
            'diagnosis'            => 'nullable|string|max:255',
            'estimated_sessions'   => 'nullable|integer|min:1',
            'stage_name'           => 'nullable|string|max:255',
            'responsible_doctor_id' => 'nullable|exists:employees,id',
            'assistant_doctor_id'   => 'nullable|exists:employees,id',
        ]);

        $priceList = isset($data['price_list_id']) ? PriceList::find($data['price_list_id']) : null;

        $extra = array_filter([
            'unit_price'            => $data['unit_price'] ?? null,
            'discount'              => $data['discount'] ?? null,
            'notes'                 => $data['notes'] ?? null,
            'diagnosis'             => $data['diagnosis'] ?? null,
            'estimated_sessions'    => $data['estimated_sessions'] ?? null,
            'stage_name'            => $data['stage_name'] ?? null,
            'responsible_doctor_id' => $data['responsible_doctor_id'] ?? null,
            'assistant_doctor_id'   => $data['assistant_doctor_id'] ?? null,
        ], fn ($v) => $v !== null);

        try {
            $this->svc->addItem(
                $treatmentPlan,
                $data['service_id'],
                $data['tooth_number'] ?? null,
                $data['quantity'],
                $priceList,
                $extra
            );
        } catch (\RuntimeException $e) {
            if (! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        // Widgets on TreatmentPlans/Show.vue call these endpoints directly via axios instead
        // of Inertia's router, so they can apply the change locally — no Inertia visit, no
        // re-render/scroll-reset of the rest of the page.
        if (! $request->header('X-Inertia')) {
            return response()->json($this->svc->payload($treatmentPlan));
        }

        return back()->with('success', 'Đã thêm dịch vụ vào kế hoạch.');
    }

    public function update(Request $request, TreatmentPlanItem $treatmentPlanItem): RedirectResponse|JsonResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'quantity'              => 'required|integer|min:1',
            'unit_price'            => 'required|integer|min:0',
            'tooth_number'          => 'nullable|string|max:20',
            'notes'                 => 'nullable|string|max:500',
            'discount'              => 'nullable|integer|min:0',
            'stage_name'            => 'nullable|string|max:255',
            'estimated_sessions'    => 'nullable|integer|min:1',
            'diagnosis'             => 'nullable|string|max:255',
            'responsible_doctor_id' => 'nullable|exists:employees,id',
            'assistant_doctor_id'   => 'nullable|exists:employees,id',
        ]);

        try {
            $this->svc->updateItem($treatmentPlanItem, $data);
        } catch (\RuntimeException $e) {
            if (! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if (! $request->header('X-Inertia')) {
            return response()->json($this->svc->payload($treatmentPlanItem->plan));
        }

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(Request $request, TreatmentPlanItem $treatmentPlanItem): RedirectResponse|JsonResponse
    {
        $this->authorize('treatment_plans.edit');

        $plan = $treatmentPlanItem->plan;

        try {
            $this->svc->removeItem($treatmentPlanItem);
        } catch (\RuntimeException $e) {
            if (! $request->header('X-Inertia')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if (! $request->header('X-Inertia')) {
            return response()->json($this->svc->payload($plan));
        }

        return back()->with('success', 'Đã xóa dịch vụ.');
    }

    public function updateStatus(Request $request, TreatmentPlanItem $treatmentPlanItem): RedirectResponse|JsonResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'status' => ['required', new Enum(TreatmentItemStatus::class)],
        ]);

        $treatmentPlanItem->update(['status' => $data['status']]);

        if (! $request->header('X-Inertia')) {
            return response()->json($this->svc->payload($treatmentPlanItem->plan));
        }

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }

    public function complete(Request $request, TreatmentPlanItem $treatmentPlanItem): RedirectResponse|JsonResponse
    {
        $this->authorize('treatment_plans.edit');
        $this->svc->completeItem($treatmentPlanItem);

        if (! $request->header('X-Inertia')) {
            return response()->json($this->svc->payload($treatmentPlanItem->plan));
        }

        return back()->with('success', 'Đã đánh dấu hoàn thành.');
    }
}
