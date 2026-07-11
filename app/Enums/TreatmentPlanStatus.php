<?php

namespace App\Enums;

enum TreatmentPlanStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Approved => 'Chưa điều trị',
            self::InProgress => 'Đang điều trị',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Approved => 'amber',
            self::InProgress => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft      => [self::Approved, self::InProgress, self::Completed, self::Cancelled],
            self::Approved   => [self::InProgress, self::Completed, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed  => [self::Approved, self::InProgress],
            self::Cancelled  => [],
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Approved]);
    }

    public function isItemsEditable(): bool
    {
        return in_array($this, [self::Draft, self::Approved, self::InProgress, self::Completed]);
    }
}
