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

    public function up(): void
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable', 'program_id')) {
                $table->unsignedInteger('program_id')->nullable()->after('department_id');
            }

            if (!Schema::hasColumn('timetable', 'semester_id')) {
                $table->unsignedInteger('semester_id')->nullable()->after('level_id');
            }

            if (!Schema::hasColumn('timetable', 'block_id')) {
                $table->unsignedBigInteger('block_id')->nullable()->after('semester_id');
            }
        });

        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'program_id') && ! $this->foreignKeyExists('timetable', 'program_id')) {
                $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('set null');
            }

            if (Schema::hasColumn('timetable', 'semester_id') && ! $this->foreignKeyExists('timetable', 'semester_id')) {
                $table->foreign('semester_id')->references('semester_id')->on('semesters')->onDelete('set null');
            }

            if (Schema::hasColumn('timetable', 'block_id') && ! $this->foreignKeyExists('timetable', 'block_id')) {
                $table->foreign('block_id')->references('id')->on('blocks')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'program_id') && $this->foreignKeyExists('timetable', 'program_id')) {
                $table->dropForeign(['program_id']);
            }

            if (Schema::hasColumn('timetable', 'semester_id') && $this->foreignKeyExists('timetable', 'semester_id')) {
                $table->dropForeign(['semester_id']);
            }

            if (Schema::hasColumn('timetable', 'block_id') && $this->foreignKeyExists('timetable', 'block_id')) {
                $table->dropForeign(['block_id']);
            }
        });

        Schema::table('timetable', function (Blueprint $table) {
            if (Schema::hasColumn('timetable', 'program_id')) {
                $table->dropColumn('program_id');
            }

            if (Schema::hasColumn('timetable', 'semester_id')) {
                $table->dropColumn('semester_id');
            }

            if (Schema::hasColumn('timetable', 'block_id')) {
                $table->dropColumn('block_id');
            }
        });
    }
};
