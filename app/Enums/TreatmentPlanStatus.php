<?php

namespace App\Enums;

enum TreatmentPlanStatus: string
{
    case Draft = 'draft';
    case Quoted = 'quoted';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Quoted => 'Đã báo giá',
            self::Approved => 'Đã duyệt',
            self::InProgress => 'Đang điều trị',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Quoted => 'blue',
            self::Approved => 'teal',
            self::InProgress => 'indigo',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Quoted, self::Cancelled],
            self::Quoted => [self::Approved, self::Draft, self::Cancelled],
            self::Approved => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [],
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Quoted]);
    }
}
