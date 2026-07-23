<?php

namespace App\Services\Dental;

use App\Enums\KpiAllocationStatus;
use App\Enums\KpiBaseType;
use App\Enums\TreatmentItemStatus;
use App\Models\DentalServiceStep;
use App\Models\KpiAllocation;
use App\Models\PatientPayment;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentStepExecution;
use Illuminate\Support\Facades\DB;

class DentalKpiService
{
    /**
     * Calculate and persist KPI allocations for a treatment plan item.
     * Idempotent: existing accrued allocations are deleted and recalculated.
     */
    public function calculateForItem(TreatmentPlanItem $item): void
    {
        // Only calculate when service is completed
        if ($item->status !== TreatmentItemStatus::Completed) {
            return;
        }

        $service = $item->service()->with(['costs', 'steps'])->first();
        if (!$service) {
            return;
        }

        DB::transaction(function () use ($item, $service) {
            // Remove only non-finalised allocations
            KpiAllocation::where('treatment_plan_item_id', $item->id)
                ->whereIn('status', [KpiAllocationStatus::Accrued->value, KpiAllocationStatus::PendingApproval->value])
                ->delete();

            $eligibleRevenue = $item->subtotal; // gross price
            $directCost      = $this->resolveDirectCost($item, $service);
            $kpiPool         = $this->resolveKpiPool($service, $eligibleRevenue, $directCost);
            $collectionFactor = $item->collectionFactor();
            $period          = now()->format('Y-m');

            $executions = $item->stepExecutions()->with(['serviceStep', 'participants'])->get();
            $allocatedPool = 0;  // tracks pool consumed by step performers ≠ main doctor

            // Step-level allocations
            foreach ($executions as $execution) {
                $step = $execution->serviceStep;
                if (!$step || !$execution->isDone()) {
                    continue;
                }

                $stepPool = (int) round($kpiPool * ($step->kpi_share_percent / 100));
                if ($stepPool <= 0) {
                    continue;
                }

                $participants = $execution->participants;

                if ($participants->isEmpty()) {
                    // Single performer
                    $this->createAllocation($item, $service, $execution, $execution->performed_by, 'step_performer',
                        $eligibleRevenue, $directCost, $kpiPool, 100, $stepPool,
                        $collectionFactor, $service->kpi_base_type->value, $period);
                } else {
                    // Multi-participant
                    foreach ($participants as $p) {
                        $personKpi = (int) round($stepPool * ($p->share_percent / 100));
                        $this->createAllocation($item, $service, $execution, $p->employee_id, $p->role ?? 'step_performer',
                            $eligibleRevenue, $directCost, $kpiPool, $p->share_percent, $personKpi,
                            $collectionFactor, $service->kpi_base_type->value, $period);
                    }
                }

                // Track how much is deducted from main doctor
                if ($step->deduct_from_main_doctor) {
                    $responsibleId = $item->responsible_doctor_id ?? ($item->plan->doctor_id ?? null);
                    $isMainDoctor  = $execution->performed_by === $responsibleId;
                    if (!$isMainDoctor) {
                        $allocatedPool += $stepPool;
                    }
                }
            }

            // Main doctor residual KPI (pool - steps deducted by others), split by which
            // doctor actually collected the payments recorded against this item — falls
            // back to the single responsible_doctor_id when no payment is tagged to the
            // item yet (legacy data, or cashier left the picker on its default).
            $mainDoctorKpi = max(0, $kpiPool - $allocatedPool);
            if ($mainDoctorKpi > 0) {
                $doctorShares = $this->resolveDoctorPaymentShares($item);

                if ($doctorShares->isEmpty()) {
                    $fallbackDoctorId = $item->responsible_doctor_id ?? $item->plan?->doctor_id;
                    if ($fallbackDoctorId) {
                        $this->createAllocation($item, $service, null, $fallbackDoctorId, 'main_doctor',
                            $eligibleRevenue, $directCost, $kpiPool, 100, $mainDoctorKpi,
                            $collectionFactor, $service->kpi_base_type->value, $period);
                    }
                } else {
                    $totalPaid = $doctorShares->sum();
                    foreach ($doctorShares as $doctorId => $doctorPaid) {
                        $sharePercent = round($doctorPaid / $totalPaid * 100, 2);
                        $doctorKpi    = (int) round($mainDoctorKpi * $doctorPaid / $totalPaid);
                        if ($doctorKpi <= 0) {
                            continue;
                        }
                        $this->createAllocation($item, $service, null, (int) $doctorId, 'main_doctor',
                            $eligibleRevenue, $directCost, $kpiPool, $sharePercent, $doctorKpi,
                            $collectionFactor, $service->kpi_base_type->value, $period);
                    }
                }
            }
        });
    }

