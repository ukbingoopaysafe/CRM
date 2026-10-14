<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientShowing;
use App\Models\DealOutcome;
use App\Models\FollowUp;
use App\Models\RentalRequest;
use App\Models\RentalRequirement;
use App\Models\ResaleRequest;
use App\Models\ResaleRequirement;
use App\Models\UnitInspection;
use App\Models\UnitNomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndCrmWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_rental_lifecycle_from_lead_to_commission(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $rentalAgent = User::factory()->create([
            'email' => 'agent@test.com',
            'role' => User::ROLE_RENTAL_AGENT,
        ]);

        // 1. تسجيل عميل موحد 360° مع تنقية الهاتف
        $client = Client::create([
            'name' => 'د. هاني شاكر',
            'phone' => '+20 100 123 4567', // Raw input with spaces
            'email' => 'hani@example.com',
        ]);
        $this->assertEquals('201001234567', $client->normalized_phone);

        // 2. إنشاء طلب إيجار مع الميزانية المزدوجة
        $rentalRequest = RentalRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $rentalAgent->id,
            'status' => RentalRequest::STATUS_NEW,
        ]);

        RentalRequirement::create([
            'rental_request_id' => $rentalRequest->id,
            'property_type' => 'شقة',
            'preferred_locations' => 'كمباوند ميفيدا',
            'stated_budget_max' => 45000,
            'qualified_budget_max' => 50000,
        ]);

        // 3. ترشيح وحدة مناسبة
        $nomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $rentalRequest->id,
            'title' => 'شقة 220م الترا لوكس بميفيدا',
            'location' => 'ميفيدا التجمع الخامس',
            'price' => 48000,
        ]);

        // صمام أمان المعاينات: الوحدة غير صالحة قبل الفحص
        $this->assertFalse($nomination->isSuitableForShowing());

        // 4. إجراء الفحص الميداني بنجاح
        UnitInspection::create([
            'unit_nomination_id' => $nomination->id,
            'inspected_by' => $rentalAgent->id,
            'inspected_at' => now(),
            'result' => UnitInspection::RESULT_SUITABLE,
            'condition_rating' => 'ممتاز',
            'is_matched_reality' => true,
        ]);
        $this->assertTrue($nomination->fresh()->isSuitableForShowing());

        // 5. جدولة معاينة ميدانية للعميل
        $showing = ClientShowing::create([
            'unit_nomination_id' => $nomination->id,
            'assigned_to' => $rentalAgent->id,
            'scheduled_at' => now()->addDay(),
            'status' => ClientShowing::STATUS_SCHEDULED,
        ]);

        // تحديث نتيجة المعاينة بعد إتمامها
        $showing->update([
            'status' => ClientShowing::STATUS_COMPLETED,
            'client_feedback' => 'أعجبته الوحدة جداً ومستعد لتوقيع العقد فوراً',
        ]);

        // 6. إضافة متابعة لإعداد العقد
        $followUp = FollowUp::create([
            'client_id' => $client->id,
            'request_type' => 'rental',
            'rental_request_id' => $rentalRequest->id,
            'assigned_to' => $rentalAgent->id,
            'action_required' => 'تجهيز مسودة عقد الإيجار ومراجعة المالك',
            'due_at' => now()->addHours(3),
            'status' => FollowUp::STATUS_PENDING,
        ]);

        $followUp->markCompleted('تمت مراجعة العقد مع المالك وجاهز للتوقيع', $rentalAgent->id);
        $this->assertEquals(FollowUp::STATUS_COMPLETED, $followUp->fresh()->status);

        // 7. إغلاق الصفقة بنجاح واحتساب العمولة
        $deal = DealOutcome::create([
            'request_type' => 'rental',
            'rental_request_id' => $rentalRequest->id,
            'unit_nomination_id' => $nomination->id,
            'outcome' => DealOutcome::OUTCOME_WON,
            'final_price' => 48000,
            'commission_amount' => 48000, // شهر إيجار عمولة
            'closed_by' => $rentalAgent->id,
            'closed_at' => now(),
        ]);

        $this->assertTrue($deal->isWon());
        $this->assertEquals(48000, (float) $deal->commission_amount);

        // التحقق من فحص الشاشة كمدير
        $response = $this->actingAs($admin)->get("/admin/rental-requests/{$rentalRequest->id}");
        $response->assertStatus(200);
        $response->assertSee('د. هاني شاكر');
        $response->assertSee('كمباوند ميفيدا');
    }

    public function test_complete_resale_lifecycle_from_lead_to_commission(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $resaleAgent = User::factory()->create([
            'email' => 'resale@test.com',
            'role' => User::ROLE_RESALE_AGENT,
        ]);

        // 1. تسجيل عميل ريسيل
        $client = Client::create([
            'name' => 'المهندس كريم الشناوي',
            'phone' => '01233445566',
            'email' => 'karim@example.com',
        ]);

        // 2. إنشاء طلب ريسيل وخطة مالية وتقسيط
        $resaleRequest = ResaleRequest::create([
            'client_id' => $client->id,
            'assigned_to' => $resaleAgent->id,
            'status' => ResaleRequest::STATUS_QUALIFICATION,
            'purpose' => 'سكن شخصي',
        ]);

        ResaleRequirement::create([
            'resale_request_id' => $resaleRequest->id,
            'property_type' => 'تاون هاوس',
            'preferred_locations' => 'كمباوند بادية 6 أكتوبر',
            'payment_method' => 'تقسيط وتسهيلات',
            'total_budget' => 14000000,
            'down_payment_max' => 3000000,
            'installment_capacity' => 400000,
            'installment_frequency' => 'ربع سنوي',
            'installment_years' => 7,
            'delivery_target' => 'فوري',
        ]);

        // 3. ترشيح وحدة وفحصها
        $nomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RESALE,
            'resale_request_id' => $resaleRequest->id,
            'title' => 'تاون هاوس كورنر 280م ببادية بالم هيلز',
            'location' => '6 أكتوبر - بادية',
            'price' => 13500000,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $nomination->id,
            'inspected_by' => $resaleAgent->id,
            'inspected_at' => now(),
            'result' => UnitInspection::RESULT_SUITABLE,
            'condition_rating' => 'ممتاز',
            'is_matched_reality' => true,
        ]);

        // 4. جدولة معاينة موقع مع العميل
        $showing = ClientShowing::create([
            'unit_nomination_id' => $nomination->id,
            'assigned_to' => $resaleAgent->id,
            'scheduled_at' => now()->addDays(2),
            'status' => ClientShowing::STATUS_COMPLETED,
            'client_feedback' => 'تم الاتفاق وسداد مقدم الحجز',
        ]);

        // 5. إغلاق الصفقة بعمولة 2.5%
        $deal = DealOutcome::create([
            'request_type' => 'resale',
            'resale_request_id' => $resaleRequest->id,
            'unit_nomination_id' => $nomination->id,
            'outcome' => DealOutcome::OUTCOME_WON,
            'final_price' => 13500000,
            'commission_amount' => 337500, // 2.5% عمولة
            'closed_by' => $resaleAgent->id,
            'closed_at' => now(),
        ]);

        $this->assertEquals(337500, (float) $deal->commission_amount);

        // التحقق من فحص الشاشة كمدير
        $response = $this->actingAs($admin)->get("/admin/resale-requests/{$resaleRequest->id}");
        $response->assertStatus(200);
        $response->assertSee('المهندس كريم الشناوي');
        $response->assertSee('كمباوند بادية 6 أكتوبر');
    }
}
