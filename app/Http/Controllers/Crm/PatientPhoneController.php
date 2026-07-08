<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientPhoneController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^0\d{8,10}$/'],
        ]);

        if ($data['phone'] === $patient->phone) {
            return back()->with('error', 'Số điện thoại này đã là số chính của khách hàng.');
        }

        if ($patient->phones()->where('phone', $data['phone'])->exists()) {
            return back()->with('error', 'Số điện thoại này đã được thêm trước đó.');
        }

        $patient->phones()->create($data);

        return back()->with('success', 'Đã thêm số điện thoại.');
    }

    public function destroy(PatientPhone $phone): RedirectResponse
    {
        $this->authorize('patients.edit');
        $phone->delete();

        return back()->with('success', 'Đã xóa số điện thoại.');
    }
}
