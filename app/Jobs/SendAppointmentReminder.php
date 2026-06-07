<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAppointmentReminder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $appointmentId) {}

    public function handle(): void
    {
        $appointment = Appointment::with(['patient', 'doctor', 'branch'])->find($this->appointmentId);

        if (! $appointment || $appointment->status->isTerminal()) {
            return;
        }

        $time = $appointment->scheduled_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        $doctor = $appointment->doctor?->full_name ?? 'Bác sĩ';
        $branch = $appointment->branch->name;

        // In-app notification (Phase 09 will flesh this out)
        // For now just log — in Phase 09 we add the notifications table
        \Log::info("Appointment reminder: [{$appointment->code}] {$appointment->patient->full_name} - {$time} - {$doctor} - {$branch}");
    }
}
