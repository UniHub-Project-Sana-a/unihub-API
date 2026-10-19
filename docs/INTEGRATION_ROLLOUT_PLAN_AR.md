# خطة توسعة التكامل الخارجي — الجداول الـ18 المتبقية

> **الحالة الراهنة:** 15 مورداً مفعّلاً (من أصل 76 جدولاً) — بنية تحتية + أشخاص + جدول مواعيد.
> **الهدف:** تفعيل باقي الجداول المرشحة (18 جدولاً) بالكامل عبر `config/integration.php`
> دون كتابة راوتات أو وحدات تحكم جديدة — بنفس محرّك الابتلاع العام `IntegrationSyncService`.

> ✅ **حالة التنفيذ: مُكتملة.** نُفّذت المراحل 0–6 (18 مورداً جديداً)، والمجموع الحالي **33 مورداً**.
> نتيجة الاختبارات: `php vendor\bin\phpunit tests\Feature\Integration` ⇒ **102 اختباراً، 0 فشل**.
> الموارد الجديدة مُفعّلة عبر الكونفيج فقط، والوثائق (MD/HTML/Postman/http/smoke) متزامنة.

---

## 1. فجوات المحرّك المكتشفة (يجب سدّها أولاً — المرحلة 0)

بعد تحليل `IntegrationSyncService` مقابل مخطط الـ18 جدولاً، تبيّن أن المحرّك الحالي يدعم
`deactivate` من نوعَين فقط (`column` / `soft_delete`)، ويفرض `insertGetId` (مفتاح أحادي
تلقائي الترقيم). ثمة 6 جداول تخالف ذلك:

| الجدول | المشكلة |
|---|---|
| `course_prerequisites` | لا `deleted_at` ولا عمود حالة ⇒ الحذف يجب أن يكون فعلياً (جدول روابط) |
| `course_descriptions` | لا `deleted_at` ولا حالة |
| `outcome_assessment_method` | جدول روابط (pivot) — الحذف = فصل الربط |
| `outcome_teaching_strategy` | جدول روابط (pivot) |
| `student_group_members` | **مفتاح مركّب** `(student_id, group_id)` بلا ترقيم تلقائي — يكسر `insertGetId` وتحديث `where(pk)` (خطر حرق بقية صفوف الطالب!) |
| `student_grades` | لا `deleted_at` ولا حالة — الحذف = إلغاء درجة فعلياً |

### التحسينان المطلوبان في `app/Services/Integration/IntegrationSyncService.php`

**أ) نوع جديد `hard_delete` في `deactivate`:**

```php
private function applyDeactivate(string $table, array $where, array $deactivate): void
{
    $type = $deactivate['type'] ?? null;

    if ($type === 'hard_delete') {
        DB::table($table)->where($where)->delete();
        return;
    }

    if ($type === 'column') {
        DB::table($table)->where($where)->update([
            $deactivate['column'] => $deactivate['value'] ?? 0,
            'updated_at' => now(),
        ]);
        return;
    }

    DB::table($table)->where($where)->update([
        'deleted_at' => now(), 'updated_at' => now(),
    ]);
}
```

(يُستدعى بـ `[$pk => $id]` في المسار القديم، أو بـ `$match` في المسار المركّب — تغيير
متوافق للخلف، لا يمسّ الموارد الـ15 الحالية.)

**ب) دعم المفتاح المركّب في `upsertSingleRow` و`deactivateSingleRow`:**

- `primary_key` يقبل الآن مصفوفة: `'primary_key' => ['student_id', 'group_id']`.
- عند المفتاح المركّب:
  - البحث عن السجل موجود بالفعل عبر `where($match)` (لا تغيير).
  - التحديث: `where($match)->update($values)` بدل `where($pk, $id)`.
  - الإدراج: `insert($values)` عادي (بلا `insertGetId`)، و`internal_id = 0` في سجل الربط
    (التعريف الحقيقي يبقى في `internal_key` = JSON للمفتاح الطبيعي).
  - التعطيل: `where($match)->delete()` — وفي مسار `external_id` يُعاد بناء `where` من
    `internal_key` المخزّن في `integration_entity_refs`.

> التغييران اختياريان وآمنان: لا يمسّان أي مورد حالي (كلها مفاتيح أحادية + `soft_delete`/`column`).

---

## 2. مراحل التنفيذ (بترتيب التبعيات)

