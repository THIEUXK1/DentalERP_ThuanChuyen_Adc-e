<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\ScheduleRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientQuickRegisterTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'appointments.create', 'guard_name' => 'web']);

        $branch = Branch::create([
            'code' => Branch::generateCode(),
            'name' => 'Chi nhánh Test',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->givePermissionTo('appointments.create');
        $this->actingAs($user);

        $this->patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }

    public function test_json_request_returns_json_instead_of_redirecting(): void
    {
        $res = $this->postJson(route('patients.quick-register', $this->patient->id), [
            'scheduled_time' => '14:30',
            'status' => 'pending',
            'notes' => 'Đau răng số 6',
        ]);

        $res->assertOk()->assertJsonStructure(['message', 'code']);

        $registration = ScheduleRegistration::where('patient_id', $this->patient->id)->sole();
        // Quy tắc đã chốt: đăng ký khám luôn thuộc ngày hôm nay, không nhận ngày từ client.
        $this->assertSame(today()->toDateString(), $registration->registration_date->toDateString());
        $this->assertSame('14:30', substr($registration->visit_time, 0, 5));
    }

    public function test_browser_request_still_redirects_to_registration_book(): void
    {
        $res = $this->post(route('patients.quick-register', $this->patient->id), [
            'scheduled_time' => '09:00',
            'status' => 'pending',
        ]);

        $res->assertRedirect(route('schedule.registrations.index'));
    }
}
