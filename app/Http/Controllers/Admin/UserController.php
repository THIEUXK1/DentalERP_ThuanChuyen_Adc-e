<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('admin.users');

        $query = User::with('roles')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%")->orWhere('email', 'ilike', "%{$v}%"))
            ->when($request->role, fn ($q, $v) => $q->role($v))
            ->when($request->status, fn ($q, $v) => $v === 'active' ? $q->where('is_active', true) : $q->where('is_active', false))
            ->orderByDesc('id');

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate(20)->through(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => $u->is_active,
                'roles' => $u->roles->pluck('name'),
                'created_at' => $u->created_at->format('d/m/Y'),
            ]),
            'roles' => Role::orderBy('name')->get()->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('admin.users');

        return Inertia::render('Admin/Users/Form', [
            'roles' => Role::orderBy('name')->get()->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin.users');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->assignRole($data['role']);

        return redirect()->route('admin.users.index')->with('success', 'Đã tạo người dùng.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('admin.users');

        return Inertia::render('Admin/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
            ],
            'roles' => Role::orderBy('name')->get()->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('admin.users');

        // Prevent self-deactivation
        if ($request->user()->id === $user->id && $request->is_active === false) {
            return back()->withErrors(['is_active' => 'Không thể tự vô hiệu hóa tài khoản của mình.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user->update(array_filter([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
            'password' => $data['password'] ?? null,
        ], fn ($v) => $v !== null));

        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật người dùng.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('admin.users');

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Đã xóa người dùng.');
    }
}
