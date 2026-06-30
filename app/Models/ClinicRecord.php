<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'record_date', 'record_time', 'patient_name', 'patient_code',
        'record_type', 'service_name', 'unit_price', 'quantity',
        'discount', 'amount', 'total_collected', 'remaining_debt',
        'collected_this_period', 'fund_name', 'treatment_step',
        'treatment_step_notes', 'consultant_name', 'doctor_name',
        'assistant_name', 'birth_year', 'gender', 'phone',
        'customer_source', 'symptoms', 'diagnosis', 'service_group', 'status',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];
}