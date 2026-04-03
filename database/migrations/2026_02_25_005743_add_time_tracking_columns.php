<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تعديل جدول الجلسات (لتوثيق الوقت الفعلي والسبب)
        Schema::table('lecture_sessions', function (Blueprint $table) {
            // وقت البدء الفعلي (لحساب المدة المقضية)
            if (!Schema::hasColumn('lecture_sessions', 'actual_start_time')) {
                $table->timestamp('actual_start_time')->nullable()->after('session_date');
            }
            
            // سبب الخروج المبكر (إذا أنهى قبل الوقت المسموح)
            if (!Schema::hasColumn('lecture_sessions', 'early_exit_reason')) {
                $table->string('early_exit_reason', 255)->nullable()->after('is_ended_remotely');
            }
        });

        // 2. تعديل جدول الجدول الدراسي (لتحديد السماحية)
        Schema::table('timetable', function (Blueprint $table) {
            // فترة السماح بالغياب (بالدقائق) للمحاضرة الواحدة
            // مثال: محاضرة ساعتين، السماحية 30 دقيقة. يعني يجب أن يحضر 90 دقيقة على الأقل.
            if (!Schema::hasColumn('timetable', 'allowance_minutes')) {
                $table->integer('allowance_minutes')->after('lecture_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->dropColumn(['actual_start_time', 'early_exit_reason']);
        });

        Schema::table('timetable', function (Blueprint $table) {
            $table->dropColumn('allowance_minutes');
        });
    }
};