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
        // 'date:Y-m-d' — không dùng 'date' vì khi serialize sang JSON, Carbon bị đổi
        // sang UTC ("2025-04-09" → "2025-04-08T17:00:00Z"), làm bảng hiển thị lùi 1 ngày
        // và sai thứ tự ngày/tháng so với các trang khác (d/m/Y).
        'record_date' => 'date:Y-m-d',
    ];
}