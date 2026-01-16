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
            $table->dropColumn([
                'attendance_overage_alert',
                'actual_attendance_count',
                'system_attendance_count',
            ]);
        });

        // إضافة شعار الكلية إلى جدول colleges
        Schema::table('colleges', function (Blueprint $table) {
            $table->string('college_logo')->nullable()->after('college_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_sessions', function (Blueprint $table) {
            $table->boolean('attendance_overage_alert')->nullable();
            $table->unsignedInteger('actual_attendance_count')->nullable();
            $table->unsignedInteger('system_attendance_count')->nullable();
        });

        // حذف عمود شعار الكلية
        Schema::table('colleges', function (Blueprint $table) {
            $table->dropColumn('college_logo');
        });
    }
};