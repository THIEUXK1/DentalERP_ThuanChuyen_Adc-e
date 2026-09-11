<?php

namespace App\Services;

use App\Enums\CallOutcome;
use App\Models\Appointment;
use App\Models\AppointmentCallLog;
use Illuminate\Support\Facades\DB;

class AppointmentCallService
{
    /**
     * Ghi một lần gọi nhắc lịch. Bản ghi là append-only; cột tóm tắt trên appointments
     * được cập nhật lại từ chính bản ghi vừa tạo để bảng lịch hẹn hiển thị không cần join.
     */
    public function log(Appointment $appointment, CallOutcome $outcome, ?string $note, int $userId): AppointmentCallLog
    {
        return DB::transaction(function () use ($appointment, $outcome, $note, $userId) {
            $log = AppointmentCallLog::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'outcome' => $outcome->value,
                // Chụp lại số đã gọi: bệnh nhân có thể đổi số sau này, bằng chứng phải giữ số lúc gọi.
                'phone' => $appointment->patient->phone ?? null,
                'note' => $note,
                'called_at' => now(),
                'created_by' => $userId,
            ]);

            $appointment->update([
                'last_call_at' => $log->called_at,
                'last_call_outcome' => $outcome->value,
                'call_count' => $appointment->callLogs()->count(),
            ]);

            return $log;
        });
    }
}
