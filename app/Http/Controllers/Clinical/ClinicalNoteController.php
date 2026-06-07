<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicalNoteController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('clinical_notes.create');

        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_done' => 'nullable|string',
            'prescription' => 'nullable|string',
            'next_visit_notes' => 'nullable|string',
        ]);

        $patient->clinicalNotes()->create([
            ...$data,
            'branch_id' => $patient->branch_id ?? auth()->user()->branch_id,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Đã lưu hồ sơ lâm sàng.');
    }

    public function update(Request $request, ClinicalNote $note): RedirectResponse
    {
        $this->authorize('clinical_notes.create');

        $data = $request->validate([
            'doctor_id' => 'nullable|exists:employees,id',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_done' => 'nullable|string',
            'prescription' => 'nullable|string',
            'next_visit_notes' => 'nullable|string',
        ]);

        $note->update($data);

        return back()->with('success', 'Đã cập nhật hồ sơ lâm sàng.');
    }

    public function destroy(ClinicalNote $note): RedirectResponse
    {
        $this->authorize('clinical_notes.create');
        $note->delete();

        return back()->with('success', 'Đã xóa.');
    }

    public static function mapDto(ClinicalNote $n): array
    {
        return [
            'id' => $n->id,
            'doctor_id' => $n->doctor_id,
            'doctor_name' => $n->doctor?->full_name,
            'appointment_id' => $n->appointment_id,
            'chief_complaint' => $n->chief_complaint,
            'diagnosis' => $n->diagnosis,
            'treatment_done' => $n->treatment_done,
            'prescription' => $n->prescription,
            'next_visit_notes' => $n->next_visit_notes,
            'created_at' => $n->created_at->format('d/m/Y H:i'),
            'creator' => $n->creator?->name,
        ];
    }
}
