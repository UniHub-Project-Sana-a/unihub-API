<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول مخرجات التعلم (Learning Outcomes - LOs)
        // A, B, C, D in your sketch
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->increments('outcome_id');
            $table->unsignedInteger('course_id');
            $table->string('code', 50)->nullable(); // e.g., CLO1, K1, S2
            $table->text('description'); // "القدرة على تحليل النظم..."
            
            // Domain of learning (Cognitive, Psychomotor, Affective) - اختياري ولكن مفيد للجودة
            $table->string('domain', 50)->nullable(); 
            
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. جدول مواضيع المادة (Course Topics)
        // القائمة الموجودة في أعلى الورقة
        Schema::create('course_topics', function (Blueprint $table) {
            $table->increments('topic_id');
            $table->unsignedInteger('course_id');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0); // لترتيب المواضيع في العرض (الأسبوع الأول، الثاني...)
            
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. جدول ربط المواضيع بمخرجات التعلم (Pivot: Topics <-> LOs)
        // الاسهم في رسمتك بين Topic و LearningOutcome
        Schema::create('topic_learning_outcomes', function (Blueprint $table) {
            $table->unsignedInteger('topic_id');
            $table->unsignedInteger('outcome_id');
            
            $table->primary(['topic_id', 'outcome_id']);
            
            $table->foreign('topic_id')->references('topic_id')->on('course_topics')->onDelete('cascade');
            $table->foreign('outcome_id')->references('outcome_id')->on('learning_outcomes')->onDelete('cascade');
        });

        // 4. جدول الأسئلة (Questions)
        // Questions -> Topics
        Schema::create('qa_questions_topics', function (Blueprint $table) {
            $table->increments('question_id');
            
            $table->unsignedInteger('topic_id');
            
            $table->unsignedInteger('outcome_id')->nullable(); 

            $table->text('question_text');
            $table->enum('question_type', ['MCQ'])->default('MCQ');
            $table->integer('difficulty_level')->default(1);
            $table->boolean('is_active')->default(true);

            $table->foreign('topic_id')->references('topic_id')->on('course_topics')->onDelete('cascade');
            $table->foreign('outcome_id')->references('outcome_id')->on('learning_outcomes')->onDelete('set null');
            
            $table->timestamps();
        });

        // 5. جدول خيارات الإجابة (MCQ Options)
        // A, B, C under Questions
        Schema::create('qa_question_options', function (Blueprint $table) {
            $table->increments('option_id');
            $table->unsignedInteger('question_id');
            $table->string('option_text', 500);
            $table->boolean('is_correct')->default(false); // تحديد الإجابة الصحيحة
            
            $table->foreign('question_id')->references('question_id')->on('qa_questions_topics')->onDelete('cascade');
            $table->timestamps();
        });

        // 6. جدول توثيق المواضيع المشروحة في الجلسة (Execution / Tracking)
        // Lecture Session -> Course Topic
        // هذا الجدول هو الذي يحقق طلبك "معرفة أي المواضيع تم اختيارها وفي أي محاضرة"
        Schema::create('session_topics_covered', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('session_id'); // مربوط بجدولك القديم lecture_sessions
            $table->unsignedInteger('topic_id');   // مربوط بجدول المواضيع أعلاه
            
            // حالة التغطية (تم الشرح بالكامل، مقدمة فقط، مراجعة)
            $table->string('coverage_status', 50)->default('fully_covered'); 
            
            $table->foreign('session_id')->references('session_id')->on('lecture_sessions')->onDelete('cascade');
            $table->foreign('topic_id')->references('topic_id')->on('course_topics')->onDelete('cascade');
            
            // لا يمكن تكرار نفس الموضوع لنفس الجلسة
            $table->unique(['session_id', 'topic_id']);
            $table->timestamps();
        });
        
        // 7. (إضافي) إجابات الطلاب للتوثيق (Student Answers Snapshot)
        // كما في رسمتك "Student Answers"
        // يستخدم هذا الجدول إذا قام المحاضر بعرض سؤال داخل المحاضرة وأجاب عليه الطلاب
        Schema::create('student_lecture_answers', function (Blueprint $table) {
            $table->increments('answer_id');
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('selected_option_id');
            $table->boolean('is_correct'); // نخزنها لتسريع التقارير
            
            $table->foreign('session_id')->references('session_id')->on('lecture_sessions')->onDelete('cascade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('question_id')->references('question_id')->on('qa_questions_topics')->onDelete('cascade');
            $table->foreign('selected_option_id')->references('option_id')->on('qa_question_options')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        
        Schema::dropIfExists('student_lecture_answers');
        Schema::dropIfExists('session_topics_covered');
        Schema::dropIfExists('qa_question_options');
        Schema::dropIfExists('qa_questions_topics');
        Schema::dropIfExists('topic_learning_outcomes');
        Schema::dropIfExists('course_topics');
        Schema::dropIfExists('learning_outcomes');
    }
};