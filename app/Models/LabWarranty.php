<?php

namespace App\Models;

use App\Enums\LabWarrantyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabWarranty extends Model
{
    protected $fillable = [
        'lab_order_id', 'patient_id', 'service_name',
        'tooth_number', 'start_date', 'end_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => LabWarrantyStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
