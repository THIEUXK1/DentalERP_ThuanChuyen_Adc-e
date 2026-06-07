<?php

namespace App\Enums;

enum CommissionType: string
{
    case RevenuePercentage = 'revenue_percentage';
    case FixedPerCase = 'fixed_per_case';

    public function label(): string
    {
        return match ($this) {
            self::RevenuePercentage => '% doanh thu',
            self::FixedPerCase => 'Cố định/ca',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RevenuePercentage => 'blue',
            self::FixedPerCase => 'purple',
        };
    }
}
