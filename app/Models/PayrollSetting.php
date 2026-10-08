<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = [
        'effective_from', 'effective_to', 'active',
        'employee_social_insurance_rate', 'employee_health_insurance_rate', 'employee_unemployment_insurance_rate',
        'company_social_insurance_rate', 'company_health_insurance_rate', 'company_unemployment_insurance_rate',
        'union_fee_rate',
        'family_deduction_amount', 'dependent_deduction_amount',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /** Returns the currently active settings row, or defaults if none seeded. */
    public static function current(): self
    {
        return static::where('active', true)->orderByDesc('effective_from')->first()
            ?? new self([
                'employee_social_insurance_rate' => 8.00,
                'employee_health_insurance_rate' => 1.50,
                'employee_unemployment_insurance_rate' => 1.00,
                'company_social_insurance_rate' => 17.50,
                'company_health_insurance_rate' => 3.00,
                'company_unemployment_insurance_rate' => 1.00,
                'union_fee_rate' => 2.00,
                'family_deduction_amount' => 11000000,
                'dependent_deduction_amount' => 4400000,
            ]);
    }
}
