<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'code', 'month', 'year', 'branch_id', 'attendance_period_id', 'status',
        'total_base_salary', 'total_allowances', 'total_company_insurance',
        'total_employee_insurance', 'total_personal_income_tax', 'total_union_fee',
        'total_gross_income', 'total_deductions', 'total_net_salary',
        'union_fee_confirmed',
        'confirmed_by', 'confirmed_at', 'locked_by', 'locked_at',
        'posted_by', 'posted_at', 'paid_by', 'paid_at',
        'created_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollStatus::class,
            'union_fee_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
            'locked_at' => 'datetime',
            'posted_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public static function generateCode(int $month, int $year): string
    {
        return 'BL-'.$year.str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    public function periodLabel(): string
    {
        return "Tháng {$this->month}/{$this->year}";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendancePeriod(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PayrollAuditLog::class);
    }
}
