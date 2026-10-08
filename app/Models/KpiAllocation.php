<?php

namespace App\Models;

use App\Enums\KpiAllocationStatus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KpiAllocation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'treatment_plan_item_id', 'service_id', 'step_execution_id', 'employee_id', 'role',
        'eligible_revenue', 'direct_cost', 'kpi_pool_amount', 'share_percent', 'kpi_amount',
        'quality_factor', 'collection_factor', 'final_kpi_amount',
        'calculation_base', 'period', 'status', 'reason', 'notes',
        'calculated_at', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => KpiAllocationStatus::class,
            'share_percent' => 'float',
            'quality_factor' => 'float',
            'collection_factor' => 'float',
            'eligible_revenue' => 'integer',
            'direct_cost' => 'integer',
            'kpi_pool_amount' => 'integer',
            'kpi_amount' => 'integer',
            'final_kpi_amount' => 'integer',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
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

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    public function stepExecution()
    {
        return $this->belongsTo(TreatmentStepExecution::class, 'step_execution_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
