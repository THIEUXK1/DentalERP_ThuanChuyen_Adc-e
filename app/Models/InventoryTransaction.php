<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id', 'branch_id', 'transaction_type',
        'qty', 'unit_cost', 'amount', 'transaction_date',
        'source_type', 'source_id', 'document_no', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => InventoryTransactionType::class,
            'qty' => 'float',
            'unit_cost' => 'integer',
            'amount' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
