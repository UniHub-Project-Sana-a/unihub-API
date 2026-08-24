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
        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'program_id')) {
                $table->unsignedInteger('program_id')->nullable()->change();
            }

            if (Schema::hasColumn('timetable', 'level_id')) {
                $table->unsignedInteger('level_id')->nullable()->change();
            }

            if (Schema::hasColumn('timetable', 'semester_id')) {
                $table->unsignedInteger('semester_id')->nullable()->change();
            }

            if (Schema::hasColumn('timetable', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'program_id')) {
                $table->unsignedInteger('program_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('timetable', 'level_id')) {
                $table->unsignedInteger('level_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('timetable', 'semester_id')) {
                $table->unsignedInteger('semester_id')->nullable(false)->change();
            }

            if (Schema::hasColumn('timetable', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable(false)->change();
            }
        });
    }
};
