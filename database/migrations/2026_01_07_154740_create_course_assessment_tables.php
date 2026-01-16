<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول بنود التقييم (رؤوس الأعمدة في الإكسل)
        Schema::create('course_assessments', function (Blueprint $table) {
            $table->increments('assessment_id');
            
            // المحددات الأساسية للوعاء
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('semester_id');
            $table->unsignedInteger('created_by'); // المحاضر
            
            // ✅ الإضافة الجوهرية: العام الدراسي
            // يجب أن يطابق العام الدراسي الموجود في جدول timetable
            $table->string('academic_year', 20); 

            // تفاصيل التقييم
            $table->string('name', 100); // مثال: "نصفي"، "واجب 1"
            $table->decimal('max_score', 5, 2); // الدرجة العظمى (مثلاً 20)
            $table->integer('weight')->default(0); // وزن نسبي (اختياري للتقارير)
            
            $table->timestamps();
            $table->softDeletes();

            // العلاقات
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('semester_id')->references('semester_id')->on('semesters')->onDelete('cascade');
            $table->foreign('created_by')->references('lecturer_id')->on('lecturers')->onDelete('cascade');

            // ✅ فهرس مركب لسرعة البحث والتقارير
            // هذا الفهرس يضمن سرعة استدعاء "كل تقييمات مادة معينة لمجموعة معينة في سنة معينة"
            $table->index(['course_id', 'group_id', 'academic_year', 'semester_id'], 'assessment_context_index');
        });

        // 2. جدول الدرجات (القيم داخل الخلايا)
        // هذا الجدول يبقى كما هو، لأنه يعتمد على assessment_id الذي يحمل بداخله كل تفاصيل السنة والمادة
        Schema::create('student_grades', function (Blueprint $table) {
            $table->increments('grade_id');
            $table->unsignedInteger('assessment_id');
            $table->unsignedInteger('student_id');
            
            $table->decimal('score', 5, 2); 
            $table->text('notes')->nullable(); 

            $table->timestamps();

            $table->foreign('assessment_id')->references('assessment_id')->on('course_assessments')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            
            // قيد: لا يمكن رصد درجتين لنفس الطالب في نفس الاختبار
            $table->unique(['assessment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
        Schema::dropIfExists('course_assessments');
    }
};