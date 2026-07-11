<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientDebt;
use App\Models\PatientInvoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(InvoiceService::class);
    }

    private function makeInvoice(int $total, int $amountPaid): PatientInvoice
    {
        $branch = Branch::create([
            'code' => Branch::generateCode(),
            'name' => 'Chi nhánh Test',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);

        $patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $invoice = PatientInvoice::create([
            'code' => PatientInvoice::generateCode(),
            'patient_id' => $patient->id,
            'branch_id' => $branch->id,
            'status' => 'partial_paid',
            'subtotal' => $total,
            'discount' => 0,
            'total' => $total,
            'amount_paid' => $amountPaid,
            'created_by' => $user->id,
        ]);

        PatientDebt::create([
            'patient_id' => $patient->id,
            'invoice_id' => $invoice->id,
            'amount' => $total,
            'paid_amount' => $amountPaid,
            'remaining' => $total - $amountPaid,
            'status' => 'partial',
        ]);

        return $invoice;
    }

    /**
     * Regression test for the bug where a legacy-synced invoice's amount_paid (correct,
     * but not fully backed by patient_payments rows) got silently overwritten down to
     * just the sum of its patient_payments rows the moment a new payment was recorded.
     */
    public function test_add_payment_keeps_pre_existing_amount_paid_not_backed_by_payment_rows(): void
    {
        // amount_paid=25,000,000 with zero patient_payments rows behind it, simulating a
        // legacy-synced invoice where the underlying payment rows were never created.
        $invoice = $this->makeInvoice(total: 31_500_000, amountPaid: 25_000_000);

        $this->svc->addPayment($invoice, [
            'amount' => 6_500_000,
            'method' => PaymentMethod::Cash->value,
            'payment_date' => now()->toDateString(),
        ]);

        $invoice->refresh();

        $this->assertSame(31_500_000, $invoice->amount_paid);
        $this->assertSame(0, $invoice->amountDue());
        $this->assertSame('paid', $invoice->status->value);
        $this->assertSame(0, $invoice->debt->remaining);
    }

    public function test_add_payment_accumulates_normally_across_multiple_payments(): void
    {
        $invoice = $this->makeInvoice(total: 10_000_000, amountPaid: 0);

        $this->svc->addPayment($invoice, [
            'amount' => 4_000_000,
            'method' => PaymentMethod::Cash->value,
            'payment_date' => now()->toDateString(),
        ]);
        $invoice->refresh();
        $this->assertSame(4_000_000, $invoice->amount_paid);

        $this->svc->addPayment($invoice, [
            'amount' => 6_000_000,
            'method' => PaymentMethod::Transfer->value,
            'payment_date' => now()->toDateString(),
        ]);
        $invoice->refresh();
        $this->assertSame(10_000_000, $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status->value);
    }
}
