<?php

namespace App\Models;

use App\Enums\ExaminationStatus;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DentalExamination extends Model
{
    use LogsActivity, GeneratesUniqueCode;

    protected $fillable = [
        'code', 'patient_id', 'appointment_id', 'branch_id', 'doctor_id', 'consultant_id',
        'chief_complaint', 'diagnosis_note', 'examination_note', 'recommended_plan_note',
        'status', 'examined_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'      => ExaminationStatus::class,
            'examined_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;

        return 'KH-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Employee::class, 'consultant_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function conditions()
    {
        return $this->hasMany(DentalExaminationCondition::class, 'examination_id');
    }

    public function treatmentPlanItems()
    {
        return $this->hasMany(TreatmentPlanItem::class, 'examination_id');
    }
}
