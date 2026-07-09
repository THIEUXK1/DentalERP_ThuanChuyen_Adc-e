<?php

namespace App\Http\Controllers\Hr;

use App\Enums\ContractType;
use App\Enums\DentalRole;
use App\Enums\EmploymentStatus;
use App\Enums\RoleType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
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

        $query = Employee::with(['branch', 'department'])
            ->when($request->search, fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('full_name', 'ilike', "%{$v}%")
                  ->orWhere('code', 'ilike', "%{$v}%")
                  ->orWhere('position', 'ilike', "%{$v}%")
                  ->orWhere('phone', 'ilike', "%{$v}%");
            }))
            ->when($request->employment_status, fn ($q, $v) => $q->where('employment_status', $v))
            ->when($request->department_id, fn ($q, $v) => $q->where('department_id', $v))
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->orderByDesc('is_active')
            ->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 50);
        $perPage = in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 50;

        return Inertia::render('Hr/Employees/Index', [
            'employees' => $query->paginate($perPage)->withQueryString()->through(fn ($e) => [
                'id'                => $e->id,
                'code'              => $e->code,
                'full_name'         => $e->full_name,
                'position'          => $e->position,
                'phone'             => $e->phone,
                'department'        => $e->department?->name,
                'branch'            => $e->branch->name,
                'start_date'        => $e->start_date?->format('d/m/Y'),
                'contract_type'     => $e->contract_type?->value,
                'contract_label'    => $e->contract_type?->label(),
                'role_type'         => $e->role_type->value,
                'role_label'        => $e->role_type->label(),
                'employment_status' => $e->employment_status?->value ?? 'active',
                'status_label'      => $e->employment_status?->label() ?? 'Đang làm',
                'status_color'      => $e->employment_status?->color() ?? 'green',
                'is_active'         => $e->is_active,
            ]),
            'branches'    => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses'    => collect(EmploymentStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
            'filters' => $request->only(['search', 'employment_status', 'department_id', 'branch_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('employees.manage');

        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $this->validated($request);

        Employee::create([...$data, 'code' => Employee::generateCode()]);

        return redirect()->route('employees.index')->with('success', 'Đã tạo hồ sơ nhân viên.');
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('employees.view');

        $employee->load(['branch', 'department', 'directManager', 'user']);

        return Inertia::render('Hr/Employees/Show', [
            'employee' => $this->toDto($employee),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('employees.manage');

        return $this->form($employee);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.manage');

        $data = $this->validated($request, $employee->id);

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Đã cập nhật hồ sơ nhân viên.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.manage');
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Đã xóa nhân viên.');
    }

    public function toggleActive(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.manage');

        $employee->update(['is_active' => ! $employee->is_active]);

        $message = $employee->is_active ? 'Đã kích hoạt nhân viên.' : 'Đã ngừng hoạt động nhân viên.';

        return back()->with('success', $message);
    }

    private function form(?Employee $employee = null): Response
    {
        $contractTypes   = collect(ContractType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]);
        $employmentStatuses = collect(EmploymentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]);
        $roleTypes       = collect(RoleType::cases())->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()]);
        $dentalRoles     = collect(DentalRole::cases())->map(fn ($d) => ['value' => $d->value, 'label' => $d->label()]);
        $managers        = Employee::where('employment_status', EmploymentStatus::Active->value)
            ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'position']);

        return Inertia::render('Hr/Employees/Form', [
            'employee'          => $employee ? $this->toDto($employee) : null,
            'branches'          => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments'       => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users'             => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
            'managers'          => $managers->map(fn ($m) => ['id' => $m->id, 'full_name' => $m->full_name, 'position' => $m->position]),
            'contractTypes'     => $contractTypes,
            'employmentStatuses' => $employmentStatuses,
            'roleTypes'         => $roleTypes,
            'dentalRoles'       => $dentalRoles,
        ]);
    }

    private function toDto(Employee $e): array
    {
        return [
            'id'                        => $e->id,
            'code'                      => $e->code,
            'user_id'                   => $e->user_id,
            'branch_id'                 => $e->branch_id,
            'department_id'             => $e->department_id,
            'full_name'                 => $e->full_name,
            'phone'                     => $e->phone,
            'email'                     => $e->email,
            'date_of_birth'             => $e->date_of_birth?->format('Y-m-d'),
            'gender'                    => $e->gender,
            'position'                  => $e->position,
            'role_type'                 => $e->role_type->value,
            'role_label'                => $e->role_type->label(),
            'specialization'            => $e->specialization,
            'license_number'            => $e->license_number,
            'is_active'                 => $e->is_active,
            // Contract
            'start_date'                => $e->start_date?->format('Y-m-d'),
            'contract_type'             => $e->contract_type?->value,
            'contract_label'            => $e->contract_type?->label(),
            'employment_status'         => $e->employment_status?->value ?? 'active',
            'status_label'              => $e->employment_status?->label() ?? 'Đang làm',
            'status_color'              => $e->employment_status?->color() ?? 'green',
            // Salary
            'base_salary'               => $e->base_salary,
            'social_insurance_enabled'  => $e->social_insurance_enabled,
            'dependents_count'          => $e->dependents_count,
            'personal_tax_code'         => $e->personal_tax_code,
            'standard_working_days'     => $e->standard_working_days,
            // Allowances
            'responsibility_allowance'  => $e->responsibility_allowance,
            'fixed_allowance'           => $e->fixed_allowance,
            'lunch_allowance'           => $e->lunch_allowance,
            'travel_allowance'          => $e->travel_allowance,
            'phone_allowance'           => $e->phone_allowance,
            // Computed
            'gross_income'              => $e->grossIncome(),
            'total_allowances'          => $e->totalAllowances(),
            // Dental professional
            'dental_role'               => $e->dental_role?->value,
            'dental_role_label'         => $e->dental_role?->label(),
            'dental_specialty'          => $e->dental_specialty,
            'practice_certificate'      => $e->practice_certificate,
            'years_of_experience'       => $e->years_of_experience,
            'dentist_license_code'      => $e->dentist_license_code,
            'xray_scan_skill'           => $e->xray_scan_skill,
            'clinical_permission'       => $e->clinical_permission,
            'work_schedule'             => $e->work_schedule,
            'assigned_chair_room'       => $e->assigned_chair_room,
            'default_kpi_rate'          => $e->default_kpi_rate,
            'support_step_rate'         => $e->support_step_rate,
            'direct_manager_id'         => $e->direct_manager_id,
            'direct_manager_name'       => $e->directManager?->full_name,
            // Location
            'permanent_address'         => $e->permanent_address,
            'notes'                     => $e->notes,
            // Relations
            'branch_name'               => $e->branch?->name,
            'department_name'           => $e->department?->name,
            'user_name'                 => $e->user?->name,
            'user_email'                => $e->user?->email,
        ];
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $contractValues = implode(',', array_column(ContractType::cases(), 'value'));
        $statusValues   = implode(',', array_column(EmploymentStatus::cases(), 'value'));
        $roleValues     = implode(',', array_column(RoleType::cases(), 'value'));
        $dentalValues   = implode(',', array_column(DentalRole::cases(), 'value'));

        return $request->validate([
            'branch_id'                 => 'required|exists:branches,id',
            'department_id'             => 'nullable|exists:departments,id',
            'full_name'                 => 'required|string|max:255',
            'phone'                     => 'nullable|string|max:20',
            'email'                     => 'nullable|email|max:255',
            'date_of_birth'             => 'nullable|date',
            'gender'                    => 'nullable|in:male,female,other',
            'position'                  => 'nullable|string|max:255',
            'role_type'                 => "required|in:{$roleValues}",
            'specialization'            => 'nullable|string|max:255',
            'license_number'            => 'nullable|string|max:100',
            'user_id'                   => "nullable|exists:users,id|unique:employees,user_id,{$ignoreId}",
            'is_active'                 => 'boolean',
            // Contract
            'start_date'                => 'nullable|date',
            'contract_type'             => "nullable|in:{$contractValues}",
            'employment_status'         => "nullable|in:{$statusValues}",
            // Salary
            'base_salary'               => 'nullable|integer|min:0',
            'social_insurance_enabled'  => 'boolean',
            'dependents_count'          => 'nullable|integer|min:0',
            'personal_tax_code'         => 'nullable|string|regex:/^\d{10}(\d{3})?$/',
            'standard_working_days'     => 'nullable|integer|min:1|max:31',
            // Allowances
            'responsibility_allowance'  => 'nullable|integer|min:0',
            'fixed_allowance'           => 'nullable|integer|min:0',
            'lunch_allowance'           => 'nullable|integer|min:0',
            'travel_allowance'          => 'nullable|integer|min:0',
            'phone_allowance'           => 'nullable|integer|min:0',
            // Dental
            'dental_role'               => "nullable|in:{$dentalValues}",
            'dental_specialty'          => 'nullable|string|max:100',
            'practice_certificate'      => 'nullable|string|max:255',
            'years_of_experience'       => 'nullable|integer|min:0|max:60',
            'dentist_license_code'      => 'nullable|string|max:100',
            'xray_scan_skill'           => 'nullable|in:none,basic,proficient,specialist',
            'clinical_permission'       => 'nullable|in:none,assistant,associate_doctor,primary_doctor,head_doctor,xray_tech',
            'work_schedule'             => 'nullable|string|max:50',
            'assigned_chair_room'       => 'nullable|string|max:255',
            'default_kpi_rate'          => 'nullable|numeric|min:0|max:100',
            'support_step_rate'         => 'nullable|numeric|min:0|max:100',
            'direct_manager_id'         => 'nullable|exists:employees,id',
            // Location
            'permanent_address'         => 'nullable|string|max:500',
            'notes'                     => 'nullable|string|max:2000',
        ]);
    }
}
