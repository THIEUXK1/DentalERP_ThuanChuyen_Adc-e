<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('admin.audit_log');

        $query = Activity::with(['causer', 'subject'])
            ->when($request->search, fn ($q, $v) => $q->whereHas('causer', fn ($cq) => $cq->where('name', 'ilike', "%{$v}%")))
            ->when($request->subject_type, fn ($q, $v) => $q->where('subject_type', 'like', "%{$v}%"))
            ->when($request->date, fn ($q, $v) => $q->whereDate('created_at', $v))
            ->orderByDesc('id');

        $subjectTypes = Activity::distinct()->pluck('subject_type')->filter()
            ->map(fn ($t) => class_basename($t))->unique()->values();

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $query->paginate(30)->through(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'event' => $a->event,
                'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->name ?? 'System',
                'properties' => $a->properties->toArray(),
                'created_at' => $a->created_at->format('d/m/Y H:i:s'),
            ]),
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['search', 'subject_type', 'date']),
        ]);
    }
}
