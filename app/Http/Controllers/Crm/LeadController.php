<?php

namespace App\Http\Controllers\Crm;

use App\Enums\ContactType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(private LeadService $leadService) {}

    public function index(Request $request): Response
    {
        $this->authorize('leads.view');

        $query = Lead::with(['assignedTo', 'branch'])
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('phone', 'ilike', "%{$v}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->source, fn ($q, $v) => $q->where('source', $v))
            ->when($request->assigned_to, fn ($q, $v) => $q->where('assigned_to', $v))
            ->orderByDesc('id');

        return Inertia::render('Crm/Leads/Index', [
            'leads' => $query->paginate(20)->through(fn ($l) => [
                'id' => $l->id,
                'code' => $l->code,
                'name' => $l->name,
                'phone' => $l->phone,
                'source' => $l->source?->value,
                'source_label' => $l->source?->label(),
                'source_color' => $l->source?->color(),
                'status' => $l->status->value,
                'status_label' => $l->status->label(),
                'status_color' => $l->status->color(),
                'assigned_to' => $l->assignedTo?->name,
                'branch' => $l->branch?->name,
                'converted' => (bool) $l->converted_patient_id,
                'created_at' => $l->created_at->format('d/m/Y'),
            ]),
            'statuses' => collect(LeadStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'assignees' => User::where('is_active', true)->orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'filters' => $request->only(['search', 'status', 'source', 'assigned_to']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('leads.create');

        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leads.create');

        $data = $this->validated($request);
        Lead::createWithCode([...$data, 'status' => LeadStatus::New->value]);

        return redirect()->route('crm.leads.index')->with('success', 'Đã tạo lead.');
    }

    public function show(Lead $lead): Response
    {
        $this->authorize('leads.view');

        $lead->load(['assignedTo', 'branch', 'convertedPatient',
            'contactActivities.creator', 'followUpTasks.assignee']);

        $allowedTransitions = $lead->status->allowedTransitions();

        return Inertia::render('Crm/Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'code' => $lead->code,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'source' => $lead->source?->value,
                'source_label' => $lead->source?->label(),
                'status' => $lead->status->value,
                'status_label' => $lead->status->label(),
                'status_color' => $lead->status->color(),
                'assigned_to' => $lead->assigned_to,
                'assignee_name' => $lead->assignedTo?->name,
                'branch' => $lead->branch?->name,
                'interest' => $lead->interest,
                'notes' => $lead->notes,
                'converted' => (bool) $lead->converted_patient_id,
                'patient_code' => $lead->convertedPatient?->code,
                'patient_id' => $lead->converted_patient_id,
            ],
            'activities' => $lead->contactActivities->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type->value,
                'type_label' => $a->type->label(),
                'type_color' => $a->type->color(),
                'content' => $a->content,
                'creator' => $a->creator->name,
                'created_at' => $a->created_at->format('d/m/Y H:i'),
            ]),
            'tasks' => $lead->followUpTasks->map(fn ($t) => [
                'id' => $t->id,
                'due_date' => $t->due_date->format('d/m/Y'),
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'status_color' => $t->status->color(),
                'note' => $t->note,
                'assignee' => $t->assignee->name,
            ]),
            'transitions' => collect($allowedTransitions)->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'contactTypes' => collect(ContactType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'assignees' => User::where('is_active', true)->orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function edit(Lead $lead): Response
    {
        $this->authorize('leads.manage');

        return $this->form($lead);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $this->validated($request, $lead->id);
        $lead->update($data);

        return redirect()->route('crm.leads.show', $lead)->with('success', 'Đã cập nhật lead.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('leads.manage');
        $lead->delete();

        return redirect()->route('crm.leads.index')->with('success', 'Đã xóa lead.');
    }

    public function transition(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $request->validate(['status' => 'required|string']);
        $to = LeadStatus::from($data['status']);

        try {
            $this->leadService->transition($lead, $to);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã cập nhật trạng thái.');
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('leads.assign');

        $data = $request->validate(['assigned_to' => 'required|exists:users,id']);
        $lead->update(['assigned_to' => $data['assigned_to']]);

        return back()->with('success', 'Đã gán lead.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('leads.manage');

        try {
            $patient = $this->leadService->convertToPatient($lead, $request->only('full_name', 'notes'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('patients.show', $patient)->with('success', "Đã chuyển thành bệnh nhân {$patient->code}.");
    }

    private function form(?Lead $lead = null): Response
    {
        return Inertia::render('Crm/Leads/Form', [
            'lead' => $lead ? [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'source' => $lead->source?->value,
                'assigned_to' => $lead->assigned_to,
                'branch_id' => $lead->branch_id,
                'interest' => $lead->interest,
                'notes' => $lead->notes,
            ] : null,
            'sources' => collect(LeadSource::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'assignees' => User::where('is_active', true)->orderBy('name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
        ]);
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'branch_id' => 'nullable|exists:branches,id',
            'interest' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
    }
}
