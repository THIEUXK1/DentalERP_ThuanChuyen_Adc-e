<?php

namespace App\Http\Controllers\Schedule;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DentalChair;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $svc) {}

    public function index(Request $request): Response
    {
        $this->authorize('appointments.view');

        $date = $request->date ?? today()->toDateString();
        $branchId = $request->branch_id;

        $query = Appointment::with(['patient', 'doctor', 'chair', 'service'])
            ->when($branchId, fn ($q) => $q->forBranch($branchId))
            ->when($request->doctor_id, fn ($q) => $q->forDoctor($request->doctor_id))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->forDate($date)
            ->orderBy('scheduled_at');

        return Inertia::render('Schedule/Appointments/Index', [
            'appointments' => $query->get()->map(fn ($a) => $this->dto($a)),
            'date' => $date,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'doctors' => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'chairs' => DentalChair::where('is_active', true)->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'branch_id' => $c->branch_id]),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'duration_minutes' => $s->duration_minutes]),
            'patients' => Patient::where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone, 'code' => $p->code, 'branch_id' => $p->branch_id]),
            'statuses' => collect(AppointmentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'filters' => $request->only(['date', 'branch_id', 'doctor_id', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('appointments.create');

        return $this->form(null, $request->only('patient_id', 'lead_id', 'branch_id', 'doctor_id'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('appointments.create');

        $data = $this->validated($request);

        try {
            $appointment = $this->svc->book($data);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['conflict' => $e->getMessage()]);
        }

        return redirect()->route('schedule.appointments.show', $appointment)
            ->with('success', "Đã đặt lịch hẹn {$appointment->code}.");
    }

    public function show(Appointment $appointment): Response
    {
        $this->authorize('appointments.view');

        $appointment->load(['patient', 'doctor', 'chair', 'service', 'branch']);
        $allowed = $appointment->status->allowedTransitions();

        return Inertia::render('Schedule/Appointments/Show', [
            'appointment' => $this->dto($appointment),
            'transitions' => collect($allowed)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('appointments.manage');

        return $this->form($appointment);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('appointments.manage');

        $data = $this->validated($request);

        try {
            $this->svc->reschedule($appointment, $data['scheduled_at'], $data['duration_minutes'] ?? 30);
            $appointment->update([
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'dental_chair_id' => $data['dental_chair_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['conflict' => $e->getMessage()]);
        }

        return redirect()->route('schedule.appointments.show', $appointment)->with('success', 'Đã cập nhật lịch hẹn.');
    }

    public function quickReschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('appointments.manage');

        $data = $request->validate([
            'scheduled_at'     => 'required|date',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'notes'            => 'nullable|string|max:1000',
        ]);

        try {
            $this->svc->reschedule(
                $appointment,
                $data['scheduled_at'],
                (int) ($data['duration_minutes'] ?? $appointment->duration_minutes)
            );
            $appointment->update(['notes' => $data['notes'] ?? $appointment->notes]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã rời lịch hẹn {$appointment->code}.");
    }

    public function transition(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('appointments.manage');

        $data = $request->validate([
            'status' => 'required|string',
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->svc->transition($appointment, AppointmentStatus::from($data['status']), $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã cập nhật trạng thái lịch hẹn.');
    }

    private function form(?Appointment $appointment, array $defaults = []): Response
    {
        $branchId = $appointment?->branch_id ?? $defaults['branch_id'] ?? null;

        if (! $branchId && ! empty($defaults['patient_id'])) {
            $patient = Patient::find($defaults['patient_id']);
            if ($patient) {
                $defaults['branch_id'] = $patient->branch_id;
                $branchId = $patient->branch_id;
            }
        }

        return Inertia::render('Schedule/Appointments/Form', [
            'appointment' => $appointment ? [
                'id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'branch_id' => $appointment->branch_id,
                'doctor_id' => $appointment->doctor_id,
                'dental_chair_id' => $appointment->dental_chair_id,
                'service_id' => $appointment->service_id,
                'lead_id' => $appointment->lead_id,
                'scheduled_at' => $appointment->scheduled_at->format('Y-m-d\TH:i'),
                'duration_minutes' => $appointment->duration_minutes,
                'notes' => $appointment->notes,
            ] : array_merge(['patient_id' => null, 'branch_id' => null, 'lead_id' => null, 'doctor_id' => null], $defaults),
            'patients' => Patient::where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone, 'code' => $p->code]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'doctors' => Employee::doctors()->where('is_active', true)
                ->when($branchId, fn ($q) => $q->byBranch($branchId))->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'branch_id' => $e->branch_id]),
            'chairs' => DentalChair::where('is_active', true)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'branch_id' => $c->branch_id]),
            'services' => DentalService::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'duration_minutes' => $s->duration_minutes]),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'branch_id' => 'required|exists:branches,id',
            'doctor_id' => 'nullable|exists:employees,id',
            'dental_chair_id' => 'nullable|exists:dental_chairs,id',
            'service_id' => 'nullable|exists:dental_services,id',
            'lead_id' => 'nullable|exists:leads,id',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'integer|min:5|max:480',
            'notes' => 'nullable|string',
        ]);
    }

    private function dto(Appointment $a): array
    {
        return [
            'id' => $a->id,
            'code' => $a->code,
            'patient' => $a->patient->full_name ?? '—',
            'patient_id' => $a->patient_id,
            'doctor_id' => $a->doctor_id,
            'branch_id' => $a->branch_id,
            'patient_phone' => $a->patient->phone ?? null,
            'doctor' => $a->doctor?->full_name ?? '—',
            'chair' => $a->chair?->name ?? '—',
            'service' => $a->service?->name ?? '—',
            'branch' => $a->branch->name ?? '—',
            'scheduled_at' => $a->scheduled_at->format('Y-m-d H:i'),
            'ends_at' => $a->ends_at->format('H:i'),
            'duration_minutes' => $a->duration_minutes,
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'status_color' => $a->status->color(),
            'cancel_reason' => $a->cancel_reason,
            'notes' => $a->notes,
        ];
    }
}
