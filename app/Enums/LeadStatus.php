<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case NoAnswer = 'no_answer';
    case Consulting = 'consulting';
    case AppointmentBooked = 'appointment_booked';
    case Visited = 'visited';
    case Quoted = 'quoted';
    case Considering = 'considering';
    case ClosedWon = 'closed_won';
    case ClosedLost = 'closed_lost';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Contacted => 'Đã liên hệ',
            self::NoAnswer => 'Không nghe máy',
            self::Consulting => 'Đang tư vấn',
            self::AppointmentBooked => 'Đã đặt lịch',
            self::Visited => 'Đã đến khám',
            self::Quoted => 'Đã báo giá',
            self::Considering => 'Đang cân nhắc',
            self::ClosedWon => 'Chốt thành công',
            self::ClosedLost => 'Thất bại',
            self::FollowUp => 'Cần follow-up',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Contacted => 'blue',
            self::NoAnswer => 'orange',
            self::Consulting => 'teal',
            self::AppointmentBooked => 'indigo',
            self::Visited => 'purple',
            self::Quoted => 'cyan',
            self::Considering => 'yellow',
            self::ClosedWon => 'green',
            self::ClosedLost => 'red',
            self::FollowUp => 'amber',
        };
    }

    /** Returns allowed next statuses from this status */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Contacted, self::NoAnswer, self::ClosedLost],
            self::Contacted => [self::Consulting, self::AppointmentBooked, self::NoAnswer, self::ClosedLost, self::FollowUp],
            self::NoAnswer => [self::Contacted, self::FollowUp, self::ClosedLost],
            self::Consulting => [self::AppointmentBooked, self::Quoted, self::ClosedLost, self::FollowUp],
            self::AppointmentBooked => [self::Visited, self::NoAnswer, self::ClosedLost],
            self::Visited => [self::Quoted, self::Consulting, self::ClosedLost],
            self::Quoted => [self::Considering, self::ClosedWon, self::ClosedLost, self::FollowUp],
            self::Considering => [self::ClosedWon, self::ClosedLost, self::FollowUp],
            self::ClosedWon => [], // terminal (only via convert)
            self::ClosedLost => [self::FollowUp],
            self::FollowUp => [self::Contacted, self::Consulting, self::ClosedLost],
        };
    }
}
