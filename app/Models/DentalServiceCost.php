<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalServiceCost extends Model
{
    protected $fillable = [
        'service_id', 'cost_type', 'cost_name', 'standard_cost',
        'is_excluded_from_kpi_base', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'standard_cost' => 'integer',
            'is_excluded_from_kpi_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    /** Cost types with Vietnamese labels */
    public static function costTypeLabel(string $type): string
    {
        return match ($type) {
            'material' => 'Vật tư',
            'lab' => 'Labo',
            'implant_fixture' => 'Trụ Implant',
            'medicine' => 'Thuốc',
            'imaging' => 'Chụp chiếu',
            'chair_overhead' => 'Chi phí ghế',
            default => 'Khác',
        };
    }
}
