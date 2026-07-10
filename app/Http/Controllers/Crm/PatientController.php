<?php

namespace App\Http\Controllers\Crm;

use App\Enums\AttachmentType;
use App\Enums\ContactType;
use App\Enums\InvoiceStatus;
use App\Enums\LeadSource;
use App\Enums\RelationshipType;
use App\Enums\ToothConditionType;
use App\Enums\TreatmentPlanStatus;
use App\Http\Controllers\Clinical\ClinicalNoteController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DentalChair;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\PendingDeletion;
use App\Models\ScheduleRegistration;
use App\Models\TreatmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('patients.view');

        return Inertia::render('Crm/Patients/Index', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function data(): \Illuminate\Http\Response
    {
        $this->authorize('patients.view');

        // Short-lived cache: this list has no per-request filters (all filtering/sorting
        // happens client-side), so one shared cache entry serves every viewer. A few
        // seconds of staleness is an acceptable trade for skipping the full scan + joins
        // on repeated loads (tab switches, going back from a patient page, etc).
        //
        // Cache the already-encoded JSON string, not the raw PHP array, and use the
        // `file` store instead of the app's default `database` store: at ~8MB per entry,
        // the `database` store's Postgres round-trip (TOAST compression) takes 800ms-1.5s
        // per read/write, while `file` takes ~20ms — the cache was making this endpoint
        // slower than just recomputing on every request.
        $json = \Illuminate\Support\Facades\Cache::store('file')->remember('patients.data.list', 20, function () {
            // Plain query-builder select instead of Eloquent + with()/withMin(): at 20k+ patients,
            // per-row model hydration and Carbon parsing dominate the cost (see
            // PatientInvoiceController::data() for the same fix applied to a bigger table).
            $excludedStatuses = ['cancelled', 'no_show', 'completed', 'in_treatment'];

            $registeredPatientIds = DB::table('schedule_registrations')
                ->whereIn('status', ['pending', 'in_treatment'])
                ->pluck('patient_id')
                ->flip();

            $nextAppointments = DB::table('appointments')
                ->whereNotIn('status', $excludedStatuses)
                ->where('scheduled_at', '>=', now())
                ->groupBy('patient_id')
                ->select('patient_id', DB::raw('MIN(scheduled_at) as next_at'))
                ->pluck('next_at', 'patient_id');

            $isSqlite = DB::getDriverName() === 'sqlite';

            $extraPhones = DB::table('patient_phones')
                ->select('patient_id', DB::raw($isSqlite
                    ? "group_concat(phone, ',') as phones"
                    : "string_agg(phone, ',') as phones"))
                ->groupBy('patient_id')
                ->pluck('phones', 'patient_id');

            $rows = DB::table('patients as p')
                ->leftJoin('branches as b', 'b.id', '=', 'p.branch_id')
                ->whereNull('p.deleted_at')
                ->orderByDesc('p.id')
                ->select(
                    'p.id', 'p.code', 'p.full_name', 'p.phone', 'p.gender', 'p.source',
                    'p.branch_id', 'p.is_active', 'p.address', 'p.dob',
                    DB::raw($isSqlite
                        ? "strftime('%d/%m/%Y', p.created_at) as created_at"
                        : "to_char(p.created_at, 'DD/MM/YYYY') as created_at"),
                    DB::raw($isSqlite
                        ? "strftime('%Y-%m-%d', p.created_at) as created_at_raw"
                        : "to_char(p.created_at, 'YYYY-MM-DD') as created_at_raw"),
                    'b.name as branch_name',
                )
                ->get();

            return $rows->map(function ($p) use ($registeredPatientIds, $nextAppointments, $extraPhones) {
                $next = $nextAppointments[$p->id] ?? null;

                return [
                    'id'                      => $p->id,
                    'code'                    => $p->code,
                    'full_name'               => $p->full_name,
                    'phone'                   => $p->phone ?? '',
                    'extra_phones'            => isset($extraPhones[$p->id]) ? explode(',', $extraPhones[$p->id]) : [],
                    'gender'                  => $p->gender,
                    'source'                  => $p->source,
                    'dob_raw'                 => $p->dob,
                    'branch'                  => $p->branch_name,
                    'branch_id'               => $p->branch_id,
                    'is_active'               => $p->is_active,
                    'address'                 => $p->address,
                    'created_at'              => $p->created_at,
                    'created_at_raw'          => $p->created_at_raw,
                    'next_appointment_at'     => $next,
                    'next_appointment_display'=> $next ? \Carbon\Carbon::parse($next)->format('d/m H:i') : null,
                    'has_registration'        => isset($registeredPatientIds[$p->id]),
                ];
            })->values()->toJson();
        });

        return response($json, 200, ['Content-Type' => 'application/json']);
    }

    public function registerAppointment(Patient $patient): Response
    {
        $this->authorize('appointments.view');

        $registrations = $patient->scheduleRegistrations()
            ->with(['doctor', 'chair'])
            ->orderByDesc('registration_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'code'         => $r->code,
                'scheduled_at' => $r->registration_date->format('d/m/Y') . ($r->visit_time ? ' ' . substr($r->visit_time, 0, 5) : ''),
                'doctor'       => $r->doctor?->full_name ?? '—',
                'chair'        => $r->chair?->name ?? '—',
                'status'       => $r->status,
                'status_label' => $r->statusLabel(),
                'status_color' => $r->statusColor(),
                'notes'        => $r->notes,
                'duration_minutes' => null,
            ]);

        $quickStatuses = [
            ['value' => 'pending',      'label' => 'Đang chờ',   'color' => 'yellow'],
            ['value' => 'in_treatment', 'label' => 'Đang làm',   'color' => 'teal'],
            ['value' => 'completed',    'label' => 'Hoàn thành', 'color' => 'green'],
            ['value' => 'cancelled',    'label' => 'Đã hủy',     'color' => 'red'],
        ];

        return Inertia::render('Crm/Patients/AppointmentRegister', [
            'patient' => [
                'id'        => $patient->id,
                'code'      => $patient->code,
                'full_name' => $patient->full_name,
                'phone'     => $patient->phone,
                'branch_id' => $patient->branch_id,
            ],
            'appointments' => $registrations,
            'doctors'  => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'chairs'   => DentalChair::where('is_active', true)->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'branch_id' => $c->branch_id]),
            'statuses' => $quickStatuses,
        ]);
    }

    public function quickRegister(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('appointments.create');

        $data = $request->validate([
            'scheduled_time'  => 'required|date_format:H:i',
            'doctor_id'       => 'nullable|exists:employees,id',
            'dental_chair_id' => 'nullable|exists:dental_chairs,id',
            'notes'           => 'nullable|string|max:2000',
            'status'          => 'required|in:pending,in_treatment,completed,cancelled',
            'redirect_after'  => 'nullable|string',
        ]);

        try {
            ScheduleRegistration::create([
                'code'              => ScheduleRegistration::generateCode(),
                'patient_id'        => $patient->id,
                'branch_id'         => $patient->branch_id ?? auth()->user()->branch_id ?? null,
                'doctor_id'         => $data['doctor_id'] ?? null,
                'dental_chair_id'   => $data['dental_chair_id'] ?? null,
                'registration_date' => today()->format('Y-m-d'),
                'visit_time'        => $data['scheduled_time'],
                'status'            => $data['status'],
                'notes'             => $data['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['scheduled_time' => 'Mã đăng ký bị trùng, vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('schedule.registrations.index')
            ->with('success', 'Đã đăng ký khám thành công.');
    }

    public function create(): Response
    {
        $this->authorize('patients.create');

        return $this->form();
    }

    public function checkDuplicate(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('patients.create');

        $name  = trim($request->input('full_name', ''));
        $phone = trim($request->input('phone', ''));

        $warnings = [
            'phone_empty'    => $phone === '',
            'name_duplicate' => null,
            'full_duplicate' => null,
        ];

        if ($name !== '') {
            $byName = Patient::whereRaw('lower(full_name) = ?', [mb_strtolower($name)])->first();
            if ($byName) {
                $warnings['name_duplicate'] = [
                    'id' => $byName->id, 'code' => $byName->code,
                    'name' => $byName->full_name, 'phone' => $byName->phone,
                ];
                if ($phone !== '' && $byName->phone === $phone) {
                    $warnings['full_duplicate'] = $warnings['name_duplicate'];
                }
            }
        }

        if ($phone !== '' && ! $warnings['full_duplicate']) {
            $secondaryHit = DB::table('patient_phones')
                ->join('patients', 'patients.id', '=', 'patient_phones.patient_id')
                ->where('patient_phones.phone', $phone)
                ->select('patients.id', 'patients.code', 'patients.full_name as name', 'patients.phone')
                ->first();
            if ($secondaryHit) {
                $warnings['full_duplicate'] = (array) $secondaryHit;
            }
        }

        return response()->json(['warnings' => $warnings]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('patients.create');

        $forceSave = (bool) $request->input('force_save', false);
        $data = $this->validated($request, null, $forceSave);
        $extraPhones = $data['extra_phones'] ?? [];
        unset($data['extra_phones']);

        $patient = Patient::create([...$data, 'code' => Patient::generateCode()]);
        $this->syncExtraPhones($patient, $extraPhones);
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.data.list');
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.lite-list');

        return redirect()->route('patients.index')->with('success', 'Đã tạo bệnh nhân.');
    }

    public function show(Patient $patient): Response
    {
        $this->authorize('patients.view');

        // Pure JS view: this page renders as an empty shell and fetches everything
        // itself from showData() — see resources/js/composables/usePatientDetail.js.
        return Inertia::render('Crm/Patients/Show', [
            'patientId' => $patient->id,
        ]);
    }

    public function showData(Patient $patient): \Illuminate\Http\JsonResponse
    {
        $this->authorize('patients.view');

        $activities = $patient->contactActivities()->with('creator')->take(20)->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'type'       => $a->type->value,
                'type_label' => $a->type->label(),
                'type_color' => $a->type->color(),
                'content'    => $a->content,
                'creator'    => $a->creator->name,
                'created_at' => $a->created_at->format('d/m/Y H:i'),
            ]);

        $clinicalNotesRaw = $patient->clinicalNotes()->with(['doctor', 'creator'])->take(30)->get();
        $clinicalNotes = $clinicalNotesRaw->map(fn ($n) => ClinicalNoteController::mapDto($n));

        $toothConditions = $patient->toothConditions()->get()
            ->map(fn ($tc) => [
                'id'              => $tc->id,
                'tooth_number'    => $tc->tooth_number,
                'condition'       => $tc->condition->value,
                'condition_label' => $tc->condition->label(),
                'condition_color' => $tc->condition->color(),
                'svg_fill'        => $tc->condition->svgFill(),
                'svg_stroke'      => $tc->condition->svgStroke(),
                'note'            => $tc->note,
            ]);

        $doctors = Employee::doctors()->where('is_active', true)->orderBy('full_name')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]);

        $chairs = DentalChair::where('is_active', true)->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'branch_id' => $c->branch_id]);

        $services = DentalService::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'duration_minutes' => $s->duration_minutes]);

        $conditionTypes = collect(ToothConditionType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'color' => $c->color()]);

        // ── Invoices (single query — used for both the financial summary and the tab list) ──
        $allInvoices = PatientInvoice::where('patient_id', $patient->id)
            ->with(['treatmentPlan', 'payments' => fn ($q) => $q->where('amount', '>', 0)->orderByDesc('payment_date')])
            // Portable "NULLS LAST": works unchanged on Postgres, MySQL and SQLite.
            ->orderByRaw('(due_date IS NULL) ASC, due_date ASC, id DESC')
            ->get();

        $billableInvoices = $allInvoices->where('status', '!=', InvoiceStatus::Cancelled->value);
        $totalAmount = $billableInvoices->sum('total');
        $amountPaid  = $billableInvoices->sum('amount_paid');
        $amountDue   = max(0, $totalAmount - $amountPaid);

        $patientInvoices = $allInvoices->map(function ($inv) {
            $lastPayment = $inv->payments->first();
            return [
                'id'                => $inv->id,
                'code'              => $inv->code,
                'status'            => $inv->status->value,
                'status_label'      => $inv->status->label(),
                'status_color'      => $inv->status->color(),
                'total'             => $inv->total,
                'amount_paid'       => $inv->amount_paid,
                'amount_due'        => $inv->amountDue(),
                'due_date'          => $inv->due_date?->format('d/m/Y'),
                'due_date_raw'      => $inv->due_date?->toDateString(),
                'installment_index' => $inv->installment_index,
                'plan_id'           => $inv->treatment_plan_id,
                'plan_code'         => $inv->treatmentPlan?->code,
                'created_at'        => $inv->created_at->format('d/m/Y'),
                'last_payment_date' => $lastPayment?->payment_date?->format('d/m/Y'),
                'payment_count'     => $inv->payments->count(),
            ];
        });

        // ── Appointments (single query — used for the tab, the timeline, and pending-deletion ids) ──
        $appointmentsRaw = Appointment::where('patient_id', $patient->id)
            ->with(['doctor', 'service'])
            ->withExists('registration')
            ->orderByDesc('scheduled_at')
            ->get();

        $appointments = $appointmentsRaw->map(fn ($a) => [
            'id'               => $a->id,
            'code'             => $a->code,
            'scheduled_at'     => $a->scheduled_at->format('d/m/Y H:i'),
            'scheduled_date'   => $a->scheduled_at->format('d/m/Y'),
            'scheduled_time'   => $a->scheduled_at->format('H:i'),
            'duration_minutes' => $a->duration_minutes,
            'doctor'           => $a->doctor?->full_name ?? '—',
            'service'          => $a->service?->name ?? '—',
            'status'           => $a->status->value,
            'status_label'     => $a->status->label(),
            'status_color'     => $a->status->color(),
            'notes'            => $a->notes,
            'has_registration' => (bool) $a->registration_exists,
        ]);

        // ── Treatment history (plans + items) ───────────────────────────────
        $treatmentPlansRaw = TreatmentPlan::where('patient_id', $patient->id)
            ->with(['doctor', 'items.service', 'invoices'])
            // Portable "NULLS LAST": works unchanged on Postgres, MySQL and SQLite.
            ->orderByRaw('(start_date IS NULL) ASC, start_date ASC')
            ->orderByDesc('created_at')
            ->get();

        $treatmentPlans = $treatmentPlansRaw->map(function ($plan) {
            $paid = $plan->invoices->sum('amount_paid');
            $due  = max(0, ($plan->total_amount - $plan->discount_amount) - $paid);
            return [
                'id'                 => $plan->id,
                'code'               => $plan->code,
                'status'             => $plan->status->value,
                'status_label'       => $plan->status->label(),
                'doctor'             => $plan->doctor?->full_name,
                'total_amount'       => $plan->total_amount,
                'discount_amount'    => $plan->discount_amount,
                'net_total'          => $plan->total_amount - $plan->discount_amount,
                'amount_paid'        => $paid,
                'amount_due'         => $due,
                'created_at'         => $plan->created_at->format('d/m/Y H:i'),
                'diagnosis'          => $plan->diagnosis,
                'chief_complaint'    => $plan->chief_complaint,
                'treatment_goal'     => $plan->treatment_goal,
                'priority'           => $plan->priority,
                'start_date'         => $plan->start_date?->format('d/m/Y'),
                'start_date_raw'     => $plan->start_date?->format('Y-m-d'),
                'expected_end_date'  => $plan->expected_end_date?->format('d/m/Y'),
                'estimated_sessions' => $plan->estimated_sessions,
                'frequency'          => $plan->frequency,
                'notes'              => $plan->notes,
                'transitions'        => collect($plan->status->allowedTransitions())->map(fn ($s) => [
                    'value' => $s->value,
                ])->values()->all(),
                'items'              => $plan->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'name'         => $item->name,
                    'tooth_number' => $item->tooth_number,
                    'unit_price'   => $item->unit_price,
                    'quantity'     => $item->quantity,
                    'discount'     => $item->discount,
                    'subtotal'     => $item->subtotal,
                    'status'       => $item->status,
                    'status_label' => match($item->status) {
                        'pending'     => 'Chờ',
                        'in_progress' => 'Đang làm',
                        'completed'   => 'Hoàn thành',
                        'cancelled'   => 'Hủy',
                        default       => $item->status,
                    },
                    'notes'  => $item->notes,
                ])->values()->all(),
            ];
        });

        // ── Phase M: Attachments ─────────────────────────────────────────────
        $attachments = $patient->attachments()->get()->map(fn ($a) => [
            'id'         => $a->id,
            'type'       => $a->type->value,
            'type_label' => $a->type->label(),
            'type_color' => $a->type->color(),
            'title'      => $a->title,
            'file_path'  => $a->file_path,
            'file_url'   => asset('storage/'.$a->file_path),
            'file_size'  => $a->file_size,
            'mime_type'  => $a->mime_type,
            'created_at' => $a->created_at->format('d/m/Y H:i'),
        ]);

        // ── Phase M: Consent Forms ───────────────────────────────────────────
        $consentForms = $patient->consentForms()->get()->map(fn ($c) => [
            'id'               => $c->id,
            'title'            => $c->title,
            'content'          => $c->content,
            'status'           => $c->status->value,
            'status_label'     => $c->status->label(),
            'status_color'     => $c->status->color(),
            'signed_at'        => $c->signed_at?->format('d/m/Y H:i'),
            'signed_by_name'   => $c->signed_by_name,
            'treatment_plan_id'=> $c->treatment_plan_id,
            'notes'            => $c->notes,
            'created_at'       => $c->created_at->format('d/m/Y'),
        ]);

        // ── Phase M: Relationships ───────────────────────────────────────────
        $relationships = $patient->relationships()->with('relatedPatient')->get()->map(fn ($r) => [
            'id'                  => $r->id,
            'related_patient_id'  => $r->related_patient_id,
            'related_patient_name'=> $r->relatedPatient->full_name,
            'related_patient_code'=> $r->relatedPatient->code,
            'relationship_type'   => $r->relationship_type->value,
            'relationship_label'  => $r->relationship_type->label(),
            'referral_rate'       => $r->referral_rate,
            'notes'               => $r->notes,
        ]);

        // ── Phase M: Treatment Timeline (reuses $appointmentsRaw / $clinicalNotesRaw above) ──
        $timeline = collect();

        $appointmentsRaw->each(function ($a) use (&$timeline) {
            $timeline->push(['date' => $a->scheduled_at->format('Y-m-d H:i'), 'type' => 'appointment', 'label' => 'Lịch hẹn', 'detail' => $a->code, 'status' => $a->status->value]);
        });

        $clinicalNotesRaw->each(function ($n) use (&$timeline) {
            $timeline->push(['date' => $n->created_at->format('Y-m-d H:i'), 'type' => 'clinical_note', 'label' => 'Ghi chú lâm sàng', 'detail' => $n->chief_complaint ?? '']);
        });

        PatientPayment::join('patient_invoices', 'patient_payments.invoice_id', '=', 'patient_invoices.id')
            ->where('patient_invoices.patient_id', $patient->id)
            ->where('patient_payments.amount', '>', 0)
            ->select('patient_payments.*')
            ->get()
            ->each(function ($p) use (&$timeline) {
                $timeline->push(['date' => $p->payment_date->format('Y-m-d H:i'), 'type' => 'payment', 'label' => 'Thanh toán', 'detail' => number_format($p->amount).' ₫']);
            });

        $timeline = $timeline->sortByDesc('date')->values()->toArray();

        return response()->json([
            'patient' => [
                'id'                => $patient->id,
                'code'              => $patient->code,
                'full_name'         => $patient->full_name,
                'phone'             => $patient->phone,
                'extra_phones'      => $patient->phones()->get(['id', 'phone']),
                'email'             => $patient->email,
                'dob'               => $patient->dob?->format('d/m/Y'),
                'dob_raw'           => $patient->dob?->format('Y-m-d'),
                'gender'            => $patient->gender,
                'address'           => $patient->address,
                'source'            => $patient->source,
                'allergies'         => $patient->allergies,
                'medical_history'   => $patient->medical_history,
                'medical_flags'     => $patient->medical_flags ?? [],
                'emergency_contact' => $patient->emergency_contact,
                'notes'             => $patient->notes,
                'is_active'         => $patient->is_active,
                'branch_id'         => $patient->branch_id,
                'photo_url'         => $patient->photo_path ? asset('storage/'.$patient->photo_path) : null,
            ],
            'financial' => [
                'total_amount' => $totalAmount,
                'amount_paid'  => $amountPaid,
                'amount_due'   => $amountDue,
            ],
            'invoices'          => $patientInvoices->values(),
            'treatmentPlans'    => $treatmentPlans->values(),
            'appointments'      => $appointments->values(),
            'pendingDeletions'  => $this->pendingDeletionsMap($appointmentsRaw->pluck('id'), $treatmentPlansRaw->pluck('id')),
            'activities'        => $activities,
            'clinicalNotes'     => $clinicalNotes,
            'toothConditions'   => $toothConditions,
            'attachments'       => $attachments,
            'consentForms'      => $consentForms,
            'relationships'     => $relationships,
            'timeline'          => $timeline,
            'doctors'           => $doctors,
            'chairs'            => $chairs,
            'services'          => $services,
            'conditionTypes'    => $conditionTypes,
            'contactTypes'      => collect(ContactType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'attachmentTypes'   => collect(AttachmentType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'relationshipTypes' => collect(RelationshipType::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'branches'          => Branch::where('is_active', true)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'sources'           => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    /**
     * Lightweight id/name/code/phone(/branch_id/is_active) list of every patient, shared by
     * every "pick a patient" UI that only needs identity fields: the merge-duplicate and
     * "add relationship" pickers on the patient page, and the "book appointment" picker on
     * the appointments board. Those are all opened on demand (rarely) — loading all 20k+
     * patients eagerly on every page view was the dominant cost on both pages (~2MB,
     * 1-5s query+hydration depending on Eloquent vs query-builder).
     * Plain query-builder select (no Eloquent hydration) + a short shared cache, same
     * pattern as data() above. Not filtered by is_active server-side (the merge picker
     * needs inactive records too) — consumers that only want bookable patients filter
     * client-side on the `is_active` flag.
     */
    public function liteList(): \Illuminate\Http\Response
    {
        $this->authorize('patients.view');

        $json = \Illuminate\Support\Facades\Cache::store('file')->remember('patients.lite-list', 60, function () {
            return DB::table('patients')
                ->whereNull('deleted_at')
                ->orderBy('full_name')
                ->select('id', 'full_name as name', 'code', 'phone', 'branch_id', 'is_active')
                ->get()
                ->toJson();
        });

        return response($json, 200, ['Content-Type' => 'application/json']);
    }

    private function pendingDeletionsMap($apptIds, $planIds): array
    {
        $rows = PendingDeletion::query()
            ->whereNull('cancelled_at')
            ->whereNull('executed_at')
            ->where(function ($q) use ($apptIds, $planIds) {
                $q->where(function ($q2) use ($apptIds) {
                    $q2->where('deletable_type', Appointment::class)
                        ->whereIn('deletable_id', $apptIds);
                })->orWhere(function ($q2) use ($planIds) {
                    $q2->where('deletable_type', TreatmentPlan::class)
                        ->whereIn('deletable_id', $planIds);
                });
            })
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $row->deletable_type . ':' . $row->deletable_id;
            $map[$key] = [
                'id'         => $row->id,
                'reason'     => $row->reason,
                'execute_at' => $row->execute_at->toIso8601String(),
                'user_id'    => $row->user_id,
            ];
        }
        return $map;
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('patients.edit');

        return $this->form($patient);
    }

    /** Same fields as edit(), but plain JSON — lets the list page open the edit form inline instead of navigating away. */
    public function editJson(Patient $patient): \Illuminate\Http\JsonResponse
    {
        $this->authorize('patients.edit');

        return response()->json([
            'id'                => $patient->id,
            'full_name'         => $patient->full_name,
            'phone'             => $patient->phone,
            'extra_phones'      => $patient->phones()->pluck('phone'),
            'email'             => $patient->email,
            'dob_raw'           => $patient->dob?->format('Y-m-d'),
            'gender'            => $patient->gender,
            'address'           => $patient->address,
            'source'            => $patient->source,
            'allergies'         => $patient->allergies,
            'medical_history'   => $patient->medical_history,
            'medical_flags'     => $patient->medical_flags ?? [],
            'emergency_contact' => $patient->emergency_contact,
            'branch_id'         => $patient->branch_id,
            'notes'             => $patient->notes,
            'is_active'         => $patient->is_active,
        ]);
    }

    public function mergePreview(Request $request, Patient $patient): \Illuminate\Http\JsonResponse
    {
        $this->authorize('patients.delete');

        $data = $request->validate([
            'loser_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('patients', 'id')->whereNull('deleted_at')],
        ]);

        if ((int) $data['loser_id'] === $patient->id) {
            return response()->json(['error' => 'Không thể gộp một hồ sơ với chính nó.'], 422);
        }

        $loser = Patient::findOrFail($data['loser_id']);

        try {
            return response()->json(app(\App\Services\PatientMergeService::class)->preview($patient, $loser));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function merge(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.delete');

        $data = $request->validate([
            'loser_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('patients', 'id')->whereNull('deleted_at')],
        ]);

        if ((int) $data['loser_id'] === $patient->id) {
            return back()->withErrors(['loser_id' => 'Không thể gộp một hồ sơ với chính nó.']);
        }

        try {
            app(\App\Services\PatientMergeService::class)->merge($patient->id, (int) $data['loser_id']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['loser_id' => $e->getMessage()]);
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Đã gộp hồ sơ thành công.');
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $forceSave = (bool) $request->input('force_save', false);
        $data = $this->validated($request, $patient->id, $forceSave);
        $hasExtraPhones = $request->has('extra_phones');
        $extraPhones = $data['extra_phones'] ?? [];
        unset($data['extra_phones']);

        $patient->update($data);
        // Only touch secondary phones when the caller actually sent this field —
        // older forms that don't know about it (e.g. Form.vue) must not wipe them out.
        if ($hasExtraPhones) {
            $this->syncExtraPhones($patient, $extraPhones);
        }
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.data.list');
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.lite-list');

        if ($request->boolean('stay')) {
            return back()->with('success', 'Đã cập nhật bệnh nhân.');
        }

        return redirect()->route('patients.show', $patient)->with('success', 'Đã cập nhật bệnh nhân.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('patients.delete');
        $patient->delete();
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.data.list');
        \Illuminate\Support\Facades\Cache::store('file')->forget('patients.lite-list');

        return redirect()->route('patients.index')->with('success', 'Đã xóa bệnh nhân.');
    }

    private function form(?Patient $patient = null): Response
    {
        return Inertia::render('Crm/Patients/Form', [
            'patient' => $patient ? [
                'id'                => $patient->id,
                'full_name'         => $patient->full_name,
                'phone'             => $patient->phone,
                'email'             => $patient->email,
                'dob'               => $patient->dob?->format('Y-m-d'),
                'gender'            => $patient->gender,
                'address'           => $patient->address,
                'source'            => $patient->source,
                'allergies'         => $patient->allergies,
                'medical_history'   => $patient->medical_history,
                'medical_flags'     => $patient->medical_flags ?? [],
                'emergency_contact' => $patient->emergency_contact,
                'branch_id'         => $patient->branch_id,
                'notes'             => $patient->notes,
                'is_active'         => $patient->is_active,
            ] : null,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function uploadAvatar(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');
        $request->validate(['photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        if ($patient->photo_path) {
            Storage::disk('public')->delete($patient->photo_path);
        }

        $path = $request->file('photo')->store("patients/{$patient->id}", 'public');
        $patient->update(['photo_path' => $path]);

        return back()->with('success', 'Đã cập nhật ảnh đại diện.');
    }

    private function validated(Request $request, ?int $ignore = null, bool $forceSave = false): array
    {
        return $request->validate([
            'full_name'         => 'required|string|max:255',
            // Same format rule on create and edit: a Vietnamese phone starts with 0.
            // Without this, editing could strip the leading 0 and silently break search/lookup by phone.
            'phone'             => [
                $forceSave ? 'nullable' : 'required', 'string', 'max:20',
                'regex:/^0\d{8,10}$/',
            ],
            'email'             => 'nullable|email|max:255',
            'dob'               => 'nullable|date',
            'gender'            => 'nullable|in:male,female,other',
            'address'           => 'nullable|string|max:500',
            'source'            => 'nullable|string',
            'allergies'         => 'nullable|string',
            'medical_history'   => 'nullable|string',
            'medical_flags'     => 'nullable|array',
            'medical_flags.*'   => 'string',
            'emergency_contact' => 'nullable|string|max:255',
            'branch_id'         => 'nullable|exists:branches,id',
            'notes'             => 'nullable|string',
            'is_active'         => 'boolean',
            'extra_phones'      => 'nullable|array',
            'extra_phones.*'    => ['string', 'max:20', 'regex:/^0\d{8,10}$/'],
        ]);
    }

    /** Replace a patient's secondary phone numbers with the given list (dedup, skip the primary phone). */
    private function syncExtraPhones(Patient $patient, array $extraPhones): void
    {
        $unique = collect($extraPhones)
            ->filter(fn ($p) => $p !== '' && $p !== $patient->phone)
            ->unique()
            ->values();

        $patient->phones()->whereNotIn('phone', $unique)->delete();

        $existing = $patient->phones()->pluck('phone');
        $unique->diff($existing)->each(fn ($phone) => $patient->phones()->create(['phone' => $phone]));
    }
}
