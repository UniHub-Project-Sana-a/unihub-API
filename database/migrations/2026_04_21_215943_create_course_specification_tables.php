<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_learning_outcomes', function (Blueprint $table) {
            $table->increments('plo_id');
            
            $table->unsignedInteger('program_id');
            
            $table->string('code', 10)->comment('مثال: A1, B1, C1, D1');
            
            $table->enum('domain', [
                'Knowledge',      
                'Intellectual',      
                'Professional',        
                'General'              
            ])->comment('مجال المخرج');
            
            $table->text('description')->comment('وصف مخرج التعلم');
            $table->decimal('weight', 5, 2)->default(0)->comment('وزن المخرج من 100');
            $table->boolean('is_active')->default(true);
            
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('program_id')
                ->references('program_id')
                ->on('programs')
                ->onDelete('cascade');
            
            $table->unique(['program_id', 'code']);
            $table->unique(['program_id', 'order'], 'unique_program_order');
            $table->index(['program_id', 'domain']);
        });

        Schema::create('course_descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('course_id');
            $table->longText('description')->nullable()->comment('وصف المقرر');
            $table->json('goals')->nullable()->comment('أهداف المقرر');
            $table->integer('word_count')->default(0)->comment('عدد الكلمات');
            $table->integer('goals_count')->default(0)->comment('عدد الأهداف');
            $table->boolean('is_completed')->default(false)->comment('هل مكتمل');
            $table->boolean('is_approved')->default(false)->comment('هل موافق عليه');
            $table->string('approved_by')->nullable()->comment('الموافق');
            $table->timestamp('approval_date')->nullable()->comment('تاريخ الموافقة');
            $table->timestamps();
            $table->foreign('course_id')
                  ->references('course_id')
                  ->on('courses')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->unique('course_id');
            $table->index('is_completed');
            $table->index('is_approved');
        });

        Schema::create('course_learning_outcomes', function (Blueprint $table) {
            $table->increments('clo_id');
            
            $table->unsignedInteger('course_id');
            
            $table->string('code', 10)->comment('مثال: a1, b1, c1, d1');
            
            $table->enum('domain', [
                'Knowledge',
                'Intellectual',
                'Professional',
                'General'
            ])->comment('مجال المخرج');
            
            $table->text('description')->comment('وصف مخرج التعلم');
            
            $table->decimal('weight', 5, 2)->default(0)
                ->comment('وزن المخرج من وزن المقرر (%)');
            
            $table->unsignedInteger('plo_id')->nullable()
                ->comment('ربط بمخرج تعلم البرنامج المناظر');
            
            $table->decimal('plo_weight', 5, 2)->nullable()
                ->comment('وزن PLO من وزن البرنامج (للمرجع فقط)');
            
            $table->integer('order')->default(0);
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->foreign('plo_id')
                ->references('plo_id')
                ->on('program_learning_outcomes')
                ->onDelete('set null');
            
            $table->unique(['course_id', 'code']);
            
            $table->index(['course_id', 'domain']);
        });

        Schema::create('course_topics', function (Blueprint $table) {
            $table->increments('topic_id');
            
            $table->unsignedInteger('course_id');
            
            $table->enum('part', [
                'نظري',
                'عملي',
                'تمارين',
                'سريري'
            ])->comment('جزء المقرر');
            
            $table->tinyInteger('week')->comment('الأسبوع (1-16)');
            
            $table->string('unit_name', 300)->comment('مثال: مقدمة في هياكل البيانات');
            
            $table->json('subtopics')->nullable()
                ->comment('مصفوفة المواضيع الفرعية');
            
            $table->boolean('is_exam')->default(false)
                ->comment('true = امتحان نصفي أو نهائي');
            
            $table->enum('exam_type', ['midterm', 'final'])->nullable()
                ->comment('نوع الامتحان');
            
            $table->decimal('hours', 5, 2)->default(0)
                ->comment('الساعات الفعلية');
            
            $table->json('clo_ids')->nullable()
                ->comment('مصفوفة رموز مخرجات التعلم');
            
            $table->integer('order')->default(0);
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->index(['course_id', 'part', 'week']);
            
            $table->unique(['course_id', 'part', 'week', 'unit_name']);
        });

        Schema::create('topic_questions', function (Blueprint $table) {
            $table->increments('question_id');
            
            $table->unsignedInteger('topic_id');
            
            $table->string('subtopic', 300)->nullable()
                ->comment('الموضوع الفرعي المحدد');
            
            $table->text('question_text')->comment('نص السؤال');
            
            $table->enum('question_type', ['MCQ', 'essay'])->default('MCQ')
                ->comment('MCQ = اختيار من متعدد، essay = مقالي');
            
            $table->tinyInteger('difficulty_level')->default(1)
                ->comment('1=سهل، 5=صعب جداً');
            
            $table->string('clo_code', 10)->nullable()
                ->comment('رمز مخرج التعلم (a1, b2, c1, إلخ)');
            
            $table->json('options')->nullable()
                ->comment('مصفوفة الخيارات (للـ MCQ)');
            
            $table->text('correct_answer')->nullable()
                ->comment('الإجابة الصحيحة (للمقالي)');
            
            $table->boolean('is_used_in_exam')->default(false);
            
            $table->boolean('is_active')->default(true);
            
            $table->integer('usage_count')->default(0);
            
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('course_topics')
                ->onDelete('cascade');
            
            $table->index(['topic_id', 'question_type', 'difficulty_level']);
        });

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->increments('assignment_id');
            
            $table->unsignedInteger('course_id');
            
            $table->enum('part', [
                'نظري',
                'عملي',
                'تمارين',
                'سريري'
            ])->comment('جزء المقرر');
            
            $table->string('title', 300)->comment('مثال: واجب 1، مشروع نهائي');
            
            $table->text('description')->nullable();
            
            $table->tinyInteger('week')->comment('الأسبوع (1-16)');
            
            $table->decimal('grade', 5, 2)->default(0)
                ->comment('الدرجة المخصصة');
            
            $table->json('clo_ids')->nullable()
                ->comment('مصفوفة رموز مخرجات التعلم');
            
            $table->enum('assignment_type', [
                'homework',
                'project',
                'presentation',
                'quiz',
                'other'
            ])->default('homework')->comment('نوع التكليف');
            
            $table->boolean('is_mandatory')->default(true);
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->index(['course_id', 'part', 'week']);
        });

        if (!Schema::hasTable('course_assessments')) {
            Schema::create('course_assessments', function (Blueprint $table) {
                $table->increments('assessment_id');
                
                $table->unsignedInteger('college_id');
                $table->unsignedInteger('course_id');
                $table->unsignedInteger('group_id')->nullable();
                $table->unsignedInteger('semester_id');
                $table->unsignedInteger('created_by')->nullable();
                
                $table->string('academic_year', 20)->nullable();
                
                $table->string('name', 300)->comment('مثال: الأنشطة والتكليفات، نصفي');
                
                $table->tinyInteger('week')->nullable()
                    ->comment('الأسبوع (0 = طوال الفصل)');
                
                $table->decimal('grade', 5, 2)->default(0)
                    ->comment('النقاط المخصصة');
                
                $table->integer('weight')->default(0)->comment('وزن نسبي');
                
                $table->decimal('percentage', 5, 2)->default(0)
                    ->comment('النسبة % من إجمالي التقويم');
                
                $table->json('clo_ids')->nullable()
                    ->comment('مصفوفة رموز مخرجات التعلم');
                
                $table->enum('assessment_type', [
                    'activities',
                    'quizzes',
                    'midterm_exam',
                    'final_exam',
                    'project',
                    'presentation',
                    'practical_exam',
                    'other'
                ])->comment('نوع التقييم');
                
                $table->integer('order')->default(0);
                
                $table->text('notes')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->foreign('college_id')
                    ->references('college_id')
                    ->on('colleges')
                    ->onDelete('cascade');
                
                $table->foreign('course_id')
                    ->references('course_id')
                    ->on('courses')
                    ->onDelete('cascade');
                
                $table->foreign('group_id')
                    ->references('group_id')
                    ->on('student_groups')
                    ->onDelete('set null');
                
                $table->foreign('semester_id')
                    ->references('semester_id')
                    ->on('semesters')
                    ->onDelete('cascade');
                
                if (Schema::hasTable('lecturers')) {
                    $table->foreign('created_by')
                        ->references('lecturer_id')
                        ->on('lecturers')
                        ->onDelete('set null');
                }
                
                $table->index(['course_id', 'group_id', 'academic_year', 'semester_id'], 'assessment_context_index');
                $table->index(['course_id', 'assessment_type']);
            });
        } else {
            Schema::table('course_assessments', function (Blueprint $table) {
                if (!Schema::hasColumn('course_assessments', 'percentage')) {
                    $table->decimal('percentage', 5, 2)->default(0)
                        ->after('weight')
                        ->comment('النسبة % من إجمالي التقويم');
                }
                
                if (!Schema::hasColumn('course_assessments', 'clo_ids')) {
                    $table->json('clo_ids')->nullable()
                        ->after('percentage')
                        ->comment('مصفوفة رموز مخرجات التعلم');
                }
                
                if (!Schema::hasColumn('course_assessments', 'assessment_type')) {
                    $table->enum('assessment_type', [
                        'activities',
                        'quizzes',
                        'midterm_exam',
                        'final_exam',
                        'project',
                        'presentation',
                        'practical_exam',
                        'other'
                    ])->default('activities')
                        ->after('clo_ids')
                        ->comment('نوع التقييم');
                }
                
                if (!Schema::hasColumn('course_assessments', 'order')) {
                    $table->integer('order')->default(0)
                        ->after('assessment_type');
                }
                
                if (!Schema::hasColumn('course_assessments', 'week')) {
                    $table->tinyInteger('week')->nullable()
                        ->after('name')
                        ->comment('الأسبوع');
                }
            });
        }

        Schema::create('teaching_strategies', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name', 200)->unique()
                ->comment('مثال: المحاضرة التفاعلية');
            
            $table->text('description')->nullable();
            
            $table->enum('category', [
                'lecture',
                'practical',
                'discussion',
                'collaboration',
                'project_based',
                'problem_solving',
                'simulation',
                'other'
            ])->default('lecture')->comment('فئة الاستراتيجية');
            
            $table->integer('order')->default(0);
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['is_active', 'category']);
        });

        Schema::create('assessment_methods', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name', 200)->unique()
                ->comment('مثال: اختبارات قصيرة');
            
            $table->text('description')->nullable();
            
            $table->enum('category', [
                'exam',
                'assignment',
                'project',
                'presentation',
                'participation',
                'portfolio',
                'other'
            ])->default('exam')->comment('فئة الطريقة');
            
            $table->integer('order')->default(0);
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['is_active', 'category']);
        });

        Schema::create('outcome_teaching_strategy', function (Blueprint $table) {
            $table->increments('id');
            
            $table->unsignedInteger('clo_id');
            
            $table->unsignedInteger('strategy_id');
            
            $table->timestamps();
            
            $table->foreign('clo_id')
                ->references('clo_id')
                ->on('course_learning_outcomes')
                ->onDelete('cascade');
            
            $table->foreign('strategy_id')
                ->references('id')
                ->on('teaching_strategies')
                ->onDelete('cascade');
            
            $table->unique(['clo_id', 'strategy_id']);
        });

        Schema::create('outcome_assessment_method', function (Blueprint $table) {
            $table->increments('id');
            
            $table->unsignedInteger('clo_id');
            
            $table->unsignedInteger('method_id');
            
            $table->timestamps();
            
            $table->foreign('clo_id')
                ->references('clo_id')
                ->on('course_learning_outcomes')
                ->onDelete('cascade');
            
            $table->foreign('method_id')
                ->references('id')
                ->on('assessment_methods')
                ->onDelete('cascade');
            
            $table->unique(['clo_id', 'method_id']);
        });

        Schema::create('course_references', function (Blueprint $table) {
            $table->increments('reference_id');
            
            $table->unsignedInteger('course_id');
            
            $table->enum('type', [
                'main',
                'support',
                'electronic'
            ])->comment('نوع المرجع');
            
            $table->enum('category', [
                'website',
                'journal',
                'other'
            ])->nullable()->comment('فئة المصدر الإلكتروني');
            
            $table->string('author', 300)->nullable();
            
            $table->year('year')->nullable();
            
            $table->string('title', 500)->comment('عنوان المرجع');
            
            $table->string('edition', 100)->nullable();
            
            $table->string('publisher', 300)->nullable();
            
            $table->string('country', 100)->nullable();
            
            $table->text('url')->nullable();
            
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->index(['course_id', 'type']);
        });

        Schema::create('course_policies', function (Blueprint $table) {
            $table->increments('policy_id');
            
            $table->unsignedInteger('course_id');
            
            $table->tinyInteger('policy_number')->comment('1-7 ثابت، 8+ مضافة');
            
            $table->string('title', 300)->comment('عنوان الضابط');
            
            $table->text('content')->comment('نص الضابط التفصيلي');
            
            $table->boolean('is_fixed')->default(false)
                ->comment('true = الضوابط السبعة الأساسية');
            
            $table->integer('order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('course_id')
                ->references('course_id')
                ->on('courses')
                ->onDelete('cascade');
            
            $table->unique(['course_id', 'policy_number']);
        });

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (!Schema::hasColumn('courses', 'is_approved')) {
                    $table->boolean('is_approved')->default(false)
                        ->after('notes')
                        ->comment('true = المقرر معتمد ومدرج رسمياً');
                }
                
                if (!Schema::hasColumn('courses', 'approval_date')) {
                    $table->date('approval_date')->nullable()
                        ->after('is_approved')
                        ->comment('تاريخ اعتماد المقرر رسمياً');
                }
                
                if (!Schema::hasColumn('courses', 'approved_by')) {
                    $table->string('approved_by', 300)->nullable()
                        ->after('approval_date')
                        ->comment('اسم الشخص الذي وافق على المقرر');
                }
                
                if (!Schema::hasColumn('courses', 'specification_status')) {
                    $table->enum('specification_status', [
                        'draft',
                        'in_progress',
                        'under_review',
                        'approved',
                        'published'
                    ])->default('draft')
                        ->after('approved_by')
                        ->comment('حالة توصيف المقرر');
                }
            });
        }
    }

    
    public function down(): void
    {
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasColumn('courses', 'specification_status')) {
                    $table->dropColumn('specification_status');
                }
                if (Schema::hasColumn('courses', 'approved_by')) {
                    $table->dropColumn('approved_by');
                }
                if (Schema::hasColumn('courses', 'approval_date')) {
                    $table->dropColumn('approval_date');
                }
                if (Schema::hasColumn('courses', 'is_approved')) {
                    $table->dropColumn('is_approved');
                }
            });
        }

        Schema::dropIfExists('course_policies');
        Schema::dropIfExists('course_references');
        Schema::dropIfExists('outcome_assessment_method');
        Schema::dropIfExists('outcome_teaching_strategy');
        Schema::dropIfExists('assessment_methods');
        Schema::dropIfExists('teaching_strategies');
        
        if (Schema::hasTable('course_assessments')) {
            Schema::table('course_assessments', function (Blueprint $table) {
                $columns = [
                    'percentage',
                    'clo_ids',
                    'assessment_type',
                    'order',
                    'week'
                ];
                
                foreach ($columns as $column) {
                    if (Schema::hasColumn('course_assessments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
        
        Schema::dropIfExists('topic_questions');
        Schema::dropIfExists('course_topics');
        Schema::dropIfExists('course_learning_outcomes');
        Schema::dropIfExists('course_descriptions');
        Schema::dropIfExists('program_learning_outcomes');
    }
};