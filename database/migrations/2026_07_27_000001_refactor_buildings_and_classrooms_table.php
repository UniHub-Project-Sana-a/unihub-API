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
        // 1. إضافة الأعمدة الجديدة لجدول classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'college_id')) {
                $table->unsignedInteger('college_id')->nullable()->after('building_id');
                $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            }

            if (!Schema::hasColumn('classrooms', 'uuid')) {
                $table->string('uuid', 36)->nullable()->unique()->after('classroom_id');
            }

            if (!Schema::hasColumn('classrooms', 'windows_count')) {
                $table->integer('windows_count')->default(0)->after('classroom_type');
            }

            if (!Schema::hasColumn('classrooms', 'has_computer')) {
                $table->boolean('has_computer')->default(false)->after('windows_count');
            }

            if (!Schema::hasColumn('classrooms', 'display_type')) {
                $table->string('display_type', 50)->default('none')->after('has_computer');
            }
        });

        // 2. ترحيل البيانات الحالية (Data Backfill)
        // نسخ college_id من المباني إلى قاعاتها القائمة لضمان عدم ضياع أي بيانات
        DB::statement("
            UPDATE classrooms 
            JOIN buildings ON classrooms.building_id = buildings.building_id 
            SET classrooms.college_id = buildings.college_id 
            WHERE classrooms.college_id IS NULL
        ");

        // 3. جعل college_id في جدول buildings قابلاً ليكون NULL وإضافة عمود code
        Schema::table('buildings', function (Blueprint $table) {
            if (!Schema::hasColumn('buildings', 'code')) {
                $table->string('code', 50)->nullable()->after('building_name');
            }
        });

        try {
            DB::statement("ALTER TABLE buildings MODIFY college_id INT UNSIGNED NULL;");
        } catch (\Exception $e) {
            // في حال وجود بيئة sqlite أو ما شابه أثناء الاختبارات
            Schema::table('buildings', function (Blueprint $table) {
                $table->unsignedInteger('college_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            if (Schema::hasColumn('classrooms', 'college_id')) {
                $table->dropForeign(['college_id']);
                $table->dropColumn('college_id');
            }
            if (Schema::hasColumn('classrooms', 'uuid')) {
                $table->dropColumn('uuid');
            }
            if (Schema::hasColumn('classrooms', 'windows_count')) {
                $table->dropColumn('windows_count');
            }
            if (Schema::hasColumn('classrooms', 'has_computer')) {
                $table->dropColumn('has_computer');
            }
            if (Schema::hasColumn('classrooms', 'display_type')) {
                $table->dropColumn('display_type');
            }
        });

        try {
            DB::statement("ALTER TABLE buildings MODIFY college_id INT UNSIGNED NOT NULL;");
        } catch (\Exception $e) {
            // Ignored on rollback if not supported
        }
    }
};
