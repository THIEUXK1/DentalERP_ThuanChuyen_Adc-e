<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'device_id', 'employee_id', 'user_pin',
        'punched_at', 'status', 'punch_type', 'is_processed',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'is_processed' => 'boolean',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Human-readable status labels
    public function statusLabel(): string
    {
        return match ($this->status) {
            0 => 'Vào',
            1 => 'Ra',
            4 => 'Vào (OT)',
            5 => 'Ra (OT)',
            default => "Status {$this->status}",
        };
    }
}
