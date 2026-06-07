<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Appointment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'patient_id', 'branch_id', 'doctor_id', 'dental_chair_id',
        'service_id', 'lead_id', 'scheduled_at', 'duration_minutes',
        'status', 'cancel_reason', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'duration_minutes' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'LH-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getEndsAtAttribute(): Carbon
    {
        return $this->scheduled_at->addMinutes($this->duration_minutes);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    public function chair()
    {
        return $this->belongsTo(DentalChair::class, 'dental_chair_id');
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('scheduled_at', $date);
    }

    public function scopeForBranch(Builder $q, int $branchId): Builder
    {
        return $q->where('branch_id', $branchId);
    }

    public function scopeForDoctor(Builder $q, int $doctorId): Builder
    {
        return $q->where('doctor_id', $doctorId);
    }

    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('scheduled_at', [$from.' 00:00:00', $to.' 23:59:59']);
    }
}
