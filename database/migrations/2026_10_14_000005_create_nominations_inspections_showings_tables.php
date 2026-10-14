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
        Schema::create('unit_nominations', function (Blueprint $table) {
            $table->id();
            $table->string('request_type')->index(); // rental, resale
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->cascadeOnDelete();
            $table->foreignId('resale_request_id')->nullable()->constrained('resale_requests')->cascadeOnDelete();
            $table->string('unit_code')->nullable()->index(); // كود الوحدة الداخلي
            $table->string('title'); // عنوان الوحدة أو ملخصها
            $table->string('location')->nullable(); // الكمباوند أو المنطقة
            $table->string('property_type')->nullable(); // نوع الوحدة
            $table->unsignedInteger('bedrooms')->nullable();
            $table->decimal('price', 15, 2)->default(0); // قيمة الإيجار أو سعر البيع الإجمالي
            $table->string('currency', 10)->default('EGP');
            $table->text('media_url')->nullable(); // رابط الصور أو المعاينة
            $table->string('client_reaction')->default('قيد الانتظار'); // قيد الانتظار، أبدى اهتماماً، مرفوضة من العميل
            $table->text('reaction_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('unit_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_nomination_id')->constrained('unit_nominations')->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('inspected_at');
            $table->string('result')->index(); // suitable (مناسبة), unsuitable (غير مناسبة)
            $table->boolean('is_matched_reality')->default(true); // مطابقة للصور والمعلومات
            $table->string('condition_rating')->nullable(); // ممتاز، جيد، يحتاج صيانة
            $table->text('rejection_reason')->nullable(); // إلزامي في حال كانت غير مناسبة
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('client_showings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_nomination_id')->constrained('unit_nominations')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->string('status')->default('مجدولة'); // مجدولة، مؤكدة، مكتملة، عدم حضور العميل، ملغاة، معاد جدولتها
            $table->text('client_feedback')->nullable(); // انطباع ورد العميل
            $table->text('objections')->nullable(); // اعتراضات العميل إن وجدت
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_showings');
        Schema::dropIfExists('unit_inspections');
        Schema::dropIfExists('unit_nominations');
    }
};
