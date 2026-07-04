<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PatientInvoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'patient_id', 'treatment_plan_id', 'installment_index', 'branch_id',
        'status', 'subtotal', 'discount', 'total', 'amount_paid',
        'due_date', 'notes', 'cancel_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'due_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'HĐ-BN-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function amountDue(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function overpaidAmount(): int
    {
        return max(0, $this->amount_paid - $this->total);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(PatientPayment::class, 'invoice_id')->orderByDesc('payment_date');
    }

    public function debt()
    {
        return $this->hasOne(PatientDebt::class, 'invoice_id');
    }
}
