<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------
        // 1. محاولة تنظيف المفاتيح الأجنبية القديمة (بأمان)
        // ---------------------------------------------------------
        
        // نحاول حذف مفتاح timetable_id في عملية مستقلة
        if (Schema::hasColumn('qa_campaigns', 'timetable_id')) {
            try {
                Schema::table('qa_campaigns', function (Blueprint $table) {
                    $table->dropForeign(['timetable_id']); 
                });
            } catch (\Throwable $e) {
                // نتيجاهل الخطأ إذا كان المفتاح غير موجود
            }
        }

        // ---------------------------------------------------------
        // 2. حذف الأعمدة القديمة وإضافة العمود الجديد
        // ---------------------------------------------------------
        Schema::table('qa_campaigns', function (Blueprint $table) {
            
            // حذف الأعمدة إذا كانت موجودة
            if (Schema::hasColumn('qa_campaigns', 'timetable_id')) {
                $table->dropColumn('timetable_id');
            }
            if (Schema::hasColumn('qa_campaigns', 'course_id')) {
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('qa_campaigns', 'lecturer_id')) {
                $table->dropColumn('lecturer_id');
            }
            if (Schema::hasColumn('qa_campaigns', 'target_type')) {
                $table->dropColumn('target_type');
            }

            // إضافة عمود الهدف (Target KPI)
            if (!Schema::hasColumn('qa_campaigns', 'target_percentage')) {
                $table->integer('target_percentage')
                      ->default(80)
                      ->after('min_attendance_percentage')
                      ->comment('النسبة المستهدفة للنجاح');
            }
        });

        // ---------------------------------------------------------
        // 3. إنشاء الجدول الوسيط (qa_campaign_assignments)
        // ---------------------------------------------------------
        if (!Schema::hasTable('qa_campaign_assignments')) {
            Schema::create('qa_campaign_assignments', function (Blueprint $table) {
                $table->id('assignment_id');
                
                $table->unsignedInteger('campaign_id');
                $table->unsignedInteger('timetable_id');
                
                $table->foreign('campaign_id')
                      ->references('campaign_id')->on('qa_campaigns')
                      ->onDelete('cascade');

                $table->foreign('timetable_id')
                      ->references('timetable_id')->on('timetable')
                      ->onDelete('cascade');
                
                $table->unique(['campaign_id', 'timetable_id'], 'unique_assignment');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_campaign_assignments');
        
        if (Schema::hasColumn('qa_campaigns', 'target_percentage')) {
            Schema::table('qa_campaigns', function (Blueprint $table) {
                $table->dropColumn('target_percentage');
            });
        }
    }
};