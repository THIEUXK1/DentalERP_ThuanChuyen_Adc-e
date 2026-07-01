<?php

namespace App\Services;

use App\Enums\DebtStatus;
use App\Enums\InvoiceStatus;
use App\Models\PatientDebt;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use App\Services\Hkd\HkdJournalService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create/re-sync one invoice per payment schedule entry.
     * Replaces fromTreatmentPlan() when the plan has a payment schedule.
     * Only deletes invoices that have not been paid yet.
     */
    public function syncInstallments(TreatmentPlan $plan): void
    {
        if (empty($plan->payment_schedule)) {
            return;
        }

        DB::transaction(function () use ($plan) {
            // Remove unpaid installment invoices so we can re-create from updated schedule
            $plan->invoices()
                ->whereNotNull('installment_index')
                ->where('amount_paid', 0)
                ->each(function ($inv) {
                    $inv->debt?->delete();
                    $inv->delete();
                });

            foreach ($plan->payment_schedule as $i => $entry) {
                $amount  = (int) ($entry['amount'] ?? 0);
                $dueDate = $entry['due_date'] ?? null;
                $note    = $entry['note'] ?? null;

                if ($amount <= 0) continue;

                // Skip installments that already have a paid invoice
                $exists = $plan->invoices()
                    ->where('installment_index', $i)
                    ->where('amount_paid', '>', 0)
                    ->exists();
                if ($exists) continue;

                $invoice = PatientInvoice::create([
                    'code'              => PatientInvoice::generateCode(),
                    'patient_id'        => $plan->patient_id,
                    'treatment_plan_id' => $plan->id,
                    'installment_index' => $i,
                    'branch_id'         => $plan->branch_id,
                    'status'            => \App\Enums\InvoiceStatus::Sent->value,
                    'subtotal'          => $amount,
                    'discount'          => 0,
                    'total'             => $amount,
                    'amount_paid'       => 0,
                    'due_date'          => $dueDate ?: null,
                    'notes'             => $note ?: null,
                    'created_by'        => auth()->id() ?? $plan->created_by,
                ]);

                \App\Models\PatientDebt::create([
                    'patient_id'        => $plan->patient_id,
                    'treatment_plan_id' => $plan->id,
                    'invoice_id'        => $invoice->id,
                    'amount'            => $amount,
                    'paid_amount'       => 0,
                    'remaining'         => $amount,
                    'status'            => \App\Enums\DebtStatus::Pending->value,
                ]);
            }
        });
    }

    public function fromTreatmentPlan(TreatmentPlan $plan): PatientInvoice
    {
        if ($plan->invoices()->exists()) {
            throw new \RuntimeException('Kế hoạch này đã có hóa đơn.');
        }

        return DB::transaction(function () use ($plan) {
            $total = $plan->net_total;

            $invoice = PatientInvoice::create([
                'code' => PatientInvoice::generateCode(),
                'patient_id' => $plan->patient_id,
                'treatment_plan_id' => $plan->id,
                'branch_id' => $plan->branch_id,
                'status' => InvoiceStatus::Sent->value,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'amount_paid' => 0,
                'created_by' => auth()->id(),
            ]);

            PatientDebt::create([
                'patient_id' => $plan->patient_id,
                'treatment_plan_id' => $plan->id,
                'invoice_id' => $invoice->id,
                'amount' => $total,
                'paid_amount' => 0,
                'remaining' => $total,
                'status' => DebtStatus::Pending->value,
            ]);

            return $invoice;
        });
    }

    public function addPayment(PatientInvoice $invoice, array $data): PatientPayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = PatientPayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'payment_date' => $data['payment_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Recompute from sum (idempotent)
            $amountPaid = $invoice->payments()->sum('amount');
            $remaining = $invoice->total - $amountPaid;

            $invoiceStatus = $remaining <= 0
                ? InvoiceStatus::Paid
                : ($amountPaid > 0 ? InvoiceStatus::PartialPaid : InvoiceStatus::Sent);

            $invoice->update([
                'amount_paid' => $amountPaid,
                'status' => $invoiceStatus,
            ]);

            // Update debt
            $debtStatus = $remaining <= 0
                ? DebtStatus::Paid
                : ($amountPaid > 0 ? DebtStatus::Partial : DebtStatus::Pending);

            $invoice->debt?->update([
                'paid_amount' => $amountPaid,
                'remaining' => max(0, $remaining),
                'status' => $debtStatus,
            ]);

            // Trigger commission calculation when invoice becomes fully paid
            if ($invoiceStatus === InvoiceStatus::Paid) {
                (new CommissionService)->calculateForInvoice($invoice->fresh());
            }

            // Ghi sổ doanh thu + sổ tiền TT152 (bỏ qua nếu chi nhánh không có HKD profile)
            app(HkdJournalService::class)->postRevenue($payment);

            return $payment;
        });
    }

    public function applyDiscount(PatientInvoice $invoice, int $discountAmount): void
    {
        DB::transaction(function () use ($invoice, $discountAmount) {
            $total = $invoice->subtotal - $discountAmount;
            $remaining = $total - $invoice->amount_paid;

            $invoice->update([
                'discount' => $discountAmount,
                'total' => $total,
                'status' => $remaining <= 0 ? InvoiceStatus::Paid : ($invoice->amount_paid > 0 ? InvoiceStatus::PartialPaid : InvoiceStatus::Sent),
            ]);

            $invoice->debt?->update([
                'amount' => $total,
                'remaining' => max(0, $remaining),
                'status' => $remaining <= 0 ? DebtStatus::Paid : ($invoice->amount_paid > 0 ? DebtStatus::Partial : DebtStatus::Pending),
            ]);
        });
    }

    public function cancel(PatientInvoice $invoice): void
    {
        if ($invoice->amount_paid > 0) {
            throw new \RuntimeException('Không thể hủy hóa đơn đã có thanh toán.');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::Cancelled]);
            $invoice->debt?->delete();
        });
    }
}
