<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdInventoryTransaction extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'item_id', 'period', 'trans_date',
        'trans_type', 'qty', 'unit_cost', 'amount',
        'document_no', 'counterpart', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'trans_date' => 'date',
            'qty' => 'decimal:3',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HkdInventoryItem::class, 'item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isImport(): bool
    {
        return $this->trans_type === 'import';
    }

    public function isExport(): bool
    {
        return $this->trans_type === 'export';
    }
}
