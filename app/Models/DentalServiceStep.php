<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DentalServiceStep extends Model
{
    protected $fillable = [
        'service_id', 'step_name', 'step_order', 'default_role',
        'estimated_minutes', 'kpi_share_percent', 'deduct_from_main_doctor',
        'require_quality_check', 'require_attachment', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kpi_share_percent' => 'float',
            'deduct_from_main_doctor' => 'boolean',
            'require_quality_check' => 'boolean',
            'require_attachment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function service()
    {
        return $this->belongsTo(DentalService::class);
    }

    public function executions()
    {
        return $this->hasMany(TreatmentStepExecution::class, 'service_step_id');
    }

    /** Default role labels */
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'counseling' => 'Tư vấn',
            'examination' => 'Khám',
            'imaging' => 'Chụp phim/CT/scan',
            'treatment_planning' => 'Lập kế hoạch',
            'main_treatment' => 'Điều trị chính',
            'chairside_assist' => 'Phụ tá ghế',
            'impression' => 'Lấy dấu/scan',
            'prosthetics' => 'Gắn phục hình',
            'follow_up' => 'Tái khám',
            'aftercare' => 'CSKH sau điều trị',
            default => $role,
        };
    }
}
