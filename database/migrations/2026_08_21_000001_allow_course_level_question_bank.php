<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_questions', function (Blueprint $table) {
            $table->unsignedInteger('course_id')->nullable()->after('question_id')->index();
            $table->enum('part', ['نظري', 'عملي', 'تمارين', 'سريري'])->nullable()->after('course_id')->index();
            $table->unsignedInteger('topic_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('topic_questions', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['part']);
            $table->dropColumn(['course_id', 'part']);
            $table->unsignedInteger('topic_id')->nullable(false)->change();
        });
    }
};
