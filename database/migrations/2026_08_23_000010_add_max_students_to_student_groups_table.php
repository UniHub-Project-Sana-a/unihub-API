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
        Schema::table('student_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('student_groups', 'max_students')) {
                $table->unsignedInteger('max_students')->nullable()->default(30)->after('group_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_groups', function (Blueprint $table) {
            if (Schema::hasColumn('student_groups', 'max_students')) {
                $table->dropColumn('max_students');
            }
        });
    }
};
