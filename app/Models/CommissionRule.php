<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'value', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => CommissionType::class,
            'value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
