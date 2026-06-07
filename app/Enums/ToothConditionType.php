<?php

namespace App\Enums;

enum ToothConditionType: string
{
    case Caries = 'caries';
    case Filling = 'filling';
    case Crown = 'crown';
    case Missing = 'missing';
    case Implant = 'implant';
    case RootCanal = 'root_canal';
    case ExtractionPlanned = 'extraction_planned';
    case Fractured = 'fractured';

    public function label(): string
    {
        return match ($this) {
            self::Caries => 'Sâu răng',
            self::Filling => 'Đã trám',
            self::Crown => 'Mão sứ',
            self::Missing => 'Mất răng',
            self::Implant => 'Implant',
            self::RootCanal => 'Chữa tủy',
            self::ExtractionPlanned => 'Dự kiến nhổ',
            self::Fractured => 'Răng gãy/nứt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Caries => 'rose',
            self::Filling => 'blue',
            self::Crown => 'purple',
            self::Missing => 'gray',
            self::Implant => 'emerald',
            self::RootCanal => 'amber',
            self::ExtractionPlanned => 'red',
            self::Fractured => 'orange',
        };
    }

    /** Hex fill color for SVG tooth */
    public function svgFill(): string
    {
        return match ($this) {
            self::Caries => '#fecaca',
            self::Filling => '#dbeafe',
            self::Crown => '#ede9fe',
            self::Missing => '#e5e7eb',
            self::Implant => '#d1fae5',
            self::RootCanal => '#fef3c7',
            self::ExtractionPlanned => '#fee2e2',
            self::Fractured => '#ffedd5',
        };
    }

    /** Hex stroke color for SVG tooth */
    public function svgStroke(): string
    {
        return match ($this) {
            self::Caries => '#fb7185',
            self::Filling => '#60a5fa',
            self::Crown => '#a78bfa',
            self::Missing => '#9ca3af',
            self::Implant => '#34d399',
            self::RootCanal => '#fbbf24',
            self::ExtractionPlanned => '#ef4444',
            self::Fractured => '#f97316',
        };
    }
}
