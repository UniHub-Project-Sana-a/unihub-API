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
        if (Schema::hasTable('timetable') && !Schema::hasColumn('timetable', 'deleted_at')) {
            Schema::table('timetable', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('lecture_sessions') && !Schema::hasColumn('lecture_sessions', 'deleted_at')) {
            Schema::table('lecture_sessions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('timetable') && Schema::hasColumn('timetable', 'deleted_at')) {
            Schema::table('timetable', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('lecture_sessions') && Schema::hasColumn('lecture_sessions', 'deleted_at')) {
            Schema::table('lecture_sessions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
