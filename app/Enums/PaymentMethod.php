<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Card = 'card';
    case EWallet = 'ewallet';
    case Installment = 'installment';
    case Voucher = 'voucher';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tiền mặt',
            self::Transfer => 'Chuyển khoản',
            self::Card => 'Thẻ',
            self::EWallet => 'Ví điện tử',
            self::Installment => 'Trả góp',
            self::Voucher => 'Voucher',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'green',
            self::Transfer => 'blue',
            self::Card => 'purple',
            self::EWallet => 'teal',
            self::Installment => 'orange',
            self::Voucher => 'pink',
        };
    }
}
