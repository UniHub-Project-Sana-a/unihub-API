<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_assessments')) {
            Schema::create('course_assessments', function (Blueprint $table) {
                $table->increments('assessment_id');
                $table->unsignedInteger('college_id');
                $table->unsignedInteger('course_id');
                $table->unsignedInteger('group_id')->nullable();
                $table->unsignedInteger('semester_id');
                $table->unsignedInteger('created_by')->nullable();
                $table->string('academic_year', 20)->nullable();
                $table->string('name', 300);
                $table->tinyInteger('week')->nullable();
                $table->decimal('grade', 5, 2)->default(0);
                $table->integer('weight')->default(0);
                $table->decimal('percentage', 5, 2)->default(0)
                    ->comment('نسبة الدرجة إلى درجة التقويم النهائي');
                $table->json('clo_ids')->nullable();
                $table->enum('assessment_type', [
                    'activities',
                    'quizzes',
                    'midterm_exam',
                    'final_exam',
                    'project',
                    'presentation',
                    'practical_exam',
                    'other',
                ])->default('activities');
                $table->integer('order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('course_id')
                    ->references('course_id')
                    ->on('courses')
                    ->cascadeOnDelete();
                $table->index(['course_id', 'assessment_type']);
            });

            return;
        }

        Schema::table('course_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('course_assessments', 'grade')) {
                $table->decimal('grade', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('course_assessments', 'percentage')) {
                $table->decimal('percentage', 5, 2)->default(0)
                    ->comment('نسبة الدرجة إلى درجة التقويم النهائي');
            }
            if (!Schema::hasColumn('course_assessments', 'clo_ids')) {
                $table->json('clo_ids')->nullable();
            }
            if (!Schema::hasColumn('course_assessments', 'assessment_type')) {
                $table->string('assessment_type', 30)->default('activities');
            }
            if (!Schema::hasColumn('course_assessments', 'order')) {
                $table->integer('order')->default(0);
            }
            if (!Schema::hasColumn('course_assessments', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Existing installations may already own these columns, so rollback is non-destructive.
    }
};