<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientShowing;
use App\Models\RentalRequest;
use App\Models\UnitInspection;
use App\Models\UnitNomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionsAndShowingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_inspections_and_showings_pages(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $response1 = $this->actingAs($admin)->get('/admin/unit-inspections');
        $response1->assertStatus(200);
        $response1->assertSee('فحص الوحدات');

        $response2 = $this->actingAs($admin)->get('/admin/client-showings');
        $response2->assertStatus(200);
        $response2->assertSee('معاينات العملاء');
    }

    public function test_gatekeeper_rule_prevents_showing_for_uninspected_or_unsuitable_unit(): void
    {
        $client = Client::create([
            'name' => 'محمد الصاوي',
            'phone' => '01011112222',
        ]);

        $request = RentalRequest::create([
            'client_id' => $client->id,
            'status' => RentalRequest::STATUS_NEW,
        ]);

        $uninspectedNomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $request->id,
            'title' => 'شقة غير مفحوصة بعد',
            'price' => 30000,
        ]);

        $this->assertFalse($uninspectedNomination->isSuitableForShowing());

        $unsuitableNomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $request->id,
            'title' => 'شقة رطوبة ومرفوضة',
            'price' => 25000,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $unsuitableNomination->id,
            'inspected_at' => now(),
            'result' => UnitInspection::RESULT_UNSUITABLE,
            'rejection_reason' => 'رطوبة شديدة بالحوائط والفرش متهالك',
        ]);

        $this->assertFalse($unsuitableNomination->isSuitableForShowing());
    }

    public function test_showing_can_be_scheduled_when_unit_is_inspected_and_suitable(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'م. طارق سليم',
            'phone' => '01055556666',
        ]);

        $request = RentalRequest::create([
            'client_id' => $client->id,
            'status' => RentalRequest::STATUS_ACTIVE,
        ]);

        $nomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $request->id,
            'title' => 'شقة 200م الترا لوكس بميفيدا',
            'location' => 'ميفيدا التجمع الخامس',
            'price' => 45000,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $nomination->id,
            'inspected_by' => $admin->id,
            'inspected_at' => now(),
            'result' => UnitInspection::RESULT_SUITABLE,
            'condition_rating' => 'ممتاز',
            'is_matched_reality' => true,
        ]);

        $this->assertTrue($nomination->isSuitableForShowing());

        $showing = ClientShowing::create([
            'unit_nomination_id' => $nomination->id,
            'assigned_to' => $admin->id,
            'scheduled_at' => now()->addDays(2),
            'status' => ClientShowing::STATUS_SCHEDULED,
        ]);

        $this->assertEquals($client->name, $showing->client->name);
        $this->assertEquals(ClientShowing::STATUS_SCHEDULED, $showing->status);

        $response = $this->actingAs($admin)->get("/admin/client-showings/{$showing->id}");
        $response->assertStatus(200);
        $response->assertSee('شقة 200م الترا لوكس بميفيدا');
        $response->assertSee('م. طارق سليم');
    }
}
