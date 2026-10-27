<?php

namespace App\Services;

use App\Enums\AttachmentType;
use App\Models\Patient;
use App\Models\PatientAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PatientAttachmentService
{
    public function store(Patient $patient, UploadedFile $file, AttachmentType $type, ?string $title, ?int $userId): PatientAttachment
    {
        $path = $file->store("patients/{$patient->id}/attachments", 'public');

        return $patient->attachments()->create([
            'type' => $type,
            // Album tải nhiều ảnh một lúc nên tiêu đề tuỳ chọn — mặc định lấy tên file gốc
            'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $userId,
        ]);
    }

    public function delete(PatientAttachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }

    public function toArray(PatientAttachment $a): array
    {
        return [
            'id' => $a->id,
            'type' => $a->type->value,
            'type_label' => $a->type->label(),
            'type_color' => $a->type->color(),
            'title' => $a->title,
            'file_path' => $a->file_path,
            'file_url' => asset('storage/'.$a->file_path),
            'file_size' => $a->file_size,
            'mime_type' => $a->mime_type,
            'created_at' => $a->created_at->format('d/m/Y H:i'),
        ];
    }
}
