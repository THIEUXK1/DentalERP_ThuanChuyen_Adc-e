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

    public function transition(TreatmentPlan $plan, TreatmentPlanStatus $to, bool $forceCompleteItems = false): void
    {
        $allowed = $plan->status->allowedTransitions();

        if (! in_array($to, $allowed)) {
            throw new \RuntimeException("Không thể chuyển từ [{$plan->status->label()}] sang [{$to->label()}].");
        }

        if ($to === TreatmentPlanStatus::Completed) {
            DB::transaction(function () use ($plan, $forceCompleteItems, $to) {
                if ($forceCompleteItems) {
                    $plan->items()
                        ->whereNotIn('status', [TreatmentItemStatus::Completed->value, TreatmentItemStatus::Cancelled->value])
                        ->update(['status' => TreatmentItemStatus::Completed->value]);
                }

                $remaining = $forceCompleteItems
                    ? $plan->items()->whereNotIn('status', [TreatmentItemStatus::Completed->value, TreatmentItemStatus::Cancelled->value])->count()
                    : $plan->items()->where('status', '!=', TreatmentItemStatus::Completed->value)->count();

                if ($remaining > 0) {
                    throw new \RuntimeException("Còn {$remaining} dịch vụ chưa hoàn thành. Vui lòng hoàn thành tất cả dịch vụ trước khi đóng kế hoạch.");
                }

                $plan->update(['status' => $to]);
            });

            return;
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

    /**
     * Shared shape for the treatment plan's "show" page — used both for the initial
     * Inertia render and for the JSON responses that axios-driven inline widgets
     * (see TreatmentPlans/Show.vue) use to refresh their local state without a full
     * page visit.
     */
    public function payload(TreatmentPlan $plan): array
    {
        $plan->refresh()->load(['patient', 'doctor', 'consultant', 'branch', 'items.service', 'items.responsibleDoctor', 'items.assistantDoctor']);
        $allowed = $plan->status->allowedTransitions();

        $installmentInvoiceMap = $plan->invoices()
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

        $primaryInvoice = $plan->invoices()->whereNull('installment_index')->first(['id']);

        return [
            'plan' => [
                'id'               => $plan->id,
                'code'             => $plan->code,
                'patient'          => $plan->patient->full_name,
                'patient_id'       => $plan->patient_id,
                'doctor'           => $plan->doctor?->full_name ?? '—',
                'doctor_id'        => $plan->doctor_id,
                'consultant'       => $plan->consultant?->full_name ?? '—',
                'consultant_id'    => $plan->consultant_id,
                'branch'           => $plan->branch->name,
                'status'           => $plan->status->value,
                'status_label'     => $plan->status->label(),
                'status_color'     => $plan->status->color(),
                'is_editable'      => $plan->status->isEditable(),
                'items_editable'   => $plan->status->isItemsEditable(),
                'total_amount'     => $plan->total_amount,
                'discount_amount'  => $plan->discount_amount,
                'deposit_amount'   => $plan->deposit_amount,
                'net_total'        => $plan->net_total,
                'notes'            => $plan->notes,
                'payment_notes'    => $plan->payment_notes,
                'approved_at'      => $plan->approved_at?->format('d/m/Y H:i'),
                'payment_schedule' => $plan->payment_schedule ?? [],
                'installment_invoice_map' => $installmentInvoiceMap,
                'created_at'       => $plan->created_at->format('d/m/Y'),
                'diagnosis'        => $plan->diagnosis,
                'chief_complaint'  => $plan->chief_complaint,
                'treatment_goal'   => $plan->treatment_goal,
                'start_date'       => $plan->start_date?->format('d/m/Y H:i'),
                'start_date_raw'   => $plan->start_date?->format('Y-m-d\TH:i'),
                'expected_end_date'=> $plan->expected_end_date?->format('d/m/Y'),
                'estimated_sessions'=> $plan->estimated_sessions,
                'frequency'        => $plan->frequency,
                'priority'         => $plan->priority,
                'has_payments'        => $plan->hasPayments(),
                'primary_invoice_id'  => $primaryInvoice?->id,
            ],
            'items' => $plan->items->map(fn ($i) => [
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
            ])->values(),
            'transitions' => collect($allowed)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->values(),
        ];
    }
}
