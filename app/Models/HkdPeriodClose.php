<?php

namespace App\Models;

use App\Enums\HkdPeriodCloseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdPeriodClose extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'period', 'status',
        'closed_at', 'closed_by', 'snapshot_data', 'snapshot_pdf_path',
        'unlock_reason', 'unlocked_at', 'unlocked_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => HkdPeriodCloseStatus::class,
            'closed_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'snapshot_data' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function unlockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function isClosed(): bool
    {
        return $this->status === HkdPeriodCloseStatus::Closed;
    }
}