    /**
     * Submit all accrued allocations for an item to pending_approval state.
     */
    public function submitForApproval(TreatmentPlanItem $item): void
    {
        KpiAllocation::where('treatment_plan_item_id', $item->id)
            ->where('status', KpiAllocationStatus::Accrued->value)
            ->update(['status' => KpiAllocationStatus::PendingApproval->value]);
    }

    /**
     * Approve a single allocation.
     */
    public function approve(KpiAllocation $allocation, int $approvedBy): void
    {
        if (!$allocation->status->canApprove()) {
            throw new \RuntimeException('Chỉ có thể duyệt KPI đang ở trạng thái Chờ duyệt.');
        }
        DB::transaction(function () use ($allocation, $approvedBy) {
            $allocation->update([
                'status'      => KpiAllocationStatus::Approved->value,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Hold (suspend) an allocation with reason.
     */
    public function hold(KpiAllocation $allocation, string $reason): void
    {
        if (!$allocation->status->canHold()) {
            throw new \RuntimeException('Không thể treo KPI ở trạng thái này.');
        }
        $allocation->update(['status' => KpiAllocationStatus::Held->value, 'reason' => $reason]);
    }

    /**
     * Reverse (claw back) an allocation.
     */
    public function reverse(KpiAllocation $allocation, string $reason): void
    {
        if (!$allocation->status->canReverse()) {
            throw new \RuntimeException('Không thể đảo KPI ở trạng thái này.');
        }
        $allocation->update(['status' => KpiAllocationStatus::Reversed->value, 'reason' => $reason]);
    }

    /**
     * Mark allocation as paid.
     */
    public function markPaid(KpiAllocation $allocation): void
    {
        if ($allocation->status !== KpiAllocationStatus::Approved) {
            throw new \RuntimeException('Chỉ có thể thanh toán KPI đã được duyệt.');
        }
        $allocation->update(['status' => KpiAllocationStatus::Paid->value, 'paid_at' => now()]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Net amount collected per doctor for this item, from patient_payments explicitly
     * tagged to it. Payments with no doctor_id set fall back to the item's/plan's doctor.
     * Summing (rather than filtering to amount > 0) lets a reversal row net out against
     * the payment it reverses instead of still counting the reversed money. Doctors whose
     * net comes out to zero or negative are dropped — they contribute no share.
     */
    private function resolveDoctorPaymentShares(TreatmentPlanItem $item): \Illuminate\Support\Collection
    {
        $fallbackDoctorId = $item->responsible_doctor_id ?? $item->plan?->doctor_id;

        return PatientPayment::where('treatment_plan_item_id', $item->id)
            ->get(['amount', 'doctor_id'])
            ->groupBy(fn ($p) => $p->doctor_id ?? $fallbackDoctorId)
            ->reject(fn ($payments, $doctorId) => $doctorId === null)
            ->map(fn ($payments) => $payments->sum('amount'))
            ->filter(fn ($net) => $net > 0);
    }

    private function resolveDirectCost(TreatmentPlanItem $item, $service): int
    {
        return (int) $service->costs()
            ->where('is_excluded_from_kpi_base', false)
            ->sum('standard_cost');
    }

    private function resolveKpiPool($service, int $eligibleRevenue, int $directCost): int
    {
        return match ($service->kpi_base_type) {
            KpiBaseType::Revenue     => (int) round($eligibleRevenue * $service->kpi_rate),
            KpiBaseType::GrossMargin => (int) round(max(0, $eligibleRevenue - $directCost) * $service->kpi_rate),
            KpiBaseType::Fixed       => (int) $service->fixed_kpi_amount,
        };
    }

    private function createAllocation(
        TreatmentPlanItem    $item,
        $service,
        ?TreatmentStepExecution $execution,
        int   $employeeId,
        string $role,
        int   $eligibleRevenue,
        int   $directCost,
        int   $kpiPool,
        float $sharePercent,
        int   $kpiAmount,
        float $collectionFactor,
        string $calculationBase,
        string $period
    ): void {
        $qualityFactor = 1.0;  // DentalQualityService may override post-creation
        $finalKpi      = (int) round($kpiAmount * $qualityFactor * $collectionFactor);

        KpiAllocation::create([
            'treatment_plan_item_id' => $item->id,
            'service_id'             => $service->id,
            'step_execution_id'      => $execution?->id,
            'employee_id'            => $employeeId,
            'role'                   => $role,
            'eligible_revenue'       => $eligibleRevenue,
            'direct_cost'            => $directCost,
            'kpi_pool_amount'        => $kpiPool,
            'share_percent'          => $sharePercent,
            'kpi_amount'             => $kpiAmount,
            'quality_factor'         => $qualityFactor,
            'collection_factor'      => $collectionFactor,
            'final_kpi_amount'       => $finalKpi,
            'calculation_base'       => $calculationBase,
            'period'                 => $period,
            'status'                 => KpiAllocationStatus::Accrued->value,
            'calculated_at'          => now(),
        ]);
    }
}
