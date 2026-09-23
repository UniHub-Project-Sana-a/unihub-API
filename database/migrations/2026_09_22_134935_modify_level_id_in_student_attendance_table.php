<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('student_attendance', function (Blueprint $table) {
            // تعديل عمود level_id ليقبل قيمة NULL
            $table->unsignedBigInteger('level_id')->nullable()->change();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('student_attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->nullable(false)->change();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};