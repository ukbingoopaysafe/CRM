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
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('broker_id')->nullable()->constrained('brokers')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->default('أخرى'); // فيسبوك، واتساب، ترشيح صديق، إلخ
            $table->string('status')->default('جديد')->index(); // جديد، نشط، تم التواصل، جارٍ البحث، تم إرسال خيارات، معاينة مجدولة، قيد التفاوض، معلق، مغلق بنجاح، مغلق دون نجاح
            $table->string('family_status')->nullable(); // فردي، عائلة، عدد الأفراد
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rental_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->unique()->constrained('rental_requests')->cascadeOnDelete();
            $table->string('property_type')->nullable(); // شقة، فيلا، دوبلكس، بنتهاوس، ستوديو
            $table->unsignedInteger('bedrooms')->nullable();
            $table->string('furnishing_status')->default('غير مفروش'); // مفروش، غير مفروش، أجهزة ومطبخ فقط
            $table->text('preferred_locations')->nullable();
            $table->string('rental_duration')->nullable(); // سنوي، شهري
            $table->date('move_in_date')->nullable();
            $table->decimal('stated_budget_min', 12, 2)->default(0);
            $table->decimal('stated_budget_max', 12, 2)->default(0);
            $table->decimal('qualified_budget_min', 12, 2)->nullable();
            $table->decimal('qualified_budget_max', 12, 2)->nullable();
            $table->string('currency', 10)->default('EGP');
            $table->text('discussion_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_requirements');
        Schema::dropIfExists('rental_requests');
    }
};
