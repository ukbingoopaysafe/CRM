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
        Schema::create('deal_outcomes', function (Blueprint $table) {
            $table->id();
            $table->string('request_type')->index(); // rental, resale
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->nullOnDelete();
            $table->foreignId('resale_request_id')->nullable()->constrained('resale_requests')->nullOnDelete();
            $table->foreignId('unit_nomination_id')->nullable()->constrained('unit_nominations')->nullOnDelete();
            $table->foreignId('broker_id')->nullable()->constrained('brokers')->nullOnDelete();
            $table->string('outcome')->index(); // won (ناجحة), lost (غير ناجحة)
            $table->decimal('final_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('EGP');
            $table->decimal('commission_amount', 15, 2)->nullable(); // عمولة الشركة
            $table->decimal('broker_commission', 15, 2)->nullable(); // عمولة الوسيط
            $table->string('loss_reason')->nullable(); // سبب الخسارة عند عدم النجاح
            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('request_type')->nullable(); // rental, resale, general
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->cascadeOnDelete();
            $table->foreignId('resale_request_id')->nullable()->constrained('resale_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('ملاحظة'); // مكالمة هاتفية، واتساب، اجتماع، معاينة، فحص، ترشيح وحدة، تغيير حالة، إغلاق صفقة
            $table->text('description');
            $table->string('result')->nullable(); // رد العميل، لم يرد، مهتم، غير مهتم
            $table->dateTime('occurred_at');
            $table->timestamps();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->string('request_type')->nullable(); // rental, resale
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->cascadeOnDelete();
            $table->foreignId('resale_request_id')->nullable()->constrained('resale_requests')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('due_at')->index();
            $table->string('action_required'); // الاتصال لتأكيد موعد المعاينة، إرسال خيارات جديدة، التفاوض على السعر
            $table->text('notes')->nullable();
            $table->string('status')->default('معلقة')->index(); // معلقة، مكتملة، ملغاة
            $table->dateTime('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('deal_outcomes');
    }
};
