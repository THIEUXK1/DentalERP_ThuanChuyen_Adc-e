<?php

namespace App\Models;

use App\Enums\RelationshipType;
use Illuminate\Database\Eloquent\Model;

class PatientRelationship extends Model
{
    protected $fillable = ['patient_id', 'related_patient_id', 'relationship_type', 'referral_rate', 'notes'];

    protected function casts(): array
    {
        return ['relationship_type' => RelationshipType::class];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function relatedPatient()
    {
        return $this->belongsTo(Patient::class, 'related_patient_id');
    }
}
