<?php

namespace App\Models;

use App\Enums\HkdTaxStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HkdProfile extends Model
{
    protected $fillable = [
        'branch_id', 'full_name', 'tax_code', 'id_number', 'address',
        'province', 'district', 'representative_name', 'representative_id',
        'tax_authority_name', 'registration_date', 'tax_status', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tax_status' => HkdTaxStatus::class,
            'registration_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(HkdBusinessLocation::class);
    }

    public function businessCategories(): HasMany
    {
        return $this->hasMany(HkdBusinessCategory::class);
    }

    public function revenueEntries(): HasMany
    {
        return $this->hasMany(HkdRevenueEntry::class);
    }

    public function expenseEntries(): HasMany
    {
        return $this->hasMany(HkdExpenseEntry::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(HkdInventoryItem::class);
    }

    public function cashAccounts(): HasMany
    {
        return $this->hasMany(HkdCashAccount::class);
    }

    public function otherTaxes(): HasMany
    {
        return $this->hasMany(HkdOtherTax::class);
    }

    public function periodCloses(): HasMany
    {
        return $this->hasMany(HkdPeriodClose::class);
    }

    public function isPeriodClosed(string $period): bool
    {
        return $this->periodCloses()
            ->where('period', $period)
            ->where('status', 'closed')
            ->exists();
    }
}
