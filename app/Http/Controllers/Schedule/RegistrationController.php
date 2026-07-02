<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\DentalChair;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\ScheduleRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    private const STATUSES = [
        ['value' => 'pending',      'label' => 'Đang chờ',   'color' => 'yellow'],
        ['value' => 'in_treatment', 'label' => 'Đang làm',   'color' => 'teal'],
        ['value' => 'completed',    'label' => 'Hoàn thành', 'color' => 'green'],
        ['value' => 'cancelled',    'label' => 'Đã hủy',     'color' => 'red'],
    ];

    public function index(): Response
    {
        $this->authorize('appointments.view');

        return Inertia::render('Schedule/Registrations/Index', [
            'all_registrations' => ScheduleRegistration::with(['patient', 'doctor', 'chair'])
                ->orderBy('registration_date')
                ->orderBy('visit_time')
                ->get()
                ->map(fn ($r) => $this->dto($r)),
            'statuses' => self::STATUSES,
            'patients' => Patient::where('is_active', true)->orderBy('full_name')->get()
                ->map(fn ($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'phone' => $p->phone, 'code' => $p->code]),
            'doctors'  => Employee::doctors()->where('is_active', true)->get()
                ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]),
            'chairs'   => DentalChair::where('is_active', true)->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('appointments.create');

        $data = $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'registration_date' => 'required|date',
            'visit_time'        => 'nullable|date_format:H:i',
            'doctor_id'         => 'nullable|exists:employees,id',
            'dental_chair_id'   => 'nullable|exists:dental_chairs,id',
            'status'            => 'required|in:pending,in_treatment,completed,cancelled',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);

        try {
            ScheduleRegistration::create([
                ...$data,
                'code'       => ScheduleRegistration::generateCode(),
                'branch_id'  => $patient->branch_id ?? auth()->user()->branch_id,
                'created_by' => auth()->id(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return back()->withErrors(['code' => 'Mã đăng ký bị trùng, vui lòng thử lại.'])->withInput();
        }

        return back()->with('success', 'Đã tạo đăng ký khám.');
    }

    public function update(Request $request, ScheduleRegistration $registration): RedirectResponse|JsonResponse
    {
        $this->authorize('appointments.create');

        $data = $request->validate([
            'patient_id'        => 'sometimes|exists:patients,id',
            'registration_date' => 'sometimes|date',
            'visit_time'        => 'nullable|date_format:H:i',
            'doctor_id'         => 'nullable|exists:employees,id',
            'dental_chair_id'   => 'nullable|exists:dental_chairs,id',
            'status'            => 'sometimes|in:pending,in_treatment,completed,cancelled',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $registration->update($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Đã cập nhật đăng ký.');
    }

    public function destroy(Request $request, ScheduleRegistration $registration): RedirectResponse
    {
        $this->authorize('appointments.create');

        $registration->delete();

        return back()->with('success', 'Đã xóa đăng ký khám.');
    }

    private function dto(ScheduleRegistration $r): array
    {
        return [
            'id'                => $r->id,
            'code'              => $r->code,
            'patient'           => $r->patient?->full_name ?? '—',
            'patient_id'        => $r->patient_id,
            'patient_phone'     => $r->patient?->phone ?? null,
            'doctor'            => $r->doctor?->full_name ?? '—',
            'doctor_id'         => $r->doctor_id,
            'chair'             => $r->chair?->name ?? '—',
            'dental_chair_id'   => $r->dental_chair_id,
            'registration_date' => $r->registration_date->format('Y-m-d'),
            'visit_time'        => $r->visit_time ? substr($r->visit_time, 0, 5) : null,
            'status'            => $r->status,
            'status_label'      => $r->statusLabel(),
            'status_color'      => $r->statusColor(),
            'pending_since'     => $r->pending_since?->toIso8601String(),
            'notes'             => $r->notes,
        ];
    }
}
