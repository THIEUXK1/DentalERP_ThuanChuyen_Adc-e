<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Patient;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactActivityController extends Controller
{
    public function __construct(private LeadService $leadService) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|in:'.implode(',', array_column(ContactType::cases(), 'value')),
            'content' => 'required|string|max:2000',
            'lead_id' => 'nullable|exists:leads,id',
            'patient_id' => 'nullable|exists:patients,id',
        ]);

        if (! $data['lead_id'] && ! $data['patient_id']) {
            return back()->with('error', 'Cần chỉ định lead hoặc bệnh nhân.');
        }

        $lead = $data['lead_id'] ? Lead::find($data['lead_id']) : null;
        $patient = $data['patient_id'] ? Patient::find($data['patient_id']) : null;

        $this->leadService->logContact($lead, $patient, ContactType::from($data['type']), $data['content']);

        return back()->with('success', 'Đã ghi hoạt động.');
    }
}
