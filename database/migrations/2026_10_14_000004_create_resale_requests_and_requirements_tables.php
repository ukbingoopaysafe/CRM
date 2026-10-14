<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resale_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('broker_id')->nullable()->constrained('brokers')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('أخرى');
            $table->string('status')->default('جديد')->index(); // جديد، تأهيل مالي، ترشيح مشروعات، معاينة موقع، تفاوض وعرض سعر، حجز وتوقيع، مغلق بنجاح، مغلق دون نجاح
            $table->string('purpose')->default('سكن شخصي'); // سكن شخصي، استثمار وإعادة بيع، استثمار للإيجار
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('resale_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resale_request_id')->unique()->constrained('resale_requests')->cascadeOnDelete();
            $table->string('property_type')->nullable(); // شقة، فيلا مستقلة، تاون هاوس، توين هاوس، دوبلكس، بنتهاوس
            $table->unsignedInteger('bedrooms')->nullable();
            $table->text('preferred_locations')->nullable();
            $table->string('payment_method')->default('كلاهما متاح'); // كاش فوري، تقسيط وتسهيلات، كلاهما متاح
            $table->decimal('total_budget', 15, 2)->default(0);
            $table->decimal('down_payment_max', 15, 2)->nullable();
            $table->decimal('installment_capacity', 15, 2)->nullable(); // القسط الدوري
            $table->string('installment_frequency')->default('ربع سنوي'); // شهري، ربع سنوي، نصف سنوي، سنوي
            $table->unsignedInteger('installment_years')->nullable();
            $table->string('delivery_target')->default('فوري'); // استلام فوري، خلال سنة، سنتين، تحت الإنشاء
            $table->string('finishing_level')->default('أي مستوى'); // بدون تشطيب (محارة)، نصف تشطيب، تشطيب كامل، الترا سوبر لوكس
            $table->string('currency', 10)->default('EGP');
            $table->text('financial_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resale_requirements');
        Schema::dropIfExists('resale_requests');
    }
};
