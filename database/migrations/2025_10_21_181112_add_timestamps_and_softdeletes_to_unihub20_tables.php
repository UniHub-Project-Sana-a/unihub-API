<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جميع جداولك كما في السكيمة
        $allTables = [
            'academic_titles','app_versions','buildings','classrooms','colleges','courses','days','departments',
            'department_programs','lecturers','lecturer_attendance','lecturer_group_notifications','lecture_sessions',
            'levels','makeup_lectures_requests','otp_device_verifications','periods','permissions','programs','qr_codes',
            'qr_refresh_options','semesters','students','student_attendance','student_excuse_submissions','student_groups',
            'student_group_members','timetable','users','user_activities','user_devices','user_types','user_type_permissions',
        ];

        // الجداول التي سنضيف لها Soft Deletes مبدئياً (تقدر تعدّلها الآن أو بعدين)
        $softDeleteTables = [
            'users','user_types','permissions','colleges','departments','programs','courses','buildings','classrooms',
            'levels','semesters','students','lecturers','periods','academic_titles','student_groups','qr_codes',
            'qr_refresh_options','app_versions',
        ];

        foreach ($allTables as $tbl) {
            // created_at إذا غير موجود
            if (!Schema::hasColumn($tbl, 'created_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dateTime('created_at')->nullable();
                });
            }
            // updated_at إذا غير موجود
            if (!Schema::hasColumn($tbl, 'updated_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dateTime('updated_at')->nullable();
                });
            }
            // deleted_at (Soft Deletes) للجداول المحددة فقط
            if (in_array($tbl, $softDeleteTables, true) && !Schema::hasColumn($tbl, 'deleted_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dateTime('deleted_at')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        // تحذير: تنفيذ down سيحذف الحقول حتى لو كانت موجودة أصلاً قبل هذه المهاجرة.
        // عادةً لن نرجع عنها، لذا إن رغبت اجعل down فارغاً.
        $allTables = [
            'academic_titles','app_versions','buildings','classrooms','colleges','courses','days','departments',
            'department_programs','lecturers','lecturer_attendance','lecturer_group_notifications','lecture_sessions',
            'levels','makeup_lectures_requests','otp_device_verifications','periods','permissions','programs','qr_codes',
            'qr_refresh_options','semesters','students','student_attendance','student_excuse_submissions','student_groups',
            'student_group_members','timetable','users','user_activities','user_devices','user_types','user_type_permissions',
        ];
        $softDeleteTables = [
            'users','user_types','permissions','colleges','departments','programs','courses','buildings','classrooms',
            'levels','semesters','students','lecturers','periods','academic_titles','student_groups','qr_codes',
            'qr_refresh_options','app_versions',
        ];

        foreach ($allTables as $tbl) {
            if (Schema::hasColumn($tbl, 'updated_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('updated_at');
                });
            }
            if (Schema::hasColumn($tbl, 'created_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('created_at');
                });
            }
            if (in_array($tbl, $softDeleteTables, true) && Schema::hasColumn($tbl, 'deleted_at')) {
                Schema::table($tbl, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }
        }
    }
};