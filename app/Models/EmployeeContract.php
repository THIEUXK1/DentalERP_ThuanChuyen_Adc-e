<?php

namespace App\Models;

use App\Enums\EmployeeContractType;
use Illuminate\Database\Eloquent\Model;

class EmployeeContract extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'start_date', 'end_date', 'base_salary', 'notes', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmployeeContractType::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return ! $this->end_date || $this->end_date->isFuture();
    }
}
