<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdCashTransaction extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'account_id', 'period', 'trans_date',
        'trans_type', 'amount', 'description', 'reference',
        'source_type', 'source_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['trans_date' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(HkdCashAccount::class, 'account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReceipt(): bool
    {
        return $this->trans_type === 'receipt';
    }

    public function isPayment(): bool
    {
        return $this->trans_type === 'payment';
    }
}
