<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('expenses.view');

        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $branchId = $request->branch_id;
        $category = $request->category;

        $expenses = Expense::with('creator')
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'category' => $e->category->value,
                'category_label' => $e->category->label(),
                'category_color' => $e->category->color(),
                'description' => $e->description,
                'amount' => $e->amount,
                'expense_date' => $e->expense_date->format('d/m/Y'),
                'payment_method' => $e->payment_method?->label(),
                'notes' => $e->notes,
                'creator' => $e->creator->name ?? '—',
            ]);

        $totalAmount = $expenses->sum('amount');

        $byCategory = $expenses->groupBy('category')
            ->map(fn ($g) => ['label' => $g->first()['category_label'], 'total' => $g->sum('amount'), 'color' => $g->first()['category_color']])
            ->values();

        return Inertia::render('Cashier/Expenses/Index', [
            'expenses' => $expenses,
            'totalAmount' => $totalAmount,
            'byCategory' => $byCategory,
            'categories' => collect(ExpenseCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
            'branches' => Branch::where('is_active', true)->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'filters' => compact('from', 'to', 'branchId', 'category'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('expenses.manage');

        $data = $request->validate([
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'description' => 'required|string|max:500',
            'amount' => 'required|integer|min:1000',
            'expense_date' => 'required|date',
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        Expense::create([...$data, 'created_by' => auth()->id()]);

        return back()->with('success', 'Đã ghi nhận phiếu chi.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('expenses.manage');
        $expense->delete();

        return back()->with('success', 'Đã xóa phiếu chi.');
    }
}
