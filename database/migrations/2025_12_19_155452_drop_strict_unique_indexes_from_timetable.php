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
        Schema::table('timetable', function (Blueprint $table) {
            // 1. حذف المفاتيح الأجنبية أولاً (لفك ارتباطها بالفهرس الفريد)
            // ملاحظة: نضع اسم العمود داخل مصفوفة لكي يعرف لارفيل اسم المفتاح تلقائياً
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['lecturer_id']);
            $table->dropForeign(['group_id']);

            // 2. الآن يمكن حذف القيود الفريدة بأمان
            $table->dropUnique('unique_classroom_slot');
            $table->dropUnique('unique_lecturer_slot');
            $table->dropUnique('unique_group_slot');

            // 3. إضافة فهارس عادية (لتحسين الأداء ولأن المفاتيح الأجنبية تحتاجها)
            $table->index('classroom_id');
            $table->index('lecturer_id');
            $table->index('group_id');

            // 4. إعادة إنشاء المفاتيح الأجنبية مرة أخرى
            $table->foreign('classroom_id')->references('classroom_id')->on('classrooms')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // في حالة التراجع، نعيد الوضع كما كان (وهذا قد يفشل إذا كانت البيانات مكررة)
        Schema::table('timetable', function (Blueprint $table) {
            $table->dropForeign(['classroom_id']);
            $table->dropForeign(['lecturer_id']);
            $table->dropForeign(['group_id']);
            
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['lecturer_id']);
            $table->dropIndex(['group_id']);

            $table->unique(['classroom_id', 'day_id', 'period_id'], 'unique_classroom_slot');
            $table->unique(['lecturer_id', 'day_id', 'period_id'], 'unique_lecturer_slot');
            $table->unique(['group_id', 'day_id', 'period_id'], 'unique_group_slot');

            $table->foreign('classroom_id')->references('classroom_id')->on('classrooms')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
        });
    }
};