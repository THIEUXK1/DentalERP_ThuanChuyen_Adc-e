<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientAttachmentController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $data = $request->validate([
            'type' => 'required|in:xray,document,photo,other',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,pdf,doc,docx|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store("patients/{$patient->id}/attachments", 'public');
        $patient->attachments()->create([
            'type' => $data['type'],
            'title' => $data['title'],
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Đã tải lên tài liệu.');
    }

    public function destroy(PatientAttachment $attachment): RedirectResponse
    {
        $this->authorize('patients.edit');

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Đã xóa tài liệu.');
    }
}
