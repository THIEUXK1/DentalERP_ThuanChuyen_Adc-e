<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoUserSeeder::class,
            BranchEmployeeSeeder::class,
            DentalServiceSeeder::class,
            PatientLeadSeeder::class,
            AppointmentSeeder::class,
            ClinicalDemoSeeder::class,
            TreatmentPlanInvoiceSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
