<?php

namespace App\Models;

use App\Enums\KpiBaseType;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DentalService extends Model
{
    use LogsActivity, SoftDeletes, GeneratesUniqueCode;

    protected $fillable = [
        'code', 'name', 'category_id', 'service_group', 'cost_price', 'selling_price',
        'duration_minutes', 'estimated_sessions', 'kpi_base_type', 'kpi_rate',
        'fixed_kpi_amount', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'kpi_base_type'      => KpiBaseType::class,
            'kpi_rate'           => 'float',
            'fixed_kpi_amount'   => 'integer',
            'estimated_sessions' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }

    public static function generateCode(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'DV-'.str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function priceListItems()
    {
        return $this->hasMany(PriceListItem::class, 'service_id');
    }

    public function costs()
    {
        return $this->hasMany(DentalServiceCost::class, 'service_id')->where('is_active', true);
    }

    public function steps()
    {
        return $this->hasMany(DentalServiceStep::class, 'service_id')->orderBy('step_order');
    }

    /** Sum of direct costs for KPI gross-margin calculation */
    public function totalDirectCost(): int
    {
        return $this->costs()->sum('standard_cost');
    }
}