| المرحلة | الموارد الجديدة | سبب الترتيب |
|---|---|---|
| **0** | تحسينات المحرّك (hard_delete + مفتاح مركّب) | شرط لـ6 جداول لاحقة |
| **1** | `blocks`, `block_relations`, `course_prerequisites` | `blocks` يُشير إليها `courses`/`student_groups`/`timetable` عبر `foreign_composite` حالياً من قاعدة يدوية |
| **2** | `program_learning_outcomes`, `course_learning_outcomes` | CLO يشير اختيارياً إلى PLO |
| **3** | `course_descriptions`, `course_topics`, `topic_questions` | topics قبل questions (سؤال يرتبط بدرس) |
| **4** | `course_assignments`, `course_assessments`, `course_references`, `course_policies` | assessments تسبق `student_grades` |
| **5** | `teaching_strategies`, `assessment_methods`, `outcome_assessment_method`, `outcome_teaching_strategy` | الروابط تحتاج CLO + قوائم القياس والتدريس |
| **6** | `student_group_members`, `student_grades` | تحتاجان `students` + `student_groups` (موجودان) و`course_assessments` (المرحلة 4) |

في كل مرحلة: كونفيج + اختبارات خاصة + تحديث الوثائق (MD/HTML/Postman/http/Smoke). ثم تشغيل
`php vendor\bin\phpunit tests\Feature\Integration` والتأكد من الصفر فشل.

---

## 3. مواصفات الموارد الـ18 (أقسام كونفيج جاهزة للصق)

> ملاحظات عامة موحّدة:
> - حقل `external_id` في الحمولة يُسجَّل في `integration_entity_refs` ولا يحتاج عموداً في الجدول.
> - قوائم `enum` ذات القيم العربية تُنقل كما هي (كما في `courses.category` الحالي).
> - حقول الـ enum القابلة للإفراغ (مثل `exam_type`): تُرسل **من غير الحقل** عند عدم القيمته،
>   لأن المحرّك يرفض `null` صريحاً في `enum`.
> - المراجع المتداخلة `parents` تقبل كائناً بالمفتاح الطبيعي للأب (بنفس أسلوب `timetable`).

### المرحلة 1

**1) `blocks`** — البنية: برنامج + رقم بلوك. `match` منطقي غير مفهرس فريداً في القاعدة
(فهرس فقط) — التفرد مضمون على مستوى المحرّك (`first()` من المفتاح الطبيعي).

```php
// [بنية] blocks — الأب: programs (+ levels اختياري)
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
```

**2) `block_relations`** — روابط بين البلوكات (أبوان إلى مورد `blocks` نفسه).

```php
// [بنية] block_relations — الأب: blocks ×2
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
    'deactivate'  => ['type' => 'soft_delete'],
],
```

**3) `course_prerequisites`** — متطلبات المقررات (حل مباشر بالكواد).

```php
// [بنية] course_prerequisites — الكود ⇒ المقرر — حذف فعلي (جدول روابط)
'course_prerequisites' => [
    'table'       => 'course_prerequisites',
    'primary_key' => 'id',
    'writable'    => ['course_code', 'prerequisite_course_code', 'type'],
    'required'    => ['course_code', 'prerequisite_course_code'],
    'defaults'    => ['type' => 'prerequisite'],
    'enum'        => ['type' => ['prerequisite', 'corequisite']],
    'foreign'     => [
        'course_code'              => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
        'prerequisite_course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'prerequisite_course_id'],
    ],
    'match'       => ['course_id', 'prerequisite_course_id', 'type'],
    'external_id' => 'external_id',
    'deactivate'  => ['type' => 'hard_delete'],
],
```

### المرحلة 2

**4) `program_learning_outcomes`** — مخرجات التعلم ل프로그램.

```php
// [مخرجات] program_learning_outcomes — الأب: programs — فريد (program_id, code)
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
```

**5) `course_learning_outcomes`** — مخرجات المقرر، مع ربط اختياري بـ PLO.

```php
// [مخرجات] course_learning_outcomes — الكود ⇒ المقرر + PLO اختياري (كائن) — فريد (course_id, code)
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
```

### المرحلة 3

**6) `course_descriptions`** — صف واحد لكل مورد (فريد `course_id`).

```php
// [مواصفة] course_descriptions — صف لكل مقرر — حذف فعلي
'course_descriptions' => [
    'table'       => 'course_descriptions',
    'primary_key' => 'id',
    'writable'    => ['course_code', 'description', 'goals', 'word_count', 'goals_count', 'is_completed'],
    'required'    => ['course_code', 'description'],
    'defaults'    => ['word_count' => 0, 'goals_count' => 0, 'is_completed' => false],
    'casts'       => ['word_count' => 'int', 'goals_count' => 'int', 'is_completed' => 'bool'],
    'foreign'     => [
        'course_code' => ['table' => 'courses', 'ref' => 'course_code', 'target' => 'course_id'],
    ],
    'match'       => ['course_id'],
    'external_id' => 'external_id',
    'deactivate'  => ['type' => 'hard_delete'],
],
```

