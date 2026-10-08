<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundAccount extends Model
{
    protected $fillable = ['name', 'type', 'initial_balance', 'bank_name', 'account_number', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'initial_balance' => 'integer'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PatientPayment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'from_account_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(FundTransfer::class, 'to_account_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function currentBalance(): int
    {
        $income = $this->payments()->where('amount', '>', 0)->sum('amount');
        $expense = $this->expenses()->sum('amount');
        $purchasePaid = $this->purchaseInvoices()->sum('paid_amount');
        $transfersOut = $this->transfersOut()->sum('amount');
        $transfersIn = $this->transfersIn()->sum('amount');

        return $this->initial_balance + $income - $expense - $purchasePaid - $transfersOut + $transfersIn;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'bank' => 'Ngân hàng',
            'ewallet' => 'Ví điện tử',
            default => 'Tiền mặt',
        };
    }
}
