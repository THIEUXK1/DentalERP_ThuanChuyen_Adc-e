<?php

namespace Database\Seeders;

use App\Models\DentalService;
use Illuminate\Database\Seeder;

class DentalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Khám tổng quát',        'category' => 'Khám',          'cost' => 50000,     'sell' => 150000,   'minutes' => 30],
            ['name' => 'Cạo vôi răng',           'category' => 'Vệ sinh',       'cost' => 80000,     'sell' => 250000,   'minutes' => 45],
            ['name' => 'Trám răng sâu',          'category' => 'Trám răng',     'cost' => 150000,    'sell' => 400000,   'minutes' => 60],
            ['name' => 'Trám thẩm mỹ Composite', 'category' => 'Trám răng',     'cost' => 300000,    'sell' => 800000,   'minutes' => 60],
            ['name' => 'Nhổ răng thường',        'category' => 'Nhổ răng',      'cost' => 100000,    'sell' => 300000,   'minutes' => 30],
            ['name' => 'Nhổ răng khôn',          'category' => 'Nhổ răng',      'cost' => 300000,    'sell' => 1000000,  'minutes' => 60],
            ['name' => 'Điều trị tủy răng',      'category' => 'Nội nha',       'cost' => 500000,    'sell' => 1500000,  'minutes' => 90],
            ['name' => 'Bọc răng sứ Emax',       'category' => 'Răng sứ',       'cost' => 1500000,   'sell' => 5000000,  'minutes' => 90],
            ['name' => 'Bọc răng sứ kim loại',   'category' => 'Răng sứ',       'cost' => 800000,    'sell' => 2500000,  'minutes' => 60],
            ['name' => 'Niềng răng kim loại',    'category' => 'Niềng răng',    'cost' => 8000000,   'sell' => 25000000, 'minutes' => 60],
            ['name' => 'Niềng răng sứ',          'category' => 'Niềng răng',    'cost' => 12000000,  'sell' => 35000000, 'minutes' => 60],
            ['name' => 'Niềng răng trong suốt',  'category' => 'Niềng răng',    'cost' => 15000000,  'sell' => 45000000, 'minutes' => 60],
            ['name' => 'Cấy Implant Nobel',      'category' => 'Implant',       'cost' => 12000000,  'sell' => 35000000, 'minutes' => 120],
            ['name' => 'Cấy Implant Straumann',  'category' => 'Implant',       'cost' => 20000000,  'sell' => 60000000, 'minutes' => 120],
            ['name' => 'Tẩy trắng răng Laser',  'category' => 'Thẩm mỹ',      'cost' => 500000,    'sell' => 2500000,  'minutes' => 60],
            ['name' => 'Veneer sứ',              'category' => 'Thẩm mỹ',      'cost' => 2500000,   'sell' => 8000000,  'minutes' => 90],
            ['name' => 'Hàm tháo lắp toàn phần', 'category' => 'Phục hình',   'cost' => 3000000,   'sell' => 8000000,  'minutes' => 60],
            ['name' => 'Chụp X-quang toàn cảnh', 'category' => 'Chẩn đoán',   'cost' => 80000,     'sell' => 250000,   'minutes' => 15],
        ];

        foreach ($services as $s) {
            $exists = DentalService::where('name', $s['name'])->first();
            if (! $exists) {
                DentalService::create([
                    'code' => DentalService::generateCode(),
                    'name' => $s['name'],
                    'category' => $s['category'],
                    'cost_price' => $s['cost'],
                    'selling_price' => $s['sell'],
                    'duration_minutes' => $s['minutes'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Dental services seeded: '.DentalService::count());
    }
}
