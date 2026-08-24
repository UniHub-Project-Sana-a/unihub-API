<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedInteger('college_id')->nullable()->change();
            $table->string('building_code', 50)->nullable()->after('building_name');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('set null');
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedInteger('college_id')->nullable()->after('building_id');
            $table->integer('windows_count')->default(0)->after('classroom_type');
            $table->boolean('has_computer')->default(false)->after('windows_count');
            $table->enum('display_type', ['none', 'screen', 'projector', 'smart_board'])
                ->default('none')
                ->after('has_computer');
            $table->text('notes')->nullable()->after('display_type');
            $table->string('location_address', 255)->nullable()->after('notes');
            $table->string('remote_id', 100)->nullable()->unique()->after('location_address');

            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropColumn([
                'college_id',
                'windows_count',
                'has_computer',
                'display_type',
                'notes',
                'location_address',
                'remote_id',
            ]);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('building_code');
            $table->unsignedInteger('college_id')->nullable(false)->change();
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
        });
    }
};
