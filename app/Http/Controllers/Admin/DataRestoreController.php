<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * A central "restore" view over the Spatie activity log: every tracked create/update/delete
 * already carries an old/new attribute snapshot, so instead of building a second logging
 * system we let admins revert straight from that history. Only the most recent log row per
 * subject is restorable, since reverting an older one would silently clobber whatever
 * happened to the record afterwards.
 */
class DataRestoreController extends Controller
{
    /** Only these logged models may be restored — guards against trusting an arbitrary class name from the DB. */
    private const RESTORABLE_MODELS = [
        \App\Models\Branch::class,
        \App\Models\PriceList::class,
        \App\Models\DentalExamination::class,
        \App\Models\DentalService::class,
        \App\Models\Lead::class,
        \App\Models\Employee::class,
        \App\Models\Appointment::class,
        \App\Models\PatientInvoice::class,
        \App\Models\Patient::class,
        \App\Models\TreatmentPlan::class,
        \App\Models\KpiAllocation::class,
        \App\Models\DentalChair::class,
        \App\Models\User::class,
        \App\Models\TreatmentStepExecution::class,
        \App\Models\ClinicalNote::class,
    ];

    public function index(Request $request): Response
    {
        $this->authorize('admin.audit_log');

        // Latest activity id per (subject_type, subject_id) — only these rows get a restore action.
        // This has to scan every matching log row to find each group's MAX(id), so it's cached
        // briefly rather than recomputed on every page load; a minute of staleness just means a
        // just-edited record's restore button lags a minute behind, which is an acceptable trade
        // for not re-scanning an ever-growing activity_log table on every visit.
        $latestIds = Cache::remember('data-restore:latest-ids', 60, function () {
            return Activity::query()
                ->selectRaw('MAX(id) as id')
                ->whereIn('event', ['created', 'updated', 'deleted'])
                ->whereIn('subject_type', self::RESTORABLE_MODELS)
                ->whereNotNull('subject_id')
                ->groupBy('subject_type', 'subject_id')
                ->pluck('id');
        });

        $query = Activity::with(['causer', 'subject'])
            ->whereIn('event', ['created', 'updated', 'deleted'])
            ->whereIn('subject_type', self::RESTORABLE_MODELS)
            ->when($request->search, fn ($q, $v) => $q->whereHas('causer', fn ($cq) => $cq->where('name', 'ilike', "%{$v}%")))
            ->when($request->subject_type, fn ($q, $v) => $q->where('subject_type', 'like', "%{$v}%"))
            ->when($request->date, fn ($q, $v) => $q->whereDate('created_at', $v))
            ->orderByDesc('id');

        $subjectTypes = collect(self::RESTORABLE_MODELS)->map(fn ($c) => class_basename($c))->values();

        $activities = $query->paginate(30)->withQueryString();
        $activities->getCollection()->transform(function (Activity $a) use ($latestIds) {
            $old = $a->properties['old'] ?? null;
            $new = $a->properties['attributes'] ?? null;

            return [
                'id' => $a->id,
                'event' => $a->event,
                'subject_type' => class_basename($a->subject_type),
                'subject_id' => $a->subject_id,
                'causer' => $a->causer?->name ?? 'Hệ thống',
                'description' => $a->description,
                'created_at' => $a->created_at->format('d/m/Y H:i:s'),
                'diff' => $this->diff($a->event, $old, $new),
                'is_latest' => $latestIds->contains($a->id),
                'restore_label' => match ($a->event) {
                    'updated' => 'Hoàn tác về giá trị cũ',
                    'deleted' => $this->wasSoftDeleted($a) ? 'Khôi phục (đã xóa mềm)' : 'Tạo lại từ dữ liệu đã lưu',
                    'created' => 'Hủy bản ghi này',
                    default => null,
                },
            ];
        });

        return Inertia::render('Admin/DataRestore/Index', [
            'activities' => $activities,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['search', 'subject_type', 'date']),
        ]);
    }

    public function restore(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('admin.audit_log');
        abort_unless(auth()->user()->hasRole(['owner', 'admin']), 403, 'Chỉ chủ sở hữu/quản trị viên mới được khôi phục dữ liệu.');
        abort_unless(in_array($activity->subject_type, self::RESTORABLE_MODELS, true), 422, 'Loại dữ liệu này không hỗ trợ khôi phục.');

        $request->validate([
            'confirmed' => ['accepted'],
        ], [
            'confirmed.accepted' => 'Bạn phải xác nhận cam kết trước khi khôi phục.',
        ]);

        $modelClass = $activity->subject_type;
        $subjectId = $activity->subject_id;
        $old = $activity->properties['old'] ?? [];
        $user = auth()->user();

        try {
            DB::transaction(function () use ($activity, $modelClass, $subjectId, $old) {
                switch ($activity->event) {
                    case 'updated':
                        $model = $modelClass::find($subjectId);
                        abort_if(! $model, 422, 'Bản ghi không còn tồn tại, không thể hoàn tác.');
                        $model->forceFill($old)->save();
                        break;

                    case 'deleted':
                        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                            $model = $modelClass::withTrashed()->find($subjectId);
                            if ($model && $model->trashed()) {
                                $model->restore();
                                break;
                            }
                        }
                        // Hard-deleted (or the soft-deleted row is itself gone): best-effort recreate.
                        // This gets a new primary key — anything that referenced the old id won't relink automatically.
                        $modelClass::create(Arr::except($old, ['id', 'created_at', 'updated_at', 'deleted_at']));
                        break;

                    case 'created':
                        $model = $modelClass::find($subjectId);
                        if ($model) {
                            $model->delete();
                        }
                        break;
                }
            });
        } catch (\Throwable $e) {
            activity('data_restore')
                ->causedBy($user)
                ->withProperties([
                    'original_activity_id' => $activity->id,
                    'subject_type' => class_basename($modelClass),
                    'subject_id' => $subjectId,
                    'event' => $activity->event,
                    'result' => 'failed',
                    'error' => $e->getMessage(),
                ])
                ->log("Khôi phục thất bại: {$user->name} cố khôi phục ".class_basename($modelClass)." #{$subjectId}");

            return back()->with('error', 'Không thể khôi phục: '.$e->getMessage());
        }

        // Explicit, dedicated trail of who confirmed and executed each restore — kept
        // separate from the incidental "updated/restored" entry the reverted model itself
        // logs, so "who approved this recovery" is never ambiguous.
        activity('data_restore')
            ->causedBy($user)
            ->withProperties([
                'original_activity_id' => $activity->id,
                'subject_type' => class_basename($modelClass),
                'subject_id' => $subjectId,
                'event' => $activity->event,
                'result' => 'success',
            ])
            ->log("{$user->name} đã khôi phục ".class_basename($modelClass)." #{$subjectId}");

        // The restore just created new log rows, so the cached "latest per subject" set is
        // stale immediately — drop it now instead of waiting out the TTL.
        Cache::forget('data-restore:latest-ids');

        return back()->with('success', 'Đã khôi phục dữ liệu.');
    }

    private function wasSoftDeleted(Activity $activity): bool
    {
        $modelClass = $activity->subject_type;

        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }

    private function diff(string $event, ?array $old, ?array $new): array
    {
        if ($event === 'updated' && $old && $new) {
            $changed = [];
            foreach ($new as $key => $value) {
                $oldValue = $old[$key] ?? null;
                if ($oldValue !== $value) {
                    $changed[] = ['field' => $key, 'old' => $oldValue, 'new' => $value];
                }
            }

            return $changed;
        }

        // created: only the initial values exist ("new"); deleted: only the last-known values
        // exist ("old") — either way there's nothing to diff against, just show the snapshot.
        $snapshot = $new ?? $old ?? [];
        $side = $new !== null ? 'new' : 'old';

        return collect($snapshot)->map(fn ($value, $key) => ['field' => $key, $side => $value])->values()->all();
    }
}
