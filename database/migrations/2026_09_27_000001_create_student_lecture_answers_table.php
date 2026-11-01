<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_lecture_answers', function (Blueprint $table) {
            $table->id();
            $table->integer('session_id');
            $table->integer('student_id');
            $table->integer('question_id');
            $table->integer('selected_option_id');
            $table->boolean('is_correct')->nullable()->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['session_id', 'student_id'], 'idx_session_student');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_lecture_answers');
    }
};