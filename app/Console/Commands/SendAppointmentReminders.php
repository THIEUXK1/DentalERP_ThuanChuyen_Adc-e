<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:remind';

    protected $description = 'Send reminders for appointments scheduled tomorrow';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh');

        $appointments = Appointment::whereDate('scheduled_at', $tomorrow)
            ->whereNotIn('status', [
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
                AppointmentStatus::Completed->value,
            ])
            ->get();

        foreach ($appointments as $appt) {
            SendAppointmentReminder::dispatch($appt->id);
        }

        $this->info("Dispatched reminders for {$appointments->count()} appointments.");

        return self::SUCCESS;
    }
}
