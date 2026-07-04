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
        ?PriceList $priceList = null,
        array $extra = []
    ): TreatmentPlanItem {
        if (! $plan->status->isItemsEditable()) {
            throw new \RuntimeException('Không thể sửa kế hoạch điều trị ở trạng thái hiện tại.');
        }

        $service = DentalService::findOrFail($serviceId);
        $basePrice = $this->priceResolver->resolve($service, $priceList);
        $price = (isset($extra['unit_price']) && $extra['unit_price'] > 0) ? (int) $extra['unit_price'] : $basePrice;
        $discount = (int) ($extra['discount'] ?? 0);
        $subtotal = $price * $quantity;
        $amount   = $subtotal - $discount;

        return DB::transaction(function () use ($plan, $service, $toothNumber, $quantity, $price, $discount, $subtotal, $amount, $extra) {
            $item = TreatmentPlanItem::create([
                'treatment_plan_id'    => $plan->id,
                'service_id'           => $service->id,
                'name'                 => $service->name,
                'tooth_number'         => $toothNumber,
                'quantity'             => $quantity,
                'unit_price'           => $price,
                'subtotal'             => $subtotal,
                'discount'             => $discount,
                'amount'               => $amount,
                'notes'                => $extra['notes'] ?? null,
                'diagnosis'            => $extra['diagnosis'] ?? null,
                'estimated_sessions'   => $extra['estimated_sessions'] ?? null,
                'stage_name'           => $extra['stage_name'] ?? null,
                'responsible_doctor_id' => $extra['responsible_doctor_id'] ?? null,
                'assistant_doctor_id'   => $extra['assistant_doctor_id'] ?? null,
                'status'               => TreatmentItemStatus::Pending->value,
            ]);
            $plan->recalcTotals();

            return $item;
        });
    }

    public function updateItem(TreatmentPlanItem $item, array $data): void
    {
        if (! $item->plan->status->isItemsEditable()) {
            throw new \RuntimeException('Không thể sửa kế hoạch điều trị ở trạng thái hiện tại.');
        }

        $quantity  = (int) $data['quantity'];
        $unitPrice = (int) $data['unit_price'];
        $discount  = (int) ($data['discount'] ?? 0);
        $subtotal  = $quantity * $unitPrice;
        $amount    = $subtotal - $discount;

        DB::transaction(function () use ($item, $quantity, $unitPrice, $discount, $subtotal, $amount, $data) {
            $item->update([
                'quantity'              => $quantity,
                'unit_price'            => $unitPrice,
                'subtotal'              => $subtotal,
                'discount'              => $discount,
                'amount'                => $amount,
                'tooth_number'          => $data['tooth_number'] ?? null,
                'notes'                 => $data['notes'] ?? null,
                'stage_name'            => $data['stage_name'] ?? null,
                'estimated_sessions'    => $data['estimated_sessions'] ?? null,
                'diagnosis'             => $data['diagnosis'] ?? null,
                'responsible_doctor_id' => $data['responsible_doctor_id'] ?? null,
                'assistant_doctor_id'   => $data['assistant_doctor_id'] ?? null,
            ]);
            $item->plan->recalcTotals();
        });
    }

    public function removeItem(TreatmentPlanItem $item): void
    {
        if (! $item->plan->status->isItemsEditable()) {
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

        if ($to === TreatmentPlanStatus::Completed) {
            $remaining = $plan->items()->where('status', '!=', TreatmentItemStatus::Completed->value)->count();
            if ($remaining > 0) {
                throw new \RuntimeException("Còn {$remaining} dịch vụ chưa hoàn thành. Vui lòng hoàn thành tất cả dịch vụ trước khi đóng kế hoạch.");
            }
        }

        DB::transaction(fn () => $plan->update(['status' => $to]));
    }

    public function approve(TreatmentPlan $plan): void
    {
        if ($plan->status !== TreatmentPlanStatus::Draft) {
            throw new \RuntimeException('Chỉ có thể chuyển kế hoạch Nháp sang Chưa điều trị.');
        }

        if ($plan->items()->count() === 0) {
            throw new \RuntimeException('Kế hoạch phải có ít nhất 1 dịch vụ trước khi chuyển.');
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
