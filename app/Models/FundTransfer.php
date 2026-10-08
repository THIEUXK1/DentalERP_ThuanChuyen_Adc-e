<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundTransfer extends Model
{
    protected $fillable = [
        'from_account_id', 'to_account_id', 'amount', 'transfer_date', 'description', 'reference', 'created_by',
    ];

    protected function casts(): array
    {
        return ['transfer_date' => 'date'];
    }

    public function fromAccount()
    {
        return $this->belongsTo(FundAccount::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(FundAccount::class, 'to_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
