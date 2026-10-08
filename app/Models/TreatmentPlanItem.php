<?php

namespace App\Models;

use App\Enums\TreatmentItemStatus;
use Illuminate\Database\Eloquent\Model;

class TreatmentPlanItem extends Model
{
    protected $fillable = [
        'treatment_plan_id', 'service_id', 'name', 'tooth_number',
        'quantity', 'unit_price', 'subtotal', 'status', 'notes',
        'responsible_doctor_id', 'assistant_doctor_id', 'examination_id', 'started_at', 'completed_at',
        'diagnosis', 'discount', 'amount', 'estimated_sessions', 'stage_name',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentItemStatus::class,
            'quantity' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    public function responsibleDoctor()
    {
        return $this->belongsTo(Employee::class, 'responsible_doctor_id');
    }

    public function assistantDoctor()
    {
        return $this->belongsTo(Employee::class, 'assistant_doctor_id');
    }

    public function examination()
    {
        return $this->belongsTo(DentalExamination::class, 'examination_id');
    }

    public function stepExecutions()
    {
        return $this->hasMany(TreatmentStepExecution::class, 'treatment_plan_item_id');
    }

    public function kpiAllocations()
    {
        return $this->hasMany(KpiAllocation::class, 'treatment_plan_item_id');
    }

    /** Amount already paid allocated to this item (proportional by subtotal) */
    public function paidAmount(): int
    {
        $plan = $this->plan;
        if (! $plan || $plan->total_amount <= 0) {
            return 0;
        }
        $totalPaid = $plan->invoices()->sum('amount_paid');

        return (int) round($totalPaid * ($this->subtotal / $plan->total_amount));
    }

    public function collectionFactor(): float
    {
        if ($this->subtotal <= 0) {
            return 0.0;
        }

        return min(1.0, $this->paidAmount() / $this->subtotal);
    }
}
