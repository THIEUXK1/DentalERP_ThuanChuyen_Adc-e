<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Model;

class ContactActivity extends Model
{
    protected $fillable = ['lead_id', 'patient_id', 'type', 'content', 'created_by'];

    protected function casts(): array
    {
        return ['type' => ContactType::class];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
