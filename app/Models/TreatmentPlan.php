<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TreatmentPlan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'patient_id', 'doctor_id', 'consultant_id', 'branch_id',
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

    public function recalcTotals(): void
    {
        $total = $this->items()->sum('subtotal');
        $this->update(['total_amount' => $total]);
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
