# دليل واجهات التكامل الخارجي (Data Ingestion) — UniHub

> الإصدار: **v1** · المسار الأساسي: `/api/external/v1` · الاتجاه: **دفع من نظام الجامعة إلى UniHub** (POST/PUT/PATCH/DELETE).
> هذه الواجهات تكتب مباشرةً في **الجداول الفعلية** لنظام UniHub (لا جداول وسيطة).

---

## 1. نظرة عامة

- **الاتجاه:** نظام الجامعة (Oracle) → UniHub. كل الطلبات تُرسَل من الجامعة إلينا (Push).
- **الطريقة:** لا توجد واجهات `GET` للجلب. الإدخال/التحديث عبر `POST` (و`PUT`/`PATCH` مرادفة)، والحذف عبر `DELETE`.
- **الدفعات:** كل طلب يحمل مصفوفة صفوف: `{ "data": [ { ... }, { ... } ] }`.
- **النجاح الجزئي:** يُعالَج كل صف مستقلاً؛ الصفوف الخاطئة تُتخطّى (تُدرَج أسبابها) دون إبطال بقية الدفعة.
- **Upsert:** التحديد بالاعتماد على **مفتاح طبيعي** (كود/اسم) أو `external_id` — فلا تتكرر السجلات عند إعادة الإرسال.

### رابط أساسي (مثال)
```
https://<نطاق-الجامعة>/api/external/v1
```

### الراوتات المتاحة (بعد الراوت العام الرئيسي)
كل مورد أدناه يقبل نفس المسار بالطرق الأربع. الراوت العام الرئيسي `/api/external/v1` هو المُسبق لكل المسارات التالية:

| الراوت (المسار الكامل) | الطرق المدعومة | الجدول المُستهدف |
|---|---|---|
| `/api/external/v1/ping` | `POST` | — (اختبار الاتصال والمصادقة) |
| `/api/external/v1/colleges` | `POST`/`PUT`/`PATCH`/`DELETE` | `colleges` |
| `/api/external/v1/departments` | `POST`/`PUT`/`PATCH`/`DELETE` | `departments` |
| `/api/external/v1/programs` | `POST`/`PUT`/`PATCH`/`DELETE` | `programs` |
| `/api/external/v1/levels` | `POST`/`PUT`/`PATCH`/`DELETE` | `levels` |
| `/api/external/v1/semesters` | `POST`/`PUT`/`PATCH`/`DELETE` | `semesters` |
| `/api/external/v1/academic_titles` | `POST`/`PUT`/`PATCH`/`DELETE` | `academic_titles` |
| `/api/external/v1/buildings` | `POST`/`PUT`/`PATCH`/`DELETE` | `buildings` |
| `/api/external/v1/classrooms` | `POST`/`PUT`/`PATCH`/`DELETE` | `classrooms` |
| `/api/external/v1/days` | `POST`/`PUT`/`PATCH`/`DELETE` | `days` |
| `/api/external/v1/periods` | `POST`/`PUT`/`PATCH`/`DELETE` | `periods` |
| `/api/external/v1/students` | `POST`/`PUT`/`PATCH`/`DELETE` | `users` + `students` |
| `/api/external/v1/lecturers` | `POST`/`PUT`/`PATCH`/`DELETE` | `users` + `lecturers` |
| `/api/external/v1/courses` | `POST`/`PUT`/`PATCH`/`DELETE` | `courses` |
| `/api/external/v1/student_groups` | `POST`/`PUT`/`PATCH`/`DELETE` | `student_groups` |
| `/api/external/v1/timetable` | `POST`/`PUT`/`PATCH`/`DELETE` | `timetable` |
| `/api/external/v1/blocks` | `POST`/`PUT`/`PATCH`/`DELETE` | `blocks` |
| `/api/external/v1/block_relations` | `POST`/`PUT`/`PATCH`/`DELETE` | `block_relations` |
| `/api/external/v1/course_prerequisites` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_prerequisites` |
| `/api/external/v1/program_learning_outcomes` | `POST`/`PUT`/`PATCH`/`DELETE` | `program_learning_outcomes` |
| `/api/external/v1/course_learning_outcomes` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_learning_outcomes` |
| `/api/external/v1/course_descriptions` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_descriptions` |
| `/api/external/v1/course_topics` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_topics` |
| `/api/external/v1/topic_questions` | `POST`/`PUT`/`PATCH`/`DELETE` | `topic_questions` |
| `/api/external/v1/course_assignments` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_assignments` |
| `/api/external/v1/course_assessments` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_assessments` |
| `/api/external/v1/course_policies` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_policies` |
| `/api/external/v1/course_references` | `POST`/`PUT`/`PATCH`/`DELETE` | `course_references` |
| `/api/external/v1/assessment_methods` | `POST`/`PUT`/`PATCH`/`DELETE` | `assessment_methods` |
| `/api/external/v1/teaching_strategies` | `POST`/`PUT`/`PATCH`/`DELETE` | `teaching_strategies` |
| `/api/external/v1/outcome_assessment_method` | `POST`/`PUT`/`PATCH`/`DELETE` | `outcome_assessment_method` |
| `/api/external/v1/outcome_teaching_strategy` | `POST`/`PUT`/`PATCH`/`DELETE` | `outcome_teaching_strategy` |
| `/api/external/v1/student_group_members` | `POST`/`PUT`/`PATCH`/`DELETE` | `student_group_members` |
| `/api/external/v1/student_grades` | `POST`/`PUT`/`PATCH`/`DELETE` | `student_grades` |

> أي مسار آخر يعيد `404 Unknown integration endpoint.` (مع بقاء المصادقة فعّالة).

---

## 2. المصادقة (عاملان مجتمعان)

يجب توفير **كل** العاملين التاليةين في كل طلب، وإلا رُفض الطلب:

| العامل | المكان | الوصف |
|---|---|---|
| Bearer Token | ترويسة `Authorization: Bearer <TOKEN>` | توكن العميل (يُخزَّن مشفَّراً على الخادم) |
| عنوان المصدر | — | IP الطلب ضمن قائمة عناوين العميل (يدعم CIDR مثل `10.0.0.0/24`) |

> **ملاحظة:** تم إلغاء فحص توقيع الجهاز `X-Client-MAC`. الترويسة، إن أُرسلت، تُتجاهل ولا تُتحقّق منها.

### ترويسات موصى بها
```
Authorization: Bearer <TOKEN>
Content-Type: application/json
Accept: application/json
```

### نقطة اختبار الاتصال
`POST /api/external/v1/ping` → يعيد `200` مع اسم العميل عند نجاح المصادقة.

---

## 3. صيغة الطلب والرد

### الطلب
```http
POST /api/external/v1/{resource}
Authorization: Bearer <TOKEN>
Content-Type: application/json

