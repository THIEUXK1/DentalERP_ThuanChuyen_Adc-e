<?php

namespace App\Models;

use App\Enums\AttendancePeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePeriod extends Model
{
    protected $fillable = [
        'code', 'month', 'year', 'status', 'note',
        'created_by', 'locked_by', 'locked_at',
        'unlocked_by', 'unlocked_at', 'unlock_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendancePeriodStatus::class,
            'month' => 'integer',
            'year' => 'integer',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
        ];
    }

    public static function generateCode(int $month, int $year): string
    {
        return 'CC-'.$year.str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    public function isLocked(): bool
    {
        return $this->status === AttendancePeriodStatus::Locked;
    }

    public function daysInMonth(): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);
    }

    public function periodLabel(): string
    {
        return "Tháng {$this->month}/{$this->year}";
    }

    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'attendance_period_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class, 'attendance_period_id');
    }
}
