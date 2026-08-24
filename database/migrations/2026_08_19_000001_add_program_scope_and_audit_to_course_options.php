<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_strategies', function (Blueprint $table) {
            $table->dropUnique('teaching_strategies_name_unique');
            $table->unsignedInteger('program_id')->nullable()->after('id')->index();
            $table->unique(['program_id', 'name'], 'teaching_strategy_program_name_unique');
        });

        Schema::table('assessment_methods', function (Blueprint $table) {
            $table->dropUnique('assessment_methods_name_unique');
            $table->unsignedInteger('program_id')->nullable()->after('id')->index();
            $table->unique(['program_id', 'name'], 'assessment_method_program_name_unique');
        });

        Schema::create('program_option_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('program_id')->index();
            $table->string('option_type', 30);
            $table->unsignedBigInteger('option_id')->nullable();
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('details')->nullable();
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_option_audits');
        Schema::table('assessment_methods', function (Blueprint $table) {
            $table->dropUnique('assessment_method_program_name_unique');
            $table->dropColumn('program_id');
            $table->unique('name');
        });
        Schema::table('teaching_strategies', function (Blueprint $table) {
            $table->dropUnique('teaching_strategy_program_name_unique');
            $table->dropColumn('program_id');
            $table->unique('name');
        });
    }
};
