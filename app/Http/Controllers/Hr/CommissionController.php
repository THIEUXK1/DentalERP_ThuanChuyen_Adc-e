<?php

namespace App\Http\Controllers\Hr;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Http\Controllers\Controller;
use App\Models\CommissionRule;
use App\Models\CommissionTransaction;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('commissions.view');

        $period = $request->period ?? now()->format('Y-m');
        $employeeId = $request->employee_id;
        $status = $request->status;

        $transactions = CommissionTransaction::with(['employee', 'invoice', 'treatmentPlan.patient'])
            ->where('period', $period)
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'employee_name' => $t->employee->full_name,
                'patient_name' => $t->treatmentPlan?->patient?->full_name ?? '—',
                'invoice_code' => $t->invoice->code,
                'invoice_total' => $t->invoice->total,
                'amount' => $t->amount,
                'period' => $t->period,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'status_color' => $t->status->color(),
            ]);

        // Summary for the period
        $summary = CommissionTransaction::where('period', $period)
            ->selectRaw('status, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $employees = Employee::where('is_active', true)->orderBy('full_name')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name]);

        return Inertia::render('Hr/Commissions/Index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'employees' => $employees,
            'statuses' => collect(CommissionStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'filters' => compact('period', 'employeeId', 'status'),
        ]);
    }

    public function approve(CommissionTransaction $transaction): RedirectResponse
    {
        $this->authorize('commissions.manage');

        if ($transaction->status !== CommissionStatus::Pending) {
            return back()->with('error', 'Chỉ có thể duyệt hoa hồng đang chờ.');
        }

        $transaction->update(['status' => CommissionStatus::Approved]);

        return back()->with('success', 'Đã duyệt hoa hồng.');
    }

    public function markPaid(CommissionTransaction $transaction): RedirectResponse
    {
        $this->authorize('commissions.manage');

        if ($transaction->status !== CommissionStatus::Approved) {
            return back()->with('error', 'Chỉ có thể thanh toán hoa hồng đã duyệt.');
        }

        $transaction->update(['status' => CommissionStatus::Paid]);

        return back()->with('success', 'Đã đánh dấu đã trả.');
    }

    // ── Commission rules management ──────────────────────────────────────────

    public function rules(Request $request): Response
    {
        $this->authorize('commissions.manage');

        $rules = CommissionRule::with('employee')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'employee_id' => $r->employee_id,
                'employee_name' => $r->employee->full_name,
                'type' => $r->type->value,
                'type_label' => $r->type->label(),
                'value' => $r->value,
                'is_active' => $r->is_active,
                'notes' => $r->notes,
            ]);

        $employees = Employee::where('is_active', true)->orderBy('full_name')->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->full_name, 'role' => $e->role_type->label()]);

        return Inertia::render('Hr/Commissions/Rules', [
            'rules' => $rules,
            'employees' => $employees,
            'types' => collect(CommissionType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $this->authorize('commissions.manage');

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:revenue_percentage,fixed_per_case',
            'value' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        CommissionRule::create([...$data, 'is_active' => true]);

        return back()->with('success', 'Đã tạo quy tắc hoa hồng.');
    }

    public function destroyRule(CommissionRule $rule): RedirectResponse
    {
        $this->authorize('commissions.manage');
        $rule->delete();

        return back()->with('success', 'Đã xóa quy tắc.');
    }
}
