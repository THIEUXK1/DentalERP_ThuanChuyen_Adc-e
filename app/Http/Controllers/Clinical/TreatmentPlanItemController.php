<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Services\TreatmentPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TreatmentPlanItemController extends Controller
{
    public function __construct(private TreatmentPlanService $svc) {}

    public function store(Request $request, TreatmentPlan $treatmentPlan): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'service_id' => 'required|exists:dental_services,id',
            'tooth_number' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:1',
            'price_list_id' => 'nullable|exists:price_lists,id',
        ]);

        $priceList = $data['price_list_id']
            ? PriceList::find($data['price_list_id'])
            : null;

        try {
            $this->svc->addItem($treatmentPlan, $data['service_id'], $data['tooth_number'], $data['quantity'], $priceList);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã thêm dịch vụ vào kế hoạch.');
    }

    public function update(Request $request, TreatmentPlanItem $treatmentPlanItem): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|integer|min:0',
            'tooth_number' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->svc->updateItem($treatmentPlanItem, $data['quantity'], $data['unit_price'], $data['tooth_number'], $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã cập nhật.');
    }

    public function destroy(TreatmentPlanItem $treatmentPlanItem): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');

        try {
            $this->svc->removeItem($treatmentPlanItem);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã xóa dịch vụ.');
    }

    public function complete(TreatmentPlanItem $treatmentPlanItem): RedirectResponse
    {
        $this->authorize('treatment_plans.edit');
        $this->svc->completeItem($treatmentPlanItem);

        return back()->with('success', 'Đã đánh dấu hoàn thành.');
    }
}
