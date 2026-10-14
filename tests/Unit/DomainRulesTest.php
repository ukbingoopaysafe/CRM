<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\UnitInspection;
use App\Models\UnitNomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_phone_normalization(): void
    {
        $normalized1 = Client::normalizePhone('010 1234-5678');
        $this->assertEquals('201012345678', $normalized1);

        $normalized2 = Client::normalizePhone('+20 (11) 9876-5432');
        $this->assertEquals('201198765432', $normalized2);

        $client = Client::create([
            'name' => 'عميل اختبار',
            'phone' => '012 3333 4444',
        ]);

        $this->assertEquals('201233334444', $client->normalized_phone);
    }

    public function test_unit_nomination_inspection_gatekeeper_rule(): void
    {
        $nomination = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'title' => 'شقة للتجربة',
            'price' => 30000,
        ]);

        // بدون فحص مسجل -> لا تجوز المعاينة
        $this->assertFalse($nomination->isSuitableForShowing());

        // تسجيل فحص بنتيجة "غير مناسبة"
        $inspection1 = UnitInspection::create([
            'unit_nomination_id' => $nomination->id,
            'inspected_at' => now()->subDay(),
            'result' => UnitInspection::RESULT_UNSUITABLE,
            'rejection_reason' => 'تسريب مياه',
        ]);

        $this->assertFalse($nomination->fresh()->isSuitableForShowing());

        // تسجيل فحص أحدث بنتيجة "مناسبة" بعد الصيانة
        $inspection2 = UnitInspection::create([
            'unit_nomination_id' => $nomination->id,
            'inspected_at' => now(),
            'result' => UnitInspection::RESULT_SUITABLE,
        ]);

        $this->assertTrue($nomination->fresh()->isSuitableForShowing());
    }

    public function test_follow_up_scopes_and_overdue_detection(): void
    {
        $overdue = FollowUp::create([
            'due_at' => now()->subHours(2),
            'action_required' => 'اتصال متأخر',
            'status' => FollowUp::STATUS_PENDING,
        ]);

        $upcoming = FollowUp::create([
            'due_at' => now()->addDay(),
            'action_required' => 'اتصال قادم',
            'status' => FollowUp::STATUS_PENDING,
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($upcoming->isOverdue());
        $this->assertEquals(1, FollowUp::overdue()->count());
    }

    public function test_user_roles_and_helpers(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $rentalAgent = new User(['role' => User::ROLE_RENTAL_AGENT]);
        $resaleAgent = new User(['role' => User::ROLE_RESALE_AGENT]);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isRentalAgent());
        $this->assertTrue($admin->isResaleAgent());

        $this->assertFalse($rentalAgent->isAdmin());
        $this->assertTrue($rentalAgent->isRentalAgent());
        $this->assertFalse($rentalAgent->isResaleAgent());

        $this->assertFalse($resaleAgent->isAdmin());
        $this->assertFalse($resaleAgent->isRentalAgent());
        $this->assertTrue($resaleAgent->isResaleAgent());
    }
}
