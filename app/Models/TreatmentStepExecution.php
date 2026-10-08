<?php

namespace App\Models;

use App\Enums\TreatmentStepStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TreatmentStepExecution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'treatment_plan_item_id', 'service_step_id', 'appointment_id',
        'performed_by', 'assisted_by', 'started_at', 'ended_at',
        'status', 'quality_status', 'quality_rule_id', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentStepStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public function planItem()
    {
        return $this->belongsTo(TreatmentPlanItem::class, 'treatment_plan_item_id');
    }

    public function serviceStep()
    {
        return $this->belongsTo(DentalServiceStep::class, 'service_step_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function performer()
    {
        return $this->belongsTo(Employee::class, 'performed_by');
    }

    public function assistant()
    {
        return $this->belongsTo(Employee::class, 'assisted_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(TreatmentStepParticipant::class, 'step_execution_id');
    }

    public function kpiAllocations()
    {
        return $this->hasMany(KpiAllocation::class, 'step_execution_id');
    }

    public function isDone(): bool
    {
        return $this->status === TreatmentStepStatus::Done;
    }

    public function isQualityPassed(): bool
    {
        return $this->quality_status === 'passed' || $this->quality_status === 'pending';
    }
}
