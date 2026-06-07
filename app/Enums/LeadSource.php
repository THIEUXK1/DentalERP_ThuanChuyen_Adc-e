<?php

namespace App\Enums;

enum LeadSource: string
{
    case Facebook = 'facebook';
    case Zalo = 'zalo';
    case Google = 'google';
    case Referral = 'referral';
    case WalkIn = 'walk_in';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Zalo => 'Zalo',
            self::Google => 'Google',
            self::Referral => 'Giới thiệu',
            self::WalkIn => 'Khách vãng lai',
            self::Other => 'Khác',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Facebook => 'blue',
            self::Zalo => 'teal',
            self::Google => 'red',
            self::Referral => 'purple',
            self::WalkIn => 'gray',
            self::Other => 'orange',
        };
    }
}
