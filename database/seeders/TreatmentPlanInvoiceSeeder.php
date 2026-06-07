<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\TreatmentItemStatus;
use App\Enums\TreatmentPlanStatus;
use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TreatmentPlanInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $branch = Branch::first();
        $doctors = Employee::doctors()->where('is_active', true)->get();
        $consultants = Employee::where('role_type', 'consultant')->where('is_active', true)->get();
        $patients = Patient::take(5)->get();
        $services = DentalService::where('is_active', true)->take(6)->get();

        if ($patients->isEmpty() || $services->isEmpty() || $doctors->isEmpty()) {
            $this->command->warn('Skipping: missing patients, services, or doctors.');

            return;
        }

        // ── Commission rules ─────────────────────────────────────────────
        foreach ($doctors as $doctor) {
            CommissionRule::firstOrCreate(
                ['employee_id' => $doctor->id, 'type' => 'revenue_percentage'],
                ['value' => 5.00, 'is_active' => true, 'notes' => 'Hoa hồng BS 5% doanh thu']
            );
        }
        foreach ($consultants as $consultant) {
            CommissionRule::firstOrCreate(
                ['employee_id' => $consultant->id, 'type' => 'revenue_percentage'],
                ['value' => 2.00, 'is_active' => true, 'notes' => 'Hoa hồng tư vấn 2% doanh thu']
            );
        }

        // ── Treatment plan definitions ────────────────────────────────────
        $doctor = $doctors->first();
        $consultant = $consultants->first();

        $plans = [
            // 2 fully paid plans (for dashboard revenue data)
            [
                'patient' => $patients[0],
                'status' => TreatmentPlanStatus::Completed,
                'invoice_status' => InvoiceStatus::Paid,
                'items' => [
                    ['service' => $services[0], 'qty' => 1, 'tooth' => '16'],
                    ['service' => $services[1], 'qty' => 1, 'tooth' => '26'],
                ],
                'days_ago' => 14,
            ],
            [
                'patient' => $patients[1],
                'status' => TreatmentPlanStatus::Completed,
                'invoice_status' => InvoiceStatus::Paid,
                'items' => [
                    ['service' => $services[2], 'qty' => 2, 'tooth' => '11,21'],
                    ['service' => $services[0], 'qty' => 1, 'tooth' => '36'],
                ],
                'days_ago' => 7,
            ],
            // 1 partial paid
            [
                'patient' => $patients[2],
                'status' => TreatmentPlanStatus::InProgress,
                'invoice_status' => InvoiceStatus::PartialPaid,
                'items' => [
                    ['service' => $services[3] ?? $services[0], 'qty' => 1, 'tooth' => '37'],
                    ['service' => $services[1], 'qty' => 1, 'tooth' => '47'],
                ],
                'days_ago' => 3,
            ],
            // 1 approved, invoice sent
            [
                'patient' => $patients[3],
                'status' => TreatmentPlanStatus::Approved,
                'invoice_status' => InvoiceStatus::Sent,
                'items' => [
                    ['service' => $services[4] ?? $services[0], 'qty' => 1, 'tooth' => '18'],
                ],
                'days_ago' => 1,
            ],
            // 1 draft (no invoice)
            [
                'patient' => $patients[4],
                'status' => TreatmentPlanStatus::Draft,
                'invoice_status' => null,
                'items' => [
                    ['service' => $services[5] ?? $services[1], 'qty' => 1, 'tooth' => '14'],
                ],
                'days_ago' => 0,
            ],
        ];

        foreach ($plans as $def) {
            if (TreatmentPlan::where('patient_id', $def['patient']->id)->exists()) {
                continue;
            }

            $createdAt = Carbon::now()->subDays($def['days_ago']);

            // Build items and total
            $itemRows = [];
            $total = 0;
            foreach ($def['items'] as $itemDef) {
                $price = $itemDef['service']->selling_price ?? 500000;
                $subtotal = $price * $itemDef['qty'];
                $total += $subtotal;
                $itemRows[] = [
                    'service_id' => $itemDef['service']->id,
                    'name' => $itemDef['service']->name,
                    'tooth_number' => $itemDef['tooth'],
                    'quantity' => $itemDef['qty'],
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'status' => in_array($def['status'], [TreatmentPlanStatus::Completed, TreatmentPlanStatus::InProgress])
                                        ? TreatmentItemStatus::Completed->value
                                        : TreatmentItemStatus::Pending->value,
                ];
            }

            $isApproved = ! in_array($def['status'], [TreatmentPlanStatus::Draft, TreatmentPlanStatus::Quoted]);

            $plan = TreatmentPlan::create([
                'code' => TreatmentPlan::generateCode(),
                'patient_id' => $def['patient']->id,
                'branch_id' => $branch->id,
                'doctor_id' => $doctor->id,
                'consultant_id' => $consultant?->id,
                'status' => $def['status']->value,
                'total_amount' => $total,
                'discount_amount' => 0,
                'deposit_amount' => 0,
                'created_by' => $admin->id,
                'approved_at' => $isApproved ? $createdAt->addHour() : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($itemRows as $row) {
                TreatmentPlanItem::create(['treatment_plan_id' => $plan->id, ...$row]);
            }

            // Create invoice if needed
            if ($def['invoice_status'] === null) {
                continue;
            }

            $invoice = PatientInvoice::create([
                'code' => PatientInvoice::generateCode(),
                'patient_id' => $def['patient']->id,
                'treatment_plan_id' => $plan->id,
                'branch_id' => $branch->id,
                'status' => $def['invoice_status']->value,
                'subtotal' => $total,
                'discount' => 0,
                'total' => $total,
                'amount_paid' => match ($def['invoice_status']) {
                    InvoiceStatus::Paid => $total,
                    InvoiceStatus::PartialPaid => (int) ($total * 0.5),
                    default => 0,
                },
                'due_date' => $createdAt->copy()->addDays(30),
                'created_by' => $admin->id,
                'created_at' => $createdAt->copy()->addHours(2),
                'updated_at' => $createdAt->copy()->addHours(2),
            ]);

            // Add payment records
            if (in_array($def['invoice_status'], [InvoiceStatus::Paid, InvoiceStatus::PartialPaid])) {
                $paidAmount = $def['invoice_status'] === InvoiceStatus::Paid ? $total : (int) ($total * 0.5);
                PatientPayment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'method' => 'cash',
                    'payment_date' => $createdAt->copy()->addHours(3)->toDateString(),
                    'created_by' => $admin->id,
                    'created_at' => $createdAt->copy()->addHours(3),
                    'updated_at' => $createdAt->copy()->addHours(3),
                ]);
            }
        }

        $this->command->info(sprintf(
            'Treatment plans: %d | Invoices: %d | Payments: %d | Commission rules: %d',
            TreatmentPlan::count(),
            PatientInvoice::count(),
            PatientPayment::count(),
            CommissionRule::count()
        ));
    }
}
