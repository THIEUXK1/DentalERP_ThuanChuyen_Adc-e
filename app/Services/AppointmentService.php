<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Models\Appointment;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    private array $terminalStatuses = ['cancelled', 'no_show', 'completed', 'arrived_early', 'checked_in', 'arrived_late'];

    public function checkConflict(array $data, ?int $ignoreId = null): void
    {
        $start = $data['scheduled_at'];
        $end = date('Y-m-d H:i:s', strtotime($start) + ($data['duration_minutes'] ?? 30) * 60);
        $excluded = array_merge($this->terminalStatuses, ['rescheduled']);

        // Check doctor conflict
        if (! empty($data['doctor_id'])) {
            $conflict = Appointment::where('doctor_id', $data['doctor_id'])
                ->whereNotIn('status', $excluded)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where(fn ($q) => $q
                    ->where(fn ($q2) => $q2->where('scheduled_at', '<', $end)
                        ->whereRaw($this->rowEndAfterExpr(), [$start])))
                ->exists();

            if ($conflict) {
                throw new \RuntimeException('Bác sĩ đã có lịch trong khung giờ này.');
            }
        }

        // Check chair conflict
        if (! empty($data['dental_chair_id'])) {
            $conflict = Appointment::where('dental_chair_id', $data['dental_chair_id'])
                ->whereNotIn('status', $excluded)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where(fn ($q) => $q
                    ->where(fn ($q2) => $q2->where('scheduled_at', '<', $end)
                        ->whereRaw($this->rowEndAfterExpr(), [$start])))
                ->exists();

            if ($conflict) {
                throw new \RuntimeException('Ghế nha đã được đặt trong khung giờ này.');
            }
        }
    }

    /** Portable "row's end time (scheduled_at + duration_minutes) is after ?" — Postgres interval vs SQLite datetime(). */
    private function rowEndAfterExpr(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "datetime(scheduled_at, '+' || duration_minutes || ' minutes') > ?"
            : "scheduled_at + interval '1 minute' * duration_minutes > ?";
    }

    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $this->checkConflict($data);

            $appointment = Appointment::createWithCode([
                ...$data,
                'status' => AppointmentStatus::Booked->value,
                'created_by' => auth()->id(),
            ]);

            // Update lead status if linked
            if (! empty($data['lead_id'])) {
                Lead::where('id', $data['lead_id'])
                    ->whereNotIn('status', ['closed_won', 'closed_lost'])
                    ->update(['status' => LeadStatus::AppointmentBooked->value]);
            }

            return $appointment;
        });
    }

    public function reschedule(Appointment $appointment, string $newScheduledAt, int $newDuration): void
    {
        DB::transaction(function () use ($appointment, $newScheduledAt, $newDuration) {
            $this->checkConflict([
                'scheduled_at' => $newScheduledAt,
                'duration_minutes' => $newDuration,
                'doctor_id' => $appointment->doctor_id,
                'dental_chair_id' => $appointment->dental_chair_id,
            ], $appointment->id);

            $appointment->update([
                'scheduled_at' => $newScheduledAt,
                'duration_minutes' => $newDuration,
                'status' => AppointmentStatus::Booked->value,
            ]);
        });
    }

    public function transition(Appointment $appointment, AppointmentStatus $to, array $extra = []): void
    {
        if ($to === AppointmentStatus::CheckedIn && now()->toDateString() < $appointment->scheduled_at->toDateString()) {
            $to = AppointmentStatus::ArrivedEarly;
        }

        $allowed = $appointment->status->allowedTransitions();

        if (! in_array($to, $allowed)) {
            throw new \RuntimeException(
                "Không thể chuyển từ [{$appointment->status->label()}] sang [{$to->label()}]."
            );
        }

        if ($to === AppointmentStatus::Cancelled && empty($extra['cancel_reason'])) {
            throw new \RuntimeException('Vui lòng nhập lý do hủy lịch.');
        }

        DB::transaction(function () use ($appointment, $to, $extra) {
            $appointment->update(array_filter([
                'status' => $to->value,
                'cancel_reason' => $extra['cancel_reason'] ?? null,
            ], fn ($v) => $v !== null));
        });
    }
}
