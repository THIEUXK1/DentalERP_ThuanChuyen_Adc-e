<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('branches.view');

        $query = Branch::with('manager')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('code', 'ilike', "%{$v}%"))
            ->orderByDesc('id');

        return Inertia::render('Core/Branches/Index', [
            'branches' => $query->paginate(20)->through(fn ($b) => [
                'id' => $b->id,
                'code' => $b->code,
                'name' => $b->name,
                'phone' => $b->phone,
                'address' => $b->address,
                'is_active' => $b->is_active,
                'manager' => $b->manager?->name,
            ]),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('branches.manage');

        return Inertia::render('Core/Branches/Form', [
            'managers' => User::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        Branch::createWithCode($data);

        return redirect()->route('branches.index')->with('success', 'Đã tạo chi nhánh.');
    }

    public function edit(Branch $branch): Response
    {
        $this->authorize('branches.manage');

        return Inertia::render('Core/Branches/Form', [
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'manager_id' => $branch->manager_id,
                'is_active' => $branch->is_active,
            ],
            'managers' => User::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('branches.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $branch->update($data);

        return redirect()->route('branches.index')->with('success', 'Đã cập nhật chi nhánh.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('branches.manage');
        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Đã xóa chi nhánh.');
    }
}
