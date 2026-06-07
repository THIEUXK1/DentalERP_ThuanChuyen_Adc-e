<?php

namespace App\Http\Controllers\Hr;

use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('employees.view');

        $query = Employee::with(['user', 'branch'])
            ->when($request->search, fn ($q, $v) => $q->where('full_name', 'ilike', "%{$v}%")->orWhere('code', 'ilike', "%{$v}%"))
            ->when($request->role_type, fn ($q, $v) => $q->where('role_type', $v))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('id');

        return Inertia::render('Hr/Employees/Index', [
            'employees' => $query->paginate(20)->through(fn ($e) => [
                'id' => $e->id,
                'code' => $e->code,
                'full_name' => $e->full_name,
                'phone' => $e->phone,
                'role_type' => $e->role_type->value,
                'role_label' => $e->role_type->label(),
                'role_color' => $e->role_type->color(),
                'branch' => $e->branch->name,
                'is_active' => $e->is_active,
            ]),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'role_types' => collect(RoleType::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => $r->label(),
                'color' => $r->color(),
            ]),
            'filters' => $request->only(['search', 'role_type', 'branch_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('employees.create');

        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.create');

        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role_type' => 'required|in:'.implode(',', array_column(RoleType::cases(), 'value')),
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
            'is_active' => 'boolean',
        ]);

        Employee::create([...$data, 'code' => Employee::generateCode()]);

        return redirect()->route('employees.index')->with('success', 'Đã tạo nhân viên.');
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('employees.manage');

        return $this->form($employee);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role_type' => 'required|in:'.implode(',', array_column(RoleType::cases(), 'value')),
            'specialization' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'user_id' => "nullable|exists:users,id|unique:employees,user_id,{$employee->id}",
            'is_active' => 'boolean',
        ]);

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'Đã cập nhật nhân viên.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.manage');
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Đã xóa nhân viên.');
    }

    private function form(?Employee $employee = null): Response
    {
        return Inertia::render('Hr/Employees/Form', [
            'employee' => $employee ? [
                'id' => $employee->id,
                'code' => $employee->code,
                'branch_id' => $employee->branch_id,
                'full_name' => $employee->full_name,
                'phone' => $employee->phone,
                'role_type' => $employee->role_type->value,
                'specialization' => $employee->specialization,
                'license_number' => $employee->license_number,
                'user_id' => $employee->user_id,
                'is_active' => $employee->is_active,
            ] : null,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name]),
            'users' => User::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
            'role_types' => collect(RoleType::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]),
        ]);
    }
}
