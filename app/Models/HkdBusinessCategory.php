<?php

namespace App\Models;

use App\Enums\HkdRevenueCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdBusinessCategory extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'industry_code', 'industry_name',
        'revenue_category', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'revenue_category' => HkdRevenueCategory::class,
            'is_primary' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }
}
