<?php

namespace App\Services;

use App\Enums\TreatmentItemStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\DentalService;
use App\Models\PriceList;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use Illuminate\Support\Facades\DB;

class TreatmentPlanService
{
    public function __construct(private PriceResolver $priceResolver) {}

    public function addItem(
        TreatmentPlan $plan,
        int $serviceId,
        ?string $toothNumber,
        int $quantity,
        ?PriceList $priceList = null
    ): TreatmentPlanItem {
        if (! $plan->status->isEditable()) {
            throw new \RuntimeException('Không thể sửa kế hoạch điều trị ở trạng thái hiện tại.');
        }

        $service = DentalService::findOrFail($serviceId);
        $price = $this->priceResolver->resolve($service, $priceList);
        $subtotal = $price * $quantity;

        return DB::transaction(function () use ($plan, $service, $toothNumber, $quantity, $price, $subtotal) {
            $item = TreatmentPlanItem::create([
                'treatment_plan_id' => $plan->id,
                'service_id' => $service->id,
                'name' => $service->name,
                'tooth_number' => $toothNumber,
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal,
                'status' => TreatmentItemStatus::Pending->value,
            ]);
            $plan->recalcTotals();

            return $item;
        });
    }

    public function updateItem(TreatmentPlanItem $item, int $quantity, int $unitPrice, ?string $toothNumber, ?string $notes): void
    {
        if (! $item->plan->status->isEditable()) {
            throw new \RuntimeException('Không thể sửa kế hoạch điều trị ở trạng thái hiện tại.');
        }

        DB::transaction(function () use ($item, $quantity, $unitPrice, $toothNumber, $notes) {
            $item->update([
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $quantity * $unitPrice,
                'tooth_number' => $toothNumber,
                'notes' => $notes,
            ]);
            $item->plan->recalcTotals();
        });
    }

    public function removeItem(TreatmentPlanItem $item): void
    {
        if (! $item->plan->status->isEditable()) {
            throw new \RuntimeException('Không thể xóa item khi kế hoạch đã duyệt.');
        }

        DB::transaction(function () use ($item) {
            $plan = $item->plan;
            $item->delete();
            $plan->recalcTotals();
        });
    }

    public function transition(TreatmentPlan $plan, TreatmentPlanStatus $to): void
    {
        $allowed = $plan->status->allowedTransitions();

        if (! in_array($to, $allowed)) {
            throw new \RuntimeException("Không thể chuyển từ [{$plan->status->label()}] sang [{$to->label()}].");
        }

        DB::transaction(fn () => $plan->update(['status' => $to]));
    }

    public function approve(TreatmentPlan $plan): void
    {
        if ($plan->status !== TreatmentPlanStatus::Quoted) {
            throw new \RuntimeException('Chỉ có thể duyệt kế hoạch ở trạng thái Đã báo giá.');
        }

        if ($plan->items()->count() === 0) {
            throw new \RuntimeException('Kế hoạch phải có ít nhất 1 dịch vụ trước khi duyệt.');
        }

        DB::transaction(fn () => $plan->update([
            'status' => TreatmentPlanStatus::Approved,
            'approved_at' => now(),
        ]));
    }

    public function completeItem(TreatmentPlanItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->update(['status' => TreatmentItemStatus::Completed->value]);

            $plan = $item->plan;
            $allDone = $plan->items()->where('status', '!=', TreatmentItemStatus::Completed->value)->doesntExist();

            if ($allDone && $plan->status === TreatmentPlanStatus::InProgress) {
                $plan->update(['status' => TreatmentPlanStatus::Completed]);
            }
        });
    }
}
