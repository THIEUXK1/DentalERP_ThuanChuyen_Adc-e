<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HkdDocument extends Model
{
    protected $fillable = [
        'hkd_profile_id', 'source_type', 'source_id',
        'file_name', 'file_path', 'file_size', 'mime_type',
        'document_date', 'retention_until', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'retention_until' => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(HkdProfile::class, 'hkd_profile_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function isExpired(): bool
    {
        return $this->retention_until->isPast();
    }
}
