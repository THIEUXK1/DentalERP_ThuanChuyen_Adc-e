<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\FollowUpTask;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $branchId = null;

        // Non-owner roles are scoped to their branch
        if (! $user->hasRole(['owner', 'admin']) && $user->branch_id) {
            $branchId = $user->branch_id;
        }
        if ($request->branch_id && $user->hasRole(['owner', 'admin'])) {
            $branchId = $request->branch_id;
        }

        $kpis = $this->reports->dashboardKpis($branchId);
        $canFinancial = $user->can('reports.financial');
        $canClinical = $user->can('reports.clinical');

        $from = now()->subDays(29)->toDateString();
        $to = now()->toDateString();

        $today = today('Asia/Ho_Chi_Minh');

        $todaySchedule = Appointment::with(['patient', 'doctor'])
            ->whereDate('scheduled_at', $today)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'patient'      => $a->patient->full_name ?? '—',
                'patient_id'   => $a->patient_id,
                'doctor'       => $a->doctor?->full_name ?? '—',
                'time'         => $a->scheduled_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i'),
                'status'       => $a->status->value,
                'status_label' => $a->status->label(),
                'status_color' => $a->status->color(),
            ]);

        $pendingTasksCount = FollowUpTask::where('due_date', '<=', $today)
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Dashboard', [
            'kpis'                   => $kpis,
            'revenueTrend'           => $canFinancial ? $this->reports->revenueTrend($from, $to, $branchId) : [],
            'apptBreakdown'          => $canClinical ? $this->reports->appointmentBreakdown($branchId) : [],
            'leadFunnel'             => $this->reports->leadFunnel($from, $to),
            'treatmentPlanConversion'=> $this->reports->treatmentPlanConversion($branchId),
            'revenueByDoctor'        => $canFinancial ? $this->reports->revenueByDoctor($from, $to, $branchId) : [],
            'revenueByService'       => $canFinancial ? $this->reports->revenueByService($from, $to, $branchId) : [],
            'todaySchedule'          => $todaySchedule,
            'pendingTasksCount'      => $pendingTasksCount,
            'branches'               => $user->hasRole(['owner', 'admin'])
                ? Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
                : [],
            'canFinancial'  => $canFinancial,
            'canClinical'   => $canClinical,
            'selectedBranch'=> $branchId,
        ]);
    }
}
