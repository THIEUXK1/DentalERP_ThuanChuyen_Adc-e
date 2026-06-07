<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('admin.roles');

        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::withCount('permissions', 'users')->orderBy('name')->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'permissions_count' => $r->permissions_count,
                    'users_count' => $r->users_count,
                ]),
        ]);
    }

    public function show(Role $role): Response
    {
        $this->authorize('admin.roles');

        $all = Permission::orderBy('name')->get()->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return Inertia::render('Admin/Roles/Show', [
            'role' => ['id' => $role->id, 'name' => $role->name],
            'permissions' => $all->map(fn ($group) => $group->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'granted' => $role->hasPermissionTo($p->name),
            ])),
        ]);
    }
}
