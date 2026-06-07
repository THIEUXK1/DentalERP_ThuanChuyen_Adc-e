<?php

namespace App\Enums;

enum ContactType: string
{
    case Call = 'call';
    case Sms = 'sms';
    case Zalo = 'zalo';
    case Note = 'note';
    case Visit = 'visit';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Gọi điện',
            self::Sms => 'SMS',
            self::Zalo => 'Zalo',
            self::Note => 'Ghi chú',
            self::Visit => 'Đến trực tiếp',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Call => 'blue',
            self::Sms => 'green',
            self::Zalo => 'teal',
            self::Note => 'gray',
            self::Visit => 'purple',
        };
    }
}
