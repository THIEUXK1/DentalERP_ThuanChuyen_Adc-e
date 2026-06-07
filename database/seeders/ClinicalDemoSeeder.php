<?php

namespace Database\Seeders;

use App\Models\ClinicalNote;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\ToothCondition;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClinicalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $doctor = Employee::doctors()->first();
        $patients = Patient::take(5)->get();

        if ($patients->isEmpty()) {
            $this->command->warn('No patients found, skipping clinical demo seeder.');

            return;
        }

        // Tooth conditions for first 3 patients
        $conditionSets = [
            [
                ['tooth_number' => '16', 'condition' => 'caries',  'note' => 'Sâu mặt nhai'],
                ['tooth_number' => '26', 'condition' => 'filling',  'note' => 'Trám composite 2 năm trước'],
                ['tooth_number' => '36', 'condition' => 'crown',    'note' => 'Mão sứ Zirconia'],
                ['tooth_number' => '46', 'condition' => 'root_canal', 'note' => 'Chữa tủy hoàn tất'],
            ],
            [
                ['tooth_number' => '18', 'condition' => 'extraction_planned', 'note' => 'Răng khôn mọc lệch'],
                ['tooth_number' => '12', 'condition' => 'fractured', 'note' => 'Gãy 1/3 thân răng'],
                ['tooth_number' => '21', 'condition' => 'crown',     'note' => 'Mão sứ thẩm mỹ'],
            ],
            [
                ['tooth_number' => '36', 'condition' => 'missing',  'note' => 'Nhổ 6 tháng trước'],
                ['tooth_number' => '37', 'condition' => 'implant',  'note' => 'Implant Nobel Biocare, đang lành thương'],
                ['tooth_number' => '11', 'condition' => 'filling',  'note' => 'Trám cổ răng'],
            ],
        ];

        foreach ($conditionSets as $i => $conditions) {
            if (! isset($patients[$i])) {
                continue;
            }
            foreach ($conditions as $c) {
                ToothCondition::updateOrCreate(
                    ['patient_id' => $patients[$i]->id, 'tooth_number' => $c['tooth_number']],
                    ['condition' => $c['condition'], 'note' => $c['note'], 'recorded_by' => $admin->id]
                );
            }
        }

        // Clinical notes for first 3 patients
        $notes = [
            [
                'chief_complaint' => 'Đau nhức răng hàm trên bên phải',
                'diagnosis' => 'Sâu răng độ III răng 16, viêm tủy có hồi phục',
                'treatment_done' => 'Lấy cao răng, hàn tạm CaOH2 răng 16',
                'prescription' => "Amoxicillin 500mg x 3 lần/ngày x 5 ngày\nIbuprofen 400mg khi đau",
                'next_visit_notes' => 'Tái khám sau 1 tuần để đánh giá và hàn vĩnh viễn',
            ],
            [
                'chief_complaint' => 'Muốn nhổ răng khôn mọc lệch',
                'diagnosis' => 'Răng 18 mọc lệch gần, chèn ép răng 17',
                'treatment_done' => 'Chụp X-quang toàn cảnh, tư vấn phẫu thuật nhổ răng',
                'prescription' => "Augmentin 625mg x 2 lần/ngày x 7 ngày\nParacetamol 500mg khi cần",
                'next_visit_notes' => 'Hẹn phẫu thuật nhổ răng 18 tuần tới',
            ],
            [
                'chief_complaint' => 'Kiểm tra implant, đau nhẹ vùng 37',
                'diagnosis' => 'Implant 37 ổn định, vùng xương lành tốt. Đau là bình thường sau phẫu thuật',
                'treatment_done' => 'Kiểm tra vít implant, vệ sinh vùng phẫu thuật',
                'prescription' => 'Chlorhexidine 0.12% súc miệng 2 lần/ngày x 2 tuần',
                'next_visit_notes' => 'Tái khám sau 1 tháng, gắn abutment và mão sứ',
            ],
        ];

        foreach ($notes as $i => $note) {
            if (! isset($patients[$i])) {
                continue;
            }
            ClinicalNote::create([
                ...$note,
                'patient_id' => $patients[$i]->id,
                'doctor_id' => $doctor?->id,
                'branch_id' => $patients[$i]->branch_id ?? 1,
                'created_by' => $admin->id,
            ]);
        }

        $this->command->info('Clinical demo data seeded: tooth conditions + clinical notes.');
    }
}
