<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Patients
            'patients.view', 'patients.create', 'patients.edit', 'patients.delete', 'patients.export',
            // Leads
            'leads.view', 'leads.create', 'leads.manage', 'leads.assign',
            // Appointments
            'appointments.view', 'appointments.create', 'appointments.manage',
            // Treatment plans
            'treatment_plans.view', 'treatment_plans.create', 'treatment_plans.edit', 'treatment_plans.approve',
            // Clinical notes & dental chart
            'clinical_notes.create',
            // Cashier
            'cashier.view', 'cashier.manage', 'cashier.approve_discount', 'cashier.approve_refund',
            // Employees
            'employees.view', 'employees.create', 'employees.manage',
            // Branches
            'branches.view', 'branches.manage',
            // Services
            'services.view', 'services.manage',
            // Reports
            'reports.view', 'reports.financial', 'reports.clinical',
            // Commissions
            'commissions.view', 'commissions.manage',
            // Expenses
            'expenses.view', 'expenses.manage',
            // Settings
            'settings.view', 'settings.manage',
            // Admin
            'admin.users', 'admin.roles', 'admin.audit_log',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $matrix = [
            'owner' => $permissions, // all

            'admin' => $permissions, // full access, same as owner

            'branch_manager' => [
                'patients.view', 'patients.create', 'patients.edit', 'patients.export',
                'leads.view', 'leads.create', 'leads.manage', 'leads.assign',
                'appointments.view', 'appointments.create', 'appointments.manage',
                'treatment_plans.view', 'treatment_plans.approve',
                'cashier.view', 'cashier.manage',
                'employees.view',
                'services.view',
                'reports.view', 'reports.financial', 'reports.clinical',
                'commissions.view', 'commissions.manage',
                'expenses.view', 'expenses.manage',
                'settings.view',
            ],

            'receptionist' => [
                'patients.view', 'patients.create', 'patients.edit',
                'leads.view', 'leads.create',
                'appointments.view', 'appointments.create', 'appointments.manage',
            ],

            'consultant' => [
                'patients.view',
                'leads.view', 'leads.create', 'leads.manage', 'leads.assign',
                'treatment_plans.view', 'treatment_plans.create', 'treatment_plans.edit',
                'appointments.view',
                'services.view',
                'reports.view',
            ],

            'doctor' => [
                'patients.view',
                'appointments.view', 'appointments.manage',
                'treatment_plans.view', 'treatment_plans.create', 'treatment_plans.edit',
                'clinical_notes.create',
                'services.view',
                'reports.clinical',
                'commissions.view',
            ],

            'assistant' => [
                'patients.view',
                'appointments.view',
                'treatment_plans.view',
                'clinical_notes.create',
            ],

            'cashier' => [
                'patients.view',
                'cashier.view', 'cashier.manage', 'cashier.approve_discount', 'cashier.approve_refund',
                'treatment_plans.view',
                'expenses.view', 'expenses.manage',
                'reports.financial',
            ],

            'accountant' => [
                'cashier.view',
                'patients.view',
                'reports.view', 'reports.financial',
                'commissions.view', 'commissions.manage',
                'expenses.view', 'expenses.manage',
                'settings.view',
            ],

            'warehouse' => [
                'patients.view',
                'services.view',
            ],

            'marketing' => [
                'patients.view',
                'leads.view', 'leads.create', 'leads.manage', 'leads.assign',
                'reports.view',
            ],
        ];

        foreach ($matrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        $this->command->info('Roles & permissions seeded: '.count($matrix).' roles, '.count($permissions).' permissions.');
    }
}
