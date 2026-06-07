<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'reports.financial', 'guard_name' => 'web']);
        Permission::create(['name' => 'reports.view', 'guard_name' => 'web']);
    }

    public function test_financial_user_can_access_revenue_report(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.financial');

        $response = $this->actingAs($user)
            ->get(route('reports.revenue'));

        $response->assertOk();
    }

    public function test_non_financial_user_cannot_access_revenue_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.revenue'));

        $response->assertStatus(403);
    }

    public function test_view_user_can_access_crm_report(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $response = $this->actingAs($user)
            ->get(route('reports.crm'));

        $response->assertOk();
    }
}
