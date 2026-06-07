<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code', 'user_id', 'branch_id', 'full_name', 'phone',
        'role_type', 'specialization', 'license_number', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
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

        return 'NV-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeDoctors(Builder $q): Builder
    {
        return $q->where('role_type', RoleType::Doctor->value);
    }

    public function scopeByBranch(Builder $q, int $branchId): Builder
    {
        return $q->where('branch_id', $branchId);
    }
}
