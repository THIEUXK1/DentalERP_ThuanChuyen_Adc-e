<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdOtherTax extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'period', 'tax_type', 'taxable_amount',
        'tax_rate', 'tax_amount', 'due_date', 'paid_date',
        'paid_amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_date' => 'date',
            'tax_rate' => 'decimal:4',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function remainingAmount(): int
    {
        return max(0, $this->tax_amount - $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->paid_amount >= $this->tax_amount;
    }
}
