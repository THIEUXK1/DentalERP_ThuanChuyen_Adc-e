<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientRelationship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientRelationshipController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $data = $request->validate([
            'related_patient_id' => 'required|exists:patients,id|different:'.$patient->id,
            'relationship_type' => 'required|in:parent,child,spouse,sibling,referrer',
            'referral_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $exists = PatientRelationship::where('patient_id', $patient->id)
            ->where('related_patient_id', $data['related_patient_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Mối quan hệ này đã tồn tại.');
        }

        $patient->relationships()->create($data);

        return back()->with('success', 'Đã thêm mối quan hệ.');
    }

    public function destroy(PatientRelationship $relationship): RedirectResponse
    {
        $this->authorize('patients.edit');
        $relationship->delete();

        return back()->with('success', 'Đã xóa mối quan hệ.');
    }
}
