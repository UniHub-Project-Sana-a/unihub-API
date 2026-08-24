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
        Schema::create('session_topics_covered', function (Blueprint $table) {
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('topic_id');
            $table->string('coverage_status')->default('fully_covered');
            $table->timestamps();

            $table->primary(['session_id', 'topic_id']);

            $table->foreign('session_id')
                ->references('session_id')
                ->on('lecture_sessions')
                ->onDelete('cascade');

            $table->foreign('topic_id')
                ->references('topic_id')
                ->on('course_topics')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_topics_covered');
    }
};
