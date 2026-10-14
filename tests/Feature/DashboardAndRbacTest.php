<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_with_widgets_and_kpis(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('إجمالي العملاء');
        $response->assertSee('طلبات الإيجار النشطة');
        $response->assertSee('طلبات الريسيل النشطة');
        $response->assertSee('أرباح العمولات المحصلة');
        $response->assertSee('أحدث المعاينات الميدانية المجدولة للعملاء');
    }

    public function test_rental_agent_can_access_rental_pipeline_but_forbidden_from_resale_and_deals(): void
    {
        $rentalAgent = User::factory()->create([
            'email' => 'rental@test.com',
            'role' => User::ROLE_RENTAL_AGENT,
        ]);

        // Allowed to view clients and rental pipeline
        $this->actingAs($rentalAgent)->get('/admin/clients')->assertStatus(200);
        $this->actingAs($rentalAgent)->get('/admin/rental-requests')->assertStatus(200);

        // Forbidden from resale pipeline & deals closer
        $this->actingAs($rentalAgent)->get('/admin/resale-requests')->assertStatus(403);
        $this->actingAs($rentalAgent)->get('/admin/deal-outcomes')->assertStatus(403);
    }

    public function test_resale_agent_can_access_resale_pipeline_but_forbidden_from_rental_and_deals(): void
    {
        $resaleAgent = User::factory()->create([
            'email' => 'resale@test.com',
            'role' => User::ROLE_RESALE_AGENT,
        ]);

        // Allowed to view clients and resale pipeline
        $this->actingAs($resaleAgent)->get('/admin/clients')->assertStatus(200);
        $this->actingAs($resaleAgent)->get('/admin/resale-requests')->assertStatus(200);

        // Forbidden from rental pipeline & deals closer
        $this->actingAs($resaleAgent)->get('/admin/rental-requests')->assertStatus(403);
        $this->actingAs($resaleAgent)->get('/admin/deal-outcomes')->assertStatus(403);
    }

    public function test_admin_has_full_unrestricted_access_to_all_departments(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/admin/clients')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/brokers')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/rental-requests')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/resale-requests')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/unit-inspections')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/client-showings')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/deal-outcomes')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/follow-ups')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/activities')->assertStatus(200);
    }
}
