<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\ConsentForm;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsentFormController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $patient->consentForms()->create([...$data, 'status' => 'pending', 'created_by' => auth()->id()]);

        return back()->with('success', 'Đã tạo phiếu đồng ý.');
    }

    public function sign(Request $request, ConsentForm $consentForm): RedirectResponse
    {
        $this->authorize('patients.edit');

        if ($consentForm->status->value !== 'pending') {
            return back()->with('error', 'Phiếu đã được ký hoặc không hợp lệ.');
        }

        $data = $request->validate([
            'signed_by_name' => 'required|string|max:255',
        ]);

        $consentForm->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by_name' => $data['signed_by_name'],
        ]);

        return back()->with('success', 'Đã ký phiếu đồng ý.');
    }

    public function destroy(ConsentForm $consentForm): RedirectResponse
    {
        $this->authorize('patients.edit');
        $consentForm->delete();

        return back()->with('success', 'Đã xóa phiếu đồng ý.');
    }
}
