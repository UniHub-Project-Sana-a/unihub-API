<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // إزالة القيد الفريد الصارم الذي يمنع تكرار نفس الكود للمقررات المشتركة عبر الكليات
            $table->dropUnique('courses_course_code_unique');
            
            // قيد فريد مركب يتيح مشاركة نفس الرمز عبر الكليات والأقسام
            $table->unique(['college_id', 'course_code'], 'courses_college_course_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_college_course_code_unique');
            $table->unique('course_code', 'courses_course_code_unique');
        });
    }
};
