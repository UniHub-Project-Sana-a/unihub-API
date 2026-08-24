<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_assessments')) {
            return;
        }

        DB::statement('ALTER TABLE course_assessments MODIFY group_id INT UNSIGNED NULL');
        DB::statement('ALTER TABLE course_assessments MODIFY created_by INT UNSIGNED NULL');
        DB::statement('ALTER TABLE course_assessments MODIFY academic_year VARCHAR(20) NULL');
    }

    public function down(): void
    {
        // Existing records may not have these context values, so rollback is non-destructive.
    }
};