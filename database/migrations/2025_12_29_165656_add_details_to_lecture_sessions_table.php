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
        Schema::table('lecture_sessions', function (Blueprint $table) {
            
            // 1. عمود رقم المحاضر (لتحديد المحاضر الفعلي للجلسة في حال كان بديلاً)
            // جعلته nullable ليأخذ القيمة من الجدول الدراسي (timetable) في حال لم يتم تغييره
            $table->unsignedInteger('lecturer_id')->nullable()->after('timetable_id');

            // 3. نوع المحاضرة (أساسية أم تعويضية)
            // default(false) تعني أنها محاضرة أساسية افتراضياً
            $table->boolean('is_makeup')->default(false)->after('status')->comment('0: Basic, 1: Makeup');

            // --- إضافة المفاتيح الأجنبية (Foreign Keys) ---
            
            // ربط المحاضر بجدول المحاضرين
            $table->foreign('lecturer_id')
                  ->references('lecturer_id')
                  ->on('lecturers')
                  ->onDelete('cascade'); // أو set null حسب رغبتك
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_sessions', function (Blueprint $table) {
            // حذف المفاتيح الأجنبية أولاً
            $table->dropForeign(['lecturer_id']);

            // حذف الأعمدة
            $table->dropColumn(['lecturer_id', 'is_makeup']);
        });
    }
};