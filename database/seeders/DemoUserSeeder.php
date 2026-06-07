<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Chủ phòng khám',   'email' => 'owner@dental.local',         'role' => 'owner'],
            ['name' => 'Admin Hệ thống',    'email' => 'admin@dental.local',          'role' => 'admin'],
            ['name' => 'Quản lý CN 1',      'email' => 'manager@dental.local',        'role' => 'branch_manager'],
            ['name' => 'Lễ tân Ngọc',       'email' => 'receptionist@dental.local',   'role' => 'receptionist'],
            ['name' => 'Tư vấn Lan',        'email' => 'consultant@dental.local',     'role' => 'consultant'],
            ['name' => 'BS. Minh',          'email' => 'doctor@dental.local',         'role' => 'doctor'],
            ['name' => 'Phụ tá Hoa',        'email' => 'assistant@dental.local',      'role' => 'assistant'],
            ['name' => 'Thu ngân Linh',     'email' => 'cashier@dental.local',        'role' => 'cashier'],
            ['name' => 'Kế toán Thủy',      'email' => 'accountant@dental.local',     'role' => 'accountant'],
            ['name' => 'Thủ kho Nam',       'email' => 'warehouse@dental.local',      'role' => 'warehouse'],
            ['name' => 'Marketing Hằng',    'email' => 'marketing@dental.local',      'role' => 'marketing'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('Demo@123'),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        $this->command->info('Demo users seeded: '.count($users).' users.');
    }
}
