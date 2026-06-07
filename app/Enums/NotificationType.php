<?php

namespace App\Enums;

enum NotificationType: string
{
    case DebtOverdue = 'debt_overdue';
    case AppointmentReminder = 'appointment_reminder';
    case LeadAssigned = 'lead_assigned';
    case PlanApproved = 'plan_approved';

    public function label(): string
    {
        return match ($this) {
            self::DebtOverdue => 'Công nợ quá hạn',
            self::AppointmentReminder => 'Nhắc lịch hẹn',
            self::LeadAssigned => 'Lead được giao',
            self::PlanApproved => 'Kế hoạch đã duyệt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DebtOverdue => 'red',
            self::AppointmentReminder => 'blue',
            self::LeadAssigned => 'purple',
            self::PlanApproved => 'green',
        };
    }
}
