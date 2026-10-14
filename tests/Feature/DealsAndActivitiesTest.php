<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Client;
use App\Models\DealOutcome;
use App\Models\FollowUp;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealsAndActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_deal_outcomes_and_followups_and_activities_pages(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $res1 = $this->actingAs($admin)->get('/admin/deal-outcomes');
        $res1->assertStatus(200);
        $res1->assertSee('إغلاق الصفقات');

        $res2 = $this->actingAs($admin)->get('/admin/follow-ups');
        $res2->assertStatus(200);
        $res2->assertSee('المتابعات الذكية');

        $res3 = $this->actingAs($admin)->get('/admin/activities');
        $res3->assertStatus(200);
        $res3->assertSee('سجل الأنشطة');
    }

    public function test_follow_up_quick_complete_action_and_activity_logging(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'م. حازم إمام',
            'phone' => '01099998888',
        ]);

        $followUp = FollowUp::create([
            'client_id' => $client->id,
            'action_required' => 'الاتصال لتأكيد موعد توقيع العقد',
            'due_at' => now()->subHour(), // Overdue
            'assigned_to' => $admin->id,
            'status' => FollowUp::STATUS_PENDING,
        ]);

        $this->assertTrue($followUp->isOverdue());

        $followUp->markCompleted('تم الاتصال وتأكيد الموعد غداً بمقر الشركة', $admin->id);

        $this->assertEquals(FollowUp::STATUS_COMPLETED, $followUp->fresh()->status);
        $this->assertFalse($followUp->fresh()->isOverdue());

        $activity = Activity::where('client_id', $client->id)->latest()->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString('إكمال متابعة', $activity->description);
    }

    public function test_deal_outcome_creation_and_attributes(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $client = Client::create([
            'name' => 'م. حازم إمام',
            'phone' => '01099998888',
        ]);

        $request = RentalRequest::create([
            'client_id' => $client->id,
            'status' => RentalRequest::STATUS_NEGOTIATING,
        ]);

        $deal = DealOutcome::create([
            'request_type' => 'rental',
            'rental_request_id' => $request->id,
            'outcome' => DealOutcome::OUTCOME_WON,
            'final_price' => 50000,
            'commission_amount' => 50000,
            'closed_by' => $admin->id,
            'closed_at' => now(),
        ]);

        $this->assertTrue($deal->isWon());
        $this->assertEquals($client->name, $deal->client->name);

        $response = $this->actingAs($admin)->get("/admin/deal-outcomes/{$deal->id}");
        $response->assertStatus(200);
        $response->assertSee('م. حازم إمام');
        $response->assertSee('ناجحة');
        $response->assertSee('عمولة الشركة');
    }
}
