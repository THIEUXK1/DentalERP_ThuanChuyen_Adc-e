<?php

namespace App\Models;

use App\Models\Appointment;
use App\Models\Concerns\GeneratesUniqueCode;
use App\Models\ScheduleRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use LogsActivity, SoftDeletes, GeneratesUniqueCode;

    protected $fillable = [
        'code', 'legacy_code', 'full_name', 'phone', 'email', 'dob', 'gender', 'address',
        'source', 'allergies', 'medical_history', 'medical_flags', 'photo_path',
        'emergency_contact', 'branch_id', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dob'           => 'date',
            'is_active'     => 'boolean',
            'medical_flags' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'BN-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function contactActivities()
    {
        return $this->hasMany(ContactActivity::class)->orderByDesc('created_at');
    }

    public function followUpTasks()
    {
        return $this->hasMany(FollowUpTask::class)->orderBy('due_date');
    }

    public function clinicalNotes()
    {
        return $this->hasMany(ClinicalNote::class)->orderByDesc('created_at');
    }

    public function toothConditions()
    {
        return $this->hasMany(ToothCondition::class);
    }

    public function invoices()
    {
        return $this->hasMany(PatientInvoice::class)->orderByDesc('created_at');
    }

    public function treatmentPlans()
    {
        return $this->hasMany(TreatmentPlan::class)->orderByDesc('created_at');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class)->orderByDesc('scheduled_at');
    }

    public function scheduleRegistrations()
    {
        return $this->hasMany(ScheduleRegistration::class);
    }

    public function attachments()
    {
        return $this->hasMany(PatientAttachment::class)->orderByDesc('created_at');
    }

    public function consentForms()
    {
        return $this->hasMany(ConsentForm::class)->orderByDesc('created_at');
    }

    public function relationships()
    {
        return $this->hasMany(PatientRelationship::class)->with('relatedPatient');
    }

    public function phones()
    {
        return $this->hasMany(PatientPhone::class)->orderByDesc('created_at');
    }

    public function scopeByBranch(Builder $q, int $branchId): Builder
    {
        return $q->where('branch_id', $branchId);
    }
}
