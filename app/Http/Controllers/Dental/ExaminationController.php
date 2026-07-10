<?php

namespace App\Http\Controllers\Dental;

use App\Enums\ExaminationStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DentalCondition;
use App\Models\DentalExamination;
use App\Models\DentalExaminationCondition;
use App\Models\Employee;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExaminationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('dental.view');

        $query = DentalExamination::with(['patient', 'doctor', 'branch'])
            ->orderByDesc('examined_at');

        if ($search = $request->search) {
            $query->whereHas('patient', fn ($q) => $q->where('full_name', 'ilike', "%$search%")
                ->orWhere('phone', 'ilike', "%$search%"));
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($doctorId = $request->doctor_id) {
            $query->where('doctor_id', $doctorId);
        }

        return Inertia::render('Dental/Examinations/Index', [
            'examinations' => $query->paginate(20)->through(fn ($e) => [
                'id'            => $e->id,
                'code'          => $e->code,
                'patient_name'  => $e->patient?->full_name,
                'patient_phone' => $e->patient?->phone,
                'doctor_name'   => $e->doctor?->full_name,
                'branch_name'   => $e->branch?->name,
                'chief_complaint' => $e->chief_complaint,
                'status'        => $e->status->value,
                'status_label'  => $e->status->label(),
                'status_color'  => $e->status->color(),
                'examined_at'   => $e->examined_at?->format('d/m/Y H:i'),
            ]),
            'doctors'  => Employee::where('is_active', true)->whereIn('role_type', ['doctor', 'specialist'])
                ->orderBy('full_name')->get(['id', 'full_name']),
            'statuses' => collect(ExaminationStatus::cases())->map(fn ($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
            'filters'  => $request->only(['search', 'status', 'doctor_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('dental.view');

        return Inertia::render('Dental/Examinations/Form', [
            'patients'    => Patient::where('is_active', true)->orderBy('full_name')
                ->get(['id', 'full_name', 'phone', 'code']),
            'doctors'     => Employee::where('is_active', true)->whereIn('role_type', ['doctor', 'specialist'])
                ->orderBy('full_name')->get(['id', 'full_name']),
            'consultants' => Employee::where('is_active', true)->whereIn('role_type', ['consultant', 'doctor', 'specialist'])
                ->orderBy('full_name')->get(['id', 'full_name']),
            'conditions'  => DentalCondition::where('is_active', true)->orderBy('group')->orderBy('name')
                ->get(['id', 'code', 'name', 'group']),
            'branches'    => Branch::where('is_active', true)->get(['id', 'name']),
            'patient_id'  => $request->patient_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('dental.view');

        $data = $request->validate([
            'patient_id'            => 'required|exists:patients,id',
            'appointment_id'        => 'nullable|exists:appointments,id',
            'branch_id'             => 'required|exists:branches,id',
            'doctor_id'             => 'required|exists:employees,id',
            'consultant_id'         => 'nullable|exists:employees,id',
            'chief_complaint'       => 'nullable|string|max:2000',
            'diagnosis_note'        => 'nullable|string|max:2000',
            'examination_note'      => 'nullable|string|max:2000',
            'recommended_plan_note' => 'nullable|string|max:2000',
            'examined_at'           => 'nullable|date',
            'conditions'            => 'nullable|array',
            'conditions.*.condition_id' => 'required|exists:dental_conditions,id',
            'conditions.*.tooth_no'     => 'nullable|string|max:10',
            'conditions.*.severity'     => 'nullable|string|max:20',
            'conditions.*.note'         => 'nullable|string|max:500',
        ]);

        $examination = DB::transaction(function () use ($data) {
            $exam = DentalExamination::createWithCode([
                ...$data,
                'status'      => ExaminationStatus::Draft->value,
                'examined_at' => $data['examined_at'] ?? now(),
                'created_by'  => auth()->id(),
            ]);

            foreach ($data['conditions'] ?? [] as $cond) {
                DentalExaminationCondition::create([
                    'examination_id' => $exam->id,
                    ...$cond,
                ]);
            }

            return $exam;
        });

        return redirect()->route('dental.examinations.show', $examination)
            ->with('success', 'Đã tạo phiếu khám.');
    }

    public function show(DentalExamination $examination): Response
    {
        $this->authorize('dental.view');

        $examination->load(['patient', 'doctor', 'consultant', 'branch',
            'conditions.condition', 'treatmentPlanItems.service', 'treatmentPlanItems.plan']);

        return Inertia::render('Dental/Examinations/Show', [
            'examination' => [
                'id'                    => $examination->id,
                'code'                  => $examination->code,
                'status'                => $examination->status->value,
                'status_label'          => $examination->status->label(),
                'status_color'          => $examination->status->color(),
                'patient'               => [
                    'id' => $examination->patient?->id, 'full_name' => $examination->patient?->full_name,
                    'phone' => $examination->patient?->phone, 'code' => $examination->patient?->code,
                ],
                'doctor'                => ['id' => $examination->doctor?->id, 'full_name' => $examination->doctor?->full_name],
                'consultant'            => ['id' => $examination->consultant?->id, 'full_name' => $examination->consultant?->full_name],
                'branch_name'           => $examination->branch?->name,
                'chief_complaint'       => $examination->chief_complaint,
                'diagnosis_note'        => $examination->diagnosis_note,
                'examination_note'      => $examination->examination_note,
                'recommended_plan_note' => $examination->recommended_plan_note,
                'examined_at'           => $examination->examined_at?->format('d/m/Y H:i'),
                'conditions'            => $examination->conditions->map(fn ($c) => [
                    'id'            => $c->id,
                    'condition_id'  => $c->condition_id,
                    'condition_name' => $c->condition?->name,
                    'group'         => $c->condition?->group?->value,
                    'tooth_no'      => $c->tooth_no,
                    'severity'      => $c->severity,
                    'note'          => $c->note,
                ]),
            ],
        ]);
    }

    public function complete(DentalExamination $examination): RedirectResponse
    {
        $this->authorize('dental.view');

        if ($examination->status !== ExaminationStatus::Draft) {
            return back()->withErrors(['status' => 'Phiếu khám đã được hoàn thành hoặc hủy.']);
        }

        $examination->update(['status' => ExaminationStatus::Completed->value]);

        return back()->with('success', 'Đã hoàn thành phiếu khám.');
    }

    public function destroy(DentalExamination $examination): RedirectResponse
    {
        $this->authorize('dental.manage');

        if ($examination->status !== ExaminationStatus::Draft) {
            return back()->withErrors(['error' => 'Chỉ có thể xóa phiếu khám nháp.']);
        }

        $examination->conditions()->delete();
        $examination->delete();

        return redirect()->route('dental.examinations.index')->with('success', 'Đã xóa phiếu khám.');
    }
}
