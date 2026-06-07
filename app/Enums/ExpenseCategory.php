<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Rent = 'rent';
    case Utilities = 'utilities';
    case Supplies = 'supplies';
    case Equipment = 'equipment';
    case Salary = 'salary';
    case Marketing = 'marketing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Tiền thuê mặt bằng',
            self::Utilities => 'Điện / nước / internet',
            self::Supplies => 'Vật tư tiêu hao',
            self::Equipment => 'Thiết bị / sửa chữa',
            self::Salary => 'Lương / thưởng',
            self::Marketing => 'Marketing / quảng cáo',
            self::Other => 'Khác',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Rent => 'blue',
            self::Utilities => 'teal',
            self::Supplies => 'green',
            self::Equipment => 'indigo',
            self::Salary => 'purple',
            self::Marketing => 'pink',
            self::Other => 'gray',
        };
    }
}
