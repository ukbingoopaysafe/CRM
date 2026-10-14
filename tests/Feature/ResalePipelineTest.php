<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ResaleRequest;
use App\Models\ResaleRequirement;
use App\Models\UnitNomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResalePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_resale_requests_index_page(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'د. خالد سليم',
            'phone' => '01211112222',
        ]);

        $request = ResaleRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'status' => ResaleRequest::STATUS_NEW,
            'purpose' => 'سكن شخصي',
        ]);

        ResaleRequirement::create([
            'resale_request_id' => $request->id,
            'property_type' => 'فيلا مستقلة',
            'payment_method' => 'تقسيط وتسهيلات',
            'total_budget' => 18000000,
            'down_payment_max' => 4000000,
        ]);

        $response = $this->actingAs($admin)->get('/admin/resale-requests');

        $response->assertStatus(200);
        $response->assertSee('طلبات الريسيل');
        $response->assertSee('د. خالد سليم');
        $response->assertSee('01211112222');
    }

    public function test_admin_can_view_resale_request_details_with_relations(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'د. خالد سليم',
            'phone' => '01211112222',
            'email' => 'khaled@test.com',
        ]);

        $request = ResaleRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'status' => ResaleRequest::STATUS_SITE_VISIT,
            'purpose' => 'استثمار وإعادة بيع',
        ]);

        ResaleRequirement::create([
            'resale_request_id' => $request->id,
            'property_type' => 'تاون هاوس',
            'preferred_locations' => 'كمباوند بادية بالم هيلز',
            'payment_method' => 'تقسيط وتسهيلات',
            'total_budget' => 12500000,
            'down_payment_max' => 2500000,
            'installment_capacity' => 350000,
            'installment_frequency' => 'ربع سنوي',
            'installment_years' => 7,
        ]);

        UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RESALE,
            'resale_request_id' => $request->id,
            'title' => 'تاون هاوس 240م ببادية 6 أكتوبر',
            'price' => 11800000,
        ]);

        $response = $this->actingAs($admin)->get("/admin/resale-requests/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('د. خالد سليم');
        $response->assertSee('كمباوند بادية بالم هيلز');
        $response->assertSee('الخطة المالية وأنظمة السداد');
        $response->assertSee('ترشيحات المشروعات والوحدات لهذا الطلب');
        $response->assertSee('سجل الأنشطة والاتصالات الخاصة بطلب الريسيل');
        $response->assertSee('متابعات وتذكيرات طلب الريسيل');
    }
}