**7) `course_topics`** — دروس المقرر (فريد `course_id, part, week, unit_name`).

```php
// [مواصفة] course_topics — الكود ⇒ المقرر — فريد (course_id, part, week, unit_name)
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
```

**8) `topic_questions`** — بنك الأسئلة (المفتاح الطبيعي: المقرر + نص السؤال).

```php
// [مواصفة] topic_questions — الدرس رابط اختياري (كائن) — المفتاح: (course_id, question_text)
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
```

### المرحلة 4

**9) `course_assignments`** — التكليفات (مفتاح موسّع: المقرر+القسم+الأسبوع+العنوان).

```php
// [تقويم] course_assignments — الكود ⇒ المقرر
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
```

**10) `course_assessments`** — عناصر التقويم (الفرض/الاختبارات) — إلزامي: الكلية والمقرر والفصل والاسم وأقصى درجة. المجموعة اختيارية (كائن)، والمحاضر إنشائي عبر رقم أكاديمي اختياري.

```php
// [تقويم] course_assessments — الكود ⇒ الكلية/المقرر + فصل (كائن) + مجموعة اختيارية (كائن) + محاضر اختياري
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
```

**11) `course_references`** — المراجع.

```php
// [مواصفة] course_references — الكود ⇒ المقرر — المفتاح: (course_id, title)
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
```

**12) `course_policies`** — سياسات المقرر (فريد `course_id, policy_number`).

```php
// [مواصفة] course_policies — الكود ⇒ المقرر — فريد (course_id, policy_number)
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
```

### المرحلة 5

**13) `teaching_strategies`** و**14) `assessment_methods`** — قوائم برامجية؛ الإلزام بوجود
البرنامج يضمن تفرّد `match` `(program_id, name)` (الفهرس الفريد في القاعدة لا يمنع التكرار
مع `program_id = NULL`، لذا نجعل البرنامج إلزامياً عبر الواجهة).

```php
// [قوائم] teaching_strategies — الأب: programs — فريد (program_id, name)
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

// [قوائم] assessment_methods — الأب: programs — فريد (program_id, name)
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
```

**15) `outcome_assessment_method`** و**16) `outcome_teaching_strategy`** — روابط CLO بالقوائم:
المخرج يُحل بمركّب `(course_id, code)` بعد حل كود المقرر، والطريقة/الاستراتيجية عبر كائن
مفتاحها الطبيعي. الحذف فصل ربط فعلي.

```php
// [روابط] outcome_assessment_method — CLO: (course_code + code) + method (كائن) — حذف فعلي
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

// [روابط] outcome_teaching_strategy — CLO + strategy (كائن) — حذف فعلي
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
```

### المرحلة 6

**17) `student_group_members`** — ربط الطلاب بالمجموعات (مفتاح مركّب — يستفيد من توسعة
المرحلة 0). الطالب عبر الرقم الأكاديمي والمجموعة بنفس مركّب `timetable` الحالي.

```php
// [طلاب] student_group_members — مفتاح مركّب (student_id, group_id) — حذف فعلي (فصل ربط)
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
```

**18) `student_grades`** — درجات الطلاب على عناصر التقويم (العنصر عبر كائن مفتاحه الطبيعي).

```php
// [طلاب] student_grades — الطالب بالرقم الأكاديمي + العنصر (كائن) — فريد (assessment_id, student_id) — حذف فعلي
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
```

---

## 4. خطة الاختبارات

ملف اختبار لكل مرحلة في `tests/Feature/Integration/` وفق نمط `IntegrationTestCase`
(يوفّر `integrationHeaders()` و`seedBaseHierarchy()` وما شابه):

