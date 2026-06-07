<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use Illuminate\Database\Eloquent\Model;

class CommissionTransaction extends Model
{
    protected $fillable = [
        'employee_id', 'invoice_id', 'treatment_plan_id',
        'amount', 'period', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommissionStatus::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function invoice()
    {
        return $this->belongsTo(PatientInvoice::class);
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }
}
