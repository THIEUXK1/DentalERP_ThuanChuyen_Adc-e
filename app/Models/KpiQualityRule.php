<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiQualityRule extends Model
{
    protected $fillable = [
        'rule_code', 'rule_name', 'quality_factor', 'hold_kpi', 'reverse_kpi',
        'trigger_event', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'quality_factor' => 'float',
            'hold_kpi' => 'boolean',
            'reverse_kpi' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Trigger event labels */
    public function triggerLabel(): string
    {
        return match ($this->trigger_event) {
            'refund' => 'Hoàn tiền',
            'redo' => 'Làm lại',
            'complaint' => 'Khiếu nại',
            'missing_attachment' => 'Thiếu hồ sơ/file',
            'protocol_violation' => 'Vi phạm quy trình',
            'incomplete_service' => 'Dịch vụ chưa hoàn thành',
            default => $this->trigger_event ?? '—',
        };
    }
}
