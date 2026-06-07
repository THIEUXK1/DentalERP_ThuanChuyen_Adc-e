<?php

namespace Database\Seeders;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PatientLeadSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'CN-0001')->first();
        $branch2 = Branch::where('code', 'CN-0002')->first();
        $consultant = User::where('email', 'consultant@dental.local')->first();

        // Demo patients
        $patients = [
            ['full_name' => 'Nguyễn Thị Hương', 'phone' => '0901234567', 'dob' => '1985-03-15', 'gender' => 'female', 'source' => 'facebook', 'branch_id' => $branch1?->id],
            ['full_name' => 'Trần Văn Nam',      'phone' => '0912345678', 'dob' => '1990-07-22', 'gender' => 'male',   'source' => 'referral',  'branch_id' => $branch1?->id, 'allergies' => 'Dị ứng Penicillin'],
            ['full_name' => 'Lê Thị Mai',        'phone' => '0923456789', 'dob' => '1975-11-08', 'gender' => 'female', 'source' => 'zalo',      'branch_id' => $branch1?->id],
            ['full_name' => 'Phạm Quốc Hùng',   'phone' => '0934567890', 'dob' => '1988-04-30', 'gender' => 'male',   'source' => 'google',    'branch_id' => $branch2?->id],
            ['full_name' => 'Vũ Thị Thu',        'phone' => '0945678901', 'dob' => '1995-09-12', 'gender' => 'female', 'source' => 'walk_in',   'branch_id' => $branch1?->id],
            ['full_name' => 'Đặng Minh Tuấn',   'phone' => '0956789012', 'dob' => '1982-01-25', 'gender' => 'male',   'source' => 'facebook',  'branch_id' => $branch2?->id],
            ['full_name' => 'Hoàng Thị Lan',     'phone' => '0967890123', 'dob' => '1993-06-18', 'gender' => 'female', 'source' => 'referral',  'branch_id' => $branch1?->id, 'medical_history' => 'Tiểu đường type 2'],
            ['full_name' => 'Bùi Văn Phong',    'phone' => '0978901234', 'dob' => '1987-12-05', 'gender' => 'male',   'source' => 'google',    'branch_id' => $branch2?->id],
            ['full_name' => 'Ngô Thị Bích',      'phone' => '0989012345', 'dob' => '1970-08-14', 'gender' => 'female', 'source' => 'walk_in',   'branch_id' => $branch1?->id],
            ['full_name' => 'Đinh Trọng Khải',  'phone' => '0990123456', 'dob' => '1998-02-28', 'gender' => 'male',   'source' => 'zalo',      'branch_id' => $branch1?->id],
        ];

        foreach ($patients as $p) {
            if (! Patient::where('phone', $p['phone'])->exists()) {
                Patient::create([...$p, 'code' => Patient::generateCode(), 'is_active' => true]);
            }
        }

        // Demo leads
        $leads = [
            ['name' => 'Lý Thị Hồng Nhung', 'phone' => '0901111111', 'source' => LeadSource::Facebook,  'status' => LeadStatus::New,              'interest' => 'Niềng răng trong suốt'],
            ['name' => 'Cao Văn Bình',       'phone' => '0912222222', 'source' => LeadSource::Zalo,      'status' => LeadStatus::Contacted,        'interest' => 'Cấy Implant'],
            ['name' => 'Trương Thị Yến',     'phone' => '0923333333', 'source' => LeadSource::Google,    'status' => LeadStatus::Consulting,       'interest' => 'Tẩy trắng răng'],
            ['name' => 'Đỗ Mạnh Hà',        'phone' => '0934444444', 'source' => LeadSource::Referral,  'status' => LeadStatus::AppointmentBooked, 'interest' => 'Nhổ răng khôn'],
            ['name' => 'Lưu Thị Phương',    'phone' => '0945555555', 'source' => LeadSource::Facebook,  'status' => LeadStatus::Quoted,           'interest' => 'Bọc răng sứ Emax'],
            ['name' => 'Phan Văn Kiên',     'phone' => '0956666666', 'source' => LeadSource::WalkIn,    'status' => LeadStatus::Considering,      'interest' => 'Niềng răng kim loại'],
            ['name' => 'Tạ Thị Minh Anh',   'phone' => '0967777777', 'source' => LeadSource::Zalo,      'status' => LeadStatus::NoAnswer,         'interest' => 'Implant'],
            ['name' => 'Quách Thành Nhân',  'phone' => '0978888888', 'source' => LeadSource::Google,    'status' => LeadStatus::ClosedLost,       'interest' => 'Veneer sứ'],
            ['name' => 'Hồ Thị Kim Chi',    'phone' => '0989999999', 'source' => LeadSource::Facebook,  'status' => LeadStatus::FollowUp,         'interest' => 'Cạo vôi + trám'],
            ['name' => 'Mạc Văn Trọng',     'phone' => '0991010101', 'source' => LeadSource::Referral,  'status' => LeadStatus::New,              'interest' => 'Khám tổng quát'],
        ];

        foreach ($leads as $l) {
            if (! Lead::where('phone', $l['phone'])->exists()) {
                Lead::create([
                    'code' => Lead::generateCode(),
                    'name' => $l['name'],
                    'phone' => $l['phone'],
                    'source' => $l['source']->value,
                    'status' => $l['status']->value,
                    'interest' => $l['interest'],
                    'assigned_to' => $consultant?->id,
                    'branch_id' => $branch1?->id,
                ]);
            }
        }

        $this->command->info('Patients seeded: '.Patient::count().' | Leads seeded: '.Lead::count());
    }
}
