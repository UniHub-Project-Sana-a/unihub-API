<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('makeup_lectures_requests', function (Blueprint $table) {
            
            // 1. التاريخ الأصلي للمحاضرة التي غاب عنها (للربط والتوثيق)
            $table->date('original_date')->nullable()->after('group_id');
            
            $table->time('start_time')->nullable()->after('requested_date');
            $table->time('end_time')->nullable()->after('start_time');

            // 3. القاعة المقترحة (اختياري)
            $table->unsignedInteger('classroom_id')->nullable()->after('end_time');
            
            // 4. نوع السبب (قائمة محددة)
            $table->enum('reason_type', [
                'sick_leave',       // إجازة مرضية
                'travel',           // سفر
                'schedule_conflict',// تعارض جداول
                'official_holiday', // أعياد وطنية / مولد
                'event',            // فعاليات ومؤتمرات
                'maintenance',      // صيانة معامل/قاعة
                'other'             // أخرى
            ])->default('other')->after('classroom_id');

            // 5. وصف إضافي للسبب (اختياري)
            $table->text('description')->nullable()->after('reason_type');

            // القيود
            $table->foreign('classroom_id')->references('classroom_id')->on('classrooms')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('makeup_lectures_requests', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropColumn(['original_date', 'start_time', 'end_time', 'classroom_id', 'reason_type', 'description']);
        });
    }
};