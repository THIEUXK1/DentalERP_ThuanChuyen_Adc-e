<?php

namespace App\Enums;

enum DebtStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa trả',
            self::Partial => 'Trả 1 phần',
            self::Paid => 'Đã trả',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'red',
            self::Partial => 'yellow',
            self::Paid => 'green',
        };
    }
}
