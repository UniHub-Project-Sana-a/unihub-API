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
        Schema::table('qa_campaigns', function (Blueprint $table) {
            // حذف الارتباط بالفصل الدراسي القديم
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
    
            // إضافة الأعمدة الجديدة
            $table->string('academic_year', 20)->after('form_id'); // السنة من جدول timetable
            $table->integer('min_attendance_percentage')->default(0)->after('academic_year'); // نسبة الحرمان
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
