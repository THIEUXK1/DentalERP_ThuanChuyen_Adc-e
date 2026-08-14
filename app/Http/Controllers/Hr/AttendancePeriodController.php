<?php

namespace App\Http\Controllers\Hr;

use App\Enums\AttendancePeriodStatus;
use App\Exports\AttendanceSheetExport;
use App\Http\Controllers\Controller;
use App\Models\AttendancePeriod;
use App\Models\AttendanceSymbol;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendancePeriodService;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendancePeriodController extends Controller
{
    public function __construct(
        private AttendancePeriodService $periodService,
        private AttendanceSummaryService $summaryService,
    ) {}

    public function index(): Response
    {
        $this->authorize('employees.manage');

        $periods = AttendancePeriod::with('creator')
            ->orderByDesc('year')->orderByDesc('month')
            ->get()
            ->map(fn ($p) => $this->listDto($p));

        return Inertia::render('Hr/Attendance/Index', [
            'periods'  => $periods,
            'statuses' => collect(AttendancePeriodStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020|max:2099',
            'note'  => 'nullable|string|max:500',
        ]);

        try {
            $period = $this->periodService->create((int) $data['month'], (int) $data['year'], $data['note'] ?? null, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('hr.attendance.show', $period)->with('success', "Đã tạo kỳ chấm công {$period->code}.");
    }

    public function show(AttendancePeriod $attendance): Response
    {
        $this->authorize('employees.manage');

        $attendance->load(['creator', 'locker', 'unlocker']);

        $employees = Employee::with('department')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn ($e) => [
                'id'         => $e->id,
                'code'       => $e->code,
                'full_name'  => $e->full_name,
                'position'   => $e->position ?? $e->role_type,
                'department' => $e->department?->name,
            ])->values()->all();

        $grid      = $this->summaryService->buildGrid($attendance);
        $summaries = $this->summaryService->summarize($attendance);

        return Inertia::render('Hr/Attendance/Show', [
            'period'    => $this->detailDto($attendance),
            'employees' => $employees,
            'grid'      => $grid,
            'summaries' => $summaries,
            'days'      => $this->buildDaysHeader($attendance),
            'symbols'   => AttendanceSymbol::activeMap(),
        ]);
    }

    public function lock(AttendancePeriod $attendance): RedirectResponse
    {
        $this->authorize('employees.manage');

        try {
            $this->periodService->lock($attendance, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã khóa bảng chấm công.');
    }

    public function unlock(AttendancePeriod $attendance, Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);

        try {
            $this->periodService->unlock($attendance, $data['reason'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã mở lại bảng chấm công.');
    }

    public function export(AttendancePeriod $attendance): BinaryFileResponse
    {
        $this->authorize('employees.manage');

        $employees = Employee::with('department')
            ->where('is_active', true)->orderBy('full_name')->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'code' => $e->code,
                'full_name' => $e->full_name,
                'position' => $e->position ?? '',
                'department' => $e->department?->name ?? '',
            ])
            ->values()->all();

        $filename = "bang-cham-cong-{$attendance->code}.xlsx";
        return Excel::download(new AttendanceSheetExport($attendance, $employees, $this->summaryService), $filename);
    }

    // ── DTOs ─────────────────────────────────────────────────────────────────

    private function listDto(AttendancePeriod $p): array
    {
        return [
            'id'           => $p->id,
            'code'         => $p->code,
            'month'        => $p->month,
            'year'         => $p->year,
            'period_label' => $p->periodLabel(),
            'status'       => $p->status->value,
            'status_label' => $p->status->label(),
            'status_color' => $p->status->color(),
            'created_by'   => $p->creator?->name,
            'created_at'   => $p->created_at?->format('d/m/Y H:i'),
            'locked_at'    => $p->locked_at?->format('d/m/Y H:i'),
        ];
    }

    private function detailDto(AttendancePeriod $p): array
    {
        return [
            ...$this->listDto($p),
            'days_in_month'  => $p->daysInMonth(),
            'locked_by_name' => $p->locker?->name,
            'unlocked_by_name' => $p->unlocker?->name,
            'unlock_reason'  => $p->unlock_reason,
            'note'           => $p->note,
        ];
    }

    private function buildDaysHeader(AttendancePeriod $p): array
    {
        $days    = [];
        $weekDay = ['', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $total   = $p->daysInMonth();

        for ($d = 1; $d <= $total; $d++) {
            $date      = \Carbon\Carbon::create($p->year, $p->month, $d);
            $wdIndex   = (int) $date->format('N');
            $days[]    = [
                'day'     => $d,
                'date'    => $date->format('Y-m-d'),
                'weekday' => $weekDay[$wdIndex],
                'is_sun'  => $wdIndex === 7,
                'is_sat'  => $wdIndex === 6,
            ];
        }

        return $days;
    }
}
