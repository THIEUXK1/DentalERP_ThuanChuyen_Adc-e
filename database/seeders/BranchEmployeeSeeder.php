<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Branches
        $b1 = Branch::firstOrCreate(['code' => 'CN-0001'], [
            'name' => 'Nha khoa Smile - Quận 1',
            'address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'phone' => '028 1234 5678',
            'is_active' => true,
        ]);

        $b2 = Branch::firstOrCreate(['code' => 'CN-0002'], [
            'name' => 'Nha khoa Smile - Quận 7',
            'address' => '456 Nguyễn Thị Thập, Quận 7, TP.HCM',
            'phone' => '028 8765 4321',
            'is_active' => true,
        ]);

        // Link manager user to branch 1
        $managerUser = User::where('email', 'manager@dental.local')->first();
        if ($managerUser) {
            $b1->update(['manager_id' => $managerUser->id]);
            $managerUser->update(['branch_id' => $b1->id]);
        }

        // Employees
        $staff = [
            ['full_name' => 'BS. Nguyễn Văn Minh',  'role_type' => RoleType::Doctor,       'branch' => $b1, 'email' => 'doctor@dental.local',      'spec' => 'Implant & Thẩm mỹ', 'license' => 'CCHN-12345'],
            ['full_name' => 'BS. Trần Thị Hoa',     'role_type' => RoleType::Doctor,       'branch' => $b2, 'email' => null,                       'spec' => 'Niềng răng',         'license' => 'CCHN-67890'],
            ['full_name' => 'BS. Lê Quang Dũng',    'role_type' => RoleType::Doctor,       'branch' => $b1, 'email' => null,                       'spec' => 'Nội nha',            'license' => 'CCHN-11111'],
            ['full_name' => 'Phụ tá Nguyễn Thị Hoa', 'role_type' => RoleType::Assistant,  'branch' => $b1, 'email' => 'assistant@dental.local',   'spec' => null, 'license' => null],
            ['full_name' => 'Lễ tân Trần Thị Ngọc', 'role_type' => RoleType::Receptionist, 'branch' => $b1, 'email' => 'receptionist@dental.local', 'spec' => null, 'license' => null],
            ['full_name' => 'Tư vấn Phạm Thị Lan',  'role_type' => RoleType::Consultant,  'branch' => $b1, 'email' => 'consultant@dental.local',  'spec' => null, 'license' => null],
            ['full_name' => 'Thu ngân Hoàng Thị Linh', 'role_type' => RoleType::Cashier,  'branch' => $b1, 'email' => 'cashier@dental.local',     'spec' => null, 'license' => null],
            ['full_name' => 'Kế toán Vũ Thị Thủy',  'role_type' => RoleType::Accountant,  'branch' => $b1, 'email' => 'accountant@dental.local',  'spec' => null, 'license' => null],
            ['full_name' => 'Quản lý CN1',           'role_type' => RoleType::Manager,     'branch' => $b1, 'email' => 'manager@dental.local',     'spec' => null, 'license' => null],
        ];

        foreach ($staff as $s) {
            $userId = null;
            if ($s['email']) {
                $user = User::where('email', $s['email'])->first();
                if ($user) {
                    $userId = $user->id;
                    $user->update(['branch_id' => $s['branch']->id]);
                }
            }

            $existing = Employee::where('user_id', $userId)->where('branch_id', $s['branch']->id)
                ->where('role_type', $s['role_type']->value)->first();

            if (! $existing) {
                Employee::create([
                    'code' => Employee::generateCode(),
                    'user_id' => $userId,
                    'branch_id' => $s['branch']->id,
                    'full_name' => $s['full_name'],
                    'role_type' => $s['role_type']->value,
                    'specialization' => $s['spec'],
                    'license_number' => $s['license'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Branches seeded: 2 | Employees seeded: '.Employee::count());
    }
}
