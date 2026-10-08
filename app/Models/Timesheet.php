<?php

namespace App\Models;

use App\Enums\TimesheetStatus;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'shift_id', 'work_date',
        'check_in', 'check_out', 'ot_hours', 'notes', 'status', 'approved_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TimesheetStatus::class,
            'work_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift()
    {
        return $this->belongsTo(WorkShift::class, 'shift_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hoursWorked(): float
    {
        if (! $this->check_in || ! $this->check_out) {
            return 0;
        }

        return round($this->check_out->diffInMinutes($this->check_in) / 60, 2);
    }
}
