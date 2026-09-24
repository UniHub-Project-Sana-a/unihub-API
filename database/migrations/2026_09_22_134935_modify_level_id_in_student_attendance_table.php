<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_attendance')) {
            return;
        }

        // فقط حذف Foreign Key وتعديل العمود
        // بدون إعادة إنشاء الـ FK (سيتم إنشاؤه في migration آخر)
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
        });

        Schema::table('student_attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->nullable()->change();
        });

        // لا نعيد إنشاء Foreign Key هنا
        // سيتم ذلك تلقائياً في migrations أخرى أو يدوياً لاحقاً
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_attendance')) {
            return;
        }

        Schema::table('student_attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->nullable(false)->change();
        });
    }
};