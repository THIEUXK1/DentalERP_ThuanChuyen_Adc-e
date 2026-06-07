<?php

namespace App\Enums;

enum RoleType: string
{
    case Doctor = 'doctor';
    case Assistant = 'assistant';
    case Receptionist = 'receptionist';
    case Consultant = 'consultant';
    case Cashier = 'cashier';
    case Accountant = 'accountant';
    case Warehouse = 'warehouse';
    case Marketing = 'marketing';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Doctor => 'Bác sĩ',
            self::Assistant => 'Phụ tá',
            self::Receptionist => 'Lễ tân',
            self::Consultant => 'Tư vấn viên',
            self::Cashier => 'Thu ngân',
            self::Accountant => 'Kế toán',
            self::Warehouse => 'Kho',
            self::Marketing => 'Marketing',
            self::Manager => 'Quản lý',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Doctor => 'blue',
            self::Assistant => 'teal',
            self::Receptionist => 'gray',
            self::Consultant => 'purple',
            self::Cashier => 'green',
            self::Accountant => 'yellow',
            self::Warehouse => 'orange',
            self::Marketing => 'pink',
            self::Manager => 'indigo',
        };
    }
}