{ "data": [ { ...صف... }, { ...صف... } ] }
```

### رد النجاح (200)
```json
{
  "status": "success",
  "message": "colleges synchronized successfully.",
  "timestamp": "2026-10-17 12:00:00",
  "inserted_records": 2,
  "updated_records": 1,
  "skipped_records": 1,
  "skipped_reasons": ["Row #3: missing required field 'college_code'."]
}
```

### رد الحذف (200)
```json
{
  "status": "success",
  "message": "colleges deactivated successfully.",
  "timestamp": "2026-10-17 12:00:00",
  "deactivated_records": 1,
  "skipped_records": 0,
  "skipped_reasons": []
}
```

### الطرق المدعومة
| العملية | الطريقة | المسار |
|---|---|---|
| إدخال/تحديث (دفعة) | `POST` / `PUT` / `PATCH` | `/api/external/v1/{resource}` |
| حذف/تعطيل (دفعة) | `DELETE` | `/api/external/v1/{resource}` |
| اختبار الاتصال | `POST` | `/api/external/v1/ping` |

---

## 4. المفاتيح الطبيعية وحل المراجع

- **المفتاح الطبيعي** يحدّد السجل الوحيد: إمّا كود (`college_code`, `course_code`, `day_name`…)، أو **مفتاح مركّب** (`(program_id, level_number)`…)، أو **كائن أب متداخل**.
- **المراجع المتداخلة (`parents`)**: يُرسَل الأب ككائن كامل يُطابق مفاتيحه الطبيعية، مثال:
  ```json
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
  "level":   { "program": { "...": "..." }, "level_number": 1 },
  "semester":{ "level": { "...": "..." }, "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1 }
  ```
- **المعرّف الخارجي `external_id`** (اختياري، مدعوم في كل الموارد): إن أرسلته الجامعة، يُستخدم أولاً لتحديد السجل، ويُخزَّن الربط داخلياً. عند إعادة الإرسال بنفس `external_id` يُحدَّث السجل نفسه.

### دلالة الحذف حسب المورد
| الحالة | السلوك |
|---|---|
| الجدول فيه `is_active` (programs, courses, assessment_methods, teaching_strategies) | `is_active = 0` |
| الجدول فيه `status` (students, lecturers, timetable) | `status = 0` |
| الجدول فيه `deleted_at` | **حذف ناعم** — وإعادة الإرسال تستعيد السجل |
| جدول روابط بلا `deleted_at` (block_relations, course_prerequisites, course_descriptions, outcome_*, student_group_members, student_grades) | **حذف فعلي** من الجدول |

> **المفتاح المركّب:** بعض الجداول بلا مفتاح تلقائي، ويُحدَّد صفّها بمفتاح مركّب من عمودين (مثل `student_group_members`: `(student_id, group_id)`). سلوك الإدراج/التحديث موحّد — إعادة الإرسال تُحدِّث الصف نفسه.

---

## 5. إعادة المحاولة الآمنة (Idempotency)

عند إرسال ترويسة اختيارية:
```
Idempotency-Key: <مفتاح فريد لكل عملية>
```
- **نفس المفتاح + نفس الجسم** → يُعاد **نفس الرد** المخزَّن حرفياً، بترويسة `Idempotent-Replay: true`، دون تنفيذ جديد.
- **نفس المفتاح + جسم مختلف** → `409 Conflict`.
- **بلا الترويسة** → تنفيذ طبيعي.
- مدة الصلاحية: **24 ساعة** (قابلة للضبط). الطلب الذي ينتج خطأ خادم `5xx` لا يُخزَّن (لإتاحة إعادة المحاولة).

---

## 6. حدّ المعدّل (Rate Limiting)

- الحدّ الافتراضي: **600 طلب/دقيقة لكل عميل** (قابل للضبط لكل عميل).
- عند التجاوز: `429 Too Many Requests` مع ترويسات:
  - `Retry-After: <ثوانٍ>`
  - `X-RateLimit-Limit` و`X-RateLimit-Remaining`

---

## 7. أكواد الأخطاء

| الكود | المعنى |
|---|---|
| `200` | نجاح (مع ملخّص الأعداد) |
| `400` | بنية طلب غير صحيحة (مثل غياب `data`) أو `Idempotency-Key` أطول من الحد |
| `401` | توكن مفقود أو غير صحيح |
| `403` | IP غير مسموح، أو العميل معطّل |
| `404` | مورد تكامل غير معروف |
| `405` | طريقة غير مدعومة (يُسمح فقط POST/PUT/PATCH/DELETE) |
| `409` | تعارض `Idempotency-Key` (نفس المفتاح بجسم مختلف) |
| `429` | تجاوز حدّ المعدّل |
| `5xx` | خطأ داخلي |

كل الأخطاء بصيغة موحّدة:
```json
{ "status": "error", "message": "...", "timestamp": "..." }
```

---

## 8. مرجع الموارد (33 مورداً)

> `*` = حقل إلزامي. الحقول المقتبسة (مثل `program`) كائنات متداخلة.
> الموارد 8.1–8.15 هي الأساسية، والموارد 8.16–8.33 هي المضافة في توسعة البنية الأكاديمية
> (البلوكات، المتطلبات، المخرجات، المحتوى، التكليفات والتقويم، الطرق والاستراتيجيات، والتسجيل).

### 8.1 `colleges` — الكليات
| الحقل | ملاحظة |
|---|---|
| `college_code` * | المفتاح الطبيعي |
| `college_name` * | |
| `college_logo` | رابط الشعار |

### 8.2 `departments` — الأقسام
| الحقل | ملاحظة |
|---|---|
| `department_code` * | المفتاح الطبيعي |
| `department_name` * | |
| `college_code` * | مرجع لكلية |

### 8.3 `programs` — البرامج
| الحقل | ملاحظة |
|---|---|
| `department_code` * | مرجع لقسم |
| `program_name` * | |
| `academic_system` | `semester` (افتراضي) أو `credit` |
| `block_based` | `true/false` (افتراضي `false`) |
| `total_hours` | عدد الساعات |
| `is_active` | افتراضي `true` |

المفتاح: `(department_id, program_name, academic_system, block_based)`. الحذف: `is_active=0`.

### 8.4 `levels` — المستويات
| الحقل | ملاحظة |
|---|---|
| `program` * | كائن البرنامج |
| `level_number` * | رقم المستوى |
| `level_name` | |

المفتاح: `(program_id, level_number)`.

### 8.5 `semesters` — الفصول
| الحقل | ملاحظة |
|---|---|
| `level` * | كائن المستوى |
| `semester_name` * | |
| `academic_year` * | |
| `term_number` * | رقم الفصل |

المفتاح: `(level_id, term_number)`.

### 8.6 `academic_titles` — الألقاب العلمية
| الحقل | ملاحظة |
|---|---|
| `college_code` * | مرجع لكلية |
| `title_name` * | |
| `title_code` * | |
| `hourly_price` | افتراضي `0` |

المفتاح: `(college_id, title_code)`.

### 8.7 `buildings` — المباني
| الحقل | ملاحظة |
|---|---|
| `building_code` * | المفتاح الطبيعي |
| `building_name` * | |
| `floors_count` | افتراضي `0` |
| `college_code` | مرجع اختياري لكلية |

### 8.8 `classrooms` — القاعات
| الحقل | ملاحظة |
|---|---|
| `building_code` * | مرجع لمبنى |
| `classroom_name` * | |
| `floor` * | رقم الطابق |
| `college_code` | |
| `capacity` / `latitude` / `longitude` / `allowed_distance` | افتراضي `0` |
| `classroom_type` / `windows_count` | أرقام |
| `has_computer` | `true/false` |
| `display_type` | `none`\|`screen`\|`projector`\|`smart_board` |
| `notes` / `location_address` / `remote_id` | |

المفتاح: `(building_id, floor, classroom_name)`.

### 8.9 `days` — الأيام
| الحقل | ملاحظة |
|---|---|
| `day_name` * | المفتاح الطبيعي (مثل `الأحد`) |

### 8.10 `periods` — الحصص
| الحقل | ملاحظة |
|---|---|
| `college_code` * | مرجع لكلية |
| `period_name` * | |
| `start_time` * | `HH:MM` |
| `end_time` * | `HH:MM` |

المفتاح: `(college_id, period_name)`.

### 8.11 `students` — الطلاب (يكتب `users` + `students`)
| الحقل | ملاحظة |
|---|---|
| `full_name` * | في `users` |
| `academic_number` * | الرقم الأكاديمي (المفتاح) |
| `email` * | إلزامي من البيانات (يُستخدم كما هو) |
| `phone` | اختياري (فريد إن وُجد) |
| `gender` | `male`/`female` أو `1`/`0` |
| `college_code` * | |
| `department_code` * | |
| `program` / `level` / `semester` | كائنات اختيارية |
| `status` | افتراضي `1` (الحذف = `0`) |

- تُنشأ **حسابات الدخول** تلقائياً بكلمة مرور افتراضية (`12345678`) مع إلزام تغييرها عند أول دخول.
- المفتاح: `academic_number`.

### 8.12 `lecturers` — أعضاء هيئة التدريس (يكتب `users` + `lecturers`)
| الحقل | ملاحظة |
|---|---|
| `full_name` * | في `users` |
| `academic_number` * | المفتاح |
| `email` * | إلزامي |
| `phone` / `gender` | |
| `college_code` * | |
| `department_code` * | |
| `title_code` | كود اللقب العلمي (اختياري، يُربط بالكلية) |
| `hire_date` * | تاريخ التعيين |
| `can_teach_externally` | `true/false` |
| `status` | افتراضي `1` |

### 8.13 `courses` — المقررات
| الحقل | ملاحظة |
|---|---|
| `course_code` * | المفتاح الطبيعي |
| `course_name` * | |
| `college_code` * | |
| `department_code` | |
| `program` / `level` / `semester` | كائنات اختيارية |
| `block_number` | رقم البلوك (ضمن البرنامج) |
| `credit_hours` | افتراضي `0` |
| `course_parts` | **JSON** (أجزاء المقرر) |
| `weight` | |
| `category` | `متطلب جامعة`\|`متطلب كلية`\|`متطلب تخصص إجباري`\|`متطلب تخصص اختياري` |
| `teaching_language` | `العربية`\|`الإنجليزية`\|`ثنائي اللغة` |
| `notes` / `is_approved` / `approval_date` / `approved_by` | |
| `specification_status` | `draft`\|`in_progress`\|`under_review`\|`approved`\|`published` |
| `is_active` | افتراضي `true` |

الحذف: `is_active=0`.

### 8.14 `student_groups` — المجموعات الطلابية
| الحقل | ملاحظة |
|---|---|
| `college_code` * | |
| `department_code` * | |
| `group_name` * | |
| `program` / `level` / `semester` | كائنات اختيارية |
| `block_number` | اختياري |
| `max_students` | افتراضي `30` |

المفتاح: `(المسار + group_name)`. الحذف: حذف ناعم.

### 8.15 `timetable` — الجدول الدراسي
| الحقل | ملاحظة |
|---|---|
| `course_code` * | مرجع لمقرر |
| `lecturer_academic_number` * | الرقم الأكاديمي للمحاضر |
| `college_code` * / `department_code` * | |
| `program` / `level` / `semester` | كائنات اختيارية |
| `block_number` | اختياري |
| `group_name` * | المجموعة (ضمن المسار) |
| `building_code` * / `floor` * / `classroom_name` * | القاعة |
| `day_name` * / `period_name` * | |
| `start_date` * / `end_date` * | `YYYY-MM-DD` |
| `academic_year` * | |
| `lecture_hours` * | |
| `lecture_type` / `status` / `gender_type` / `allowance_minutes` | افتراضي `0/1/0/0` |

- **المفتاح الطبيعي:** `(المجموعة + اليوم + الحصة + السنة الأكاديمية)`.
- **كشف التعارض:** يُرفض الصف (نجاح جزئي) إذا تزاحم **المحاضر** أو **المجموعة** أو **القاعة** في نفس (اليوم + الحصة + السنة) مع تقاطع الفترات الزمنية.
  مثال: `"Row #2: conflict: classroom is already booked on the same day/period (2026)."`
- **الحذف:** `status = 0`.

### 8.16 `blocks` — بلوكات البرنامج
| الحقل | ملاحظة |
|---|---|
| `program` * | كائن البرنامج |
| `level` | كائن المستوى (اختياري) |
| `block_number` * | رقم البلوك |
| `block_name` * | |
| `type` | `compulsory` (افتراضي) \| `elective` |
| `weight` / `credit_hours` | افتراضي `0` |
| `weeks` | افتراضي `1` |
| `description` | |

المفتاح: `(program_id, block_number)`. الحذف: حذف ناعم.

### 8.17 `block_relations` — روابط البلوكات
| الحقل | ملاحظة |
|---|---|
| `block` * | كائن البلوك (المصدر) |
| `related_block` * | كائن البلوك المرتبط |
| `relation_type` * | `prerequisite` \| `concurrent` \| `next` |

المفتاح: `(block_id, related_block_id, relation_type)`. الحذف: **حذف فعلي**.

### 8.18 `course_prerequisites` — متطلبات المقررات
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `prerequisite_course_code` * | كود المقرر المتطلب |
| `type` | `prerequisite` (افتراضي) \| `corequisite` |

المفتاح: `(course_id, prerequisite_course_id, type)`. الحذف: **حذف فعلي**.

### 8.19 `program_learning_outcomes` — مخرجات تعلم البرنامج (PLO)
| الحقل | ملاحظة |
|---|---|
| `program` * | كائن البرنامج |
| `code` * | كود المخرج (مثل `PLO1`) |
| `domain` * | `Knowledge` \| `Intellectual` \| `Professional` \| `General` |
| `description` * | |
| `weight` / `order` | افتراضي `0` |
| `is_active` | افتراضي `true` |

المفتاح: `(program_id, code)`. الحذف: حذف ناعم.

### 8.20 `course_learning_outcomes` — مخرجات تعلم المقرر (CLO)
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `code` * | كود المخرج (مثل `a1`) |
| `domain` * | كالمخرج البرامجي |
| `description` * | |
| `plo` | كائن PLO الأب (اختياري) |
| `plo_weight` / `weight` / `order` | |
| `is_active` | افتراضي `true` |

المفتاح: `(course_id, code)`. الحذف: حذف ناعم.

### 8.21 `course_descriptions` — وصف المقرر (صف واحد لكل مقرر)
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `description` * | |
| `goals` | **JSON** (الأهداف) |
| `word_count` / `goals_count` | افتراضي `0` |
| `is_completed` | افتراضي `false` |

المفتاح: `course_id` (صف واحد). الحذف: **حذف فعلي**.

### 8.22 `course_topics` — دروس المقرر
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `part` * | `نظري` \| `عملي` \| `تمارين` \| `سريري` |
| `week` * | رقم الأسبوع |
| `unit_name` * | اسم الوحدة |
| `subtopics` | **JSON** (العناصر الفرعية) |
| `is_exam` / `exam_type` | `exam_type`: `midterm`\|`final` (اتركه فارغاً ولا ترسل `null`) |
| `hours` / `order` / `notes` | |
| `clo_ids` | **JSON** (معرّفات مخرجات المقرر) |

المفتاح: `(course_id, part, week, unit_name)`. الحذف: حذف ناعم.

### 8.23 `topic_questions` — بنك الأسئلة
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `part` * | `نظري` \| `عملي` \| `تمارين` \| `سريري` |
| `topic` | كائن الدرس (اختياري) |
| `subtopic` | |
| `question_text` * | نص السؤال |
| `question_type` | `MCQ` (افتراضي) \| `essay` |
| `difficulty_level` | افتراضي `1` |
| `clo_code` | كود مخرج المقرر |
| `options` | **JSON** (الخيارات) |
| `correct_answer` / `order` | |

المفتاح: `(course_id, question_text)`. الحذف: حذف ناعم.

### 8.24 `course_assignments` — التكليفات
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `part` * | `نظري` \| `عملي` \| `تمارين` \| `سريري` |
| `title` * | عنوان التكليف |
| `week` * | رقم الأسبوع |
| `description` | |
| `grade` | افتراضي `0` |
| `clo_ids` | **JSON** |
| `assignment_type` | `homework` (افتراضي) \| `project` \| `presentation` \| `quiz` \| `other` |
| `is_mandatory` | افتراضي `true` |
| `notes` | |

المفتاح: `(course_id, part, week, title)`. الحذف: حذف ناعم.

### 8.25 `course_assessments` — عناصر التقويم
| الحقل | ملاحظة |
|---|---|
| `college_code` * | مرجع لكلية |
| `course_code` * | كود المقرر |
| `semester` * | كائن الفصل |
| `name` * | اسم عنصر التقويم |
| `max_score` * | الدرجة العظمى |
| `group` | كائن المجموعة (اختياري) |
| `academic_year` | |
| `week` / `weight` / `percentage` / `order` / `grade` | |
| `clo_ids` | **JSON** |
| `assessment_type` | `activities` (افتراضي) \| `quizzes` \| `midterm_exam` \| `final_exam` \| `project` \| `presentation` \| `practical_exam` \| `other` |
| `created_academic_number` | الرقم الأكاديمي للمحاضر (يُخزَّن في `created_by`) |
| `notes` | |

المفتاح: `(course_id, semester_id, name)`. الحذف: حذف ناعم.

### 8.26 `course_policies` — سياسات المقرر
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `policy_number` * | رقم السياسة |
| `title` * | |
| `content` * | |
| `is_fixed` | افتراضي `false` |
| `order` | افتراضي `0` |

المفتاح: `(course_id, policy_number)`. الحذف: حذف ناعم.

### 8.27 `course_references` — مراجع المقرر
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `type` * | `main` \| `support` \| `electronic` |
| `title` * | |
| `category` | `website` \| `journal` \| `other` |
| `author` / `year` / `edition` / `publisher` / `country` / `url` | |
| `order` | افتراضي `0` |

المفتاح: `(course_id, title)`. الحذف: حذف ناعم.

### 8.28 `assessment_methods` — طرق التقويم (قائمة برامجية)
| الحقل | ملاحظة |
|---|---|
| `program` * | كائن البرنامج (إلزامي لضمان التفرّد) |
| `name` * | اسم الطريقة |
| `description` | |
| `category` | `exam` (افتراضي) \| `assignment` \| `project` \| `presentation` \| `participation` \| `portfolio` \| `other` |
| `order` | افتراضي `0` |
| `is_active` | افتراضي `true` |

المفتاح: `(program_id, name)`. الحذف: `is_active = 0`.

### 8.29 `teaching_strategies` — استراتيجيات التدريس (قائمة برامجية)
| الحقل | ملاحظة |
|---|---|
| `program` * | كائن البرنامج (إلزامي) |
| `name` * | اسم الاستراتيجية |
| `description` | |
| `category` | `lecture` (افتراضي) \| `practical` \| `discussion` \| `collaboration` \| `project_based` \| `problem_solving` \| `simulation` \| `other` |
| `order` | افتراضي `0` |
| `is_active` | افتراضي `true` |

المفتاح: `(program_id, name)`. الحذف: `is_active = 0`.

### 8.30 `outcome_assessment_method` — ربط مخرج المقرر بطريقة التقويم
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `code` * | كود مخرج المقرر (CLO) |
| `method` * | كائن طريقة التقويم (يجب أن تكون قائمة) |

المفتاح: `(clo_id, method_id)`. الحذف: **حذف فعلي**.

### 8.31 `outcome_teaching_strategy` — ربط مخرج المقرر باستراتيجية التدريس
| الحقل | ملاحظة |
|---|---|
| `course_code` * | كود المقرر |
| `code` * | كود مخرج المقرر (CLO) |
| `strategy` * | كائن استراتيجية التدريس (يجب أن تكون قائمة) |

المفتاح: `(clo_id, strategy_id)`. الحذف: **حذف فعلي**.

### 8.32 `student_group_members` — عضوية الطلاب في المجموعات
| الحقل | ملاحظة |
|---|---|
| `academic_number` * | الرقم الأكاديمي للطالب |
| `college_code` * / `department_code` * | مراجع (ضمن مسار المجموعة) |
| `program` / `level` / `semester` | كائنات اختيارية (مسار المجموعة) |
| `block_number` | اختياري |
| `group_name` * | اسم المجموعة (بنفس مسار `student_groups`) |

- **المفتاح المركّب:** `(student_id, group_id)` — إضافة الطالب لمجموعة أو إزالته منها.
- الحذف: **حذف فعلي**.

### 8.33 `student_grades` — درجات الطلاب على عناصر التقويم
| الحقل | ملاحظة |
|---|---|
| `academic_number` * | الرقم الأكاديمي للطالب |
| `assessment` * | كائن عنصر التقويم (`course_assessments`) |
| `score` * | الدرجة |
| `notes` | |

المفتاح: `(assessment_id, student_id)`. الحذف: **حذف فعلي**.

---

## 9. أمثلة كاملة

### 9.1 كلية
```json
{ "data": [ { "college_code": "fcit", "college_name": "كلية الحاسبات وتقنية المعلومات" } ] }
```

### 9.2 قسم
```json
{ "data": [ { "department_code": "CS-DEP", "department_name": "علوم الحاسب", "college_code": "fcit" } ] }
```

### 9.3 برنامج
```json
{ "data": [ { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false, "total_hours": 132 } ] }
```

### 9.4 مستوى
```json
{ "data": [ {
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
  "level_number": 1, "level_name": "المستوى الأول"
} ] }
```

### 9.5 فصل
```json
{ "data": [ {
  "level": {
    "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
    "level_number": 1
  },
  "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
} ] }
```

### 9.6 طالب (ينشئ حساب دخول)
```json
{ "data": [ {
  "full_name": "محمد أحمد", "academic_number": "2026001", "email": "s2026001@univ.edu",
  "phone": "0500000000", "gender": "male",
  "college_code": "fcit", "department_code": "CS-DEP",
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
  "level": {
    "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
    "level_number": 1
  },
  "semester": {
    "level": {
      "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
      "level_number": 1
    },
    "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
  },
  "status": 1
} ] }
```

### 9.7 مقرر
```json
{ "data": [ {
  "course_code": "CS101", "course_name": "مقدمة في البرمجة", "college_code": "fcit", "department_code": "CS-DEP",
  "credit_hours": 3, "category": "متطلب تخصص إجباري", "teaching_language": "العربية",
  "course_parts": { "theory": 2, "practical": 1 }
} ] }
```

### 9.8 مجموعة طلابية
```json
{ "data": [ {
  "college_code": "fcit", "department_code": "CS-DEP",
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
  "level": {
    "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
    "level_number": 1
  },
  "semester": {
    "level": {
      "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
      "level_number": 1
    },
    "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
  },
  "group_name": "المجموعة أ", "max_students": 30
} ] }
```

### 9.9 محاضرة جدول
```json
{ "data": [ {
  "course_code": "CS101", "lecturer_academic_number": "LEC001",
  "college_code": "fcit", "department_code": "CS-DEP",
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
  "level": {
    "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
    "level_number": 1
  },
  "semester": {
    "level": {
      "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": false },
      "level_number": 1
    },
    "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
  },
  "group_name": "المجموعة أ",
  "building_code": "B1", "floor": 1, "classroom_name": "101",
  "day_name": "الأحد", "period_name": "الحصة الأولى",
  "start_date": "2026-02-01", "end_date": "2026-05-31", "academic_year": "2026",
  "lecture_type": 1, "lecture_hours": 2, "allowance_minutes": 10
} ] }
```

### 9.10 الحذف (تعطيل)
```http
DELETE /api/external/v1/courses
Content-Type: application/json

