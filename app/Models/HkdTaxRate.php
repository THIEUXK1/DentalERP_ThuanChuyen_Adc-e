<?php

namespace App\Models;

use App\Enums\HkdRevenueCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class HkdTaxRate extends Model
{
    protected $fillable = [
        'revenue_category', 'vat_rate', 'pit_rate',
        'effective_from', 'effective_to', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'revenue_category' => HkdRevenueCategory::class,
            'vat_rate' => 'decimal:4',
            'pit_rate' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public static function rateFor(string $category, Carbon $date): ?self
    {
        return static::where('revenue_category', $category)
            ->where('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();
    }
}
