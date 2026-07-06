<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TreatmentPlan extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::deleting(function (TreatmentPlan $plan) {
            $plan->invoices()->each(function ($invoice) {
                $invoice->debt()?->delete();
                $invoice->payments()->delete();
                $invoice->delete();
            });
        });
    }

    protected $fillable = [
        'code', 'legacy_group_key', 'patient_id', 'doctor_id', 'consultant_id', 'branch_id',
        'appointment_id', 'status', 'total_amount', 'discount_amount',
        'deposit_amount', 'notes', 'payment_schedule', 'payment_notes', 'created_by', 'approved_at',
        'diagnosis', 'chief_complaint', 'treatment_goal', 'start_date', 'expected_end_date',
        'estimated_sessions', 'frequency', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentPlanStatus::class,
            'approved_at' => 'datetime',
            'payment_schedule' => 'array',
            'start_date' => 'date',
            'expected_end_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'KHDT-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getNetTotalAttribute(): int
    {
        return $this->total_amount - $this->discount_amount;
    }

    public function hasPayments(): bool
    {
        return PatientPayment::whereHas('invoice', fn ($q) => $q->where('treatment_plan_id', $this->id))
            ->where('amount', '>', 0)
            ->exists();
    }

    public function recalcTotals(): void
    {
        $this->update([
            'total_amount'    => $this->items()->sum('subtotal'),
            'discount_amount' => $this->items()->sum('discount'),
        ]);

        // Keep the primary invoice (not yet split into installments) in sync with the
        // plan's totals; amount_due/overpaid will reflect any gap caused by editing paid items.
        $invoice = $this->invoices()->whereNull('installment_index')->first();
        if (! $invoice) {
            return;
        }

        $total = $this->net_total;
        $remaining = $total - $invoice->amount_paid;

        $invoice->update([
            'subtotal' => $this->total_amount,
            'discount' => $this->discount_amount,
            'total'    => $total,
            'status'   => $remaining <= 0
                ? \App\Enums\InvoiceStatus::Paid
                : ($invoice->amount_paid > 0 ? \App\Enums\InvoiceStatus::PartialPaid : \App\Enums\InvoiceStatus::Sent),
        ]);

        $invoice->debt?->update([
            'amount'    => $total,
            'remaining' => max(0, $remaining),
            'status'    => $remaining <= 0
                ? \App\Enums\DebtStatus::Paid
                : ($invoice->amount_paid > 0 ? \App\Enums\DebtStatus::Partial : \App\Enums\DebtStatus::Pending),
        ]);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Employee::class, 'consultant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(TreatmentPlanItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(PatientInvoice::class, 'treatment_plan_id');
    }
}