{ "data": [ { "course_code": "CS101" } ] }
```
> يمكن الحذف بالمفتاح الطبيعي، أو بالمعرّف الخارجي: `{ "data": [ { "external_id": "oracle-123" } ] }`.

### 9.11 بلوك برنامج
```json
{ "data": [ {
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true },
  "level": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "level_number": 1 },
  "block_number": 1, "block_name": "البلوك الأول", "type": "compulsory", "weeks": 16, "credit_hours": 15
} ] }
```

### 9.12 مخرج تعلم مقرر (CLO) مرتبط بـ PLO
```json
{ "data": [ {
  "course_code": "CS101", "code": "a1", "domain": "Knowledge",
  "description": "يشرح المفاهيم الأساسية للبرمجة",
  "plo": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "code": "PLO1" },
  "weight": 25, "order": 1
} ] }
```

### 9.13 عنصر تقويم
```json
{ "data": [ {
  "college_code": "fcit", "course_code": "CS101",
  "semester": {
    "level": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "level_number": 1 },
    "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
  },
  "name": "الاختبار النصفي", "max_score": 20, "assessment_type": "midterm_exam",
  "week": 7, "weight": 20, "created_academic_number": "LEC001"
} ] }
```

### 9.14 إضافة طالب إلى مجموعة
```json
{ "data": [ {
  "academic_number": "2026001", "college_code": "fcit", "department_code": "CS-DEP",
  "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true },
  "level": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "level_number": 1 },
  "semester": {
    "level": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "level_number": 1 },
    "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
  },
  "group_name": "المجموعة أ"
} ] }
```

### 9.15 درجة طالب على عنصر تقويم
```json
{ "data": [ {
  "academic_number": "2026001",
  "assessment": {
    "college_code": "fcit", "course_code": "CS101",
    "semester": {
      "level": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "level_number": 1 },
      "semester_name": "الفصل الأول", "academic_year": "2026", "term_number": 1
    },
    "name": "الاختبار النصفي", "max_score": 20
  },
  "score": 17.5, "notes": "أداء جيد"
} ] }
```

### 9.16 ربط مخرج مقرر بطريقة تقويم
```json
{ "data": [ {
  "course_code": "CS101", "code": "a1",
  "method": { "program": { "department_code": "CS-DEP", "program_name": "fd", "academic_system": "semester", "block_based": true }, "name": "الاختبار النصفي" }
} ] }
```

---

## 10. السجلات والاحتفاظ

- يُسجَّل كل طلب في `integration_logs`: العميل، الطريقة، المسار، IP، حمولة الطلب (مقتطعة)، أحجام الطلب/الرد، الحالة، الملخّص، والمدة.
- **مدة الاحتفاظ:** 90 يوماً (افتراضية)، ويُنظَّف يومياً عبر `php artisan integration:prune`.

---

## 11. أوامر التشغيل (على خادم UniHub)

```bash
# إنشاء عميل تكامل وتوليد توكنه (يُعرض مرة واحدة)
php artisan integration:client "university-oracle" --ips=10.0.0.0/24

