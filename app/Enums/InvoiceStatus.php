<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartialPaid = 'partial_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Sent => 'Đã gửi',
            self::PartialPaid => 'Thanh toán 1 phần',
            self::Paid => 'Đã thanh toán',
            self::Overdue => 'Quá hạn',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'blue',
            self::PartialPaid => 'yellow',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Cancelled => 'gray',
        };
    }
}
