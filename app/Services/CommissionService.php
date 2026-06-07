<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Models\CommissionRule;
use App\Models\CommissionTransaction;
use App\Models\PatientInvoice;

class CommissionService
{
    /**
     * Calculate and record commissions when an invoice is fully paid.
     * Called from InvoiceService::addPayment after status becomes Paid.
     */
    public function calculateForInvoice(PatientInvoice $invoice): void
    {
        // Skip if already has commission records for this invoice
        if (CommissionTransaction::where('invoice_id', $invoice->id)->exists()) {
            return;
        }

        $plan = $invoice->treatmentPlan;
        $period = now()->format('Y-m');

        // Collect doctor_id + consultant_id from treatment plan
        $employeeIds = array_filter(
            [$plan?->doctor_id, $plan?->consultant_id],
            fn ($id) => $id !== null
        );

        foreach (array_unique($employeeIds) as $employeeId) {
            $rules = CommissionRule::where('employee_id', $employeeId)
                ->where('is_active', true)
                ->get();

            foreach ($rules as $rule) {
                $amount = $rule->type === CommissionType::RevenuePercentage
                    ? (int) round($invoice->total * $rule->value / 100)
                    : (int) $rule->value;

                if ($amount <= 0) {
                    continue;
                }

                CommissionTransaction::create([
                    'employee_id' => $employeeId,
                    'invoice_id' => $invoice->id,
                    'treatment_plan_id' => $plan?->id,
                    'amount' => $amount,
                    'period' => $period,
                    'status' => CommissionStatus::Pending,
                ]);
            }
        }
    }
}
