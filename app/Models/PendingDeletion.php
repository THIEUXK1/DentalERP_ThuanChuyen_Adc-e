<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PendingDeletion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'deletable_type', 'deletable_id',
        'reason', 'user_id', 'label',
        'execute_at', 'cancelled_at', 'executed_at',
    ];

    protected $casts = [
        'execute_at'   => 'datetime',
        'cancelled_at' => 'datetime',
        'executed_at'  => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function deletable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return is_null($this->cancelled_at) && is_null($this->executed_at);
    }
}
