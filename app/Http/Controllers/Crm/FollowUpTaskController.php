<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\FollowUpTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpTaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('leads.view');

        $query = FollowUpTask::with(['lead', 'patient', 'assignee'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->user()->id && ! $request->user()->hasRole(['owner', 'admin', 'branch_manager']),
                fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->orderBy('due_date');

        return Inertia::render('Crm/Tasks/Index', [
            'tasks' => $query->paginate(30)->through(fn ($t) => [
                'id' => $t->id,
                'due_date' => $t->due_date->format('d/m/Y'),
                'overdue' => $t->due_date->isPast() && $t->status === TaskStatus::Pending,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'status_color' => $t->status->color(),
                'note' => $t->note,
                'assignee' => $t->assignee->name,
                'subject' => $t->lead ? "Lead: {$t->lead->name}" : "BN: {$t->patient?->full_name}",
                'subject_url' => $t->lead_id
                    ? route('crm.leads.show', $t->lead_id)
                    : route('patients.show', $t->patient_id),
            ]),
            'statuses' => collect(TaskStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leads.view');

        $data = $request->validate([
            'lead_id' => 'nullable|exists:leads,id',
            'patient_id' => 'nullable|exists:patients,id',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date',
            'note' => 'nullable|string|max:500',
        ]);

        FollowUpTask::create([
            ...$data,
            'status' => TaskStatus::Pending->value,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Đã tạo task follow-up.');
    }

    public function complete(FollowUpTask $task): RedirectResponse
    {
        $this->authorize('leads.view');
        $task->update(['status' => TaskStatus::Done->value]);

        return back()->with('success', 'Đã đánh dấu hoàn thành.');
    }
}
