<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\ToothCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ClinicalNoteTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'clinical_notes.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'patients.view', 'guard_name' => 'web']);

        $this->branch = Branch::create([
            'code' => Branch::generateCode(),
            'name' => 'Chi nhánh Test',
            'is_active' => true,
        ]);
    }

    public function test_user_with_permission_can_create_clinical_note(): void
    {
        $user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
        $user->givePermissionTo('clinical_notes.create');
        $user->givePermissionTo('patients.view');

        $patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('clinical-notes.store', $patient), [
                'chief_complaint' => 'Đau răng hàm dưới bên trái',
                'diagnosis' => 'Sâu răng sâu số 36',
                'treatment_done' => 'Hàn răng Composite',
                'prescription' => 'Paracetamol 500mg x 10 viên',
                'next_visit_notes' => 'Tái khám sau 1 tuần',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clinical_notes', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Đau răng hàm dưới bên trái',
        ]);
    }

    public function test_user_without_permission_cannot_create_clinical_note(): void
    {
        $user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
        $user->givePermissionTo('patients.view');

        $patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('clinical-notes.store', $patient), [
                'chief_complaint' => 'Đau răng',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_upsert_tooth_condition(): void
    {
        $user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
        $user->givePermissionTo('clinical_notes.create');
        $user->givePermissionTo('patients.view');

        $patient = Patient::create([
            'code' => Patient::generateCode(),
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('tooth-conditions.upsert', $patient), [
                'tooth_number' => '36',
                'condition' => 'caries',
                'note' => 'Sâu mặt nhai',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tooth_conditions', [
            'patient_id' => $patient->id,
            'tooth_number' => '36',
            'condition' => 'caries',
        ]);
    }
}
