<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\RentalRequest;
use App\Models\RentalRequirement;
use App\Models\UnitNomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_rental_requests_index_page(): void
    {
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $client = Client::create([
            'name' => 'مستأجر تجريبي',
            'phone' => '01099998888',
        ]);

        $request = RentalRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'status' => RentalRequest::STATUS_NEW,
        ]);

        RentalRequirement::create([
            'rental_request_id' => $request->id,
            'property_type' => 'شقة',
            'preferred_locations' => 'ميفيدا التجمع الخامس',
            'stated_budget_max' => 45000,
            'qualified_budget_max' => 50000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/rental-requests');
        $response->assertStatus(200);
        $response->assertSee('مستأجر تجريبي');
        $response->assertSee('ميفيدا التجمع الخامس');
    }

    public function test_admin_can_view_rental_request_details_with_relations(): void
    {
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $client = Client::create([
            'name' => 'م. طارق عبد الرحمن',
            'phone' => '01012345678',
        ]);

        $request = RentalRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'status' => RentalRequest::STATUS_SHOWING_SCHEDULED,
        ]);

        RentalRequirement::create([
            'rental_request_id' => $request->id,
            'property_type' => 'شقة',
            'furnishing_status' => 'مفروش بالكامل',
            'preferred_locations' => 'كمباوند ميفيدا',
            'stated_budget_max' => 45000,
            'qualified_budget_max' => 50000,
        ]);

        UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $request->id,
            'title' => 'شقة 210م مفروشة بميفيدا',
            'price' => 48000,
        ]);

        $response = $this->actingAs($admin)->get("/admin/rental-requests/{$request->id}");
        $response->assertStatus(200);
        $response->assertSee('م. طارق عبد الرحمن');
        $response->assertSee('كمباوند ميفيدا');
        $response->assertSee('ترشيحات الوحدات المتاحة لهذا الطلب');
        $response->assertSee('سجل الأنشطة والاتصالات الخاصة بالطلب');
        $response->assertSee('متابعات وتذكيرات طلب الإيجار');
    }
}