# تدوير التوكن
php artisan integration:client "university-oracle" --rotate

# تعطيل/تفعيل
php artisan integration:client "university-oracle" --disable
php artisan integration:client "university-oracle" --enable

# ضبط حدّ المعدّل لهذا العميل (0 = بلا حدّ)
php artisan integration:client "university-oracle" --rate-limit=300

# تقليم السجلات ومفاتيح Idempotency المنتهية
php artisan integration:prune
php artisan integration:prune --days=30 --dry-run
```

---

## 12. متغيّرات البيئة

| المتغيّر | الافتراضي | الوصف |
|---|---|---|
| `INTEGRATION_RATE_LIMIT` | `600` | حدّ الطلبات/دقيقة الافتراضي لكل عميل |
| `INTEGRATION_IDEMPOTENCY_TTL_HOURS` | `24` | صلاحية مفاتيح Idempotency |
| `INTEGRATION_LOG_RETENTION_DAYS` | `90` | أيام الاحتفاظ بالسجلات |
| `INTEGRATION_LOG_PAYLOAD_MAX_BYTES` | `65535` | الحد الأقصى لحمولة الطلب المسجَّلة |
| `DEFAULT_STUDENT_PASSWORD` | `12345678` | كلمة المرور الافتراضية لحسابات الطلاب/المحاضرين |

---

## 13. ملاحظات تشغيلية

1. **الترتيب مهم:** أنشئ البيانات المرجعية (كليات/أقسام/برامج/مستويات/فصول/مبانٍ/قاعات/أيام/حصص/ألقاب) **قبل** الأشخاص والمحتوى والجدول. ثم رتّب الموارد الأكاديمية: `blocks` → `block_relations` → `course_prerequisites` → المخرجات → المحتوى → التكليفات والتقويم → الطرق والاستراتيجيات → روابط المخرجات → التسجيل (`student_group_members` / `student_grades`).
2. **الحقول غير المرسَلة لا تُطمس** — يُحدَّث فقط ما ترسله الجامعة (ما عدا القيم الافتراضية عند الإنشاء).
3. **البيانات الفعلية:** الحقول المرجعية (`college_code` …) تُحوَّل داخلياً إلى معرّفاتها.
4. **المراجع المتداخلة تُطابَق ولا تُنشأ:** كائن الأب (`program`, `semester`, `block`, `method`, `assessment` …) يجب أن يكون **موجوداً مسبقاً** ويُطابَق بمفتاحه الطبيعي، وإلا تُتخطّى الصفوف.
5. **قيم `enum` القابلة للإفراغ (`exam_type`, `category`):** أَهمِل الحقل عند عدم الحاجة، **ولا ترسل `null`** صريحاً (يُرفض الصف).
6. **أمان:** لا تُشارك التوكن؛ يُخزَّن مشفَّراً ولا يظهر بعد التوليد الأول.

---

## 14. كيف تختبر الواجهة

ثلاث طرق متتابعة: **آليّة (PHPUnit) → سريعة حيّة (سكربت) → يدويّة (Postman/.http)**.

### 14.1 الاختبارات الآلية (شغّلها أولاً)

```bash
# من مجلد unihub-API
php vendor\bin\phpunit tests\Feature\Integration
```

- قاعدة الاختبار `unihub20_test` (مضبوطة في `phpunit.xml`) — **منفصلة** عن قاعدة التطبيق `unihub20`، فلا تُمسّ بياناتك الحقيقية.
- النتيجة المرجعية: **102 اختباراً · 557 تأكيداً · 0 فشل**.

| الملف | ما يغطيه |
|---|---|
| `IntegrationAuthTest` | التوكن/IP (وأن `X-Forwarded-For` لا يُزوير به)، 405/404، إلغاء فحص MAC، التسجيل و`last_used_at` |
| `IntegrationReferenceSyncTest` | الموارد المرجعية + النجاح الجزئي + الحذف الناعم |
| `IntegrationPeopleSyncTest` | الطلاب/المحاضرون وحسابات `users` المرتبطة |
| `IntegrationTimetableSyncTest` | المجموعات/الجدول وكشف التعارضات |
| `IntegrationBlocksSyncTest` | `blocks` / `block_relations` / `course_prerequisites` |
| `IntegrationOutcomesSyncTest` | مخرجات البرنامج/المقرر |
| `IntegrationContentSyncTest` | الوصف/الموضوعات/الأسئلة |
| `IntegrationAssessmentsSyncTest` | التكليفات/التقويم/السياسات/المراجع |
| `IntegrationOutcomeLinksSyncTest` | الطرق/الاستراتيجيات وروابط المخرجات |
| `IntegrationEnrollmentSyncTest` | التسجيل والدرجات |
| `IntegrationEngineExtensionsTest` | امتدادات المحرّك: `hard_delete`، المفتاح المركّب، المراجع غير المحلولة |
| `IntegrationIdempotencyTest` | إعادة إرسال آمنة / `409` / مفتاح منتهٍ |
| `IntegrationRateLimitTest` | `429` وترويسات `X-RateLimit-*` |
| `IntegrationPruneCommandTest` | `integration:prune` و`--dry-run` |

### 14.2 تجربة حيّة على خادم يعمل

```powershell
# 1) أنشئ عميلاً وانسخ التوكن (يُعرض مرة واحدة فقط)
php artisan integration:client "local-test" --ips=127.0.0.1

