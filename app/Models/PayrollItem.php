<?php

namespace App\Models;

use App\Enums\KpiSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'department_id',
        'employee_code', 'employee_name', 'position_name', 'department_name',
        'standard_working_days', 'actual_working_days', 'workday_ratio',
        'base_salary', 'insurance_salary', 'salary_by_workday',
        'fixed_allowance', 'responsibility_allowance',
        'lunch_allowance', 'phone_allowance', 'travel_allowance',
        'performance_kpi_amount', 'other_allowance',
        'company_social_insurance', 'company_health_insurance', 'company_unemployment_insurance', 'total_company_insurance',
        'employee_social_insurance', 'employee_health_insurance', 'employee_unemployment_insurance', 'total_employee_insurance',
        'taxable_income', 'dependents_count', 'family_deduction', 'dependent_deduction',
        'personal_income_tax', 'pit_manual_override', 'pit_manual_amount',
        'union_fee_amount', 'union_fee_confirmed',
        'gross_income', 'total_deductions', 'net_salary',
        'social_insurance_enabled', 'insurance_manual_override', 'salary_manual_override',
        'kpi_source_type', 'status', 'note',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'kpi_source_type' => KpiSourceType::class,
            'social_insurance_enabled' => 'boolean',
            'insurance_manual_override' => 'boolean',
            'salary_manual_override' => 'boolean',
            'pit_manual_override' => 'boolean',
            'union_fee_confirmed' => 'boolean',
            'actual_working_days' => 'float',
            'workday_ratio' => 'float',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
