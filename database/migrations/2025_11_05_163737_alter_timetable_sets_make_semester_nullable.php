<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        
        Schema::table('timetable_sets', function (Blueprint $table) {
            $table->unsignedInteger('semester_id')->nullable()->change();
        });
}
public function down(): void {
    Schema::table('timetable_sets', function (Blueprint $table) {
        $table->unsignedInteger('semester_id')->nullable(false)->change();
    });
}
};