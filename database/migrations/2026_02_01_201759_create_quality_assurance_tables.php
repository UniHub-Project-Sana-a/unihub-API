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
        // 1. جدول نماذج التقييم (QA Forms)
        // هذا الجدول يمثل "رأس" الاستمارة. يمكن أن يكون هناك نماذج مختلفة (نظري، عملي، دراسات عليا، إلخ).
        Schema::create('qa_forms', function (Blueprint $table) {
            $table->increments('form_id');
            $table->string('title', 150); // مثال: "استمارة تقييم عضو هيئة التدريس 2025"
            $table->string('description', 255)->nullable();
            $table->enum('target_type', ['theory', 'practical', 'both'])->default('theory'); // طبيعة المقرر المستهدف
            $table->boolean('is_active')->default(true); // هل النموذج فعال حالياً؟
            $table->unsignedInteger('college_id')->nullable(); // إذا كان النموذج خاص بكلية معينة (null = للجامعة كلها)
            $table->string('academic_year', 20); // العام الدراسي المرتبط به
            
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. جدول المجالات (QA Domains)
        // يمثل العمود الأيمن في الصورة: "الشخصية"، "تنفيذ المحاضرة"، "التقييم".
        Schema::create('qa_domains', function (Blueprint $table) {
            $table->increments('domain_id');
            $table->unsignedInteger('form_id');
            $table->string('domain_name', 100); // اسم المجال
            $table->integer('sort_order')->default(0); // لترتيب ظهور المجالات في الواجهة
            
            $table->foreign('form_id')->references('form_id')->on('qa_forms')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. جدول الأسئلة / الفقرات (QA Questions)
        // يمثل الأسئلة المرقمة (1, 2, 3...) في الصورة.
        Schema::create('qa_questions', function (Blueprint $table) {
            $table->increments('question_id');
            $table->unsignedInteger('domain_id');
            $table->text('question_text'); // نص السؤال: "يعزز الهوية الإيمانية..."
            $table->integer('sort_order')->default(0); // ترتيب السؤال داخل المجال
            
            $table->foreign('domain_id')->references('domain_id')->on('qa_domains')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. جدول عمليات التقييم المفتوحة (QA Campaigns / Assignments)
        // هذا الجدول يربط النموذج بالمواد أو الأقسام. 
        // يسمح لنا بتحديد: "هذا النموذج يطبق على هذه المواد في هذا الوقت".
        Schema::create('qa_campaigns', function (Blueprint $table) {
            $table->increments('campaign_id');
            $table->string('campaign_name', 100); // "تقييم الفصل الأول 2025"
            $table->unsignedInteger('form_id');
            $table->unsignedInteger('semester_id'); // لربطه بالفصل الدراسي الحالي
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_published')->default(false); // هل يظهر للطلاب الآن؟
            
            $table->foreign('form_id')->references('form_id')->on('qa_forms')->onDelete('cascade');
            $table->foreign('semester_id')->references('semester_id')->on('semesters')->onDelete('cascade');
            $table->timestamps();
        });

        // 5. جدول استجابات الطلاب - الرأس (QA Submissions)
        // يسجل أن الطالب الفلاني قيم المحاضر الفلاني في المادة الفلانية.
        // يمنع التكرار (طالب واحد للمادة الواحدة للمحاضر الواحد).
        Schema::create('qa_submissions', function (Blueprint $table) {
            $table->increments('submission_id');
            $table->unsignedInteger('campaign_id');
            $table->unsignedInteger('student_id'); // الطالب المقيم
            $table->unsignedInteger('lecturer_id'); // المحاضر الذي يتم تقييمه
            $table->unsignedInteger('course_id'); // المادة
            $table->unsignedInteger('submission_date_timestamp')->nullable(); // توقيت التسليم
            
            // بيانات إضافية للتوثيق التاريخي (كما في رأس الاستمارة)
            $table->boolean('is_practical')->default(false); // هل المقرر عملي؟ (من خانة طبيعة المقرر)
            
            $table->foreign('campaign_id')->references('campaign_id')->on('qa_campaigns')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');

            // القيد الأهم: الطالب يقيم المحاضر في المادة مرة واحدة فقط لكل حملة تقييم
            $table->unique(['campaign_id', 'student_id', 'lecturer_id', 'course_id'], 'unique_student_evaluation');
            
            $table->timestamps();
        });

        // 6. جدول تفاصيل الإجابات (QA Answers)
        // يخزن إجابة كل سؤال (1, 2, 3).
        Schema::create('qa_answers', function (Blueprint $table) {
            $table->id('answer_id'); // BigInt لكثرة البيانات المتوقعة
            $table->unsignedInteger('submission_id');
            $table->unsignedInteger('question_id');
            $table->tinyInteger('rating_value'); // القيمة: 3, 2, 1
            
            $table->foreign('submission_id')->references('submission_id')->on('qa_submissions')->onDelete('cascade');
            $table->foreign('question_id')->references('question_id')->on('qa_questions')->onDelete('cascade');
            
            // فهرس لتحسين سرعة استخراج التقارير والتحليل الإحصائي
            $table->index(['question_id', 'rating_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qa_answers');
        Schema::dropIfExists('qa_submissions');
        Schema::dropIfExists('qa_campaigns');
        Schema::dropIfExists('qa_questions');
        Schema::dropIfExists('qa_domains');
        Schema::dropIfExists('qa_forms');
    }
};