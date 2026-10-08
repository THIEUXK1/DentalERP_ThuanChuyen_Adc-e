<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpi extends Model
{
    protected $fillable = [
        'employee_id', 'period', 'revenue_target', 'case_target',
        'bonus_amount', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'revenue_target' => 'integer',
            'case_target' => 'integer',
            'bonus_amount' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return $this->status === 'approved' ? 'Đã duyệt' : 'Nháp';
    }

    public function statusColor(): string
    {
        return $this->status === 'approved' ? 'green' : 'gray';
    }
}
