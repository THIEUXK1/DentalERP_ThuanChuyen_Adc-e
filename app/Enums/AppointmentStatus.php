<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending      = 'pending';
    case Booked       = 'booked';
    case Confirmed    = 'confirmed';
    case Rescheduled  = 'rescheduled';
    case ArrivedEarly = 'arrived_early';
    case CheckedIn    = 'checked_in';
    case ArrivedLate  = 'arrived_late';
    case NoShow       = 'no_show';
    case Cancelled    = 'cancelled';

    // Giữ lại để không lỗi với dữ liệu cũ
    case InTreatment  = 'in_treatment';
    case Completed    = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending      => 'Tạm hẹn',
            self::Booked       => 'Đang hẹn',
            self::Confirmed    => 'Đã xác nhận',
            self::Rescheduled  => 'Đã chuyển',
            self::ArrivedEarly => 'Đến trước hẹn',
            self::CheckedIn    => 'Đã đến',
            self::ArrivedLate  => 'Đến sau hẹn',
            self::NoShow       => 'Không đến',
            self::Cancelled    => 'Hủy',
            self::InTreatment  => 'Đang điều trị',
            self::Completed    => 'Hoàn thành',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending      => 'gray',
            self::Booked       => 'blue',
            self::Confirmed    => 'indigo',
            self::Rescheduled  => 'yellow',
            self::ArrivedEarly => 'teal',
            self::CheckedIn    => 'teal',
            self::ArrivedLate  => 'orange',
            self::NoShow       => 'red',
            self::Cancelled    => 'red',
            self::InTreatment  => 'purple',
            self::Completed    => 'green',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending      => [self::Booked, self::Confirmed, self::Cancelled],
            self::Booked       => [self::Confirmed, self::Rescheduled, self::ArrivedEarly, self::CheckedIn, self::ArrivedLate, self::NoShow, self::Cancelled],
            self::Confirmed    => [self::ArrivedEarly, self::CheckedIn, self::ArrivedLate, self::Rescheduled, self::NoShow, self::Cancelled],
            self::Rescheduled  => [self::Pending, self::Booked, self::Cancelled],
            self::ArrivedEarly => [self::Cancelled],
            self::CheckedIn    => [self::Cancelled],
            self::ArrivedLate  => [self::Cancelled],
            self::NoShow       => [],
            self::Cancelled    => [],
            self::InTreatment  => [self::Completed],
            self::Completed    => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::ArrivedEarly, self::CheckedIn, self::ArrivedLate,
            self::NoShow, self::Cancelled, self::Completed,
        ]);
    }
}
