<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'clinic.name' => 'Nha khoa Smile',
            'clinic.address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'clinic.phone' => '028 1234 5678',
            'clinic.email' => 'info@smile-dental.vn',
            'clinic.tax_code' => '',
            'schedule.default_duration' => '30',
            'schedule.working_hours' => '08:00-17:00',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('Settings seeded: '.count($defaults).' keys.');
    }
}
