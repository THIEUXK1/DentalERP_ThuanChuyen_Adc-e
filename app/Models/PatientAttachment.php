<?php

namespace App\Models;

use App\Enums\AttachmentType;
use Illuminate\Database\Eloquent\Model;

class PatientAttachment extends Model
{
    protected $fillable = ['patient_id', 'type', 'title', 'file_path', 'file_size', 'mime_type', 'uploaded_by'];

    protected function casts(): array
    {
        return ['type' => AttachmentType::class];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
