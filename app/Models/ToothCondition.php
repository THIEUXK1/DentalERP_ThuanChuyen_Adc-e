<?php

namespace App\Models;

use App\Enums\ToothConditionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToothCondition extends Model
{
    protected $fillable = ['patient_id', 'tooth_number', 'condition', 'note', 'recorded_by'];

    protected function casts(): array
    {
        return ['condition' => ToothConditionType::class];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
