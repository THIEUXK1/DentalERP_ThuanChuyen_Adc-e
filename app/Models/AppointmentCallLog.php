<?php

namespace App\Models;

use App\Enums\CallOutcome;
use Illuminate\Database\Eloquent\Model;

/**
 * Nhật ký gọi điện nhắc lịch — append-only: không sửa, không xoá.
 * Đây là bằng chứng đối chất khi bệnh nhân nói phòng khám không gọi cho họ.
 */
class AppointmentCallLog extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_id', 'outcome', 'phone', 'note', 'called_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => CallOutcome::class,
            'called_at' => 'datetime',
        ];
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