| المرحلة | ملف الاختبار | التغطية الدنيا |
|---|---|---|
| 0 | `IntegrationEngineExtensionsTest.php` | hard_delete يحذف فعلياً؛ مورد مركّب يُدرج دون `id` ويحدّث صفوفه وحده ويحذفه دون المساس بصفوف الطالب الأخرى |
| 1 | `IntegrationBlocksSyncTest.php` | إدراج/تحديث (idempotent) blocks؛ إنشاء block_relations بمرجعيتين؛ حذف فعلي للشرط المتقدم؛ رفض قيمة enum خاطئة |
| 2 | `IntegrationOutcomesSyncTest.php` | PLO لكل برنامج؛ CLO لكل مقرر مع/بلا `plo`؛ تفرّد `(program_id, code)` و`(course_id, code)`؛ إلغاء نشاط PLO ينعكس |
| 3 | `IntegrationContentSyncTest.php` | description صف واحد يتحدّث؛ topics بالفهرس المركّب + subtopics JSON؛ questions مع `topic` اختياري + خيار بدونه؛ DELETE ناعم يُخفي الصف |
| 4 | `IntegrationAssessmentsSyncTest.php` | assignments؛ assessments بفصل كائن + مجموعة اختيارية + محاضر رقم أكاديمي؛ references؛ policies؛ رفض عنصر بلا `max_score` |
| 5 | `IntegrationOutcomeLinksSyncTest.php` | قوائم (تفعيل/تعطيل `is_active`)؛ رابط CLO×method وCLO×strategy؛ الحذف الفعلي يفصل الربط فقط |
| 6 | `IntegrationEnrollmentSyncTest.php` | إضافة طالبين لمجموعة واحدة (مركّب)؛ إزالة طالب واحد لا تمسّ الآخر؛ إرسال درجة/تعديلها/حذفها؛ رفض طالب غير موجود |

قواعد عامة لكل اختبار:
- إرسال الحمولة مرتين ⇒ `updated` في الثانية (idempotency على مستوى المورد).
- استرجاع سجل محذوف ناعماً عند إعادة الإرسال ⇒ `updated` مع `deleted_at = null`.
- مرجع غير محلول (`course_code` غير معروف) ⇒ صف `skipped` برسالة، لا فشل كامل.

**تشغيل التشغيل:** `php vendor\bin\phpunit tests\Feature\Integration` (MySQL يعمل على 3306).
المعيار: 0 فشل مع بقاء باقي ملفات الاختبار خضراء.

---

## 5. خطة التوثيق (بعد كل مرحلة أو دفعة أخيرة)

1. `docs/INTEGRATION_API_GUIDE_AR.md` — إضافة قسم لكل مورد جديد (الحمولة المطلوبة + مثال).
2. `docs/INTEGRATION_API_GUIDE_AR.html` — تحديث جدول «الراوتات المتاحة» من 16 إلى 34 صفاً
   (ping + الموارد 15 + 18 الجديدة = 34 مورداً) وإضافة أقسام الحمولات.
3. `docs/integration_postman_collection.json` — إضافة طلبات لكل مورد (بدون توكنات حقيقية).
4. `docs/integration.http` — أمثلة حية لكل مورد.
5. `docs/integration_smoke_test.ps1` — تمريرة التدخين تشمل الموارد الجديدة.
6. تحديث تعليق `config/integration.php` الترويسة (عدد الموارد).

---

## 6. معايير القبول

- [x] المحرّك: `hard_delete` + مفتاح مركّب، مع بقاء الاختبارات الحالية خضراء.
- [x] **18 مورداً** مفعّلاً جديداً (15 + 18 = **33 مورداً**) عبر الكونفيج فقط — بلا راوتات أو تحكمات جديدة.
- [x] كل مورد جديد له اختباراته (إدراج/تحديث/حذف/مرجع غير محلول).
- [x] الوثائق الخمسة متزامنة، وبدون أي توكنات أو مفاتيح حقيقية داخلها.

## 7. المخاطر والقيود المعروفة

| الخطر | المعالجة |
|---|---|
| `blocks` لا فهرس فريد على `(program_id, block_number)` | التفرد مضمون على مستوى المحرّك؛ يُستبعد migration فريد لاحقاً بعد تنظيف البيانات إن رغبنا |
| `program_learning_outcomes` له فريد `(program_id, order)` إضافي | تعارض الترتيب يظهر كصف `skipped` (نجاح جزئي صف-بصف) — يوثَّق في الدليل |
| إرسال `null` صريحاً لحقل enum قابل للإفراغ (مثل `exam_type`) | المحرّك يرفضه؛ التوثيق يوجّه بإهمال الحقل بدل إرسال `null` (سلوك المحرّك الحالي) |
| `topic_questions` مطابقة نص السؤال (text) | مقبول للاستخدام الفعلي؛ `external_id` يوفّر التحديد الدقيق عند الحاجة |
| `student_group_members` يتطلب معرفة مجموعتكاملة كما في `timetable` | متسق مع السلوك الحالي؛ مثال كامل في الدليل |
