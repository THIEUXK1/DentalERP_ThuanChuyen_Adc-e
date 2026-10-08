<?php

namespace App\Models;

use App\Enums\ConsentStatus;
use Illuminate\Database\Eloquent\Model;

class ConsentForm extends Model
{
    protected $fillable = [
        'patient_id', 'treatment_plan_id', 'title', 'content',
        'status', 'signed_at', 'signed_by_name', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConsentStatus::class,
            'signed_at' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatmentPlan()
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
