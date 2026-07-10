<?php

namespace App\Models;

use App\Enums\LabOrderStatus;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    use GeneratesUniqueCode;

    protected $fillable = [
        'code', 'lab_id', 'treatment_plan_id', 'patient_id', 'branch_id',
        'status', 'items', 'total_amount', 'notes',
        'expected_date', 'sent_date', 'received_date', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'        => LabOrderStatus::class,
            'items'         => 'array',
            'expected_date' => 'date',
            'sent_date'     => 'date',
            'received_date' => 'date',
        ];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'DXL-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(LabWarranty::class);
    }
}
