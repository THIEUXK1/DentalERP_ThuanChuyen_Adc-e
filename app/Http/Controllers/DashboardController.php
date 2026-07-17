<?php

namespace App\Http\Controllers;

use App\Exports\DashboardReportExport;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\FollowUpTask;
use App\Models\PatientPayment;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', $this->buildDashboardData($request));
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $data = $this->normalizeEnumLabels($this->buildDashboardData($request));
        $pdf = Pdf::loadView('pdf.dashboard-report', $data)->setPaper('a4', 'portrait');

        return $pdf->download("bao-cao-tong-quan-{$data['selectedDate']}.pdf");
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->normalizeEnumLabels($this->buildDashboardData($request));

        return Excel::download(new DashboardReportExport($data), "bao-cao-tong-quan-{$data['selectedDate']}.xlsx");
    }

    /**
     * Inertia::render() JSON-encodes props, which auto-unwraps backed enums (status, method)
     * to their scalar value — the Vue side then maps that value to a Vietnamese label itself.
     * The PDF/Excel exports consume the same arrays directly in PHP though, so the enum
     * objects need converting to their label here instead.
     */
    private function normalizeEnumLabels(array $data): array
    {
        $label = fn ($status) => is_object($status) && method_exists($status, 'label') ? $status->label() : (string) $status;

        $data['apptBreakdown'] = array_map(fn ($r) => ['status' => $label($r['status']), 'count' => $r['count']], $data['apptBreakdown']);
        $data['leadFunnel'] = array_map(fn ($r) => ['status' => $label($r['status']), 'count' => $r['count']], $data['leadFunnel']);

        return $data;
    }

    private function buildDashboardData(Request $request): array
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

        // The "today's" cards (appointments/revenue/payments) can look back at a past day;
        // everything else on the dashboard stays anchored to the real current date.
        $selectedDate = $request->filled('date')
            ? \Carbon\Carbon::parse($request->date, 'Asia/Ho_Chi_Minh')->startOfDay()
            : today('Asia/Ho_Chi_Minh');

        $kpis = $this->reports->dashboardKpis($branchId, $selectedDate);
        $canFinancial = $user->can('reports.financial');
        $canClinical = $user->can('reports.clinical');

        $from = now()->subDays(29)->toDateString();
        $to = now()->toDateString();

        $today = today('Asia/Ho_Chi_Minh');

        $todaySchedule = Appointment::with(['patient', 'doctor'])
            ->whereDate('scheduled_at', $selectedDate)
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

        // Lịch sử thanh toán — lets staff cross-check "Doanh thu hôm nay" against the actual entries.
        $todayPayments = $canFinancial
            ? PatientPayment::with(['invoice.patient', 'creator'])
                ->whereDate('payment_date', $selectedDate)
                ->when($branchId, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('branch_id', $branchId)))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($p) => [
                    'id'           => $p->id,
                    'time'         => $p->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i'),
                    'patient'      => $p->invoice?->patient?->full_name ?? '—',
                    'patient_id'   => $p->invoice?->patient?->id,
                    'invoice_id'   => $p->invoice_id,
                    'invoice_code' => $p->invoice?->code,
                    'amount'       => $p->amount,
                    'method'       => $p->method->value,
                    'method_label' => $p->method->label(),
                    'method_color' => $p->method->color(),
                    'creator'      => $p->creator?->name,
                ])
            : collect();

        $branchName = $branchId
            ? Branch::find($branchId)?->name ?? 'Tất cả chi nhánh'
            : 'Tất cả chi nhánh';

        return [
            'kpis'                   => $kpis,
            'revenueTrend'           => $canFinancial ? $this->reports->revenueTrend($from, $to, $branchId) : [],
            'apptBreakdown'          => $canClinical ? $this->reports->appointmentBreakdown($branchId) : [],
            'leadFunnel'             => $this->reports->leadFunnel($from, $to),
            'treatmentPlanConversion'=> $this->reports->treatmentPlanConversion($branchId),
            'revenueByDoctor'        => $canFinancial ? $this->reports->revenueByDoctor($from, $to, $branchId) : [],
            'revenueByService'       => $canFinancial ? $this->reports->revenueByService($from, $to, $branchId) : [],
            'todaySchedule'          => $todaySchedule,
            'todayPayments'          => $todayPayments,
            'pendingTasksCount'      => $pendingTasksCount,
            'branches'               => $user->hasRole(['owner', 'admin'])
                ? Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
                : [],
            'branchName'    => $branchName,
            'canFinancial'  => $canFinancial,
            'canClinical'   => $canClinical,
            'selectedBranch'=> $branchId,
            'selectedDate'  => $selectedDate->toDateString(),
            'isToday'       => $selectedDate->isSameDay($today),
        ];
    }
}
