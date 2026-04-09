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
        Schema::disableForeignKeyConstraints();
    
        Schema::table('programs', function (Blueprint $table) {
            $table->enum('academic_system', ['semester', 'credit'])->default('semester')->after('program_name');
            $table->integer('total_hours')->nullable()->after('academic_system');
            $table->boolean('block_based')->default(false)->after('academic_system');
    
            $table->dropUnique('unique_program_per_department');
    
            $table->unique(
                ['department_id', 'program_name', 'academic_system', 'block_based'], 
                'program_full_unique_index'
            );
        });
    
        Schema::enableForeignKeyConstraints();
    }
    
    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique('program_full_unique_index');
            $table->dropColumn(['academic_system', 'block_based', 'total_hours']);
            
            // إعادة القيد القديم إذا أردت عمل Rollback
            $table->unique(['department_id', 'program_name'], 'unique_program_per_department');
        });
    }
};
