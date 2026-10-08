<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'attendance_period_id', 'employee_id', 'work_date', 'weekday',
        'symbol', 'status_type', 'check_in_time', 'check_out_time',
        'working_hours', 'overtime_hours', 'paid_workday', 'unpaid_workday',
        'note', 'source_type', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'working_hours' => 'float',
            'overtime_hours' => 'float',
            'paid_workday' => 'float',
            'unpaid_workday' => 'float',
            'weekday' => 'integer',
        ];
    }

    public function isSunday(): bool
    {
        return $this->weekday === 7;
    }

    public function displaySymbol(): string
    {
        return $this->symbol === 'O' ? 'Ô' : ($this->symbol ?? '');
    }

    // Relations
    public function period(): BelongsTo
    {
        return $this->belongsTo(AttendancePeriod::class, 'attendance_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
