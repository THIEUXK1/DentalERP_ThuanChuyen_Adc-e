<?php

namespace App\Services;

use App\Enums\ContactType;
use App\Enums\LeadStatus;
use App\Models\ContactActivity;
use App\Models\Lead;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function transition(Lead $lead, LeadStatus $to): void
    {
        $allowed = $lead->status->allowedTransitions();

        if (! in_array($to, $allowed)) {
            throw new \RuntimeException(
                "Không thể chuyển từ [{$lead->status->label()}] sang [{$to->label()}]."
            );
        }

        DB::transaction(function () use ($lead, $to) {
            $lead->update(['status' => $to]);
            $this->logContact($lead, null, ContactType::Note, "Trạng thái chuyển sang: {$to->label()}");
        });
    }

    public function convertToPatient(Lead $lead, array $extra = []): Patient
    {
        if ($lead->converted_patient_id) {
            throw new \RuntimeException('Lead này đã được chuyển thành bệnh nhân.');
        }

        // Warn if phone already exists as patient
        $existing = Patient::where('phone', $lead->phone)->first();

        return DB::transaction(function () use ($lead, $extra, $existing) {
            if ($existing) {
                $patient = $existing;
            } else {
                $patient = Patient::create([
                    'code' => Patient::generateCode(),
                    'full_name' => $extra['full_name'] ?? $lead->name,
                    'phone' => $lead->phone,
                    'email' => $lead->email,
                    'source' => $lead->source?->value,
                    'branch_id' => $lead->branch_id,
                    'notes' => $extra['notes'] ?? $lead->notes,
                    'is_active' => true,
                ]);
            }

            $lead->update([
                'status' => LeadStatus::ClosedWon,
                'converted_patient_id' => $patient->id,
            ]);

            $this->logContact($lead, null, ContactType::Note, "Đã chuyển thành bệnh nhân: {$patient->code}");

            return $patient;
        });
    }

    public function logContact(
        ?Lead $lead,
        ?Patient $patient,
        ContactType $type,
        string $content
    ): ContactActivity {
        return ContactActivity::create([
            'lead_id' => $lead?->id,
            'patient_id' => $patient?->id,
            'type' => $type->value,
            'content' => $content,
            'created_by' => auth()->id(),
        ]);
    }
}
