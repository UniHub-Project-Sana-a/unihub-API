<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_assessments')) {
            return;
        }

        Schema::table('course_assessments', function (Blueprint $table) {
            // Make columns nullable (works for both MySQL and PostgreSQL)
            $table->unsignedInteger('group_id')->nullable()->change();
            $table->unsignedInteger('created_by')->nullable()->change();
            $table->string('academic_year', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('course_assessments')) {
            return;
        }

        Schema::table('course_assessments', function (Blueprint $table) {
            // Revert to non-nullable (optional rollback)
            $table->unsignedInteger('group_id')->nullable(false)->change();
            $table->unsignedInteger('created_by')->nullable(false)->change();
            $table->string('academic_year', 20)->nullable(false)->change();
        });
    }
};