<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Broker;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic real estate CRM data.
     */
    public function run(): void
    {
        // 1. المستخدمون والأدوار
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'phone' => '01000000001',
                'is_active' => true,
            ]
        );

        $rentalAgent = User::firstOrCreate(
            ['email' => 'rental@crm.com'],
            [
                'name' => 'أحمد عزت (مسؤول إيجارات)',
                'password' => Hash::make('agent123'),
                'role' => User::ROLE_RENTAL_AGENT,
                'phone' => '01111111112',
                'is_active' => true,
            ]
        );

        $resaleAgent = User::firstOrCreate(
            ['email' => 'resale@crm.com'],
            [
                'name' => 'سارة حسن (مسؤولة ريسيل ومبيعات)',
                'password' => Hash::make('agent123'),
                'role' => User::ROLE_RESALE_AGENT,
                'phone' => '01222222223',
                'is_active' => true,
            ]
        );

        // 2. الوسطاء والشركاء
        $broker1 = Broker::create([
            'name' => 'كريم الشناوي',
            'phone' => '01099887766',
            'company_name' => 'برايم لوكيشن للوساطة العقارية',
            'notes' => 'وسيط موثوق في التجمع الخامس، عمولة الإيجار شهر مناصفة، والبيع 1%',
            'created_by' => $admin->id,
        ]);

        $broker2 = Broker::create([
            'name' => 'مكتب أفق الشيخ زايد',
            'phone' => '01233445566',
            'company_name' => 'أفق زايد لإدارة الأصول',
            'notes' => 'متخصصون في فيلات ويست تاون وبيفرلي هيلز',
            'created_by' => $admin->id,
        ]);

        // 3. العملاء (مع كشف تكرار الهواتف وتوحيدها تلقائياً)
        $client1 = Client::create([
            'name' => 'م. طارق عبد الرحمن',
            'phone' => '01012345678',
            'email' => 'tarek@example.com',
            'nationality' => 'مصري',
            'notes' => 'مهندس استشاري، يبحث عن شقة مفروشة راقية للإيجار، ومهتم بشراء تاون هاوس ريسيل',
            'created_by' => $rentalAgent->id,
        ]);

        $client2 = Client::create([
            'name' => 'د. منى الشريف',
            'phone' => '01123456789',
            'email' => 'mona@example.com',
            'nationality' => 'مصرية',
            'notes' => 'طبيبة، تبحث عن بنتهاوس للبيع ريسيل كاش فوري في التجمع الخامس',
            'created_by' => $resaleAgent->id,
        ]);

        $client3 = Client::create([
            'name' => 'أ. عبد الله الهاشمي',
            'phone' => '01234567890',
            'email' => 'alhashemi@example.com',
            'nationality' => 'إماراتي',
            'notes' => 'مستثمر، يبحث عن وحدات تجارية أو سكنية للإيجار السنوي المتكرر',
            'created_by' => $rentalAgent->id,
        ]);

        $client4 = Client::create([
            'name' => 'م. حسام الدين',
            'phone' => '01055566677',
            'email' => 'hossam@example.com',
            'nationality' => 'مصري',
            'notes' => 'يبحث عن فيلا تاون هاوس ريسيل تقسيط مع استلام خلال سنة',
            'created_by' => $resaleAgent->id,
        ]);

        // 4. طلبات الإيجار والمتطلبات والترشيحات والفحص والمعاينات
        $rentalReq1 = RentalRequest::create([
            'client_id' => $client1->id,
            'assigned_to' => $rentalAgent->id,
            'source' => 'إعلان ممول فيسبوك',
            'status' => RentalRequest::STATUS_SHOWING_SCHEDULED,
            'family_status' => 'أسرة مكونة من 4 أفراد',
            'notes' => 'جاهز للسكن الفوري، يفضل أدوار متكررة مع مصعد',
        ]);

        RentalRequirement::create([
            'rental_request_id' => $rentalReq1->id,
            'property_type' => 'شقة',
            'bedrooms' => 3,
            'furnishing_status' => 'مفروش',
            'preferred_locations' => 'التجمع الخامس - النرجس عمارات أو كمباوند ميفيدا',
            'rental_duration' => 'سنة قابلة للتجديد',
            'move_in_date' => now()->addDays(15),
            'stated_budget_min' => 30000,
            'stated_budget_max' => 45000,
            'qualified_budget_min' => 40000,
            'qualified_budget_max' => 50000,
            'currency' => 'EGP',
            'discussion_notes' => 'وافق على زيادة الميزانية حتى 50 ألف في حال كان الفرش فندقياً بالكامل',
        ]);

        // ترشيح وحدة 1 (تم فحصها وهي مناسبة وتمت جدولة معاينة)
        $nomination1 = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $rentalReq1->id,
            'unit_code' => 'MIV-A-204',
            'title' => 'شقة 210م مفروشة الترا لوكس بكمباوند ميفيدا',
            'location' => 'التجمع الخامس - ميفيدا',
            'property_type' => 'شقة',
            'bedrooms' => 3,
            'price' => 48000,
            'currency' => 'EGP',
            'media_url' => 'https://photos.app.goo.gl/sample-unit-1',
            'client_reaction' => UnitNomination::REACTION_INTERESTED,
            'reaction_notes' => 'أعجبته الصور ويطلب معاينة يوم الجمعة',
            'created_by' => $rentalAgent->id,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $nomination1->id,
            'inspected_by' => $rentalAgent->id,
            'inspected_at' => now()->subDays(2),
            'result' => UnitInspection::RESULT_SUITABLE,
            'is_matched_reality' => true,
            'condition_rating' => 'ممتاز وفندقي',
            'notes' => 'التكييفات والأجهزة تعمل بكفاءة، والعقار جاهز للمعاينة الفورية',
        ]);

        ClientShowing::create([
            'unit_nomination_id' => $nomination1->id,
            'assigned_to' => $rentalAgent->id,
            'scheduled_at' => now()->addDays(2)->setHour(16)->setMinute(0),
            'status' => ClientShowing::STATUS_CONFIRMED,
            'notes' => 'تم تأكيد الموعد مع العميل وحارس العقار',
        ]);

        // ترشيح وحدة 2 (فحصت وظهرت غير مناسبة، لذا يمنع النظام المعاينة عليها)
        $nomination2 = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RENTAL,
            'rental_request_id' => $rentalReq1->id,
            'unit_code' => 'NRG-B-12',
            'title' => 'شقة 180م مفروشة بالنرجس عمارات',
            'location' => 'التجمع الخامس - النرجس',
            'property_type' => 'شقة',
            'bedrooms' => 3,
            'price' => 38000,
            'currency' => 'EGP',
            'client_reaction' => UnitNomination::REACTION_PENDING,
            'created_by' => $rentalAgent->id,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $nomination2->id,
            'inspected_by' => $rentalAgent->id,
            'inspected_at' => now()->subDay(),
            'result' => UnitInspection::RESULT_UNSUITABLE,
            'is_matched_reality' => false,
            'condition_rating' => 'يحتاج صيانة ونظافة عميقة',
            'rejection_reason' => 'الصور قديمة والفرش متهالك وتوجد تسريبات مياه في الحمام الرئيسي',
        ]);

        // 5. طلب ريسيل ناجح ومكتمل (Closed Won Deal)
        $resaleReq1 = ResaleRequest::create([
            'client_id' => $client2->id,
            'assigned_to' => $resaleAgent->id,
            'source' => 'ترشيح من عميل سابق',
            'status' => ResaleRequest::STATUS_CLOSED_WON,
            'purpose' => 'سكن شخصي',
            'notes' => 'عميلة جادة جداً تبحث عن تميز وخصوصية',
        ]);

        ResaleRequirement::create([
            'resale_request_id' => $resaleReq1->id,
            'property_type' => 'بنتهاوس',
            'bedrooms' => 4,
            'preferred_locations' => 'كمباوند قطامية ديونز أو هايد بارك التجمع',
            'payment_method' => 'كاش فوري',
            'total_budget' => 14000000,
            'down_payment_max' => 14000000,
            'delivery_target' => 'فوري',
            'finishing_level' => 'الترا سوبر لوكس',
            'currency' => 'EGP',
            'financial_notes' => 'السيولة جاهزة في حساب بنكي داخل مصر',
        ]);

        $resaleNom1 = UnitNomination::create([
            'request_type' => UnitNomination::TYPE_RESALE,
            'resale_request_id' => $resaleReq1->id,
            'unit_code' => 'HYD-PH-09',
            'title' => 'بنتهاوس 320م + روف 140م فيو لاندسكيب هايد بارك',
            'location' => 'التجمع الخامس - هايد بارك',
            'property_type' => 'بنتهاوس',
            'bedrooms' => 4,
            'price' => 13500000,
            'currency' => 'EGP',
            'client_reaction' => UnitNomination::REACTION_INTERESTED,
            'created_by' => $resaleAgent->id,
        ]);

        UnitInspection::create([
            'unit_nomination_id' => $resaleNom1->id,
            'inspected_by' => $resaleAgent->id,
            'inspected_at' => now()->subDays(10),
            'result' => UnitInspection::RESULT_SUITABLE,
            'is_matched_reality' => true,
            'condition_rating' => 'تشطيب خاص استثنائي',
            'notes' => 'خالص الأقساط ووديعة الصيانة من المطور',
        ]);

        ClientShowing::create([
            'unit_nomination_id' => $resaleNom1->id,
            'assigned_to' => $resaleAgent->id,
            'scheduled_at' => now()->subDays(7),
            'status' => ClientShowing::STATUS_COMPLETED,
            'client_feedback' => 'أبدت إعجابها التام بالفيو والتشطيب وتم تقديم عرض سعر',
        ]);

        DealOutcome::create([
            'request_type' => 'resale',
            'resale_request_id' => $resaleReq1->id,
            'unit_nomination_id' => $resaleNom1->id,
            'broker_id' => $broker1->id,
            'outcome' => DealOutcome::OUTCOME_WON,
            'final_price' => 13200000,
            'currency' => 'EGP',
            'commission_amount' => 198000, // 1.5%
            'broker_commission' => 66000,  // حصة الوسيط
            'notes' => 'تم توقيع العقد النهائي ونقل الملكية بحضور محامي الطرفين',
            'closed_by' => $admin->id,
            'closed_at' => now()->subDays(2),
        ]);

        // 6. طلب ريسيل نشط بنظام التقسيط
        $resaleReq2 = ResaleRequest::create([
            'client_id' => $client4->id,
            'assigned_to' => $resaleAgent->id,
            'source' => 'موقع الشركة',
            'status' => ResaleRequest::STATUS_PROJECTS_SENT,
            'purpose' => 'سكن شخصي',
            'notes' => 'مهتم بكمباوندات الشيخ زايد وسفنكس',
        ]);

        ResaleRequirement::create([
            'resale_request_id' => $resaleReq2->id,
            'property_type' => 'تاون هاوس',
            'bedrooms' => 3,
            'preferred_locations' => 'الشيخ زايد - بادية أو فيليت',
            'payment_method' => 'تقسيط وتسهيلات',
            'total_budget' => 9500000,
            'down_payment_max' => 3000000,
            'installment_capacity' => 250000,
            'installment_frequency' => 'ربع سنوي',
            'installment_years' => 5,
            'delivery_target' => 'خلال سنة',
            'finishing_level' => 'نصف تشطيب',
            'currency' => 'EGP',
        ]);

        // 7. سجل الأنشطة والمتابعات
        Activity::log(
            type: Activity::TYPE_CALL,
            description: 'مكالمة هاتفية مع العميل لتأكيد الميزانية وتفضيلات التشطيب',
            userId: $rentalAgent->id,
            clientId: $client1->id,
            requestType: 'rental',
            requestId: $rentalReq1->id,
            result: 'تم التوافق على نطاق ميزانية 45-50 ألف للفرش الكامل'
        );

        Activity::log(
            type: Activity::TYPE_DEAL_CLOSED,
            description: 'إغلاق صفقة بنتهاوس هايد بارك بنجاح بمبلغ 13,200,000 ج.م',
            userId: $admin->id,
            clientId: $client2->id,
            requestType: 'resale',
            requestId: $resaleReq1->id,
            result: 'تم سداد عمولة الشركة بالكامل'
        );

        // متابعة مستحقة اليوم
        FollowUp::create([
            'client_id' => $client1->id,
            'request_type' => 'rental',
            'rental_request_id' => $rentalReq1->id,
            'assigned_to' => $rentalAgent->id,
            'due_at' => now()->setHour(12)->setMinute(0),
            'action_required' => 'الاتصال بالعميل لتأكيد الحضور لمعاينة ميفيدا',
            'notes' => 'التأكيد على الموقع واللوكيشن عبر واتساب',
            'status' => FollowUp::STATUS_PENDING,
        ]);

        // متابعة متأخرة
        FollowUp::create([
            'client_id' => $client4->id,
            'request_type' => 'resale',
            'resale_request_id' => $resaleReq2->id,
            'assigned_to' => $resaleAgent->id,
            'due_at' => now()->subDay()->setHour(15)->setMinute(0),
            'action_required' => 'إرسال جدول أقساط وحدات بادية بالم هيلز',
            'notes' => 'طلب جدول أقساط تفصيلي مع وديعة الصيانة',
            'status' => FollowUp::STATUS_PENDING,
        ]);

        // متابعة قادمة
        FollowUp::create([
            'client_id' => $client3->id,
            'request_type' => 'rental',
            'assigned_to' => $rentalAgent->id,
            'due_at' => now()->addDays(3)->setHour(11)->setMinute(0),
            'action_required' => 'التواصل لعرض فرص الإيجار التجاري الجديدة',
            'status' => FollowUp::STATUS_PENDING,
        ]);
    }
}
