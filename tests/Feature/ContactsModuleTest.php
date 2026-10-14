<?php

namespace Tests\Feature;

use App\Models\Broker;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_clients_index_page(): void
    {
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $client = Client::create([
            'name' => 'عميل اختبار تجريبي',
            'phone' => '01011112222',
        ]);

        $response = $this->actingAs($admin)->get('/admin/clients');
        $response->assertStatus(200);
        $response->assertSee('عميل اختبار تجريبي');
    }

    public function test_admin_can_access_brokers_index_page(): void
    {
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $broker = Broker::create([
            'name' => 'وسيط اختبار تجريبي',
            'phone' => '01122223333',
            'company_name' => 'شركة الوساطة الحديثة',
        ]);

        $response = $this->actingAs($admin)->get('/admin/brokers');
        $response->assertStatus(200);
        $response->assertSee('وسيط اختبار تجريبي');
        $response->assertSee('شركة الوساطة الحديثة');
    }

    public function test_client_view_page_renders_with_relations(): void
    {
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'م. طارق عبد الرحمن',
            'phone' => '01012345678',
            'email' => 'tarek@example.com',
        ]);

        $response = $this->actingAs($admin)->get("/admin/clients/{$client->id}");
        $response->assertStatus(200);
        $response->assertSee('م. طارق عبد الرحمن');
        $response->assertSee('طلبات الإيجار التابعة للعميل');
        $response->assertSee('طلبات الريسيل والشراء التابعة للعميل');
        $response->assertSee('سجل الأنشطة والتواصل مع العميل');
        $response->assertSee('متابعات وتذكيرات العميل');
    }
}
