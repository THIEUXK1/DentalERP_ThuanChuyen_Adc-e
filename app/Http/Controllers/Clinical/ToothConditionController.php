<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\ToothConditionType;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\ToothCondition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToothConditionController extends Controller
{
    /** Upsert a single tooth condition for a patient */
    public function upsert(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('clinical_notes.create');

        $data = $request->validate([
            'tooth_number' => 'required|string|max:10',
            'condition' => ['required', 'string', Rule::enum(ToothConditionType::class)],
            'note' => 'nullable|string|max:500',
        ]);

        ToothCondition::updateOrCreate(
            ['patient_id' => $patient->id, 'tooth_number' => $data['tooth_number']],
            ['condition' => $data['condition'], 'note' => $data['note'] ?? null, 'recorded_by' => auth()->id()]
        );

        return back()->with('success', 'Đã cập nhật tình trạng răng.');
    }

    /** Remove a tooth condition record */
    public function destroy(Patient $patient, ToothCondition $condition): RedirectResponse
    {
        $this->authorize('clinical_notes.create');
        abort_if($condition->patient_id !== $patient->id, 403);
        $condition->delete();

        return back()->with('success', 'Đã xóa tình trạng răng.');
    }
}
