<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientPhone extends Model
{
    protected $fillable = ['patient_id', 'phone'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
