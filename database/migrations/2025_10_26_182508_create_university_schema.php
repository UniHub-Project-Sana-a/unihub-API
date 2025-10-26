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
        // 1. جداول أساسية لا تعتمد على غيرها
        Schema::create('colleges', function (Blueprint $table) {
            $table->increments('college_id');
            $table->string('college_name', 100);
            $table->string('college_code', 20)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_types', function (Blueprint $table) {
            $table->increments('user_type_id');
            $table->string('user_type_name', 50)->unique();
            $table->string('user_type_code', 30)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('permission_id');
            $table->string('permission_key', 100)->unique();
            $table->string('permission_name', 100);
            $table->string('description', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->increments('program_id');
            $table->string('program_name', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->increments('course_id');
            $table->string('course_name', 150);
            $table->string('course_code', 50)->unique();
            $table->tinyInteger('course_type')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('days', function (Blueprint $table) {
            $table->increments('day_id');
            $table->string('day_name', 20)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_groups', function (Blueprint $table) {
            $table->increments('group_id');
            $table->string('group_name', 100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qr_refresh_options', function (Blueprint $table) {
            $table->increments('option_id');
            $table->integer('interval_seconds')->unique();
            $table->string('description', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('app_versions', function (Blueprint $table) {
            $table->increments('version_id');
            $table->string('package_name', 50);
            $table->string('version_number', 20);
            $table->date('release_date');
            $table->boolean('is_mandatory_update')->default(false);
            $table->string('platform', 20);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. جداول تعتمد على الجداول الأساسية
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('full_name', 100)->index();
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->unique();
            $table->string('password');
            $table->string('academic_number', 50)->unique();
            $table->tinyInteger('gender');
            $table->unsignedInteger('user_type_id');
            $table->foreign('user_type_id')->references('user_type_id')->on('user_types')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->increments('department_id');
            $table->string('department_name', 100);
            $table->string('department_code', 20)->nullable()->unique();
            $table->unsignedInteger('college_id');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('academic_titles', function (Blueprint $table) {
            $table->increments('title_id');
            $table->unsignedInteger('college_id');
            $table->string('title_name', 100);
            $table->string('title_code', 50)->unique();
            $table->decimal('hourly_price', 10, 2);
            $table->decimal('lecture_price', 10, 2)->default(0.00);
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('buildings', function (Blueprint $table) {
            $table->increments('building_id');
            $table->string('building_name', 100)->index();
            $table->integer('floors_count');
            $table->unsignedInteger('college_id');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('periods', function (Blueprint $table) {
            $table->increments('period_id');
            $table->unsignedInteger('college_id');
            $table->string('period_name', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('session_type', 10);
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. جداول تعتمد على ما سبق
        Schema::create('levels', function (Blueprint $table) {
            $table->increments('level_id');
            $table->string('level_name', 50);
            $table->unsignedInteger('department_id');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lecturers', function (Blueprint $table) {
            $table->increments('lecturer_id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('title_id')->nullable();
            $table->date('hire_date');
            $table->boolean('status')->default(true);
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('title_id')->references('title_id')->on('academic_titles')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->increments('classroom_id');
            $table->string('classroom_name', 100)->index();
            $table->unsignedInteger('building_id');
            $table->integer('floor');
            $table->integer('capacity');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('allowed_distance', 5, 2);
            $table->tinyInteger('classroom_type');
            $table->foreign('building_id')->references('building_id')->on('buildings')->onDelete('cascade');
            $table->unique(['building_id', 'floor', 'classroom_name'], 'unique_room_per_floor_per_building');
            $table->timestamps();
            $table->softDeletes();
        });

        // جداول الربط
        Schema::create('department_programs', function (Blueprint $table) {
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('program_id');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('cascade');
            $table->primary(['department_id', 'program_id']);
            $table->timestamps();
        });

        Schema::create('user_type_permissions', function (Blueprint $table) {
            $table->unsignedInteger('user_type_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('permission_id');
            $table->foreign('user_type_id')->references('user_type_id')->on('user_types')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('permission_id')->references('permission_id')->on('permissions')->onDelete('cascade');
            $table->primary(['user_type_id', 'permission_id', 'college_id']);
            $table->timestamps();
        });

        // 4. جداول تعتمد على المستويات والبرامج
        Schema::create('semesters', function (Blueprint $table) {
            $table->increments('semester_id');
            $table->string('semester_name', 50);
            $table->string('academic_year', 20);
            $table->unsignedInteger('level_id');
            $table->foreign('level_id')->references('level_id')->on('levels')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->increments('student_id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('level_id');
            $table->unsignedInteger('program_id')->nullable();
            $table->boolean('status')->default(true);
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('level_id')->references('level_id')->on('levels')->onDelete('cascade');
            $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
        
        // 5. الجدول الدراسي (Timetable)
        Schema::create('timetable', function (Blueprint $table) {
            $table->increments('timetable_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('day_id');
            $table->unsignedInteger('period_id');
            $table->tinyInteger('lecture_type');
            $table->tinyInteger('status')->default(1);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('academic_year', 20);
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->tinyInteger('gender_type')->default(0);
            $table->decimal('lecture_hours', 4, 2);
            $table->timestamps();

            // Foreign Keys
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('classroom_id')->references('classroom_id')->on('classrooms')->onDelete('cascade');
            $table->foreign('day_id')->references('day_id')->on('days')->onDelete('cascade');
            $table->foreign('period_id')->references('period_id')->on('periods')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');

            // Unique Constraints
            $table->unique(['classroom_id', 'day_id', 'period_id'], 'unique_classroom_slot');
            $table->unique(['lecturer_id', 'day_id', 'period_id'], 'unique_lecturer_slot');
            $table->unique(['group_id', 'day_id', 'period_id'], 'unique_group_slot');
        });

        // 6. جداول تعتمد على Timetable و Students/Lecturers
        Schema::create('lecture_sessions', function (Blueprint $table) {
            $table->increments('session_id');
            $table->unsignedInteger('timetable_id');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('actual_classroom_id')->nullable();
            $table->integer('actual_attendance_count')->nullable();
            $table->string('session_code', 50)->unique();
            $table->tinyInteger('status')->default(0);
            $table->boolean('attendance_overage_alert')->default(false);
            $table->integer('system_attendance_count')->default(0);
            $table->foreign('timetable_id')->references('timetable_id')->on('timetable')->onDelete('cascade');
            $table->foreign('actual_classroom_id')->references('classroom_id')->on('classrooms')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('lecturer_attendance', function (Blueprint $table) {
            $table->increments('attendance_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('timetable_id');
            $table->date('attendance_date')->index();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('notification_status')->default(0);
            $table->unsignedInteger('college_id');
            $table->decimal('lecture_hours', 4, 2);
            $table->string('session_code', 50);
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('timetable_id')->references('timetable_id')->on('timetable')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->unique(['lecturer_id', 'session_code'], 'unique_lecturer_session');
            $table->timestamps();
        });

        Schema::create('student_attendance', function (Blueprint $table) {
            $table->increments('attendance_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('timetable_id');
            $table->date('attendance_date');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('notification_status')->default(0);
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->string('session_code', 50);
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('timetable_id')->references('timetable_id')->on('timetable')->onDelete('cascade');
            $table->foreign('college_id')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->unique(['student_id', 'session_code'], 'unique_student_session');
            $table->index(['student_id', 'attendance_date']);
            $table->timestamps();
        });

        Schema::create('student_group_members', function (Blueprint $table) {
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('group_id');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->primary(['student_id', 'group_id']);
            $table->timestamps();
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->increments('qr_id');
            $table->unsignedInteger('timetable_id');
            $table->unsignedInteger('refresh_option_id')->nullable();
            $table->string('qr_code_value');
            $table->dateTime('generated_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by'); // lecturer_id
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('allowed_distance', 5, 2);
            $table->foreign('timetable_id')->references('timetable_id')->on('timetable')->onDelete('cascade');
            $table->foreign('refresh_option_id')->references('option_id')->on('qr_refresh_options')->onDelete('set null');
            $table->foreign('created_by')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->timestamps();
        });
        
        // 7. جداول الطلبات والإشعارات
        Schema::create('lecturer_group_notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->unsignedInteger('lecturer_user_id');
            $table->string('subject', 150);
            $table->text('message_body');
            $table->dateTime('send_at')->useCurrent();
            $table->unsignedInteger('group_id');
            $table->boolean('is_sent')->default(true);
            $table->boolean('is_seen')->default(false);
            $table->foreign('lecturer_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->unique(['lecturer_user_id', 'group_id', 'send_at'], 'unique_group_notification');
            $table->timestamps();
        });

        Schema::create('makeup_lectures_requests', function (Blueprint $table) {
            $table->increments('request_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('group_id');
            $table->date('requested_date');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('notification_status')->default(0);
            $table->foreign('lecturer_id')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('group_id')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('student_excuse_submissions', function (Blueprint $table) {
            $table->increments('submission_id');
            $table->unsignedInteger('student_user_id');
            $table->date('request_date');
            $table->text('reason');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('lecturer_user_id');
            $table->boolean('is_lecturer_notified')->default(false);
            $table->tinyInteger('response_status')->default(0);
            $table->string('lecturer_comment')->nullable();
            $table->foreign('student_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('lecturer_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->unique(['student_user_id', 'course_id', 'request_date'], 'unique_student_course_date');
            $table->timestamps();
        });

        // 8. جداول الأجهزة والتوثيق
        Schema::create('user_devices', function (Blueprint $table) {
            $table->increments('device_id');
            $table->unsignedInteger('user_id');
            $table->string('device_name', 100);
            $table->string('mac_address', 100);
            $table->string('os_type', 50);
            $table->boolean('is_auto_attendance_enabled')->default(false);
            $table->dateTime('registered_at')->useCurrent();
            $table->dateTime('last_login_at')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('otp_device_verifications', function (Blueprint $table) {
            $table->increments('verification_id');
            $table->unsignedInteger('user_id');
            $table->string('otp_code', 10);
            $table->string('device_name', 100);
            $table->string('mac_address', 100);
            $table->string('os_type', 50);
            $table->tinyInteger('delivery_status')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->dateTime('expires_at');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('user_activities', function (Blueprint $table) {
            $table->increments('activity_id');
            $table->unsignedInteger('user_id');
            $table->string('action_type', 50);
            $table->text('action_description')->nullable();
            $table->string('module_name', 50)->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // الحذف بترتيب عكسي لتجنب مشاكل المفاتيح الأجنبية
        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('otp_device_verifications');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('student_excuse_submissions');
        Schema::dropIfExists('makeup_lectures_requests');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('student_group_members');
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('lecturer_attendance');
        Schema::dropIfExists('lecture_sessions');
        Schema::dropIfExists('timetable');
        Schema::dropIfExists('students');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('lecturers');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('periods');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('academic_titles');
        Schema::dropIfExists('user_type_permissions');
        Schema::dropIfExists('department_programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('app_versions');
        Schema::dropIfExists('qr_refresh_options');
        Schema::dropIfExists('student_groups');
        Schema::dropIfExists('days');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_types');
        Schema::dropIfExists('colleges');
    }
};