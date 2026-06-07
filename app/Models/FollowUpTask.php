<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;

class FollowUpTask extends Model
{
    protected $fillable = ['lead_id', 'patient_id', 'assigned_to', 'due_date', 'status', 'note', 'created_by'];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
