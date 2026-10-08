<?php

namespace App\Models;

use App\Enums\HkdCashAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HkdCashAccount extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'type', 'name', 'bank_name',
        'account_number', 'opening_balance', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => HkdCashAccountType::class,
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(HkdCashTransaction::class, 'account_id');
    }

    public function balanceForPeriod(string $period): int
    {
        $receipts = $this->transactions()->where('period', $period)->where('trans_type', 'receipt')->sum('amount');
        $payments = $this->transactions()->where('period', $period)->where('trans_type', 'payment')->sum('amount');

        return $this->opening_balance + $receipts - $payments;
    }
}
