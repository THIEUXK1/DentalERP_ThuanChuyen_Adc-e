<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Model;

class PatientDebt extends Model
{
    protected $fillable = [
        'patient_id', 'treatment_plan_id', 'invoice_id',
        'amount', 'paid_amount', 'remaining', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => DebtStatus::class,
            'due_date' => 'date',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice()
    {
        return $this->belongsTo(PatientInvoice::class, 'invoice_id');
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }
}
