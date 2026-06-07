<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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

        return Inertia::render('Dashboard', [
            'kpis' => $kpis,
            'revenueTrend' => $canFinancial ? $this->reports->revenueTrend($from, $to, $branchId) : [],
            'apptBreakdown' => $canClinical ? $this->reports->appointmentBreakdown($branchId) : [],
            'leadFunnel' => $this->reports->leadFunnel($from, $to),
            'treatmentPlanConversion' => $this->reports->treatmentPlanConversion($branchId),
            'revenueByDoctor' => $canFinancial ? $this->reports->revenueByDoctor($from, $to, $branchId) : [],
            'revenueByService' => $canFinancial ? $this->reports->revenueByService($from, $to, $branchId) : [],
            'branches' => $user->hasRole(['owner', 'admin'])
                ? Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
                : [],
            'canFinancial' => $canFinancial,
            'canClinical' => $canClinical,
            'selectedBranch' => $branchId,
        ]);
    }
}