# 2) شغّل سكربت التدخين (الأقسام [1]..[7])
powershell -ExecutionPolicy Bypass -File docs\integration_smoke_test.ps1 `
    -BaseUrl "http://127.0.0.1:8099" -Token "<TOKEN>"
```

> يجب أن يكون عنوان جهازك ضمن `allowed_ips`، وإلا ستحصل على `403 Unauthorized IP address`.

### 14.3 تجربة يدوية (Postman / VS Code / curl)

| الأداة | الملف | الخطوة |
|---|---|---|
| Postman | `docs/integration_postman_collection.json` | استورد المجموعة (٦ مجلدات) وضع `{{token}}` في المتغيّرات |
| VS Code REST Client | `docs/integration.http` | عدّل `@token` ثم **Send Request** فوق كل طلب `###` |
| curl | — | `curl -X POST <BASE>/api/external/v1/ping -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json"` |

```powershell
Invoke-RestMethod -Method Post `
    -Uri "http://127.0.0.1:8099/api/external/v1/ping" `
    -Headers @{ Authorization = "Bearer <TOKEN>"; Accept = "application/json" }
```

### 14.4 تشخيص سريع للأعراض

| الرسالة | السبب | الحل |
|---|---|---|
| `401 … Invalid or missing security token` | لا توكن، أو توكن خاطئ، أو توكن مُدوَّر | استخدم التوكن الحالي؛ إن نُسي فـ `--rotate` |
| `403 … Unauthorized IP address (x.x.x.x)` | عنوانك خارج `allowed_ips` | `integration:client <name> --ips=<عنوانك>` (أو CIDR) |
| `403 … Integration client is disabled` | العميل معطّل | `integration:client <name> --enable` |
| `405 … requests are allowed` | استخدام `GET`/`HEAD` | استخدم POST/PUT/PATCH/DELETE فقط |
| `404 Unknown integration endpoint` | مسار غير مدرج | راجع جدول الراوتات في القسم 1 |
| `429 Too many requests` | تجاوز حدّ العميل | انتظر `Retry-After` أو ارفع `--rate-limit` |
| رد `200` مع `skipped_records > 0` | صفوف مرفوضة (مرجع مفقود أو `enum` بقيمة `null`) | اقرأ `skipped_reasons` وراجع القسمين 4 و13 |

> راجع أيضاً **القسم 7 (أكواد الأخطاء)** و**القسم 5 (Idempotency)** قبل إعادة إرسال الدفعة نفسها.
