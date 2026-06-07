<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case InTreatment = 'in_treatment';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Đã đặt',
            self::Confirmed => 'Đã xác nhận',
            self::CheckedIn => 'Đã check-in',
            self::InTreatment => 'Đang điều trị',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::NoShow => 'Không đến',
            self::Rescheduled => 'Đã dời lịch',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Booked => 'gray',
            self::Confirmed => 'blue',
            self::CheckedIn => 'teal',
            self::InTreatment => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'red',
            self::NoShow => 'orange',
            self::Rescheduled => 'yellow',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Booked => [self::Confirmed, self::Cancelled, self::Rescheduled, self::NoShow],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow, self::Rescheduled],
            self::CheckedIn => [self::InTreatment, self::Cancelled],
            self::InTreatment => [self::Completed],
            self::Completed => [],
            self::Cancelled => [],
            self::NoShow => [],
            self::Rescheduled => [self::Booked],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::NoShow]);
    }
}
