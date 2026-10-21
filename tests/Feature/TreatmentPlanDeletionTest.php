<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\TreatmentPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentPlanDeletionTest extends TestCase
{
    use RefreshDatabase;

    private TreatmentPlanService $svc;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(TreatmentPlanService::class);
    }

    /** KHDT 2.000.000 đã thu đủ bằng một khoản chuyển khoản. */
    private function makePaidPlan(): array
    {
        $branch = Branch::create(['code' => Branch::generateCode(), 'name' => 'CN Test', 'is_active' => true]);
        $this->user = User::factory()->create(['branch_id' => $branch->id]);
        $this->actingAs($this->user);

        $patient = Patient::create([
            'code' => Patient::generateCode(), 'full_name' => 'Nguyễn Văn A', 'phone' => '0901234567',
            'dob' => '1990-01-01', 'gender' => 'male', 'branch_id' => $branch->id, 'is_active' => true,
        ]);

        $plan = TreatmentPlan::create([
            'code' => 'KHDT-TEST', 'patient_id' => $patient->id, 'branch_id' => $branch->id,
            'status' => 'completed', 'total_amount' => 2_000_000, 'discount_amount' => 0,
            'created_by' => $this->user->id,
        ]);

        $invoice = PatientInvoice::create([
            'code' => PatientInvoice::generateCode(), 'patient_id' => $patient->id, 'branch_id' => $branch->id,
            'treatment_plan_id' => $plan->id, 'status' => 'sent', 'subtotal' => 2_000_000, 'discount' => 0,
            'total' => 2_000_000, 'amount_paid' => 0, 'created_by' => $this->user->id,
        ]);

        app(InvoiceService::class)->addPayment($invoice, [
            'amount' => 2_000_000, 'method' => PaymentMethod::Transfer->value, 'payment_date' => now()->toDateString(),
        ]);

        return [$plan, $invoice->fresh()];
    }

    public function test_impact_lists_payments_and_blocks_without_refund_permission(): void
    {
        [$plan] = $this->makePaidPlan();

        $impact = $this->svc->deletionImpact($plan, canReverse: false);

        $this->assertSame(2_000_000, $impact['net_paid']);
        $this->assertCount(1, $impact['payments_to_reverse']);
        $this->assertNotEmpty($impact['blockers']);
        $this->assertEmpty($this->svc->deletionImpact($plan, canReverse: true)['blockers']);
    }

    public function test_request_deletion_reverses_payments(): void
    {
        [$plan, $invoice] = $this->makePaidPlan();

        $this->svc->requestDeletion($plan, 'Nhập nhầm', $this->user->id, canReverse: true);

        $this->assertSame(0, $plan->netPaidAmount());
        $this->assertSame(0, (int) $invoice->fresh()->amount_paid);
        $this->assertSame(2, PatientPayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_cancel_deletion_restores_payments_exactly(): void
    {
        [$plan, $invoice] = $this->makePaidPlan();

        $pending = $this->svc->requestDeletion($plan, 'Nhập nhầm', $this->user->id, canReverse: true);
        $this->svc->cancelDeletion($pending);

        $invoice->refresh();
        $this->assertNotNull($pending->fresh()->cancelled_at);
        $this->assertSame(2_000_000, (int) $invoice->amount_paid);
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        // Chỉ còn dòng thu gốc — bút toán hoàn tác đã được gỡ hẳn.
        $this->assertSame(1, PatientPayment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(2_000_000, $plan->netPaidAmount());
    }
}
