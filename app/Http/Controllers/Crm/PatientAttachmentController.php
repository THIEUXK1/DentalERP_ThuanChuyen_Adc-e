<?php

namespace App\Http\Controllers\Crm;

use App\Enums\AttachmentType;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Services\PatientAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientAttachmentController extends Controller
{
    public function __construct(private PatientAttachmentService $service) {}

    // Mỗi request 1 file: client gửi tuần tự từng ảnh để không vượt post_max_size khi chọn nhiều
    public function store(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('patients.edit');

        $data = $request->validate([
            'type' => 'required|in:xray,document,photo,other',
            'title' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:jpeg,jpg,png,webp,pdf,doc,docx|max:10240',
        ]);

        $attachment = $this->service->store(
            $patient,
            $request->file('file'),
            AttachmentType::from($data['type']),
            $data['title'] ?? null,
            auth()->id(),
        );

        return response()->json(['attachment' => $this->service->toArray($attachment)], 201);
    }

    public function destroy(PatientAttachment $attachment): JsonResponse
    {
        $this->authorize('patients.edit');

        $this->service->delete($attachment);

        return response()->json(['message' => 'Đã xóa.']);
    }
}
