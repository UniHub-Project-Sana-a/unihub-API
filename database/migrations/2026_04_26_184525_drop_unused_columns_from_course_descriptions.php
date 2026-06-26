<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_descriptions', function (Blueprint $table) {
            // ✅ احذف الأعمدة غير المطلوبة
            $table->dropColumn([
                'is_approved',
                'approved_by',
                'approval_date'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('course_descriptions', function (Blueprint $table) {
            // للعودة في حالة الخطأ
            $table->boolean('is_approved')->default(false);
            $table->string('approved_by')->nullable();
            $table->timestamp('approval_date')->nullable();
        });
    }
};