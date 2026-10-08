<?php

namespace App\Models;

use App\Enums\PerformanceReviewStatus;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = [
        'employee_id', 'period', 'reviewer_id', 'overall_score',
        'punctuality_score', 'quality_score', 'teamwork_score',
        'strengths', 'improvements', 'goals', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['status' => PerformanceReviewStatus::class];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function averageScore(): float
    {
        return round(($this->overall_score + $this->punctuality_score + $this->quality_score + $this->teamwork_score) / 4, 1);
    }
}
