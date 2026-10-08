<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryServiceTemplate extends Model
{
    protected $fillable = [
        'service_id', 'service_step_id', 'inventory_item_id',
        'qty_per_execution', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'qty_per_execution' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    public function serviceStep()
    {
        return $this->belongsTo(DentalServiceStep::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
