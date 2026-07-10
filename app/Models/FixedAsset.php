<?php

namespace App\Models;

use App\Enums\FixedAssetCategory;
use App\Enums\FixedAssetStatus;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use GeneratesUniqueCode;

    protected $fillable = [
        'code', 'name', 'category', 'branch_id',
        'acquisition_date', 'acquisition_cost', 'useful_life_months',
        'monthly_depreciation', 'accumulated_depreciation', 'net_book_value',
        'last_depreciation_period', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'status'           => FixedAssetStatus::class,
            'category'         => FixedAssetCategory::class,
        ];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'TSC-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class)->orderBy('period');
    }
}
