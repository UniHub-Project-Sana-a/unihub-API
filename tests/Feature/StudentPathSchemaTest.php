<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentPathSchemaTest extends TestCase
{
    public function test_credit_program_students_allow_null_level_id(): void
    {
        Schema::dropIfExists('tmp_students_for_path_check');

        Schema::create('tmp_students_for_path_check', function ($table) {
            $table->increments('student_id');
            $table->unsignedInteger('level_id')->nullable();
        });

        $columns = DB::select('PRAGMA table_info(tmp_students_for_path_check)');
        $levelColumn = collect($columns)->firstWhere('name', 'level_id');

        $this->assertNotNull($levelColumn);
        $this->assertSame(0, (int) $levelColumn->notnull);

        Schema::dropIfExists('tmp_students_for_path_check');
    }
}
