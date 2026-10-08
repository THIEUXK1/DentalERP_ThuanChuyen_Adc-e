<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HkdInventoryItem extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'code', 'name', 'unit',
        'opening_qty', 'opening_unit_cost', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_qty' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(HkdInventoryTransaction::class, 'item_id');
    }

    public function transactionsForPeriod(string $period): HasMany
    {
        return $this->transactions()->where('period', $period)->orderBy('trans_date');
    }
}
