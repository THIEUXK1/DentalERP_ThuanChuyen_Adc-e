<?php

namespace App\Models;

use App\Enums\HkdExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HkdExpenseEntry extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'period', 'entry_date', 'document_no',
        'supplier_name', 'supplier_tax_code', 'category', 'description',
        'amount', 'source_type', 'source_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => HkdExpenseCategory::class,
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
            ->where('source_type', 'expense');
    }
}
