<?php

namespace App\Models;

use App\Enums\HkdRevenueCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HkdRevenueEntry extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'period', 'entry_date', 'document_no',
        'buyer_name', 'buyer_tax_code', 'description', 'revenue_category',
        'amount', 'vat_amount', 'pit_amount',
        'source_type', 'source_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'revenue_category' => HkdRevenueCategory::class,
            'entry_date' => 'date',
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

    public function documents(): HasMany
    {
        return $this->hasMany(HkdDocument::class, 'source_id')
            ->where('source_type', 'revenue');
    }

    public function totalWithTax(): int
    {
        return $this->amount + $this->vat_amount;
    }
}
