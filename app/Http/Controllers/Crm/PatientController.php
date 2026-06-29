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
use App\Models\Employee;
use App\Models\Patient;
use App\Models\PatientInvoice;
use App\Models\PatientPayment;
use App\Models\TreatmentPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('patients.view');

        $query = Patient::with('branch')
            ->when($request->search, fn ($q, $v) => $q->where('full_name', 'ilike', "%{$v}%")
                ->orWhere('phone', 'ilike', "%{$v}%")
                ->orWhere('code', 'ilike', "%{$v}%"))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->source, fn ($q, $v) => $q->where('source', $v))
            ->orderByDesc('id');

        return Inertia::render('Crm/Patients/Index', [
            'patients' => $query->paginate(20)->through(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'full_name' => $p->full_name,
                'phone' => $p->phone,
                'gender' => $p->gender,
                'source' => $p->source,
                'branch' => $p->branch?->name,
                'is_active' => $p->is_active,
                'created_at' => $p->created_at->format('d/m/Y'),
            ]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'filters' => $request->only(['search', 'branch_id', 'source']),
        ]);
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

        return response()->json(['warnings' => $warnings]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('patients.create');

        $forceSave = (bool) $request->input('force_save', false);
        $data = $this->validated($request, null, $forceSave);
        Patient::create([...$data, 'code' => Patient::generateCode()]);

        return redirect()->route('patients.index')->with('success', 'Đã tạo bệnh nhân.');
    }

    public function show(Patient $patient): Response
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

        $clinicalNotes = $patient->clinicalNotes()->with(['doctor', 'creator'])->take(30)->get()
            ->map(fn ($n) => ClinicalNoteController::mapDto($n));

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

        $doctors = Employee::doctors()->orderBy('full_name')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]);

        $conditionTypes = collect(ToothConditionType::cases())
            ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'color' => $c->color()]);

        // ── Financial summary ───────────────────────────────────────────────
        $invoices = PatientInvoice::where('patient_id', $patient->id)
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->get();
        $totalAmount = $invoices->sum('total');
        $amountPaid  = $invoices->sum('amount_paid');
        $amountDue   = max(0, $totalAmount - $amountPaid);

        // ── Treatment history (plans + items) ───────────────────────────────
        $treatmentPlans = TreatmentPlan::where('patient_id', $patient->id)
            ->with(['doctor', 'items.service', 'invoices'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($plan) {
                $paid = $plan->invoices->sum('amount_paid');
                $due  = max(0, ($plan->total_amount - $plan->discount_amount) - $paid);
                return [
                    'id'           => $plan->id,
                    'code'         => $plan->code,
                    'status'       => $plan->status->value,
                    'status_label' => $plan->status->label(),
                    'doctor'       => $plan->doctor?->full_name,
                    'total_amount' => $plan->total_amount - $plan->discount_amount,
                    'amount_paid'  => $paid,
                    'amount_due'   => $due,
                    'created_at'   => $plan->created_at->format('d/m/Y H:i'),
                    'items'        => $plan->items->map(fn ($item) => [
                        'id'           => $item->id,
                        'name'         => $item->name,
                        'tooth_number' => $item->tooth_number,
                        'unit_price'   => $item->unit_price,
                        'quantity'     => $item->quantity,
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

        // ── Phase M: Treatment Timeline ──────────────────────────────────────
        $timeline = collect();

        Appointment::where('patient_id', $patient->id)->get()->each(function ($a) use (&$timeline) {
            $timeline->push(['date' => $a->scheduled_at->format('Y-m-d H:i'), 'type' => 'appointment', 'label' => 'Lịch hẹn', 'detail' => $a->code, 'status' => $a->status->value]);
        });

        $patient->clinicalNotes()->get()->each(function ($n) use (&$timeline) {
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

        return Inertia::render('Crm/Patients/Show', [
            'patient' => [
                'id'                => $patient->id,
                'code'              => $patient->code,
                'full_name'         => $patient->full_name,
                'phone'             => $patient->phone,
                'email'             => $patient->email,
                'dob'               => $patient->dob?->format('d/m/Y'),
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
            'treatmentPlans'    => $treatmentPlans,
            'activities'        => $activities,
            'clinicalNotes'     => $clinicalNotes,
            'toothConditions'   => $toothConditions,
            'attachments'       => $attachments,
            'consentForms'      => $consentForms,
            'relationships'     => $relationships,
            'timeline'          => $timeline,
            'doctors'           => $doctors,
            'conditionTypes'    => $conditionTypes,
            'contactTypes'      => collect(ContactType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'attachmentTypes'   => collect(AttachmentType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'relationshipTypes' => collect(RelationshipType::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
            'allPatients'       => Patient::where('id', '!=', $patient->id)->where('is_active', true)->orderBy('full_name')->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->full_name, 'code' => $p->code, 'phone' => $p->phone]),
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('patients.edit');

        return $this->form($patient);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('patients.edit');

        $data = $this->validated($request, $patient->id);
        $patient->update($data);

        return redirect()->route('patients.show', $patient)->with('success', 'Đã cập nhật bệnh nhân.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('patients.delete');
        $patient->delete();

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
            'phone'             => ($forceSave ? 'nullable' : 'required').'|string|max:20',
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
        ]);
    }
}
