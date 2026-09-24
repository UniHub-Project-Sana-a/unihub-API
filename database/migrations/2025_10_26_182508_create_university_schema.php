<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * UniHub university schema.
 *
 * Creates every application table (62 tables) with the exact columns, types,
 * defaults, comments, primary keys, unique keys, indexes and foreign keys of the
 * original database dump.
 *
 * Tables that are already created by their own migrations are intentionally NOT
 * part of this file:
 *   - oauth_auth_codes, oauth_access_tokens, oauth_refresh_tokens,
 *     oauth_clients, oauth_device_codes   (Laravel Passport)
 *   - password_reset_tokens, cache, cache_locks, settings
 *   - migrations                          (managed by the framework itself)
 *
 * Tables are created in dependency order (parents before children), and every
 * foreign key is declared inside its own table definition.
 */
return new class extends Migration
{
    /**
     * Every table created by this migration, in creation order.
     * down() drops them in the reverse order.
     */
    private const TABLES = [
        'assessment_methods',
        'colleges',
        'days',
        'ip_restrictions',
        'permissions',
        'program_option_audits',
        'teaching_strategies',
        'user_types',
        'academic_titles',
        'buildings',
        'departments',
        'periods',
        'qa_forms',
        'user_type_permissions',
        'users',
        'classrooms',
        'financial_cycles',
        'lecturers',
        'otp_device_verifications',
        'programs',
        'qa_campaigns',
        'qa_domains',
        'user_activities',
        'user_devices',
        'lecturer_payouts',
        'levels',
        'program_learning_outcomes',
        'qa_questions',
        'blocks',
        'payout_adjustments',
        'semesters',
        'block_relations',
        'courses',
        'student_groups',
        'students',
        'course_assessments',
        'course_assignments',
        'course_descriptions',
        'course_learning_outcomes',
        'course_policies',
        'course_prerequisites',
        'course_references',
        'course_topics',
        'lecturer_group_notifications',
        'makeup_lectures_requests',
        'qa_submissions',
        'student_excuse_submissions',
        'student_group_members',
        'timetable',
        'lecture_sessions',
        'lecturer_attendance',
        'notification_reads',
        'outcome_assessment_method',
        'outcome_teaching_strategy',
        'qa_answers',
        'qa_campaign_assignments',
        'student_attendance',
        'student_grades',
        'topic_questions',
        'lecture_attachments',
        'qr_codes',
        'session_topics_covered',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ======================================================================
        // Level 0
        // ======================================================================

        // [1/62] assessment_methods
        Schema::create('assessment_methods', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('id');
            $table->unsignedInteger('program_id')->nullable();
            $table->string('name', 200)->comment('مثال: اختبارات قصيرة');
            $table->text('description')->nullable();
            $table->enum('category', ['exam', 'assignment', 'project', 'presentation', 'participation', 'portfolio', 'other'])->default('exam')->comment('فئة الطريقة');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'name'], 'assessment_method_program_name_unique');

            $table->index(['is_active', 'category'], 'assessment_methods_is_active_category_index');
            $table->index('program_id', 'assessment_methods_program_id_index');
        });

        // [2/62] colleges
        Schema::create('colleges', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('college_id');
            $table->string('college_name', 100);
            $table->string('college_code', 20)->nullable();
            $table->string('college_logo', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('college_code', 'colleges_college_code_unique');
        });

        // [3/62] days
        Schema::create('days', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('day_id');
            $table->string('day_name', 20);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('day_name', 'days_day_name_unique');
        });

        // [4/62] ip_restrictions
        Schema::create('ip_restrictions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->string('type', 255);
            $table->string('ip_address', 255);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // [5/62] permissions
        Schema::create('permissions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('permission_id');
            $table->string('permission_key', 100);
            $table->string('permission_name', 100);
            $table->string('description', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('permission_key', 'permissions_permission_key_unique');
        });

        // [6/62] program_option_audits
        Schema::create('program_option_audits', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->unsignedInteger('program_id');
            $table->string('option_type', 30);
            $table->unsignedBigInteger('option_id')->nullable();
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->json('details')->nullable();
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->index('program_id', 'program_option_audits_program_id_index');
        });

        // [7/62] teaching_strategies
        Schema::create('teaching_strategies', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('id');
            $table->unsignedInteger('program_id')->nullable();
            $table->string('name', 200)->comment('مثال: المحاضرة التفاعلية');
            $table->text('description')->nullable();
            $table->enum('category', ['lecture', 'practical', 'discussion', 'collaboration', 'project_based', 'problem_solving', 'simulation', 'other'])->default('lecture')->comment('فئة الاستراتيجية');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['program_id', 'name'], 'teaching_strategy_program_name_unique');

            $table->index(['is_active', 'category'], 'teaching_strategies_is_active_category_index');
            $table->index('program_id', 'teaching_strategies_program_id_index');
        });

        // [8/62] user_types
        Schema::create('user_types', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('user_type_id');
            $table->string('user_type_name', 50);
            $table->string('user_type_code', 30);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_type_name', 'user_types_user_type_name_unique');
            $table->unique('user_type_code', 'user_types_user_type_code_unique');
        });

        // ======================================================================
        // Level 1
        // ======================================================================

        // [9/62] academic_titles
        Schema::create('academic_titles', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('title_id');
            $table->unsignedInteger('college_id');
            $table->string('title_name', 100);
            $table->string('title_code', 50);
            $table->decimal('hourly_price', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['college_id', 'title_code'], 'academic_titles_college_id_title_code_unique');
            $table->unique(['college_id', 'title_name'], 'academic_titles_college_id_title_name_unique');

            $table->foreign('college_id', 'academic_titles_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
        });

        // [10/62] buildings
        Schema::create('buildings', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('building_id');
            $table->string('building_name', 100);
            $table->string('building_code', 50)->nullable();
            $table->integer('floors_count');
            $table->unsignedInteger('college_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('building_name', 'buildings_building_name_index');
            $table->index('college_id', 'buildings_college_id_foreign');

            $table->foreign('college_id', 'buildings_college_id_foreign')->references('college_id')->on('colleges')->onDelete('set null');
        });

        // [11/62] departments
        Schema::create('departments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('department_id');
            $table->string('department_name', 100);
            $table->string('department_code', 20)->nullable();
            $table->unsignedInteger('college_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('department_code', 'departments_department_code_unique');

            $table->index('college_id', 'departments_college_id_foreign');

            $table->foreign('college_id', 'departments_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
        });

        // [12/62] periods
        Schema::create('periods', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('period_id');
            $table->unsignedInteger('college_id');
            $table->string('period_name', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('session_type', 10);
            $table->timestamps();
            $table->softDeletes();

            $table->index('college_id', 'periods_college_id_foreign');

            $table->foreign('college_id', 'periods_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
        });

        // [13/62] qa_forms
        Schema::create('qa_forms', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('form_id');
            $table->string('title', 150);
            $table->string('description', 255)->nullable();
            $table->enum('target_type', ['theory', 'practical', 'both'])->default('theory');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('college_id')->nullable();
            $table->string('academic_year', 20);
            $table->timestamps();
            $table->softDeletes();

            $table->index('college_id', 'qa_forms_college_id_foreign');

            $table->foreign('college_id', 'qa_forms_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
        });

        // [14/62] user_type_permissions
        Schema::create('user_type_permissions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->unsignedInteger('user_type_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('permission_id');
            $table->timestamps();

            $table->primary(['user_type_id', 'permission_id', 'college_id']);

            $table->index('college_id', 'user_type_permissions_college_id_foreign');
            $table->index('permission_id', 'user_type_permissions_permission_id_foreign');

            $table->foreign('college_id', 'user_type_permissions_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('permission_id', 'user_type_permissions_permission_id_foreign')->references('permission_id')->on('permissions')->onDelete('cascade');
            $table->foreign('user_type_id', 'user_type_permissions_user_type_id_foreign')->references('user_type_id')->on('user_types')->onDelete('cascade');
        });

        // [15/62] users
        Schema::create('users', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('user_id');
            $table->string('full_name', 100);
            $table->string('email', 100);
            $table->string('phone', 20)->nullable();
            $table->unsignedInteger('college_id')->nullable();
            $table->string('password', 255);
            $table->string('academic_number', 50);
            $table->tinyInteger('gender');
            $table->unsignedInteger('user_type_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('email', 'users_email_unique');
            $table->unique('academic_number', 'users_academic_number_unique');
            $table->unique('phone', 'users_phone_unique');

            $table->index('user_type_id', 'users_user_type_id_foreign');
            $table->index('college_id', 'users_college_id_foreign');
            $table->index('full_name', 'users_full_name_index');

            $table->foreign('college_id', 'users_college_id_foreign')->references('college_id')->on('colleges')->onDelete('set null');
            $table->foreign('user_type_id', 'users_user_type_id_foreign')->references('user_type_id')->on('user_types')->onDelete('cascade');
        });

        // ======================================================================
        // Level 2
        // ======================================================================

        // [16/62] classrooms
        Schema::create('classrooms', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('classroom_id');
            $table->string('classroom_name', 100);
            $table->unsignedInteger('building_id');
            $table->unsignedInteger('college_id')->nullable();
            $table->integer('floor');
            $table->integer('capacity');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('allowed_distance', 5, 2);
            $table->tinyInteger('classroom_type');
            $table->integer('windows_count')->default(0);
            $table->boolean('has_computer')->default(false);
            $table->enum('display_type', ['none', 'screen', 'projector', 'smart_board'])->default('none');
            $table->text('notes')->nullable();
            $table->string('location_address', 255)->nullable();
            $table->string('remote_id', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['building_id', 'floor', 'classroom_name'], 'unique_room_per_floor_per_building');
            $table->unique('remote_id', 'classrooms_remote_id_unique');

            $table->index('classroom_name', 'classrooms_classroom_name_index');
            $table->index('college_id', 'classrooms_college_id_foreign');

            $table->foreign('building_id', 'classrooms_building_id_foreign')->references('building_id')->on('buildings')->onDelete('cascade');
            $table->foreign('college_id', 'classrooms_college_id_foreign')->references('college_id')->on('colleges')->onDelete('set null');
        });

        // [17/62] financial_cycles
        Schema::create('financial_cycles', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('cycle_id');
            $table->unsignedInteger('college_id');
            $table->string('month_year', 7)->comment('Format: MM-YYYY');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->decimal('total_payout', 15, 2)->default(0);
            $table->integer('lecturers_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['college_id', 'month_year'], 'financial_cycles_college_id_month_year_unique');

            $table->index('created_by', 'financial_cycles_created_by_foreign');

            $table->foreign('college_id', 'financial_cycles_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('created_by', 'financial_cycles_created_by_foreign')->references('user_id')->on('users')->onDelete('set null');
        });

        // [18/62] lecturers
        Schema::create('lecturers', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('lecturer_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('title_id')->nullable();
            $table->date('hire_date');
            $table->boolean('status')->default(true);
            $table->boolean('can_teach_externally')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id', 'lecturers_user_id_unique');

            $table->index('college_id', 'lecturers_college_id_foreign');
            $table->index('department_id', 'lecturers_department_id_foreign');
            $table->index('title_id', 'lecturers_title_id_foreign');

            $table->foreign('college_id', 'lecturers_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id', 'lecturers_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('title_id', 'lecturers_title_id_foreign')->references('title_id')->on('academic_titles')->onDelete('set null');
            $table->foreign('user_id', 'lecturers_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [19/62] otp_device_verifications
        Schema::create('otp_device_verifications', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('verification_id');
            $table->unsignedInteger('user_id');
            $table->string('otp_code', 255);
            $table->string('device_name', 100);
            $table->string('mac_address', 100);
            $table->string('device_identifier', 255)->nullable();
            $table->string('os_type', 50);
            $table->string('installation_path', 255)->nullable();
            $table->tinyInteger('delivery_status')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index('user_id', 'otp_device_verifications_user_id_foreign');

            $table->foreign('user_id', 'otp_device_verifications_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [20/62] programs
        Schema::create('programs', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('program_id');
            $table->unsignedInteger('department_id');
            $table->string('program_name', 50);
            $table->enum('academic_system', ['semester', 'credit'])->default('semester');
            $table->boolean('block_based')->default(false);
            $table->integer('total_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['department_id', 'program_name', 'academic_system', 'block_based'], 'program_full_unique_index');

            $table->foreign('department_id', 'programs_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
        });

        // [21/62] qa_campaigns
        Schema::create('qa_campaigns', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('campaign_id');
            $table->string('campaign_name', 100);
            $table->unsignedInteger('form_id');
            $table->string('academic_year', 20);
            $table->integer('min_attendance_percentage')->default(0);
            $table->integer('target_percentage')->default(80)->comment('النسبة المستهدفة للنجاح');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('form_id', 'qa_campaigns_form_id_foreign');

            $table->foreign('form_id', 'qa_campaigns_form_id_foreign')->references('form_id')->on('qa_forms')->onDelete('cascade');
        });

        // [22/62] qa_domains
        Schema::create('qa_domains', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('domain_id');
            $table->unsignedInteger('form_id');
            $table->string('domain_name', 100);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('form_id', 'qa_domains_form_id_foreign');

            $table->foreign('form_id', 'qa_domains_form_id_foreign')->references('form_id')->on('qa_forms')->onDelete('cascade');
        });

        // [23/62] user_activities
        Schema::create('user_activities', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('activity_id');
            $table->unsignedInteger('user_id');
            $table->string('action_type', 50);
            $table->text('action_description')->nullable();
            $table->string('module_name', 50)->nullable();
            $table->timestamps();

            $table->index('user_id', 'user_activities_user_id_foreign');

            $table->foreign('user_id', 'user_activities_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [24/62] user_devices
        Schema::create('user_devices', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('device_id');
            $table->unsignedInteger('user_id');
            $table->string('device_name', 100);
            $table->string('mac_address', 100);
            $table->string('device_identifier', 255)->nullable();
            $table->string('os_type', 50);
            $table->string('installation_path', 255)->nullable();
            $table->boolean('is_auto_attendance_enabled')->default(false);
            $table->dateTime('registered_at')->useCurrent();
            $table->dateTime('last_login_at')->nullable();

            $table->index('user_id', 'user_devices_user_id_foreign');

            $table->foreign('user_id', 'user_devices_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // ======================================================================
        // Level 3
        // ======================================================================

        // [25/62] lecturer_payouts
        Schema::create('lecturer_payouts', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('payout_id');
            $table->unsignedInteger('cycle_id');
            $table->unsignedInteger('lecturer_id');
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('total_bonuses', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            if (DB::connection()->getDriverName() === 'pgsql') {
                // PostgreSQL Syntax (بدون backticks)
                DB::statement('ALTER TABLE lecturer_payouts ADD COLUMN net_amount DECIMAL(12,2) GENERATED ALWAYS AS (base_amount + total_bonuses - total_deductions - tax_amount) STORED');
            } else {
                // MySQL Syntax (الكود الأصلي)
                $table->decimal('net_amount', 12, 2)->storedAs('`base_amount` + `total_bonuses` - `total_deductions` - `tax_amount`');
            }
            $table->string('status', 20)->default('pending');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'lecturer_id'], 'lecturer_payouts_cycle_id_lecturer_id_unique');

            $table->index('lecturer_id', 'lecturer_payouts_lecturer_id_foreign');

            $table->foreign('cycle_id', 'lecturer_payouts_cycle_id_foreign')->references('cycle_id')->on('financial_cycles')->onDelete('cascade');
            $table->foreign('lecturer_id', 'lecturer_payouts_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
        });

        // [26/62] levels
        Schema::create('levels', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('level_id');
            $table->string('level_name', 50)->nullable();
            $table->unsignedInteger('program_id');
            $table->tinyInteger('level_number');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'level_number'], 'unique_program_level_number');

            $table->foreign('program_id', 'levels_program_id_foreign')->references('program_id')->on('programs')->onDelete('cascade');
        });

        // [27/62] program_learning_outcomes
        Schema::create('program_learning_outcomes', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('plo_id');
            $table->unsignedInteger('program_id');
            $table->string('code', 10)->comment('مثال: A1, B1, C1, D1');
            $table->enum('domain', ['Knowledge', 'Intellectual', 'Professional', 'General'])->comment('مجال المخرج');
            $table->text('description')->comment('وصف مخرج التعلم');
            $table->decimal('weight', 5, 2)->default(0)->comment('وزن المخرج من 100');
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'code'], 'program_learning_outcomes_program_id_code_unique');
            $table->unique(['program_id', 'order'], 'unique_program_order');

            $table->index(['program_id', 'domain'], 'program_learning_outcomes_program_id_domain_index');

            $table->foreign('program_id', 'program_learning_outcomes_program_id_foreign')->references('program_id')->on('programs')->onDelete('cascade');
        });

        // [28/62] qa_questions
        Schema::create('qa_questions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('question_id');
            $table->unsignedInteger('domain_id');
            $table->text('question_text');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('domain_id', 'qa_questions_domain_id_foreign');

            $table->foreign('domain_id', 'qa_questions_domain_id_foreign')->references('domain_id')->on('qa_domains')->onDelete('cascade');
        });

        // ======================================================================
        // Level 4
        // ======================================================================

        // [29/62] blocks
        Schema::create('blocks', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->string('block_name', 255);
            $table->integer('block_number');
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('credit_hours', 5, 2)->nullable()->default(0);
            $table->integer('weeks')->default(1);
            $table->enum('type', ['compulsory', 'elective'])->default('compulsory');
            $table->unsignedInteger('program_id');
            $table->unsignedInteger('level_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('program_id', 'blocks_program_id_foreign');
            $table->index('level_id', 'blocks_level_id_foreign');

            $table->foreign('level_id', 'blocks_level_id_foreign')->references('level_id')->on('levels')->onDelete('set null');
            $table->foreign('program_id', 'blocks_program_id_foreign')->references('program_id')->on('programs')->onDelete('cascade');
        });

        // [30/62] payout_adjustments
        Schema::create('payout_adjustments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('adjustment_id');
            $table->unsignedInteger('payout_id');
            $table->string('type', 20);
            $table->decimal('amount', 10, 2);
            $table->string('reason', 255)->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->timestamps();

            $table->index('payout_id', 'payout_adjustments_payout_id_foreign');

            $table->foreign('payout_id', 'payout_adjustments_payout_id_foreign')->references('payout_id')->on('lecturer_payouts')->onDelete('cascade');
        });

        // [31/62] semesters
        Schema::create('semesters', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('semester_id');
            $table->string('semester_name', 50);
            $table->string('academic_year', 20);
            $table->unsignedInteger('level_id');
            $table->tinyInteger('term_number');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['level_id', 'term_number'], 'unique_level_term_number');

            $table->foreign('level_id', 'semesters_level_id_foreign')->references('level_id')->on('levels')->onDelete('cascade');
        });

        // ======================================================================
        // Level 5
        // ======================================================================

        // [32/62] block_relations
        Schema::create('block_relations', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->unsignedBigInteger('block_id');
            $table->unsignedBigInteger('related_block_id');
            $table->enum('relation_type', ['prerequisite', 'concurrent', 'next']);
            $table->timestamps();

            $table->index('block_id', 'block_relations_block_id_foreign');
            $table->index('related_block_id', 'block_relations_related_block_id_foreign');

            $table->foreign('block_id', 'block_relations_block_id_foreign')->references('id')->on('blocks')->onDelete('cascade');
            $table->foreign('related_block_id', 'block_relations_related_block_id_foreign')->references('id')->on('blocks')->onDelete('cascade');
        });

        // [33/62] courses
        Schema::create('courses', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('course_id');
            $table->string('course_name', 150);
            $table->string('course_code', 50);
            $table->tinyInteger('course_type')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('program_id')->nullable();
            $table->unsignedInteger('level_id')->nullable();
            $table->unsignedInteger('semester_id')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->integer('credit_hours')->default(0);
            $table->json('course_parts')->nullable()->comment('أجزاء المقرر: نظري، عملي، تمارين، سريري');
            $table->decimal('weight', 5, 2)->default(0)->comment('وزن المقرر % من مخرجات البرنامج');
            $table->enum('category', ['متطلب جامعة', 'متطلب كلية', 'متطلب تخصص إجباري', 'متطلب تخصص اختياري'])->default('متطلب تخصص إجباري');
            $table->enum('teaching_language', ['العربية', 'الإنجليزية', 'ثنائي اللغة'])->default('العربية');
            $table->string('notes', 500)->nullable();
            $table->boolean('is_approved')->default(false)->comment('true = المقرر معتمد ومدرج رسمياً');
            $table->date('approval_date')->nullable()->comment('تاريخ اعتماد المقرر رسمياً');
            $table->string('approved_by', 300)->nullable()->comment('اسم الشخص الذي وافق على المقرر');
            $table->enum('specification_status', ['draft', 'in_progress', 'under_review', 'approved', 'published'])->default('draft')->comment('حالة توصيف المقرر');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('course_code', 'courses_course_code_unique');

            $table->index('college_id', 'courses_college_id_foreign');
            $table->index('department_id', 'courses_department_id_foreign');
            $table->index('program_id', 'courses_program_id_foreign');
            $table->index('level_id', 'courses_level_id_foreign');
            $table->index('semester_id', 'courses_semester_id_foreign');
            $table->index('block_id', 'courses_block_id_foreign');

            $table->foreign('block_id', 'courses_block_id_foreign')->references('id')->on('blocks')->onDelete('set null');
            $table->foreign('college_id', 'courses_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id', 'courses_department_id_foreign')->references('department_id')->on('departments')->onDelete('set null');
            $table->foreign('level_id', 'courses_level_id_foreign')->references('level_id')->on('levels')->onDelete('set null');
            $table->foreign('program_id', 'courses_program_id_foreign')->references('program_id')->on('programs')->onDelete('set null');
            $table->foreign('semester_id', 'courses_semester_id_foreign')->references('semester_id')->on('semesters')->onDelete('cascade');
        });

        // [34/62] student_groups
        Schema::create('student_groups', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('group_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('program_id')->nullable();
            $table->unsignedInteger('level_id')->nullable();
            $table->unsignedInteger('semester_id')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->string('group_name', 100);
            $table->unsignedInteger('max_students')->nullable()->default(30);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'], 'unique_group_per_path_v2');

            $table->index('department_id', 'student_groups_department_id_foreign');
            $table->index('level_id', 'student_groups_level_id_foreign');
            $table->index('semester_id', 'student_groups_semester_id_foreign');
            $table->index('block_id', 'student_groups_block_id_foreign');
            $table->index(['program_id', 'level_id', 'semester_id', 'block_id'], 'student_groups_program_id_level_id_semester_id_block_id_index');

            $table->foreign('block_id', 'student_groups_block_id_foreign')->references('id')->on('blocks')->onDelete('set null');
            $table->foreign('college_id', 'student_groups_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id', 'student_groups_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('level_id', 'student_groups_level_id_foreign')->references('level_id')->on('levels')->onDelete('cascade');
            $table->foreign('program_id', 'student_groups_program_id_foreign')->references('program_id')->on('programs')->onDelete('cascade');
            $table->foreign('semester_id', 'student_groups_semester_id_foreign')->references('semester_id')->on('semesters')->onDelete('cascade');
        });

        // [35/62] students
        Schema::create('students', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('student_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('level_id')->nullable();
            $table->unsignedInteger('semester_id')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
            $table->unsignedInteger('program_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id', 'students_user_id_unique');

            $table->index('college_id', 'students_college_id_foreign');
            $table->index('department_id', 'students_department_id_foreign');
            $table->index('level_id', 'students_level_id_foreign');
            $table->index('semester_id', 'students_semester_id_foreign');
            $table->index('block_id', 'students_block_id_foreign');
            $table->index(['program_id', 'level_id', 'semester_id', 'block_id'], 'students_program_id_level_id_semester_id_block_id_index');

            $table->foreign('block_id', 'students_block_id_foreign')->references('id')->on('blocks')->onDelete('set null');
            $table->foreign('college_id', 'students_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id', 'students_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('level_id', 'students_level_id_foreign')->references('level_id')->on('levels')->onDelete('cascade');
            $table->foreign('program_id', 'students_program_id_foreign')->references('program_id')->on('programs')->onDelete('set null');
            $table->foreign('semester_id', 'students_semester_id_foreign')->references('semester_id')->on('semesters')->onDelete('set null');
            $table->foreign('user_id', 'students_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // ======================================================================
        // Level 6
        // ======================================================================

        // [36/62] course_assessments
        Schema::create('course_assessments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('assessment_id');
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('group_id')->nullable();
            $table->unsignedInteger('semester_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('name', 100);
            $table->tinyInteger('week')->nullable()->comment('الأسبوع');
            $table->decimal('max_score', 5, 2);
            $table->integer('weight')->default(0);
            $table->decimal('percentage', 5, 2)->default(0)->comment('النسبة % من إجمالي التقويم');
            $table->json('clo_ids')->nullable()->comment('مصفوفة رموز مخرجات التعلم');
            $table->enum('assessment_type', ['activities', 'quizzes', 'midterm_exam', 'final_exam', 'project', 'presentation', 'practical_exam', 'other'])->default('activities')->comment('نوع التقييم');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->decimal('grade', 5, 2)->default(0);
            $table->text('notes')->nullable();

            $table->index('college_id', 'course_assessments_college_id_foreign');
            $table->index('group_id', 'course_assessments_group_id_foreign');
            $table->index('semester_id', 'course_assessments_semester_id_foreign');
            $table->index('created_by', 'course_assessments_created_by_foreign');
            $table->index(['course_id', 'group_id', 'academic_year', 'semester_id'], 'assessment_context_index');

            $table->foreign('college_id', 'course_assessments_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('course_id', 'course_assessments_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('created_by', 'course_assessments_created_by_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('group_id', 'course_assessments_group_id_foreign')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('semester_id', 'course_assessments_semester_id_foreign')->references('semester_id')->on('semesters')->onDelete('cascade');
        });

        // [37/62] course_assignments
        Schema::create('course_assignments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('assignment_id');
            $table->unsignedInteger('course_id');
            $table->enum('part', ['نظري', 'عملي', 'تمارين', 'سريري'])->comment('جزء المقرر');
            $table->string('title', 300)->comment('مثال: واجب 1، مشروع نهائي');
            $table->text('description')->nullable();
            $table->tinyInteger('week')->comment('الأسبوع (1-16)');
            $table->decimal('grade', 5, 2)->default(0)->comment('الدرجة المخصصة');
            $table->json('clo_ids')->nullable()->comment('مصفوفة رموز مخرجات التعلم');
            $table->enum('assignment_type', ['homework', 'project', 'presentation', 'quiz', 'other'])->default('homework')->comment('نوع التكليف');
            $table->boolean('is_mandatory')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'part', 'week'], 'course_assignments_course_id_part_week_index');

            $table->foreign('course_id', 'course_assignments_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
        });

        // [38/62] course_descriptions
        Schema::create('course_descriptions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->unsignedInteger('course_id');
            $table->longText('description')->nullable()->comment('وصف المقرر');
            $table->json('goals')->nullable()->comment('أهداف المقرر');
            $table->integer('word_count')->default(0)->comment('عدد الكلمات');
            $table->integer('goals_count')->default(0)->comment('عدد الأهداف');
            $table->boolean('is_completed')->default(false)->comment('هل مكتمل');
            $table->timestamps();

            $table->unique('course_id', 'course_descriptions_course_id_unique');

            $table->index('is_completed', 'course_descriptions_is_completed_index');

            $table->foreign('course_id', 'course_descriptions_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade')->onUpdate('cascade');
        });

        // [39/62] course_learning_outcomes
        Schema::create('course_learning_outcomes', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('clo_id');
            $table->unsignedInteger('course_id');
            $table->string('code', 10)->comment('مثال: a1, b1, c1, d1');
            $table->enum('domain', ['Knowledge', 'Intellectual', 'Professional', 'General'])->comment('مجال المخرج');
            $table->text('description')->comment('وصف مخرج التعلم');
            $table->decimal('weight', 5, 2)->default(0)->comment('وزن المخرج من وزن المقرر (%)');
            $table->unsignedInteger('plo_id')->nullable()->comment('ربط بمخرج تعلم البرنامج المناظر');
            $table->decimal('plo_weight', 5, 2)->nullable()->comment('وزن PLO من وزن البرنامج (للمرجع فقط)');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'code'], 'course_learning_outcomes_course_id_code_unique');

            $table->index('plo_id', 'course_learning_outcomes_plo_id_foreign');
            $table->index(['course_id', 'domain'], 'course_learning_outcomes_course_id_domain_index');

            $table->foreign('course_id', 'course_learning_outcomes_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('plo_id', 'course_learning_outcomes_plo_id_foreign')->references('plo_id')->on('program_learning_outcomes')->onDelete('set null');
        });

        // [40/62] course_policies
        Schema::create('course_policies', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('policy_id');
            $table->unsignedInteger('course_id');
            $table->tinyInteger('policy_number')->comment('1-7 ثابت، 8+ مضافة');
            $table->string('title', 300)->comment('عنوان الضابط');
            $table->text('content')->comment('نص الضابط التفصيلي');
            $table->boolean('is_fixed')->default(false)->comment('true = الضوابط السبعة الأساسية');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'policy_number'], 'course_policies_course_id_policy_number_unique');

            $table->foreign('course_id', 'course_policies_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
        });

        // [41/62] course_prerequisites
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('id');
            $table->unsignedInteger('course_id')->comment('المقرر الحالي');
            $table->unsignedInteger('prerequisite_course_id')->comment('المقرر المطلوب');
            $table->enum('type', ['prerequisite', 'corequisite'])->default('prerequisite')->comment('prerequisite=سابق، corequisite=مصاحب');
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id', 'type'], 'course_prereq_unique');

            $table->index('prerequisite_course_id', 'course_prerequisites_prerequisite_course_id_foreign');

            $table->foreign('course_id', 'course_prerequisites_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('prerequisite_course_id', 'course_prerequisites_prerequisite_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
        });

        // [42/62] course_references
        Schema::create('course_references', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('reference_id');
            $table->unsignedInteger('course_id');
            $table->enum('type', ['main', 'support', 'electronic'])->comment('نوع المرجع');
            $table->enum('category', ['website', 'journal', 'other'])->nullable()->comment('فئة المصدر الإلكتروني');
            $table->string('author', 300)->nullable();
            $table->year('year')->nullable();
            $table->string('title', 500)->comment('عنوان المرجع');
            $table->string('edition', 100)->nullable();
            $table->string('publisher', 300)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('url')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_id', 'type'], 'course_references_course_id_type_index');

            $table->foreign('course_id', 'course_references_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
        });

        // [43/62] course_topics
        Schema::create('course_topics', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('topic_id');
            $table->unsignedInteger('course_id');
            $table->enum('part', ['نظري', 'عملي', 'تمارين', 'سريري'])->comment('جزء المقرر');
            $table->tinyInteger('week')->comment('الأسبوع (1-16)');
            $table->string('unit_name', 300)->comment('مثال: مقدمة في هياكل البيانات');
            $table->json('subtopics')->nullable()->comment('مصفوفة المواضيع الفرعية');
            $table->boolean('is_exam')->default(false)->comment('true = امتحان نصفي أو نهائي');
            $table->enum('exam_type', ['midterm', 'final'])->nullable()->comment('نوع الامتحان');
            $table->decimal('hours', 5, 2)->default(0)->comment('الساعات الفعلية');
            $table->json('clo_ids')->nullable()->comment('مصفوفة رموز مخرجات التعلم');
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'part', 'week', 'unit_name'], 'course_topics_course_id_part_week_unit_name_unique');

            $table->index(['course_id', 'part', 'week'], 'course_topics_course_id_part_week_index');

            $table->foreign('course_id', 'course_topics_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
        });

        // [44/62] lecturer_group_notifications
        Schema::create('lecturer_group_notifications', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('notification_id');
            $table->unsignedInteger('lecturer_user_id');
            $table->string('subject', 150);
            $table->text('message_body');
            $table->dateTime('send_at')->useCurrent();
            $table->unsignedInteger('group_id');
            $table->boolean('is_sent')->default(true);
            $table->boolean('is_seen')->default(false);
            $table->timestamps();

            $table->unique(['lecturer_user_id', 'group_id', 'send_at'], 'unique_group_notification');

            $table->index('group_id', 'lecturer_group_notifications_group_id_foreign');

            $table->foreign('group_id', 'lecturer_group_notifications_group_id_foreign')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('lecturer_user_id', 'lecturer_group_notifications_lecturer_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [45/62] makeup_lectures_requests
        Schema::create('makeup_lectures_requests', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('request_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('group_id');
            $table->date('original_date')->nullable();
            $table->date('requested_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('classroom_id')->nullable();
            $table->enum('reason_type', ['sick_leave', 'travel', 'schedule_conflict', 'official_holiday', 'event', 'maintenance', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('notification_status')->default(0);
            $table->timestamps();

            $table->index('lecturer_id', 'makeup_lectures_requests_lecturer_id_foreign');
            $table->index('course_id', 'makeup_lectures_requests_course_id_foreign');
            $table->index('group_id', 'makeup_lectures_requests_group_id_foreign');
            $table->index('classroom_id', 'makeup_lectures_requests_classroom_id_foreign');

            $table->foreign('classroom_id', 'makeup_lectures_requests_classroom_id_foreign')->references('classroom_id')->on('classrooms')->onDelete('set null');
            $table->foreign('course_id', 'makeup_lectures_requests_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('group_id', 'makeup_lectures_requests_group_id_foreign')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('lecturer_id', 'makeup_lectures_requests_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
        });

        // [46/62] qa_submissions
        Schema::create('qa_submissions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('submission_id');
            $table->unsignedInteger('campaign_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('submission_date_timestamp')->nullable();
            $table->boolean('is_practical')->default(false);
            $table->timestamps();

            $table->unique(['campaign_id', 'student_id', 'lecturer_id', 'course_id'], 'unique_student_evaluation');

            $table->index('student_id', 'qa_submissions_student_id_foreign');
            $table->index('lecturer_id', 'qa_submissions_lecturer_id_foreign');
            $table->index('course_id', 'qa_submissions_course_id_foreign');

            $table->foreign('campaign_id', 'qa_submissions_campaign_id_foreign')->references('campaign_id')->on('qa_campaigns')->onDelete('cascade');
            $table->foreign('course_id', 'qa_submissions_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('lecturer_id', 'qa_submissions_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('student_id', 'qa_submissions_student_id_foreign')->references('student_id')->on('students')->onDelete('cascade');
        });

        // [47/62] student_excuse_submissions
        Schema::create('student_excuse_submissions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('submission_id');
            $table->unsignedInteger('student_user_id');
            $table->date('request_date');
            $table->text('reason');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('lecturer_user_id');
            $table->boolean('is_lecturer_notified')->default(false);
            $table->tinyInteger('response_status')->default(0);
            $table->string('lecturer_comment', 255)->nullable();
            $table->timestamps();
            $table->string('excuse_image', 255)->nullable();

            $table->unique(['student_user_id', 'course_id', 'request_date'], 'unique_student_course_date');

            $table->index('course_id', 'student_excuse_submissions_course_id_foreign');
            $table->index('lecturer_user_id', 'student_excuse_submissions_lecturer_user_id_foreign');

            $table->foreign('course_id', 'student_excuse_submissions_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('lecturer_user_id', 'student_excuse_submissions_lecturer_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('student_user_id', 'student_excuse_submissions_student_user_id_foreign')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [48/62] student_group_members
        Schema::create('student_group_members', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->unsignedInteger('student_id');
            $table->unsignedInteger('group_id');
            $table->timestamps();

            $table->primary(['student_id', 'group_id']);

            $table->index('group_id', 'student_group_members_group_id_foreign');

            $table->foreign('group_id', 'student_group_members_group_id_foreign')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('student_id', 'student_group_members_student_id_foreign')->references('student_id')->on('students')->onDelete('cascade');
        });

        // [49/62] timetable
        Schema::create('timetable', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('timetable_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('level_id')->nullable();
            $table->unsignedInteger('semester_id')->nullable();
            $table->unsignedBigInteger('block_id')->nullable();
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
            $table->unsignedInteger('program_id')->nullable();
            $table->tinyInteger('gender_type')->default(0);
            $table->decimal('lecture_hours', 4, 2);
            $table->integer('allowance_minutes');
            $table->timestamps();

            $table->index('course_id', 'timetable_course_id_foreign');
            $table->index('day_id', 'timetable_day_id_foreign');
            $table->index('period_id', 'timetable_period_id_foreign');
            $table->index('college_id', 'timetable_college_id_foreign');
            $table->index('department_id', 'timetable_department_id_foreign');
            $table->index('level_id', 'timetable_level_id_foreign');
            $table->index('classroom_id', 'timetable_classroom_id_index');
            $table->index('lecturer_id', 'timetable_lecturer_id_index');
            $table->index('group_id', 'timetable_group_id_index');
            $table->index('program_id', 'timetable_program_id_foreign');
            $table->index('semester_id', 'timetable_semester_id_foreign');
            $table->index('block_id', 'timetable_block_id_foreign');

            $table->foreign('block_id', 'timetable_block_id_foreign')->references('id')->on('blocks')->onDelete('set null');
            $table->foreign('classroom_id', 'timetable_classroom_id_foreign')->references('classroom_id')->on('classrooms')->onDelete('cascade');
            $table->foreign('college_id', 'timetable_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('course_id', 'timetable_course_id_foreign')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('day_id', 'timetable_day_id_foreign')->references('day_id')->on('days')->onDelete('cascade');
            $table->foreign('department_id', 'timetable_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('group_id', 'timetable_group_id_foreign')->references('group_id')->on('student_groups')->onDelete('cascade');
            $table->foreign('lecturer_id', 'timetable_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('level_id', 'timetable_level_id_foreign')->references('level_id')->on('levels')->onDelete('cascade');
            $table->foreign('period_id', 'timetable_period_id_foreign')->references('period_id')->on('periods')->onDelete('cascade');
            $table->foreign('program_id', 'timetable_program_id_foreign')->references('program_id')->on('programs')->onDelete('set null');
            $table->foreign('semester_id', 'timetable_semester_id_foreign')->references('semester_id')->on('semesters')->onDelete('set null');
        });

        // ======================================================================
        // Level 7
        // ======================================================================

        // [50/62] lecture_sessions
        Schema::create('lecture_sessions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('session_id');
            $table->unsignedInteger('timetable_id');
            $table->unsignedInteger('lecturer_id')->nullable();
            $table->date('session_date');
            $table->timestamp('actual_start_time')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamp('actual_end_time')->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->boolean('is_ended_remotely')->default(false);
            $table->string('early_exit_reason', 255)->nullable();
            $table->unsignedInteger('actual_classroom_id')->nullable();
            $table->string('session_code', 50);
            $table->tinyInteger('status')->default(0);
            $table->boolean('is_makeup')->default(false)->comment('0: Basic, 1: Makeup');
            $table->timestamps();

            $table->unique('session_code', 'lecture_sessions_session_code_unique');

            $table->index('timetable_id', 'lecture_sessions_timetable_id_foreign');
            $table->index('actual_classroom_id', 'lecture_sessions_actual_classroom_id_foreign');
            $table->index('lecturer_id', 'lecture_sessions_lecturer_id_foreign');

            $table->foreign('actual_classroom_id', 'lecture_sessions_actual_classroom_id_foreign')->references('classroom_id')->on('classrooms')->onDelete('set null');
            $table->foreign('lecturer_id', 'lecture_sessions_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('timetable_id', 'lecture_sessions_timetable_id_foreign')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });

        // [51/62] lecturer_attendance
        Schema::create('lecturer_attendance', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('attendance_id');
            $table->unsignedInteger('lecturer_id');
            $table->unsignedInteger('timetable_id');
            $table->date('attendance_date');
            $table->tinyInteger('status')->default(0)->comment('0: غائب, 1: حاضر');
            $table->tinyInteger('notification_status')->default(0);
            $table->unsignedInteger('college_id');
            $table->decimal('lecture_hours', 4, 2);
            $table->decimal('hourly_rate_at_attendance', 10, 2)->nullable()->default(0);
            $table->decimal('lecture_rate_at_attendance', 10, 2)->nullable()->default(0);
            $table->string('session_code', 50);
            $table->timestamps();

            $table->unique(['lecturer_id', 'session_code'], 'unique_lecturer_session');

            $table->index('timetable_id', 'lecturer_attendance_timetable_id_foreign');
            $table->index('college_id', 'lecturer_attendance_college_id_foreign');
            $table->index('attendance_date', 'lecturer_attendance_attendance_date_index');

            $table->foreign('college_id', 'lecturer_attendance_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('lecturer_id', 'lecturer_attendance_lecturer_id_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('timetable_id', 'lecturer_attendance_timetable_id_foreign')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });

        // [52/62] notification_reads
        Schema::create('notification_reads', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('read_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('notification_id');
            $table->timestamp('read_at')->nullable()->useCurrent();

            $table->unique(['user_id', 'notification_id'], 'unique_user_notification');

            $table->index('notification_id', 'fk_read_notification');

            $table->foreign('notification_id', 'fk_read_notification')->references('notification_id')->on('lecturer_group_notifications')->onDelete('cascade');
            $table->foreign('user_id', 'fk_read_user')->references('user_id')->on('users')->onDelete('cascade');
        });

        // [53/62] outcome_assessment_method
        Schema::create('outcome_assessment_method', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('id');
            $table->unsignedInteger('clo_id');
            $table->unsignedInteger('method_id');
            $table->timestamps();

            $table->unique(['clo_id', 'method_id'], 'outcome_assessment_method_clo_id_method_id_unique');

            $table->index('method_id', 'outcome_assessment_method_method_id_foreign');

            $table->foreign('clo_id', 'outcome_assessment_method_clo_id_foreign')->references('clo_id')->on('course_learning_outcomes')->onDelete('cascade');
            $table->foreign('method_id', 'outcome_assessment_method_method_id_foreign')->references('id')->on('assessment_methods')->onDelete('cascade');
        });

        // [54/62] outcome_teaching_strategy
        Schema::create('outcome_teaching_strategy', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('id');
            $table->unsignedInteger('clo_id');
            $table->unsignedInteger('strategy_id');
            $table->timestamps();

            $table->unique(['clo_id', 'strategy_id'], 'outcome_teaching_strategy_clo_id_strategy_id_unique');

            $table->index('strategy_id', 'outcome_teaching_strategy_strategy_id_foreign');

            $table->foreign('clo_id', 'outcome_teaching_strategy_clo_id_foreign')->references('clo_id')->on('course_learning_outcomes')->onDelete('cascade');
            $table->foreign('strategy_id', 'outcome_teaching_strategy_strategy_id_foreign')->references('id')->on('teaching_strategies')->onDelete('cascade');
        });

        // [55/62] qa_answers
        Schema::create('qa_answers', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('answer_id');
            $table->unsignedInteger('submission_id');
            $table->unsignedInteger('question_id');
            $table->tinyInteger('rating_value');

            $table->index('submission_id', 'qa_answers_submission_id_foreign');
            $table->index(['question_id', 'rating_value'], 'qa_answers_question_id_rating_value_index');

            $table->foreign('question_id', 'qa_answers_question_id_foreign')->references('question_id')->on('qa_questions')->onDelete('cascade');
            $table->foreign('submission_id', 'qa_answers_submission_id_foreign')->references('submission_id')->on('qa_submissions')->onDelete('cascade');
        });

        // [56/62] qa_campaign_assignments
        Schema::create('qa_campaign_assignments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->bigIncrements('assignment_id');
            $table->unsignedInteger('campaign_id');
            $table->unsignedInteger('timetable_id');

            $table->unique(['campaign_id', 'timetable_id'], 'unique_assignment');

            $table->index('timetable_id', 'qa_campaign_assignments_timetable_id_foreign');

            $table->foreign('campaign_id', 'qa_campaign_assignments_campaign_id_foreign')->references('campaign_id')->on('qa_campaigns')->onDelete('cascade');
            $table->foreign('timetable_id', 'qa_campaign_assignments_timetable_id_foreign')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });

        // [57/62] student_attendance
        Schema::create('student_attendance', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('attendance_id');
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('timetable_id');
            $table->unsignedBigInteger('level_id')->nullable();
            $table->date('attendance_date');
            $table->tinyInteger('status')->default(0);
            $table->text('solved')->nullable();
            $table->tinyInteger('attendance_method')->default(0)->comment('0: QR, 1: Manual, etc');
            $table->tinyInteger('notification_status')->default(0);
            $table->unsignedInteger('college_id');
            $table->unsignedInteger('department_id');
            $table->string('session_code', 50);
            $table->timestamps();

            $table->unique(['student_id', 'session_code'], 'unique_student_session');

            $table->index('timetable_id', 'student_attendance_timetable_id_foreign');
            $table->index('college_id', 'student_attendance_college_id_foreign');
            $table->index('level_id', 'student_attendance_level_id_foreign');
            $table->index('department_id', 'student_attendance_department_id_foreign');
            $table->index(['student_id', 'attendance_date'], 'student_attendance_student_id_attendance_date_index');

            $table->foreign('college_id', 'student_attendance_college_id_foreign')->references('college_id')->on('colleges')->onDelete('cascade');
            $table->foreign('department_id', 'student_attendance_department_id_foreign')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('student_id', 'student_attendance_student_id_foreign')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('timetable_id', 'student_attendance_timetable_id_foreign')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });

        // [58/62] student_grades
        Schema::create('student_grades', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('grade_id');
            $table->unsignedInteger('assessment_id');
            $table->unsignedInteger('student_id');
            $table->decimal('score', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id'], 'student_grades_assessment_id_student_id_unique');

            $table->index('student_id', 'student_grades_student_id_foreign');

            $table->foreign('assessment_id', 'student_grades_assessment_id_foreign')->references('assessment_id')->on('course_assessments')->onDelete('cascade');
            $table->foreign('student_id', 'student_grades_student_id_foreign')->references('student_id')->on('students')->onDelete('cascade');
        });

        // [59/62] topic_questions
        Schema::create('topic_questions', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('question_id');
            $table->unsignedInteger('course_id')->nullable();
            $table->enum('part', ['نظري', 'عملي', 'تمارين', 'سريري'])->nullable();
            $table->unsignedInteger('topic_id')->nullable();
            $table->string('subtopic', 300)->nullable()->comment('الموضوع الفرعي المحدد');
            $table->text('question_text')->comment('نص السؤال');
            $table->enum('question_type', ['MCQ', 'essay'])->default('MCQ')->comment('MCQ = اختيار من متعدد، essay = مقالي');
            $table->tinyInteger('difficulty_level')->default(1)->comment('1=سهل، 5=صعب جداً');
            $table->string('clo_code', 10)->nullable()->comment('رمز مخرج التعلم (a1, b2, c1, إلخ)');
            $table->json('options')->nullable()->comment('مصفوفة الخيارات (للـ MCQ)');
            $table->text('correct_answer')->nullable()->comment('الإجابة الصحيحة (للمقالي)');
            $table->boolean('is_used_in_exam')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['topic_id', 'question_type', 'difficulty_level'], 'topic_questions_topic_id_question_type_difficulty_level_index');
            $table->index('course_id', 'topic_questions_course_id_index');
            $table->index('part', 'topic_questions_part_index');

            $table->foreign('topic_id', 'topic_questions_topic_id_foreign')->references('topic_id')->on('course_topics')->onDelete('cascade');
        });

        // ======================================================================
        // Level 8
        // ======================================================================

        // [60/62] lecture_attachments
        Schema::create('lecture_attachments', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('attachment_id');
            $table->unsignedInteger('session_id');
            $table->enum('type', ['video', 'file', 'link']);
            $table->string('title', 200);
            $table->text('url');
            $table->string('file_size', 50)->nullable();
            $table->timestamps();

            $table->index('session_id', 'lecture_attachments_session_id_foreign');

            $table->foreign('session_id', 'lecture_attachments_session_id_foreign')->references('session_id')->on('lecture_sessions')->onDelete('cascade');
        });

        // [61/62] qr_codes
        Schema::create('qr_codes', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->increments('qr_id');
            $table->unsignedInteger('timetable_id');
            $table->unsignedInteger('session_id');
            $table->string('qr_code_value', 255);
            $table->dateTime('generated_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('allowed_distance', 5, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('timetable_id', 'qr_codes_timetable_id_foreign');
            $table->index('session_id', 'qr_codes_session_id_foreign');
            $table->index('created_by', 'qr_codes_created_by_foreign');

            $table->foreign('created_by', 'qr_codes_created_by_foreign')->references('lecturer_id')->on('lecturers')->onDelete('cascade');
            $table->foreign('session_id', 'qr_codes_session_id_foreign')->references('session_id')->on('lecture_sessions')->onDelete('cascade');
            $table->foreign('timetable_id', 'qr_codes_timetable_id_foreign')->references('timetable_id')->on('timetable')->onDelete('cascade');
        });

        // [62/62] session_topics_covered
        Schema::create('session_topics_covered', function (Blueprint $table) {
            $this->tableOptions($table);

            $table->unsignedInteger('session_id');
            $table->unsignedInteger('topic_id');
            $table->string('coverage_status', 255)->default('fully_covered');
            $table->timestamps();

            $table->primary(['session_id', 'topic_id']);

            $table->index('topic_id', 'session_topics_covered_topic_id_foreign');

            $table->foreign('session_id', 'session_topics_covered_session_id_foreign')->references('session_id')->on('lecture_sessions')->onDelete('cascade');
            $table->foreign('topic_id', 'session_topics_covered_topic_id_foreign')->references('topic_id')->on('course_topics')->onDelete('cascade');
        });

        // في نهاية الـ up() function
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE lecturer_payouts 
                ADD COLUMN net_amount DECIMAL(12,2) 
                GENERATED ALWAYS AS (base_amount + total_bonuses - total_deductions - tax_amount) STORED
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse(self::TABLES) as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Engine / charset / collation shared by every table (same as the source dump).
     */
    private function tableOptions(Blueprint $table): void
    {
        $table->engine = 'InnoDB';
        $table->charset = 'utf8mb4';
        $table->collation = 'utf8mb4_unicode_ci';
    }
};
