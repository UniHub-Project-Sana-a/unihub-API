<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function foreignKeyExists(string $table, string $column): bool
    {
        $connection = Schema::getConnection();

        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $manager = $connection->getDoctrineSchemaManager();
            foreach ($manager->listTableForeignKeys($table) as $foreignKey) {
                if (in_array($column, $foreignKey->getLocalColumns(), true)) {
                    return true;
                }
            }
        }

        $result = $connection->selectOne(
            "SELECT COUNT(*) AS count
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column]
        );

        return (int) ($result->count ?? 0) > 0;
    }

    protected function uniqueIndexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        if (method_exists($connection, 'getDoctrineSchemaManager')) {
            $manager = $connection->getDoctrineSchemaManager();
            foreach ($manager->listTableIndexes($table) as $indexNameCandidate => $index) {
                if ($indexNameCandidate === $indexName) {
                    return true;
                }
            }
        }

        $result = $connection->selectOne(
            "SELECT COUNT(*) AS count
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?",
            [$table, $indexName]
        );

        return (int) ($result->count ?? 0) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1) توسيع بيانات الطالب ليحتفظ بمساره الأكاديمي الحالي حسب النوع الجديد
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'semester_id')) {
                $table->unsignedInteger('semester_id')->nullable()->after('level_id');
            }

            if (!Schema::hasColumn('students', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable()->after('semester_id');
            }
        });

        if (Schema::hasColumn('students', 'block_id')) {
            $connection = Schema::getConnection();
            $type = $connection->getSchemaBuilder()->getColumnType('students', 'block_id');
            if ($type !== 'bigint') {
                $connection->statement('ALTER TABLE students MODIFY COLUMN block_id BIGINT UNSIGNED NULL');
            }
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'semester_id') && ! $this->foreignKeyExists('students', 'semester_id')) {
                $table->foreign('semester_id')->references('semester_id')->on('semesters')->onDelete('set null');
            }

            if (Schema::hasColumn('students', 'block_id') && ! $this->foreignKeyExists('students', 'block_id')) {
                $table->foreign('block_id')->references('id')->on('blocks')->onDelete('set null');
            }
        });

        // 2) توسيع مسار المجموعة ليدعم البرامج ذات الأنظمة الأربعة
        Schema::table('student_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('student_groups', 'program_id')) {
                $table->unsignedInteger('program_id')->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('student_groups', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable()->after('semester_id');
            }
        });

        if (Schema::hasColumn('student_groups', 'block_id')) {
            $connection = Schema::getConnection();
            $type = $connection->getSchemaBuilder()->getColumnType('student_groups', 'block_id');
            if ($type !== 'bigint') {
                $connection->statement('ALTER TABLE student_groups MODIFY COLUMN block_id BIGINT UNSIGNED NULL');
            }
        }

        Schema::table('student_groups', function (Blueprint $table) {
            if (Schema::hasColumn('student_groups', 'program_id') && ! $this->foreignKeyExists('student_groups', 'program_id')) {
                $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('cascade');
            }

            if (Schema::hasColumn('student_groups', 'block_id') && ! $this->foreignKeyExists('student_groups', 'block_id')) {
                $table->foreign('block_id')->references('id')->on('blocks')->onDelete('set null');
            }
        });

        Schema::table('student_groups', function (Blueprint $table) {
            if (Schema::getConnection()->getSchemaBuilder()->getColumnType('student_groups', 'level_id') !== 'integer') {
                // no-op; keep compatibility with MariaDB/MySQL
            }
        });

        // 3) جعل الحقول القديمة اختيارية عند الحاجة
        Schema::table('student_groups', function (Blueprint $table) {
            $table->unsignedInteger('level_id')->nullable()->change();
            $table->unsignedInteger('semester_id')->nullable()->change();
        });

        // 4) استبدال القيد الفريد القديم بقيد جديد مرن يراعي المسارات المختلفة
        Schema::table('student_groups', function (Blueprint $table) {
            if ($this->uniqueIndexExists('student_groups', 'unique_group_per_path')) {
                $table->dropUnique('unique_group_per_path');
            }
        });

        Schema::table('student_groups', function (Blueprint $table) {
            $table->unique(['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'], 'unique_group_per_path_v2');
        });

        Schema::table('student_groups', function (Blueprint $table) {
            $table->index(['program_id', 'level_id', 'semester_id', 'block_id']);
        });

        Schema::table('student_groups', function (Blueprint $table) {
            if (! $this->uniqueIndexExists('student_groups', 'unique_group_per_path_v2')) {
                $table->unique(['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'], 'unique_group_per_path_v2');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (! $this->uniqueIndexExists('students', 'students_program_id_level_id_semester_id_block_id_index')) {
                $table->index(['program_id', 'level_id', 'semester_id', 'block_id']);
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('student_groups', function (Blueprint $table) {
            if ($this->uniqueIndexExists('student_groups', 'unique_group_per_path_v2')) {
                $table->dropUnique('unique_group_per_path_v2');
            }

            if (Schema::hasColumn('student_groups', 'program_id') && $this->foreignKeyExists('student_groups', 'program_id')) {
                $table->dropForeign(['program_id']);
            }

            if (Schema::hasColumn('student_groups', 'block_id') && $this->foreignKeyExists('student_groups', 'block_id')) {
                $table->dropForeign(['block_id']);
            }

            if (Schema::hasColumn('student_groups', 'program_id')) {
                $table->dropColumn(['program_id', 'block_id']);
            }

            if (! $this->uniqueIndexExists('student_groups', 'unique_group_per_path')) {
                $table->unique(['college_id', 'department_id', 'level_id', 'semester_id', 'group_name'], 'unique_group_per_path');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'program_id')) {
                $table->dropIndex(['program_id', 'level_id', 'semester_id', 'block_id']);
            }

            if (Schema::hasColumn('students', 'semester_id') && $this->foreignKeyExists('students', 'semester_id')) {
                $table->dropForeign(['semester_id']);
            }

            if (Schema::hasColumn('students', 'block_id') && $this->foreignKeyExists('students', 'block_id')) {
                $table->dropForeign(['block_id']);
            }

            if (Schema::hasColumn('students', 'semester_id') || Schema::hasColumn('students', 'block_id')) {
                $table->dropColumn(['semester_id', 'block_id']);
            }
        });

        Schema::enableForeignKeyConstraints();
    }
};
