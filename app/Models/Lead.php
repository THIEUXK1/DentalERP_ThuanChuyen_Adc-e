<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lead extends Model
{
    use LogsActivity, SoftDeletes, GeneratesUniqueCode;

    protected $fillable = [
        'code', 'name', 'phone', 'email', 'source', 'status',
        'assigned_to', 'branch_id', 'interest', 'converted_patient_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'KH-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function convertedPatient()
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
    }

    public function contactActivities()
    {
        return $this->hasMany(ContactActivity::class)->orderByDesc('created_at');
    }

    public function followUpTasks()
    {
        return $this->hasMany(FollowUpTask::class)->orderBy('due_date');
    }

    public function scopeByStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }

    public function scopeByAssignee(Builder $q, int $userId): Builder
    {
        return $q->where('assigned_to', $userId);
    }
}
