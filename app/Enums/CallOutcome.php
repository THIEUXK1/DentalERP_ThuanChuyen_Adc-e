<?php

namespace App\Enums;

enum CallOutcome: string
{
    case Answered = 'answered';
    case NoAnswer = 'no_answer';
    case Busy = 'busy';
    case Unreachable = 'unreachable';

    public function label(): string
    {
        return match ($this) {
            self::Answered => 'Đã nghe máy',
            self::NoAnswer => 'Không nghe máy',
            self::Busy => 'Máy bận',
            self::Unreachable => 'Không liên lạc được',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Answered => 'Nghe máy',
            self::NoAnswer => 'Không nghe',
            self::Busy => 'Máy bận',
            self::Unreachable => 'Không LL được',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Answered => 'green',
            self::NoAnswer => 'orange',
            self::Busy => 'yellow',
            self::Unreachable => 'red',
        };
    }

    /** Có nói chuyện được với bệnh nhân hay không — dùng khi đối chất "phòng khám không gọi cho tôi". */
    public function isContacted(): bool
    {
        return $this === self::Answered;
    }
}
