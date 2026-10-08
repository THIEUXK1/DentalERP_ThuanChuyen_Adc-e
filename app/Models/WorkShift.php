<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    protected $fillable = ['branch_id', 'name', 'start_time', 'end_time', 'days_of_week', 'is_active'];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'shift_id');
    }
}
