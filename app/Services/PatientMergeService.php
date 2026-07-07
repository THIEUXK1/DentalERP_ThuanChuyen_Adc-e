<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PatientMergeService
{
    /** Tables with a plain `patient_id` FK and no unique-constraint conflict risk. */
    private const DIRECT_TABLES = [
        'contact_activities', 'follow_up_tasks', 'appointments', 'treatment_plans',
        'patient_invoices', 'patient_debts', 'clinical_notes', 'lab_orders',
        'lab_warranties', 'message_logs', 'patient_attachments', 'consent_forms',
        'dental_examinations', 'schedule_registrations',
    ];

    /** Related-record counts shown on the merge preview screen. */
    private const PREVIEW_TABLES = [
        'invoices'         => ['table' => 'patient_invoices', 'label' => 'Hóa đơn'],
        'treatment_plans'  => ['table' => 'treatment_plans', 'label' => 'Kế hoạch điều trị'],
        'appointments'     => ['table' => 'appointments', 'label' => 'Lịch hẹn'],
        'clinical_notes'   => ['table' => 'clinical_notes', 'label' => 'Ghi chú lâm sàng'],
        'attachments'      => ['table' => 'patient_attachments', 'label' => 'Tệp đính kèm'],
        'consent_forms'    => ['table' => 'consent_forms', 'label' => 'Cam kết'],
        'tooth_conditions' => ['table' => 'tooth_conditions', 'label' => 'Tình trạng răng'],
        'relationships'    => ['table' => 'patient_relationships', 'label' => 'Mối quan hệ'],
    ];

    /** Profile fields that get filled in on the survivor when empty (never overwritten). */
    private const MERGEABLE_FIELDS = [
        'address', 'email', 'dob', 'gender', 'allergies',
        'medical_history', 'emergency_contact', 'source', 'branch_id',
    ];

    public function preview(Patient $survivor, Patient $loser): array
    {
        $this->assertDistinctAndActive($survivor, $loser);

        $counts = [];
        foreach (self::PREVIEW_TABLES as $key => $meta) {
            $counts[$key] = [
                'label'    => $meta['label'],
                'survivor' => DB::table($meta['table'])->where('patient_id', $survivor->id)->count(),
                'loser'    => DB::table($meta['table'])->where('patient_id', $loser->id)->count(),
            ];
        }

        $fieldDiffs = [];
        foreach (self::MERGEABLE_FIELDS as $field) {
            $survivorValue = $survivor->{$field};
            $loserValue    = $loser->{$field};
            if (blank($survivorValue) && ! blank($loserValue)) {
                $fieldDiffs[$field] = ['survivor' => $survivorValue, 'loser' => $loserValue];
            }
        }

        $extraPhones = collect([$loser->phone])
            ->merge($loser->phones()->pluck('phone'))
            ->filter()
            ->reject(fn ($p) => $p === $survivor->phone)
            ->unique()
            ->values();

        return [
            'survivor' => [
                'id' => $survivor->id, 'code' => $survivor->code,
                'full_name' => $survivor->full_name, 'phone' => $survivor->phone,
            ],
            'loser' => [
                'id' => $loser->id, 'code' => $loser->code,
                'full_name' => $loser->full_name, 'phone' => $loser->phone,
            ],
            'counts'              => $counts,
            'field_diffs'         => $fieldDiffs,
            'notes_will_append'   => filled($loser->notes),
            'medical_flags_union' => collect($survivor->medical_flags ?? [])
                ->merge($loser->medical_flags ?? [])->unique()->values()->all(),
            'extra_phones' => $extraPhones->all(),
        ];
    }

    public function merge(int $survivorId, int $loserId): void
    {
        if ($survivorId === $loserId) {
            throw new \RuntimeException('Không thể gộp một hồ sơ với chính nó.');
        }

        DB::transaction(function () use ($survivorId, $loserId) {
            $ids = [$survivorId, $loserId];
            sort($ids);
            $locked = Patient::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $survivor = $locked->get($survivorId);
            $loser    = $locked->get($loserId);

            if (! $survivor || ! $loser) {
                throw new \RuntimeException('Một trong hai hồ sơ không còn tồn tại hoặc đã bị xóa.');
            }

            $this->fillGaps($survivor, $loser);
            $this->repointDirectTables($survivorId, $loserId);
            $this->repointLeads($survivorId, $loserId);
            $this->repointToothConditions($survivorId, $loserId);
            $this->repointRelationships($survivorId, $loserId);
            $this->repointActivityLog($survivor, $loser);
            $this->mergePhones($survivor, $loser);
            $this->stampAndDeleteLoser($survivor, $loser);
        });

        Cache::forget('patients.data.list');
    }

    private function assertDistinctAndActive(Patient $a, Patient $b): void
    {
        if ($a->id === $b->id) {
            throw new \RuntimeException('Không thể gộp một hồ sơ với chính nó.');
        }
        if ($a->trashed() || $b->trashed()) {
            throw new \RuntimeException('Một trong hai hồ sơ đã bị xóa.');
        }
    }

    private function fillGaps(Patient $survivor, Patient $loser): void
    {
        foreach (self::MERGEABLE_FIELDS as $field) {
            if (blank($survivor->{$field}) && ! blank($loser->{$field})) {
                $survivor->{$field} = $loser->{$field};
            }
        }

        $survivor->notes = trim(collect([
            $survivor->notes,
            filled($loser->notes) ? "[Từ {$loser->code}] {$loser->notes}" : null,
        ])->filter()->implode("\n\n"));

        $survivor->medical_flags = collect($survivor->medical_flags ?? [])
            ->merge($loser->medical_flags ?? [])
            ->unique()->values()->all();

        $survivor->save();
    }

    private function repointDirectTables(int $survivorId, int $loserId): void
    {
        foreach (self::DIRECT_TABLES as $table) {
            DB::table($table)->where('patient_id', $loserId)->update(['patient_id' => $survivorId]);
        }
    }

    private function repointLeads(int $survivorId, int $loserId): void
    {
        DB::table('leads')->where('converted_patient_id', $loserId)->update(['converted_patient_id' => $survivorId]);
    }

    private function repointToothConditions(int $survivorId, int $loserId): void
    {
        // Survivor's tooth record wins on a conflict — drop loser's duplicate tooth_number rows first.
        DB::table('tooth_conditions')
            ->where('patient_id', $loserId)
            ->whereIn('tooth_number', function ($q) use ($survivorId) {
                $q->select('tooth_number')->from('tooth_conditions')->where('patient_id', $survivorId);
            })
            ->delete();

        DB::table('tooth_conditions')->where('patient_id', $loserId)->update(['patient_id' => $survivorId]);
    }

    private function repointRelationships(int $survivorId, int $loserId): void
    {
        // Drop any relationship row directly between survivor and loser (either direction) —
        // repointing it would create a nonsensical self-relationship.
        DB::table('patient_relationships')
            ->where(function ($q) use ($survivorId, $loserId) {
                $q->where('patient_id', $survivorId)->where('related_patient_id', $loserId);
            })
            ->orWhere(function ($q) use ($survivorId, $loserId) {
                $q->where('patient_id', $loserId)->where('related_patient_id', $survivorId);
            })
            ->delete();

        // Drop loser-side rows that would collide with a relationship the survivor already has
        // to the same third patient (unique on patient_id + related_patient_id).
        DB::table('patient_relationships as pr')
            ->where('pr.patient_id', $loserId)
            ->whereExists(function ($q) use ($survivorId) {
                $q->select(DB::raw(1))
                    ->from('patient_relationships as pr2')
                    ->where('pr2.patient_id', $survivorId)
                    ->whereColumn('pr2.related_patient_id', 'pr.related_patient_id');
            })
            ->delete();

        DB::table('patient_relationships as pr')
            ->where('pr.related_patient_id', $loserId)
            ->whereExists(function ($q) use ($survivorId) {
                $q->select(DB::raw(1))
                    ->from('patient_relationships as pr3')
                    ->where('pr3.related_patient_id', $survivorId)
                    ->whereColumn('pr3.patient_id', 'pr.patient_id');
            })
            ->delete();

        DB::table('patient_relationships')->where('patient_id', $loserId)->update(['patient_id' => $survivorId]);
        DB::table('patient_relationships')->where('related_patient_id', $loserId)->update(['related_patient_id' => $survivorId]);
    }

    private function repointActivityLog(Patient $survivor, Patient $loser): void
    {
        DB::table('activity_log')
            ->where('subject_type', Patient::class)
            ->where('subject_id', $loser->id)
            ->update(['subject_id' => $survivor->id]);

        activity('patient_merge')
            ->performedOn($survivor)
            ->causedBy(auth()->user())
            ->withProperties(['merged_patient_id' => $loser->id, 'merged_patient_code' => $loser->code])
            ->log("Đã gộp hồ sơ {$loser->code} vào {$survivor->code}");
    }

    private function mergePhones(Patient $survivor, Patient $loser): void
    {
        $incoming = collect([$loser->phone])
            ->merge(DB::table('patient_phones')->where('patient_id', $loser->id)->pluck('phone'))
            ->filter()
            ->unique();

        foreach ($incoming as $phone) {
            if ($phone === $survivor->phone) {
                continue;
            }
            DB::table('patient_phones')->updateOrInsert(
                ['patient_id' => $survivor->id, 'phone' => $phone],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        DB::table('patient_phones')->where('patient_id', $loser->id)->delete();
    }

    private function stampAndDeleteLoser(Patient $survivor, Patient $loser): void
    {
        $loser->notes = trim(collect([
            $loser->notes,
            '[MERGED] Đã gộp vào hồ sơ ' . $survivor->code . ' lúc ' . now()->format('d/m/Y H:i'),
        ])->filter()->implode("\n\n"));
        $loser->save();
        $loser->delete();
    }
}
