<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\DentalRole;
use App\Enums\EmploymentStatus;
use App\Enums\RoleType;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use LogsActivity, SoftDeletes, GeneratesUniqueCode;

    protected $fillable = [
        'code', 'user_id', 'branch_id', 'department_id',
        'full_name', 'phone', 'email', 'date_of_birth', 'gender', 'position',
        'role_type', 'specialization', 'license_number', 'is_active',
        // Contract
        'start_date', 'contract_type', 'employment_status',
        // Salary & insurance
        'base_salary', 'social_insurance_enabled', 'dependents_count',
        'personal_tax_code', 'standard_working_days',
        // Allowances
        'responsibility_allowance', 'fixed_allowance',
        'lunch_allowance', 'travel_allowance', 'phone_allowance',
        // Dental professional
        'dental_role', 'dental_specialty', 'practice_certificate',
        'years_of_experience', 'dentist_license_code',
        'xray_scan_skill', 'clinical_permission', 'work_schedule',
        'assigned_chair_room', 'default_kpi_rate', 'support_step_rate',
        'direct_manager_id',
        // Location
        'permanent_address', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'role_type'                 => RoleType::class,
            'contract_type'             => ContractType::class,
            'employment_status'         => EmploymentStatus::class,
            'dental_role'               => DentalRole::class,
            'is_active'                 => 'boolean',
            'social_insurance_enabled'  => 'boolean',
            'date_of_birth'             => 'date',
            'start_date'                => 'date',
            'base_salary'               => 'integer',
            'responsibility_allowance'  => 'integer',
            'fixed_allowance'           => 'integer',
            'lunch_allowance'           => 'integer',
            'travel_allowance'          => 'integer',
            'phone_allowance'           => 'integer',
            'default_kpi_rate'          => 'float',
            'support_step_rate'         => 'float',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'base_salary', 'responsibility_allowance', 'fixed_allowance',
                'lunch_allowance', 'travel_allowance', 'phone_allowance',
                'social_insurance_enabled', 'default_kpi_rate', 'support_step_rate',
                'dental_role', 'dental_specialty', 'clinical_permission',
                'employment_status',
            ])
            ->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'NV-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function grossIncome(): int
    {
        return $this->base_salary
            + $this->responsibility_allowance
            + $this->fixed_allowance
            + $this->lunch_allowance
            + $this->travel_allowance
            + $this->phone_allowance;
    }

    public function totalAllowances(): int
    {
        return $this->responsibility_allowance
            + $this->fixed_allowance
            + $this->lunch_allowance
            + $this->travel_allowance
            + $this->phone_allowance;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'direct_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'direct_manager_id');
    }

    public function scopeDoctors(Builder $q): Builder
    {
        return $q->where('role_type', RoleType::Doctor->value);
    }

    public function scopeByBranch(Builder $q, int $branchId): Builder
    {
        return $q->where('branch_id', $branchId);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('employment_status', EmploymentStatus::Active->value);
    }
}
