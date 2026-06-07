<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Patient extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code', 'full_name', 'phone', 'email', 'dob', 'gender', 'address',
        'source', 'allergies', 'medical_history', 'emergency_contact',
        'branch_id', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'is_active' => 'boolean',
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

    public function scopeByBranch(Builder $q, int $branchId): Builder
    {
        return $q->where('branch_id', $branchId);
    }
}
