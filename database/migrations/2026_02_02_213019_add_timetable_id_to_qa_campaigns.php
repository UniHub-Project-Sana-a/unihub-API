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
        Schema::table('qa_campaigns', function (Blueprint $table) {
            // إضافة معرف الجدول الدراسي
            $table->unsignedInteger('timetable_id')->nullable()->after('form_id');
            
            // ربطه كمفتاح أجنبي (اختياري لكن مفضل)
            $table->foreign('timetable_id')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qa_campaigns', function (Blueprint $table) {
            //
        });
    }
};
