<?php

namespace App\Enums;

enum TreatmentItemStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ',
            self::InProgress => 'Đang làm',
            self::Completed => 'Xong',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InProgress => 'yellow',
            self::Completed => 'green',
        };
    }
}
