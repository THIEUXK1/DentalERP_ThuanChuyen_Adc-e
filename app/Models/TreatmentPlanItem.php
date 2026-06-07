<?php

namespace App\Models;

use App\Enums\TreatmentItemStatus;
use Illuminate\Database\Eloquent\Model;

class TreatmentPlanItem extends Model
{
    protected $fillable = [
        'treatment_plan_id', 'service_id', 'name', 'tooth_number',
        'quantity', 'unit_price', 'subtotal', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TreatmentItemStatus::class,
            'quantity' => 'integer',
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
}
