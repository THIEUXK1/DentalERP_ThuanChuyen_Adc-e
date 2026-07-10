<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use App\Models\Concerns\GeneratesUniqueCode;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use GeneratesUniqueCode;

    protected $fillable = [
        'code', 'name', 'category', 'unit', 'branch_id',
        'min_stock_qty', 'current_stock_qty', 'unit_cost',
        'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_qty'     => 'float',
            'current_stock_qty' => 'float',
            'unit_cost'         => 'integer',
            'is_active'         => 'boolean',
        ];
    }

    public static function generateCode(): string
    {
        $last = static::max('id') ?? 0;
        return 'VT-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function serviceTemplates()
    {
        return $this->hasMany(InventoryServiceTemplate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock_qty <= $this->min_stock_qty;
    }

    public static function categoryLabel(string $cat): string
    {
        return match($cat) {
            'material'    => 'Vật tư',
            'medicine'    => 'Thuốc',
            'equipment'   => 'Thiết bị nhỏ',
            'consumable'  => 'Vật tư tiêu hao',
            default       => 'Khác',
        };
    }
}
