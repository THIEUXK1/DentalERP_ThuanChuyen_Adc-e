<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\DentalChair;
use App\Models\DentalService;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $b1 = Branch::where('code', 'CN-0001')->first();
        $b2 = Branch::where('code', 'CN-0002')->first();

        // Dental chairs for branch 1
        $chairs = [
            ['branch_id' => $b1->id, 'code' => 'GHE-01', 'name' => 'Ghế số 1'],
            ['branch_id' => $b1->id, 'code' => 'GHE-02', 'name' => 'Ghế số 2'],
            ['branch_id' => $b1->id, 'code' => 'GHE-03', 'name' => 'Ghế số 3'],
            ['branch_id' => $b2->id, 'code' => 'GHE-01', 'name' => 'Ghế số 1'],
            ['branch_id' => $b2->id, 'code' => 'GHE-02', 'name' => 'Ghế số 2'],
        ];

        foreach ($chairs as $c) {
            DentalChair::firstOrCreate(['branch_id' => $c['branch_id'], 'code' => $c['code']], $c + ['is_active' => true]);
        }

        $creator = User::where('email', 'receptionist@dental.local')->first();
        $doctor = Employee::doctors()->where('branch_id', $b1->id)->first();
        $chair = DentalChair::where('branch_id', $b1->id)->where('code', 'GHE-01')->first();
        $patients = Patient::take(5)->get();
        $services = DentalService::take(4)->get();

        if (! $creator || ! $doctor || ! $chair || $patients->isEmpty()) {
            $this->command->warn('Skipping appointment seed — missing required records.');

            return;
        }

        $base = Carbon::today('Asia/Ho_Chi_Minh')->setHour(8)->setMinute(0);
        $times = ['08:00', '08:45', '09:30', '10:15', '11:00', '14:00', '14:45', '15:30'];
        $statuses = [AppointmentStatus::Booked, AppointmentStatus::Confirmed, AppointmentStatus::CheckedIn, AppointmentStatus::Completed];

        foreach ($patients->take(5) as $idx => $patient) {
            [$h, $m] = explode(':', $times[$idx]);
            $scheduledAt = Carbon::today('Asia/Ho_Chi_Minh')->setHour((int) $h)->setMinute((int) $m);

            if (! Appointment::where('patient_id', $patient->id)->whereDate('scheduled_at', today())->exists()) {
                Appointment::create([
                    'code' => Appointment::generateCode(),
                    'patient_id' => $patient->id,
                    'branch_id' => $b1->id,
                    'doctor_id' => $doctor->id,
                    'dental_chair_id' => $chair->id,
                    'service_id' => $services->get($idx % $services->count())?->id,
                    'scheduled_at' => $scheduledAt->toDateTimeString(),
                    'duration_minutes' => 45,
                    'status' => $statuses[$idx % count($statuses)]->value,
                    'created_by' => $creator->id,
                ]);
            }
        }

        $this->command->info('Dental chairs: '.DentalChair::count().' | Appointments: '.Appointment::count());
    }
}
