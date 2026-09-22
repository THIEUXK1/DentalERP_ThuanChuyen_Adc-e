<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AppointmentMoveTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $svc;

    private User $user;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AppointmentService::class);

        $this->branch = Branch::create([
            'code' => Branch::generateCode(),
            'name' => 'Chi nhánh Test',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->actingAs($this->user);
    }

    private function makeAppointment(string $scheduledAt): Appointment
    {
        $patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        return Appointment::createWithCode([
            'patient_id' => $patient->id,
            'branch_id' => $this->branch->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'status' => AppointmentStatus::Booked->value,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_move_creates_new_appointment_and_keeps_old_one_as_rescheduled(): void
    {
        $old = $this->makeAppointment('2026-09-22 09:00:00');

        $new = $this->svc->moveToNewDate($old, '2026-09-25 09:00:00');

        $this->assertSame(AppointmentStatus::Rescheduled, $old->refresh()->status);
        $this->assertSame('2026-09-22 09:00', $old->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame($old->id, $new->rescheduled_from_id);
        $this->assertSame('2026-09-25 09:00', $new->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(AppointmentStatus::Booked, $new->status);
        $this->assertSame($old->patient_id, $new->patient_id);
    }

    public function test_move_is_rejected_when_appointment_was_already_moved(): void
    {
        $old = $this->makeAppointment('2026-09-22 09:00:00');
        $this->svc->moveToNewDate($old, '2026-09-25 09:00:00');

        $this->expectException(\RuntimeException::class);
        $this->svc->moveToNewDate($old->refresh(), '2026-09-26 09:00:00');
    }

    public function test_bulk_move_endpoint_moves_selected_appointments(): void
    {
        Permission::create(['name' => 'appointments.manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('appointments.manage');

        $a = $this->makeAppointment('2026-09-22 09:00:00');
        $b = $this->makeAppointment('2026-09-22 10:30:00');

        $res = $this->postJson(route('schedule.appointments.bulk-move'), [
            'ids' => [$a->id, $b->id],
            'date' => '2026-09-25',
        ]);

        $res->assertOk()->assertJsonPath('moved', 2);

        // Giữ nguyên giờ cũ của từng bệnh nhân khi không chỉ định giờ chung.
        $this->assertSame('2026-09-25 09:00', $a->rescheduledTo->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame('2026-09-25 10:30', $b->rescheduledTo->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame(AppointmentStatus::Rescheduled, $a->refresh()->status);
    }
}
