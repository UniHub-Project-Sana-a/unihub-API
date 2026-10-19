<?php

/**
 * تهيئة واجهات التكامل الخارجي (Data Ingestion).
 *
 * كل "مورد" (resource) يصف جدولاً حقيقياً قائماً في قاعدة البيانات — لا جداول جديدة،
 * بل نفس الجداول والأعمدة والصفوف القائمة. المفاتيح:
 *
 *   table             : اسم الجدول الحقيقي
 *   primary_key       : المفتاح الأساسي للجدول (اسم عمود، أو مصفوفة لمفتاح مركّب — pivot)
 *   writable          : الأعمدة المسموح كتابتها من الخارج (حماية ضمنية)
 *   required          : حقول إلزامية في كل صف
 *   defaults          : قيم افتراضية عند غياب الحقل
 *   enum              : تحقّق من قوائم القيم المسموحة
 *   casts             : تحويل الأنواع (bool / int / string / gender / json)
 *   foreign           : حقل إدخال => [جدول، عمود مرجعي (ref)، عمود الهدف (target)]  (حل المفاتيح بالأكواد؛
 *                       عمود اختياري select لقراءة عمود آخر من جدول المصدر عند اختلافه عن target)
 *   foreign_composite : عمود الهدف => [جدول، target، key[]، unset[]، nullable]  (مفتاح مركّب؛ nullable يقبل IS NULL)
 *   foreign_via_user  : حقل إدخال => [user_ref[], child_table، child_fk، select، target]  (عبر users: academic_number/email →
 *                       عمود select من الجدول الفرعي يُخزَّن في target؛ select اختياري ويؤخذ من target افتراضياً)
 *   parents           : عمود الهدف => [resource, field, optional]  (مرجع متداخل بكائن المفتاح الطبيعي للأب)
 *   linked_user       : مورد يكتب في جدولين: users + الجدول الفرعي (students/lecturers)
 *   match             : أعمدة المفتاح الطبيعي لتحديد السجل الموجود (بعد حل المفاتيح)
 *   conflict          : كشف تزاحم: dimensions[] + scope[] + dates + active
 *   external_id       : اسم حقل المعرّف الخارجي (اختياري) للتسجيل في integration_entity_refs
 *   deactivate        : استراتيجية DELETE (عمود مثل is_active/status، أو soft_delete، أو hard_delete فعلي)
 *
 * لإضافة أي جدول آخر من القاعدة لاحقاً: أضف مقطعاً هنا فقط — دون أي كود إضافي.
 *
 * عدد الموارد المفعّلة: 33 مورداً (15 أساسياً + 18 في توسعة البنية الأكاديمية:
 * بلوكات ومتطلبات، مخرجات تعلم، محتوى المقرر، تكليفات وتقويم، طرق واستراتيجيات، وتسجيل الطلاب).
 */
return [
    // كلمة المرور الافتراضية للحسابات الجديدة (كما في النظام الحالي — تُطلب تغييرها عند أول دخول)
    'default_user_password' => env('DEFAULT_STUDENT_PASSWORD', '12345678'),

    // ---- إعدادات التقوية (I6) ----
    // حدّ الطلبات الافتراضي لكل دقيقة (يمكن تجاوزه لكل عميل عبر عمود rate_limit_per_minute)
    'default_rate_limit'     => (int) env('INTEGRATION_RATE_LIMIT', 600),
    // مدة صلاحية مفتاح Idempotency بالساعات (0 = تعطيل)
    'idempotency_ttl_hours'  => (int) env('INTEGRATION_IDEMPOTENCY_TTL_HOURS', 24),
    // مدة الاحتفاظ بسجلات التكامل بالأيام
    'log_retention_days'     => (int) env('INTEGRATION_LOG_RETENTION_DAYS', 90),
    // الحدّ الأقصى لحمولة الطلب المسجَّلة بالبايت (تُقتطع الزيادة)
    'log_payload_max_bytes'  => (int) env('INTEGRATION_LOG_PAYLOAD_MAX_BYTES', 65535),

    // مصدر عنوان IP للعميل: false = REMOTE_ADDR (غير قابل للتزوير، الافتراضي)،
    // true = الاعتماد على X-Forwarded-For (للعمل خلف reverse proxy موثوق فقط).
    'trust_forwarded_ip'     => (bool) env('INTEGRATION_TRUST_FORWARDED_IP', false),

    'resources' => [

        // [2/62] colleges — بلا is_active، لذا DELETE = حذف ناعم
        'colleges' => [
            'table'       => 'colleges',
            'primary_key' => 'college_id',
            'writable'    => ['college_code', 'college_name', 'college_logo'],
            'required'    => ['college_code', 'college_name'],
            'match'       => ['college_code'],
            'foreign'     => [],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [11/62] departments — بلا is_active، لذا DELETE = حذف ناعم
        'departments' => [
            'table'       => 'departments',
            'primary_key' => 'department_id',
            'writable'    => ['department_code', 'department_name', 'college_code'],
            'required'    => ['department_code', 'department_name', 'college_code'],
            'match'       => ['department_code'],
            'foreign'     => [
                'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
            ],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [20/62] programs — بلا كود خاص (مفتاح مركّب)، وله is_active
        'programs' => [
            'table'       => 'programs',
            'primary_key' => 'program_id',
            'writable'    => ['department_code', 'program_name', 'academic_system', 'block_based', 'total_hours', 'is_active'],
            'required'    => ['department_code', 'program_name'],
            'defaults'    => ['academic_system' => 'semester', 'block_based' => false],
            'enum'        => ['academic_system' => ['semester', 'credit']],
            'casts'       => ['block_based' => 'bool', 'is_active' => 'bool', 'total_hours' => 'int'],
            'match'       => ['department_id', 'program_name', 'academic_system', 'block_based'],
            'foreign'     => [
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'is_active'],
        ],

        // [26/62] levels — الأب: programs (بلا كود ⇒ كائن مفتاح طبيعي)
        'levels' => [
            'table'       => 'levels',
            'primary_key' => 'level_id',
            'writable'    => ['program', 'level_number', 'level_name'],
            'required'    => ['program', 'level_number'],
            'casts'       => ['level_number' => 'int'],
            'parents'     => [
                'program_id' => ['resource' => 'programs', 'field' => 'program'],
            ],
            'match'       => ['program_id', 'level_number'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [31/62] semesters — الأب: levels (بلا كود ⇒ كائن مفتاح طبيعي متداخل)
        'semesters' => [
            'table'       => 'semesters',
            'primary_key' => 'semester_id',
            'writable'    => ['level', 'semester_name', 'academic_year', 'term_number'],
            'required'    => ['level', 'semester_name', 'academic_year', 'term_number'],
            'casts'       => ['term_number' => 'int'],
            'parents'     => [
                'level_id' => ['resource' => 'levels', 'field' => 'level'],
            ],
            'match'       => ['level_id', 'term_number'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [9/62] academic_titles — الأب: colleges (بالكود)
        'academic_titles' => [
            'table'       => 'academic_titles',
            'primary_key' => 'title_id',
            'writable'    => ['college_code', 'title_name', 'title_code', 'hourly_price'],
            'required'    => ['college_code', 'title_name', 'title_code'],
            'defaults'    => ['hourly_price' => 0],
            'foreign'     => [
                'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
            ],
            'match'       => ['college_id', 'title_code'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [10/62] buildings — الأب: colleges (اختياري بالكود)
        'buildings' => [
            'table'       => 'buildings',
            'primary_key' => 'building_id',
            'writable'    => ['building_code', 'building_name', 'floors_count', 'college_code'],
            'required'    => ['building_code', 'building_name'],
            'defaults'    => ['floors_count' => 0],
            'casts'       => ['floors_count' => 'int'],
            'foreign'     => [
                'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
            ],
            'match'       => ['building_code'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [18/62] classrooms — الأب: buildings + colleges
        'classrooms' => [
            'table'       => 'classrooms',
            'primary_key' => 'classroom_id',
            'writable'    => [
                'building_code', 'college_code', 'classroom_name', 'floor', 'capacity',
                'latitude', 'longitude', 'allowed_distance', 'classroom_type',
                'windows_count', 'has_computer', 'display_type', 'notes',
                'location_address', 'remote_id',
            ],
            'required'    => ['building_code', 'classroom_name', 'floor'],
            'defaults'    => [
                'capacity' => 0, 'latitude' => 0, 'longitude' => 0, 'allowed_distance' => 0,
                'classroom_type' => 0, 'windows_count' => 0, 'has_computer' => false, 'display_type' => 'none',
            ],
            'enum'        => ['display_type' => ['none', 'screen', 'projector', 'smart_board']],
            'casts'       => [
                'floor' => 'int', 'capacity' => 'int', 'classroom_type' => 'int',
                'windows_count' => 'int', 'has_computer' => 'bool',
            ],
            'foreign'     => [
                'building_code' => ['table' => 'buildings', 'ref' => 'building_code', 'target' => 'building_id'],
                'college_code'  => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
            ],
            'match'       => ['building_id', 'floor', 'classroom_name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [3/62] days — مستقل (day_name فريد)
        'days' => [
            'table'       => 'days',
            'primary_key' => 'day_id',
            'writable'    => ['day_name'],
            'required'    => ['day_name'],
            'match'       => ['day_name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [12/62] periods — الأب: colleges (بالكود)، المفتاح الطبيعي (college_id, period_name)
        'periods' => [
            'table'       => 'periods',
            'primary_key' => 'period_id',
            'writable'    => ['college_code', 'period_name', 'start_time', 'end_time'],
            'required'    => ['college_code', 'period_name', 'start_time', 'end_time'],
            'foreign'     => [
                'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
            ],
            'match'       => ['college_id', 'period_name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [34/62] students — جدولان: users + students (يُنشأ الحساب تلقائياً)
        'students' => [
            'table'       => 'students',
            'primary_key' => 'student_id',
            'linked_user' => [
                'table'       => 'users',
                'primary_key' => 'user_id',
                'foreign_key' => 'user_id',   // العمود في students الذي يشير إلى users
                'user_type'   => 'student',
                'writable'    => ['full_name', 'email', 'phone', 'academic_number', 'gender', 'college_code'],
                'required'    => ['full_name', 'academic_number', 'email'],
                'defaults'    => ['gender' => 0],
                'casts'       => ['gender' => 'gender'],
                'foreign'     => [
                    'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                ],
            ],
            'writable'    => ['college_code', 'department_code', 'program', 'level', 'semester', 'status'],
            'required'    => ['college_code', 'department_code'],
            'defaults'    => ['status' => 1],
            'casts'       => ['status' => 'bool'],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'parents'     => [
                'program_id'  => ['resource' => 'programs', 'field' => 'program', 'optional' => true],
                'level_id'    => ['resource' => 'levels', 'field' => 'level', 'optional' => true],
                'semester_id' => ['resource' => 'semesters', 'field' => 'semester', 'optional' => true],
            ],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'status'],
        ],

        // [18/62] lecturers — جدولان: users + lecturers (يُنشأ الحساب تلقائياً)
        'lecturers' => [
            'table'       => 'lecturers',
            'primary_key' => 'lecturer_id',
            'linked_user' => [
                'table'       => 'users',
                'primary_key' => 'user_id',
                'foreign_key' => 'user_id',
                'user_type'   => 'lecturer',
                'writable'    => ['full_name', 'email', 'phone', 'academic_number', 'gender', 'college_code'],
                'required'    => ['full_name', 'academic_number', 'email'],
                'defaults'    => ['gender' => 0],
                'casts'       => ['gender' => 'gender'],
                'foreign'     => [
                    'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                ],
            ],
            'writable'    => ['college_code', 'department_code', 'title_code', 'hire_date', 'can_teach_externally', 'status'],
            'required'    => ['college_code', 'department_code', 'hire_date'],
            'defaults'    => ['status' => 1, 'can_teach_externally' => false],
            'casts'       => ['status' => 'bool', 'can_teach_externally' => 'bool'],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'foreign_composite' => [
                'title_id' => [
                    'table'  => 'academic_titles',
                    'target' => 'title_id',
                    'key'    => ['college_id', 'title_code'],
                    'unset'  => ['title_code'],
                ],
            ],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'status'],
        ],

        // [33/62] courses — كود فريد، مراجع: college/department/program/level/semester/block
        'courses' => [
            'table'       => 'courses',
            'primary_key' => 'course_id',
            'writable'    => [
                'course_code', 'course_name', 'course_type', 'college_code', 'department_code',
                'program', 'level', 'semester', 'block_number', 'credit_hours', 'course_parts',
                'weight', 'category', 'teaching_language', 'notes', 'is_approved', 'approval_date',
                'approved_by', 'specification_status', 'is_active',
            ],
            'required'    => ['course_code', 'course_name', 'college_code'],
            'defaults'    => [
                'course_type' => 0, 'credit_hours' => 0, 'weight' => 0,
                'is_approved' => false, 'is_active' => true,
                'category' => 'متطلب تخصص إجباري', 'teaching_language' => 'العربية',
                'specification_status' => 'draft',
            ],
            'enum'        => [
                'category'             => ['متطلب جامعة', 'متطلب كلية', 'متطلب تخصص إجباري', 'متطلب تخصص اختياري'],
                'teaching_language'    => ['العربية', 'الإنجليزية', 'ثنائي اللغة'],
                'specification_status' => ['draft', 'in_progress', 'under_review', 'approved', 'published'],
            ],
            'casts'       => [
                'course_type' => 'int', 'credit_hours' => 'int', 'is_approved' => 'bool',
                'is_active' => 'bool', 'course_parts' => 'json',
            ],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'parents'     => [
                'program_id'  => ['resource' => 'programs', 'field' => 'program', 'optional' => true],
                'level_id'    => ['resource' => 'levels', 'field' => 'level', 'optional' => true],
                'semester_id' => ['resource' => 'semesters', 'field' => 'semester', 'optional' => true],
            ],
            'foreign_composite' => [
                'block_id' => ['table' => 'blocks', 'target' => 'id', 'key' => ['program_id', 'block_number'], 'unset' => ['block_number']],
            ],
            'match'       => ['course_code'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'is_active'],
        ],

        // [34/62] student_groups — مفتاح مركّب (المسار + group_name)، بلا is_active ⇒ حذف ناعم
        'student_groups' => [
            'table'       => 'student_groups',
            'primary_key' => 'group_id',
            'writable'    => ['college_code', 'department_code', 'program', 'level', 'semester', 'block_number', 'group_name', 'max_students'],
            'required'    => ['college_code', 'department_code', 'group_name'],
            'defaults'    => ['max_students' => 30],
            'casts'       => ['max_students' => 'int'],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'parents'     => [
                'program_id'  => ['resource' => 'programs', 'field' => 'program', 'optional' => true],
                'level_id'    => ['resource' => 'levels', 'field' => 'level', 'optional' => true],
                'semester_id' => ['resource' => 'semesters', 'field' => 'semester', 'optional' => true],
            ],
            'foreign_composite' => [
                'block_id' => ['table' => 'blocks', 'target' => 'id', 'key' => ['program_id', 'block_number'], 'unset' => ['block_number']],
            ],
            'match'       => ['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [49/62] timetable — مفتاح طبيعي (المجموعة+اليوم+الحصة+السنة) + كشف تعارض (محاضر/مجموعة/قاعة)
        'timetable' => [
            'table'       => 'timetable',
            'primary_key' => 'timetable_id',
            'writable'    => [
                'course_code', 'lecturer_academic_number',
                'college_code', 'department_code', 'program', 'level', 'semester', 'block_number',
                'group_name', 'building_code', 'floor', 'classroom_name',
                'day_name', 'period_name',
                'lecture_type', 'status', 'start_date', 'end_date', 'academic_year',
                'gender_type', 'lecture_hours', 'allowance_minutes',
            ],
            'required'    => [
                'course_code', 'lecturer_academic_number',
                'college_code', 'department_code',
                'group_name', 'building_code', 'floor', 'classroom_name',
                'day_name', 'period_name',
                'start_date', 'end_date', 'academic_year', 'lecture_hours',
            ],
            'defaults'    => [
                'lecture_type' => 0, 'status' => 1, 'gender_type' => 0, 'allowance_minutes' => 0,
            ],
            'casts'       => [
                'lecture_type' => 'int', 'status' => 'int', 'gender_type' => 'int', 'allowance_minutes' => 'int',
            ],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
                'course_code'     => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
                'day_name'        => ['table' => 'days', 'ref' => 'day_name', 'target' => 'day_id'],
                'building_code'   => ['table' => 'buildings', 'ref' => 'building_code', 'target' => 'building_id'],
            ],
            'foreign_via_user' => [
                'lecturer_academic_number' => [
                    'user_ref'    => ['academic_number', 'email'],
                    'child_table' => 'lecturers',
                    'child_fk'    => 'user_id',
                    'target'      => 'lecturer_id',
                ],
            ],
            'parents'     => [
                'program_id'  => ['resource' => 'programs', 'field' => 'program', 'optional' => true],
                'level_id'    => ['resource' => 'levels', 'field' => 'level', 'optional' => true],
                'semester_id' => ['resource' => 'semesters', 'field' => 'semester', 'optional' => true],
            ],
            'foreign_composite' => [
                'block_id'     => ['table' => 'blocks', 'target' => 'id', 'key' => ['program_id', 'block_number'], 'unset' => ['block_number']],
                'classroom_id' => ['table' => 'classrooms', 'target' => 'classroom_id', 'key' => ['building_id', 'floor', 'classroom_name'], 'unset' => ['floor', 'classroom_name', 'building_id']],
                'period_id'    => ['table' => 'periods', 'target' => 'period_id', 'key' => ['college_id', 'period_name'], 'unset' => ['period_name']],
                'group_id'     => ['table' => 'student_groups', 'target' => 'group_id', 'key' => ['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'], 'unset' => ['group_name'], 'nullable' => true],
            ],
            'conflict'    => [
                'dimensions' => [
                    ['column' => 'lecturer_id', 'label' => 'lecturer'],
                    ['column' => 'group_id', 'label' => 'group'],
                    ['column' => 'classroom_id', 'label' => 'classroom'],
                ],
                'scope'  => ['day_id', 'period_id', 'academic_year'],
                'dates'  => ['start' => 'start_date', 'end' => 'end_date'],
                'active' => ['column' => 'status', 'value' => 1],
            ],
            'match'       => ['group_id', 'day_id', 'period_id', 'academic_year'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'status'],
        ],

        // [15/62] blocks — البنية: برنامج (إلزامي) + مستوى (اختياري) — المفتاح (program_id, block_number)
        'blocks' => [
            'table'       => 'blocks',
            'primary_key' => 'id',
            'writable'    => ['program', 'level', 'block_number', 'block_name', 'type', 'weight', 'credit_hours', 'weeks', 'description'],
            'required'    => ['program', 'block_number', 'block_name'],
            'defaults'    => ['type' => 'compulsory', 'weight' => 0, 'credit_hours' => 0, 'weeks' => 1],
            'enum'        => ['type' => ['compulsory', 'elective']],
            'casts'       => ['block_number' => 'int', 'weeks' => 'int'],
            'parents'     => [
                'program_id' => ['resource' => 'programs', 'field' => 'program'],
                'level_id'   => ['resource' => 'levels',   'field' => 'level', 'optional' => true],
            ],
            'match'       => ['program_id', 'block_number'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [16/62] block_relations — روابط البلوكات (أبوان إلى blocks) — حذف فعلي (جدول روابط)
        'block_relations' => [
            'table'       => 'block_relations',
            'primary_key' => 'id',
            'writable'    => ['block', 'related_block', 'relation_type'],
            'required'    => ['block', 'related_block', 'relation_type'],
            'enum'        => ['relation_type' => ['prerequisite', 'concurrent', 'next']],
            'parents'     => [
                'block_id'         => ['resource' => 'blocks', 'field' => 'block'],
                'related_block_id' => ['resource' => 'blocks', 'field' => 'related_block'],
            ],
            'match'       => ['block_id', 'related_block_id', 'relation_type'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [17/62] course_prerequisites — الكود ⇒ المقرر — حذف فعلي (جدول روابط)
        'course_prerequisites' => [
            'table'       => 'course_prerequisites',
            'primary_key' => 'id',
            'writable'    => ['course_code', 'prerequisite_course_code', 'type'],
            'required'    => ['course_code', 'prerequisite_course_code'],
            'defaults'    => ['type' => 'prerequisite'],
            'enum'        => ['type' => ['prerequisite', 'corequisite']],
            'foreign'     => [
                'course_code'              => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
                'prerequisite_course_code' => ['table' => 'courses', 'ref' => 'course_code', 'select' => 'course_id', 'target' => 'prerequisite_course_id'],
            ],
            'match'       => ['course_id', 'prerequisite_course_id', 'type'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [27/62] program_learning_outcomes — مخرجات التعلم للبرنامج — الأب: programs — فريد (program_id, code)
        'program_learning_outcomes' => [
            'table'       => 'program_learning_outcomes',
            'primary_key' => 'plo_id',
            'writable'    => ['program', 'code', 'domain', 'description', 'weight', 'order', 'is_active'],
            'required'    => ['program', 'code', 'domain', 'description'],
            'defaults'    => ['domain' => 'Knowledge', 'weight' => 0, 'order' => 0, 'is_active' => true],
            'enum'        => ['domain' => ['Knowledge', 'Intellectual', 'Professional', 'General']],
            'casts'       => ['order' => 'int', 'is_active' => 'bool'],
            'parents'     => ['program_id' => ['resource' => 'programs', 'field' => 'program']],
            'match'       => ['program_id', 'code'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [39/62] course_learning_outcomes — مخرجات المقرر — الكود ⇒ المقرر + PLO اختياري (كائن) — فريد (course_id, code)
        'course_learning_outcomes' => [
            'table'       => 'course_learning_outcomes',
            'primary_key' => 'clo_id',
            'writable'    => ['course_code', 'code', 'domain', 'description', 'weight', 'plo', 'plo_weight', 'order', 'is_active'],
            'required'    => ['course_code', 'code', 'domain', 'description'],
            'defaults'    => ['domain' => 'Knowledge', 'weight' => 0, 'order' => 0, 'is_active' => true],
            'enum'        => ['domain' => ['Knowledge', 'Intellectual', 'Professional', 'General']],
            'casts'       => ['order' => 'int', 'is_active' => 'bool'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'parents'     => [
                'plo_id' => ['resource' => 'program_learning_outcomes', 'field' => 'plo', 'optional' => true],
            ],
            'match'       => ['course_id', 'code'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [38/62] course_descriptions — صف واحد لكل مقرر (فريد course_id) — حذف فعلي (بلا deleted_at)
        'course_descriptions' => [
            'table'       => 'course_descriptions',
            'primary_key' => 'id',
            'writable'    => ['course_code', 'description', 'goals', 'word_count', 'goals_count', 'is_completed'],
            'required'    => ['course_code', 'description'],
            'defaults'    => ['word_count' => 0, 'goals_count' => 0, 'is_completed' => false],
            'casts'       => ['word_count' => 'int', 'goals_count' => 'int', 'is_completed' => 'bool', 'goals' => 'json'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'match'       => ['course_id'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [43/62] course_topics — دروس المقرر — فريد (course_id, part, week, unit_name)
        'course_topics' => [
            'table'       => 'course_topics',
            'primary_key' => 'topic_id',
            'writable'    => ['course_code', 'part', 'week', 'unit_name', 'subtopics', 'is_exam', 'exam_type', 'hours', 'clo_ids', 'order', 'notes'],
            'required'    => ['course_code', 'part', 'week', 'unit_name'],
            'defaults'    => ['is_exam' => false, 'hours' => 0, 'order' => 0],
            'enum'        => [
                'part'      => ['نظري', 'عملي', 'تمارين', 'سريري'],
                'exam_type' => ['midterm', 'final'],
            ],
            'casts'       => ['week' => 'int', 'is_exam' => 'bool', 'order' => 'int', 'subtopics' => 'json', 'clo_ids' => 'json'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'match'       => ['course_id', 'part', 'week', 'unit_name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [59/62] topic_questions — بنك الأسئلة — الدرس رابط اختياري (كائن) — المفتاح (course_id, question_text)
        'topic_questions' => [
            'table'       => 'topic_questions',
            'primary_key' => 'question_id',
            'writable'    => ['course_code', 'part', 'topic', 'subtopic', 'question_text', 'question_type',
                              'difficulty_level', 'clo_code', 'options', 'correct_answer', 'order'],
            'required'    => ['course_code', 'part', 'question_text'],
            'defaults'    => ['question_type' => 'MCQ', 'difficulty_level' => 1, 'order' => 0],
            'enum'        => [
                'part'          => ['نظري', 'عملي', 'تمارين', 'سريري'],
                'question_type' => ['MCQ', 'essay'],
            ],
            'casts'       => ['difficulty_level' => 'int', 'order' => 'int', 'options' => 'json'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'parents'     => [
                'topic_id' => ['resource' => 'course_topics', 'field' => 'topic', 'optional' => true],
            ],
            'match'       => ['course_id', 'question_text'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [35/62] course_assignments — التكليفات — الكود ⇒ المقرر — المفتاح (course_id, part, week, title)
        'course_assignments' => [
            'table'       => 'course_assignments',
            'primary_key' => 'assignment_id',
            'writable'    => ['course_code', 'part', 'title', 'description', 'week', 'grade', 'clo_ids', 'assignment_type', 'is_mandatory', 'notes'],
            'required'    => ['course_code', 'part', 'title', 'week'],
            'defaults'    => ['assignment_type' => 'homework', 'grade' => 0, 'is_mandatory' => true],
            'enum'        => [
                'part'            => ['نظري', 'عملي', 'تمارين', 'سريري'],
                'assignment_type' => ['homework', 'project', 'presentation', 'quiz', 'other'],
            ],
            'casts'       => ['week' => 'int', 'is_mandatory' => 'bool', 'clo_ids' => 'json'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'match'       => ['course_id', 'part', 'week', 'title'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [36/62] course_assessments — عناصر التقويم — فصل (إلزامي بكائن) + مجموعة اختيارية + محاضر عبر رقم أكاديمي
        'course_assessments' => [
            'table'       => 'course_assessments',
            'primary_key' => 'assessment_id',
            'writable'    => ['college_code', 'course_code', 'group', 'semester', 'academic_year', 'name',
                              'week', 'max_score', 'weight', 'percentage', 'clo_ids', 'assessment_type',
                              'order', 'grade', 'notes', 'created_academic_number'],
            'required'    => ['college_code', 'course_code', 'semester', 'name', 'max_score'],
            'defaults'    => ['assessment_type' => 'activities', 'weight' => 0, 'percentage' => 0, 'grade' => 0, 'order' => 0],
            'enum'        => ['assessment_type' => ['activities', 'quizzes', 'midterm_exam', 'final_exam', 'project', 'presentation', 'practical_exam', 'other']],
            'casts'       => ['week' => 'int', 'weight' => 'int', 'order' => 'int', 'clo_ids' => 'json'],
            'foreign'     => [
                'college_code' => ['table' => 'colleges', 'ref' => 'college_code', 'target' => 'college_id'],
                'course_code'  => ['table' => 'courses',  'ref' => 'course_code',  'target' => 'course_id'],
            ],
            'foreign_via_user' => [
                'created_academic_number' => [
                    'user_ref'    => ['academic_number', 'email'],
                    'child_table' => 'lecturers',
                    'child_fk'    => 'user_id',
                    'select'      => 'lecturer_id',
                    'target'      => 'created_by',
                ],
            ],
            'parents'     => [
                'semester_id' => ['resource' => 'semesters',      'field' => 'semester'],
                'group_id'    => ['resource' => 'student_groups', 'field' => 'group', 'optional' => true],
            ],
            'match'       => ['course_id', 'semester_id', 'name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [40/62] course_policies — سياسات المقرر — فريد (course_id, policy_number)
        'course_policies' => [
            'table'       => 'course_policies',
            'primary_key' => 'policy_id',
            'writable'    => ['course_code', 'policy_number', 'title', 'content', 'is_fixed', 'order'],
            'required'    => ['course_code', 'policy_number', 'title', 'content'],
            'defaults'    => ['is_fixed' => false, 'order' => 0],
            'casts'       => ['policy_number' => 'int', 'is_fixed' => 'bool', 'order' => 'int'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'match'       => ['course_id', 'policy_number'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [41/62] course_references — المراجع — الكود ⇒ المقرر — المفتاح (course_id, title)
        'course_references' => [
            'table'       => 'course_references',
            'primary_key' => 'reference_id',
            'writable'    => ['course_code', 'type', 'category', 'author', 'year', 'title', 'edition', 'publisher', 'country', 'url', 'order'],
            'required'    => ['course_code', 'type', 'title'],
            'defaults'    => ['order' => 0],
            'enum'        => [
                'type'     => ['main', 'support', 'electronic'],
                'category' => ['website', 'journal', 'other'],
            ],
            'casts'       => ['year' => 'int', 'order' => 'int'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'match'       => ['course_id', 'title'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'soft_delete'],
        ],

        // [1/62] assessment_methods — قائمة برامجية — الأب: programs (إلزامي) — فريد (program_id, name)
        'assessment_methods' => [
            'table'       => 'assessment_methods',
            'primary_key' => 'id',
            'writable'    => ['program', 'name', 'description', 'category', 'order', 'is_active'],
            'required'    => ['program', 'name'],
            'defaults'    => ['category' => 'exam', 'order' => 0, 'is_active' => true],
            'enum'        => ['category' => ['exam', 'assignment', 'project', 'presentation', 'participation', 'portfolio', 'other']],
            'casts'       => ['order' => 'int', 'is_active' => 'bool'],
            'parents'     => ['program_id' => ['resource' => 'programs', 'field' => 'program']],
            'match'       => ['program_id', 'name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'is_active'],
        ],

        // [7/62] teaching_strategies — قائمة برامجية — الأب: programs (إلزامي) — فريد (program_id, name)
        'teaching_strategies' => [
            'table'       => 'teaching_strategies',
            'primary_key' => 'id',
            'writable'    => ['program', 'name', 'description', 'category', 'order', 'is_active'],
            'required'    => ['program', 'name'],
            'defaults'    => ['category' => 'lecture', 'order' => 0, 'is_active' => true],
            'enum'        => ['category' => ['lecture', 'practical', 'discussion', 'collaboration', 'project_based', 'problem_solving', 'simulation', 'other']],
            'casts'       => ['order' => 'int', 'is_active' => 'bool'],
            'parents'     => ['program_id' => ['resource' => 'programs', 'field' => 'program']],
            'match'       => ['program_id', 'name'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'column', 'column' => 'is_active'],
        ],

        // [53/62] outcome_assessment_method — ربط CLO بالطريقة — CLO: (course_id + code) — حذف فعلي (جدول روابط)
        'outcome_assessment_method' => [
            'table'       => 'outcome_assessment_method',
            'primary_key' => 'id',
            'writable'    => ['course_code', 'code', 'method'],
            'required'    => ['course_code', 'code', 'method'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'parents'     => [
                'method_id' => ['resource' => 'assessment_methods', 'field' => 'method'],
            ],
            'foreign_composite' => [
                'clo_id' => ['table' => 'course_learning_outcomes', 'target' => 'clo_id', 'key' => ['course_id', 'code'], 'unset' => ['code', 'course_id']],
            ],
            'match'       => ['clo_id', 'method_id'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [54/62] outcome_teaching_strategy — ربط CLO بالاستراتيجية — CLO: (course_id + code) — حذف فعلي
        'outcome_teaching_strategy' => [
            'table'       => 'outcome_teaching_strategy',
            'primary_key' => 'id',
            'writable'    => ['course_code', 'code', 'strategy'],
            'required'    => ['course_code', 'code', 'strategy'],
            'foreign'     => [
                'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
            ],
            'parents'     => [
                'strategy_id' => ['resource' => 'teaching_strategies', 'field' => 'strategy'],
            ],
            'foreign_composite' => [
                'clo_id' => ['table' => 'course_learning_outcomes', 'target' => 'clo_id', 'key' => ['course_id', 'code'], 'unset' => ['code', 'course_id']],
            ],
            'match'       => ['clo_id', 'strategy_id'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [48/62] student_group_members — ربط الطالب بالمجموعة — مفتاح مركّب (student_id, group_id) — حذف فعلي
        'student_group_members' => [
            'table'       => 'student_group_members',
            'primary_key' => ['student_id', 'group_id'],
            'writable'    => ['academic_number', 'college_code', 'department_code', 'program', 'level', 'semester', 'block_number', 'group_name'],
            'required'    => ['academic_number', 'college_code', 'department_code', 'group_name'],
            'foreign_via_user' => [
                'academic_number' => [
                    'user_ref'    => ['academic_number'],
                    'child_table' => 'students',
                    'child_fk'    => 'user_id',
                    'target'      => 'student_id',
                ],
            ],
            'foreign'     => [
                'college_code'    => ['table' => 'colleges',    'ref' => 'college_code',    'target' => 'college_id'],
                'department_code' => ['table' => 'departments', 'ref' => 'department_code', 'target' => 'department_id'],
            ],
            'parents'     => [
                'program_id'  => ['resource' => 'programs', 'field' => 'program', 'optional' => true],
                'level_id'    => ['resource' => 'levels',   'field' => 'level', 'optional' => true],
                'semester_id' => ['resource' => 'semesters', 'field' => 'semester', 'optional' => true],
            ],
            'foreign_composite' => [
                'group_id' => [
                    'table'    => 'student_groups',
                    'target'   => 'group_id',
                    'key'      => ['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id', 'group_name'],
                    'unset'    => ['group_name', 'college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id'],
                    'nullable' => true,
                ],
            ],
            'match'       => ['student_id', 'group_id'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

        // [58/62] student_grades — درجة الطالب على عنصر تقويم (كائن) — فريد (assessment_id, student_id) — حذف فعلي
        'student_grades' => [
            'table'       => 'student_grades',
            'primary_key' => 'grade_id',
            'writable'    => ['academic_number', 'assessment', 'score', 'notes'],
            'required'    => ['academic_number', 'assessment', 'score'],
            'foreign_via_user' => [
                'academic_number' => [
                    'user_ref'    => ['academic_number', 'email'],
                    'child_table' => 'students',
                    'child_fk'    => 'user_id',
                    'target'      => 'student_id',
                ],
            ],
            'parents'     => [
                'assessment_id' => ['resource' => 'course_assessments', 'field' => 'assessment'],
            ],
            'match'       => ['assessment_id', 'student_id'],
            'external_id' => 'external_id',
            'deactivate'  => ['type' => 'hard_delete'],
        ],

    ],
];
