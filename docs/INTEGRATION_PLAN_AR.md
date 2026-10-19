# خطة التكامل — UniHub API ↔ نظام الجامعة (Data Ingestion / Integration)

> وثيقة تخطيط. كل مرحلة تُنفّذ لوحدها: **متطلبات → تصميم → تنفيذ → اختبار → تسليم**.
> الحالة: ⬜ لم تبدأ · 🟨 جارية · ✅ مكتملة
>
> ⚠️ **تحديث لاحق (له الأولوية على ما دونه):** أُلغي **فحص `X-Client-MAC` نهائياً** في واجهات التكامل، فأصبحت الحماية **عاملين فقط: Bearer Token + IP المصدر** (الترويسة إن أُرسلت تُسجَّل ولا تُفحص). كما حُذف خيار `--macs` من أمر `integration:client`. ما يلي هو **سجل تاريخي** كما نُفِّذ آنذاك؛ والمرجع الحالي المعتمد هو `INTEGRATION_API_GUIDE_AR.md`.

---

## 0. الخلاصة التنفيذية

نظام الجامعة (Oracle) يريد **دفع (Push)** بيانات إلى نظامنا: إنشاء/تحديث/حذف لكيانات هيكلية وأكاديمية
(كليات، أقسام، برامج، طلاب، هيئة تدريس، مقررات، جداول...). المطلوب بناء **واجهة استقبال موحّدة**
داخل مشروع `unihub-API` بنفس **مستوى الحماية** الموجود في نموذج الدكتور (`C:\xampp\htdocs\unihub\apis`).
حالياً **لا يوجد أي مسار تكامل** في المشروع لا GET ولا POST.

---

## 1. المتطلبات كما وصلت

### 1.1 استفسار الجامعة (٣ نقاط)
1. **نوع الواجهات:** المستند المتوفر لديهم فيه **GET فقط**؛ يحتاجون **POST/PUT/PATCH** للدفع. الكيانات: الطلاب، هيئة التدريس، المقررات، الجداول، وغيرها.
2. **المصادقة:** استدعاء `GET /api/external/colleges` يحوّلهم لصفحة تسجيل دخول؛ يريدون معرفة الآلية (Bearer / API Key / Basic / OAuth2).
3. **Base URL:** لا يعرفون الرابط الأساسي المعتمد.

### 1.2 النموذج المرجعي (نموذج الدكتور في `apis/`)
- **Base:** `https://<domain>/api/<endpoint>.php` — POST فقط، JSON (`application/json; charset=utf-8`).
- **العمليات:** Upsert = `INSERT ... ON DUPLICATE KEY UPDATE`.
- **الأمان (٣ عوامل إلزامية معاً):**
  | العامل | الآلية |
  |---|---|
  | IP Whitelist | `REMOTE_ADDR` مقابل قائمة مسموحة (ثوابت + جدول `ip_restrictions`) |
  | Hardware Signature | هيدر `X-Client-MAC` مقابل قائمة MACs |
  | Bearer Token | هيدر `Authorization: Bearer <TOKEN>` (مقارنة `hash_equals`) |
- **الرد الموحّد:** `{status, message, timestamp, inserted_records, updated_records, skipped_reasons}`.
- **الكيانات الموثّقة (defination.docx):** `/sync_colleges.php`, `/sync_departments.php`, `/sync_programs.php` — مع حل المفاتيح الأجنبية بالأكواد (`college_code` → `college_id`).

### 1.3 الفجوة الحالية
- لا يوجد أي مسار/كونترولر/ميدل وير للتكامل في `unihub-API`.
- ملف `university_integration_apis.pdf` غير موجود في المشروع (يجب توفيره).
- الجداول لا تحتوي أعمدة **معرّف خارجي** موحّد؛ بعض الكيانات بلا كود طبيعي (Programs, Timetable, Students).

---

## 2. التصميم المقترح

### 2.0 قرارات معتمدة
1. تخزين مصادقة العملاء في جدول `integration_clients` (توكن hash + IPs؛ وعمود `allowed_macs` أُلغي فحصه لاحقاً).
2. `DELETE` = تعطيل السجل (`is_active=0`).
3. الدفعات بنمط **نجاح جزئي صف-بصف** (لا إبطال للدفعة كاملة).
4. **الدفع فقط** (POST/PUT/PATCH/DELETE) — لا نبني واجهات GET حالياً.

### 2.1 المسار والتسمية
مجموعة مسارات مستقلة تحت `/api/external/v1/...` (نفس بادئة PDF لتفادي الالتباس):
| الطريقة | المسار | الوظيفة |
|---|---|---|
| POST | `/api/external/v1/{resource}` | إدخال/تحديث مجمّع (Upsert batch) `{ "data": [...] }` |
| PUT | `/api/external/v1/{resource}/{externalKey}` | تحديث سجل كامل |
| PATCH | `/api/external/v1/{resource}/{externalKey}` | تحديث جزئي |
| DELETE | `/api/external/v1/{resource}/{externalKey}` | تعطيل السجل (`is_active=0`) |
| ~~GET~~ | — | لا ننفّذ واجهات سحب حالياً (الدفع فقط) |

`resource` بالجمع والإنجليزي: `colleges, departments, programs, levels, semesters, academic-titles, buildings, classrooms, days, periods, students, lecturers, courses, student-groups, timetable`.

### 2.2 الأمان (مطابق لنموذج الدكتور + أفضل)
عوامل إلزامية مجتمعة **كما صُمِّمت آنذاك**:
1. **IP Whitelist** (whitelist من جدول `ip_restrictions` الموسّع أو جدول عملاء التكامل).
2. ~~**`X-Client-MAC`** مقابل قائمة MACs مسجّلة.~~ ← **أُلغي نهائياً لاحقاً** (MAC لم يعد مطلوباً ولا مفحوصاً).
3. **Bearer Token** مُخزّن كـ **hash** (وليس ثابت في الكود).

> 🔹 **الحالة النهائية المعتمدة:** عاملان فقط — **Bearer Token + IP المصدر** (المرجع: `INTEGRATION_API_GUIDE_AR.md` §2).

**الاقتراح (أفضل من النموذج):** جدول `integration_clients`:
`id, name, token_hash, allowed_ips(JSON), allowed_macs(JSON) — خامل، is_active, last_used_at, timestamps`
+ ميدل وير `AuthenticateIntegrationClient` يتحقق من العوامل (كان الثلاثة، وأصبح **العاملان**: Bearer + IP بعد إلغاء MAC)، يضيف `rate limit`، ويسجّل الطلب.
> ✅ **قرار معتمد:** نستخدم جدول `integration_clients`.
> ملاحظة أمنية: نموذج الدكتور فيه توكن ثابت داخل الملفات (خطر)، وMAC في الهيدر قابل للتزوير — لذا التوكن hash + IP إلزامي.

### 2.3 نموذج البيانات والمفاتيح
- **Upsert بالمفتاح الطبيعي** (الكود) حيث يوجد:
  - `colleges.college_code` (فريد)، `departments.department_code` (فريد)، `courses.course_code` (فريد)
  - `classrooms`: (building, floor, name) أو `remote_id`
  - `academic_titles`: (college_id, title_code)
  - `levels`: (program_id, level_number) · `semesters`: (level_id, term_number)
  - `programs`: (department_id, program_name, academic_system, block_based) — **بلا كود**
- **الكيانات بلا كود طبيعي** (Programs/Timetable/Students): الحل الموصى به =
  جدول تعيين `integration_entity_refs` (source, entity, external_id, internal_id)
  بحيث يرسل النظام الخارجي **معرّفه** ونتتبعه لضمان تحديث/حذف دقيقين.
- **حل المفاتيح الأجنبية بالأكواد** (مثل النموذج): `college_code → college_id`, `department_code → department_id`, `course_code → course_id`, `group_name/path → group_id`... إلخ.

### 2.4 عقد الاستجابة
مطابق للنموذج: `{status, message, timestamp, inserted_records, updated_records, skipped_reasons[], errors[]}`.
للعنصر الواحد: نفس الشكل + `data`.

### 2.5 دلالات الإدخال/التحديث/الحذف
- **Insert/Update:** Upsert بمفتاح طبيعي/خارجي.
- **Delete:** ✅ **قرار:** تعطيل السجل (`is_active=0`) وليس حذفاً فعلياً.
- **معاملة (Transaction)** لكل دفعة، ✅ **قرار:** **نجاح جزئي صف-بصف** — تُجمَع أسباب رفض الصفوف الخاطئة في `skipped_reasons` دون إبطال البقية.

### 2.6 السجلات والتتبع
جدول `integration_logs`: client_id, endpoint, method, ip, mac, payload, http_status, summary(JSON), duration_ms, created_at.
(ضروري للتشخيص + إثبات ما وصل + إعادة المحاولة الآمنة Idempotency).

---

## 3. جدول الكيانات والمفاتيح (مسودة)

| الكيان | الجدول | المفتاح الطبيعي/الخارجي | حل المرتبطات |
|---|---|---|---|
| Colleges | colleges | college_code | — |
| Departments | departments | department_code | college_code |
| Programs | programs | *(لا كود)* → مفتاح مركب أو external_id | department_code |
| Levels | levels | (program, level_number) | program |
| Semesters | semesters | (level, term_number) | level |
| Academic Titles | academic_titles | (college_id, title_code) | college_code |
| Buildings | buildings | building_code | college_code |
| Classrooms | classrooms | (building, floor, name) / remote_id | building_code |
| Days / Periods | days / periods | day_name / (college, period) | college_code |
| Students | users+students | academic_number (+email مُولّد) | college/department/program/level/semester |
| Academic Staff | users+lecturers | academic_number (+email مُولّد) | college/department/title_code |
| Courses | courses | course_code | college/department/program/level/semester/block |
| Student Groups | student_groups | (المسار + group_name) | college/department/program/level/semester/block |
| Timetable | timetable | (المجموعة + اليوم + الحصة + السنة) / external_id | course/lecturer/group/classroom/day/period (+كشف تعارض) |

---

## 4. المراحل

| # | المرحلة | المخرجات | الحالة |
|---|---------|----------|--------|
| **I0** | اكتشاف وقرارات | ملف PDF، قائمة الكيانات، مصدر الحقيقة، توفر External IDs | ⬜ |
| **I1** | أساس أمني | جدول `integration_clients` + ميدل وير ٣ عوامل + مجموعة `/api/external/v1` + `ping` | ✅ |
| **I2** | بيانات مرجعية هيكلية | Colleges, Departments, Programs, Levels, Semesters, Academic Titles, Buildings, Classrooms, Days, Periods | ✅ |
| **I3** | الأشخاص | Students (users+students)، Academic Staff (users+lecturers) | ✅ |
| **I4** | المحتوى الأكاديمي | Courses، Student Groups | ✅ |
| **I5** | الجداول | Timetable (مع كشف التعارض) | ✅ |
| **I6** | تقوية وتوثيق | سجلات، Idempotency، Rate limit، دليل PDF/HTML + Postman | ✅ |

---

## 5. الأسئلة والقرارات المطلوبة (قبل البدء)

1. **ملف `university_integration_apis.pdf`** — أرسله؛ لمعرفة الواجهات (GET) والكيانات بالضبط.
2. **المعرّفات الخارجية:** هل نظام Oracle يرسل معرّفاً ثابتاً لكل سجل، أم الأكواد/الأسماء فقط؟ (يحدد جدول التعيين).
3. **مصدر الحقيقة:** هل الجامعة تملك حق الكتابة على كل الحقول (قد تُطمس تعديلات محلية)؟
4. **الحذف:** Soft delete؟ أم تعطيل (`is_active=0`)؟ أم منع إن كان مرتبطاً؟
5. **تخزين الأمان:** جدول `integration_clients` (موصى به) أم ثوابت مثل النموذج؟
6. **Base URL الفعلي:** ما الدومين/الرابط العام المخصص للتكامل؟ (وخلف VPN أم إنترنت عام؟)
7. **قائمة الكيانات النهائية** ("وغيرها" — ما هي بالتحديد؟).
8. **سلوك الدفعة:** كل شيء أو لا شيء (Transaction) أم نجاح جزئي صف-بصف؟

---

## 6. رد الجامعة (المخرجات التوثيقية)
سنجهّز مستنداً محدّثاً يجيب نقاطهم الثلاث: **Base URL + آلية المصادقة (Bearer + IP) + واجهات POST/PUT/PATCH/DELETE** مع أمثلة، إضافةً إلى واجهات GET للسحب إن لزم.

---

## سجل التنفيذ
> يُحدّث عند إنجاز كل مرحلة.

### I6 — التقوية والتوثيق ✅ (2026-10-17)
**القرارات المعتمدة:**
- **Idempotency:** هيدر `Idempotency-Key` **اختياري**، صلاحية **24 ساعة**؛ نفس المفتاح+الجسم → إعادة الرد حرفياً (`Idempotent-Replay: true`)؛ نفس المفتاح+جسم مختلف → **409**.
- **Rate limit:** **600 طلب/دقيقة** لكل عميل افتراضياً، مع عمود تجاوز لكل عميل (`rate_limit_per_minute`)، ورد **429** موحّد + `Retry-After` + `X-RateLimit-*`.
- **السجلات:** التقاط **الحمولة** (مقتطعة) + أحجام الطلب/الرد + **ملخّص الرد** (أعداد inserted/updated/skipped + أسباب مختصرة)، احتفاظ **90 يوماً**، أمر تقليم + **جدولة يومية**.
- **التوثيق:** دليل **Markdown** شامل + مجموعة **Postman** + ملف **HTML** جاهز للطباعة PDF (بلا اعتماديات جديدة).

**أُنشئ/عُدِّل:**
- Migrations: `2026_10_17_000004_create_integration_idempotency_table`، `_000005_add_rate_limit_to_integration_clients_table`، `_000006_add_payload_capture_to_integration_logs_table` (شُغّلت).
- Model: `App\Models\IntegrationIdempotency`. تحديث `IntegrationClient`/`IntegrationLog`.
- Middleware: `ThrottleIntegrationClient` (حدّ لكل عميل)، `EnsureIdempotency` (إعادة محاولة آمنة). تحديث `AuthenticateIntegrationClient` (التقاط الحمولة + ملخّص الرد).
- Limiter: `integration-ip` (حماية أولية قبل المصادقة) في `AppServiceProvider`.
- Routes: `/api/external/v1` → `['throttle:integration-ip','integration.auth','integration.throttle','integration.idempotency']`.
- Command: `integration:prune [--days=] [--dry-run]` + جدولة يومية؛ وتوسيع `integration:client` بـ `--rate-limit=`.
- إعدادات `config/integration.php`: `default_rate_limit`, `idempotency_ttl_hours`, `log_retention_days`, `log_payload_max_bytes`.
- توثيق: `docs/INTEGRATION_API_GUIDE_AR.md`, `docs/INTEGRATION_API_GUIDE_AR.html`, `docs/integration_postman_collection.json`.

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| نفس المفتاح + نفس الجسم | 200 + `Idempotent-Replay: true` + نفس الرد (نفس الطابع الزمني) |
| نفس المفتاح + جسم مختلف | 409 برسالة واضحة |
| مفتاح جديد | 200 طبيعي |
| تجاوز الحد (عميل بحدّ 5) | 429 + `Retry-After` + `X-RateLimit-Limit: 5` |
| التقاط الحمولة | `request_bytes` + `request_payload` + `summary` (أعداد + أسباب) ظاهرة في `integration_logs` |
| `integration:prune` | حذف السجلات الأقدم من المدة + مفاتيح Idempotency المنتهية |
| الجدولة | `integration:prune` يومياً (ظهرت في `schedule:list`) |

تم تنظيف كل بيانات الاختبار (العملاء/السجلات/المفاتيح/الكليات/الذاكرة) وإيقاف الخادم.

---

### I5 — الجداول: Timetable مع كشف التعارض ✅ (2026-10-17)
**القرارات المعتمدة:**
- المفتاح الطبيعي: (**المجموعة + اليوم + الحصة + السنة الأكاديمية**) + دعم `external_id`.
- نطاق كشف التعارض: **المحاضر + المجموعة + القاعة** في نفس (اليوم + الحصة + السنة) مع **تقاطع التواريخ** (`start_date`..`end_date`).
- عند التعارض: **تخطّي الصف بسبب واضح** (نجاح جزئي).
- الحذف: **`status=0`** (تعطيل مع بقاء الصف)، والسجل المعطّل **لا يمنع** حجزاً جديداً.

**التوسعة في المحرّك:**
- **`foreign_via_user`**: حل مرجع عبر جدول `users` (مثال: `academic_number`/`email` → `lecturer_id`).
- **`foreign_composite` مع `nullable`**: مرجع مركّب يقبل أعمدة فارغة (`IS NULL`) — لحل المجموعة الطلابية عبر مسارها (قد يكون البرنامج/المستوى/الفصل/البلوك فارغاً).
- **`conflict`**: قاعدة عامة لكشف التزاحم: أبعاد (`dimensions`) + نطاق (`scope`) + تقاطع تواريخ (`dates`) + فلترة النشِط (`active`). تُفحَص قبل الكتابة، وتُستثني عند التحديث نفس السجل.

**المرجع إلى الكيانات في حِمل الجدول:**
`course_code` • `lecturer_academic_number` • `college_code`/`department_code` • برنامج/مستوى/فصل (كائنات متداخلة) • `block_number` • `group_name` (+ المسار) • `building_code`/`floor`/`classroom_name` • `day_name` • `period_name`.

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| إنشاء المتطلبات عبر التكامل (بناية/قاعتان/يوم/مقرر/محاضران/مجموعتان) | الكل `inserted` |
| دفعة جدول: صف سليم + تعارض محاضر + تعارض قاعة + صف سليم | `inserted=2, skipped=2` بسببين واضحين |
| حل المراجع | course/lecturer/group/classroom/day/period كلها صحيحة |
| إعادة إرسال الدفعة | `updated=2, skipped=2` (لا تكرار) |
| DELETE بالمفتاح الطبيعي (المجموعة) | `status=0` |
| DELETE بالمعرّف الخارجي | `status=0` |
| إدخال صف بعد تعطيل ما يتعارض معه | نجح (المعطّل لا يمنع الحجز) |

تم تنظيف كل بيانات الاختبار (الجدول/المقرر/المجموعات/القاعات/البناية/اليوم/المحاضرون/المراجع/السجلات/العميل).

---

### I4 — المحتوى الأكاديمي: المقررات والمجموعات الطلابية ✅ (2026-10-17)
**الموارد المُضافة (2):** `courses`, `student_groups`.

**التوسعة في المحرّك:**
- **`casts` نوع `json`**: تخزين حقول JSON (مثل `courses.course_parts`) كنص JSON صحيح.
- **إعادة ترتيب الحل:** `foreign` → `parents` → `foreign_composite`، حتى يستفيد المرجع المركّب من مفاتيح الأب المحلولة (مثال: `block_id` يحتاج `program_id` المحلول من كائن البرنامج).
- **`buildMatch` مع أعمدة NULL:** أعمدة التطابق الغائبة تُعامَل كـ `NULL` (ضروري للمفتاح المركّب للمجموعات الذي يحوي أعمدة اختيارية).

**التهيئة:**
- `courses`: مفتاح طبيعي `course_code`؛ مراجع `college_code`/`department_code` + برنامج/مستوى/فصل + بلوك (مركّب: `program_id`+`block_number`)؛ قيم افتراضية (نوع/لغة/حالة التوصيف...)؛ تحقّق enum للتصنيف واللغة وحالة التوصيف. الحذف = `is_active=0`.
- `student_groups`: مفتاح مركّب (المسار + `group_name`)؛ الحذف = **حذف ناعم** (بلا `is_active`).

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| مقرّر بسيط (كلية فقط) | `inserted=1`، الافتراضيات مطبّقة |
| مقرّر كامل (برنامج + بلوك + `course_parts` JSON) | `inserted=1`، حُلّ `block_id`، وخُزّن JSON صحيحاً |
| مجموعتان (بلا مسار / بمسار كامل) | `inserted=2`، الافتراضي `max_students=30` |
| إعادة إرسال المقررات والمجموعات | `updated=2` لكلٍّ (لا تكرار) |
| DELETE مقرّر | `is_active=0` |
| DELETE مجموعة | حذف ناعم (`deleted_at`) |
| إعادة الإرسال بعد الحذف | استُرجعت (`is_active=1` / `deleted_at=NULL`) |

تم تنظيف كل بيانات الاختبار (المقررات/المجموعات/المراجع/السجلات/العميل).

---

### I3 — الأشخاص: الطلاب وهيئة التدريس ✅ (2026-10-17)
**المطلوب (كما اتفقنا):** النظام الخارجي ينشئ حساب المستخدم لدينا، وحسابنا منفصل تماماً عنهم. يرسلون بيانات الطالب كما في جدولنا (ومنها **الإيميل**)، ويدخل الطالب **بالإيميل** بكلمة مرور افتراضية تُطلب تغييرها عند أول دخول. المعرّف الفريد = `academic_number`. هيئة التدريس بنفس المنطق.

**الاكتشاف من النظام الحالي:**
- `user_types`: `student`=1، `lecturer`=8.
- كلمة المرور الافتراضية: `12345678` (أو `DEFAULT_STUDENT_PASSWORD`)، و`AuthController` يعيد `require_password_change: true` عند الدخول بها.
- `gender`: 1=ذكر، 0=أنثى (نفس `parseGender` الحالي).

**التوسعة في المحرّك (تكفي كل الجداول المستقبلية):**
- **`linked_user`**: مورد يكتب في **جدولين** — يُنشئ/يُحدّث صف `users` أولاً ثم صف الجدول الفرعي (`students`/`lecturers`) المرتبط بـ `user_id`.
  - يُحدّد `user_type` تلقائياً (student/lecturer)، ويُشفّر كلمة المرور الافتراضية للحسابات الجديدة فقط (لا يغيّرها عند التحديث).
  - إيجاد المستخدم بـ `academic_number` أو `email` أو `phone`، واسترجاع المحذوف ناعماً.
- **`foreign_composite`**: حل مرجع بمفتاح مركّب (مثال: `title_code` + `college_id` → `title_id` للدرجة العلمية).
- **`parents.optional`**: مراجع أب اختيارية (برنامج/مستوى/فصل للطالب).

**الموارد المُضافة (2):** `students`, `lecturers`.

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| إنشاء طالب | `users`+`students`، الإيميل من البيانات، النوع=طالب، الجنس صحيح |
| الدخول بالإيميل + `12345678` | نجح + `require_password_change: true` |
| إعادة إرسال نفس الطالب | `updated=1` (مستخدم واحد، سجل واحد — لا تكرار) |
| تحديث مع إيميل صريح + جنس أنثى | تحدّث الإيميل والجنس والصف |
| مراجع متداخلة (برنامج/مستوى/فصل) | حُلّت `program_id/level_id/semester_id` |
| إنشاء محاضر مع `title_code` | النوع=محاضر، حُلّ `title_id` بالمرجع المركّب، `hire_date` و`can_teach_externally` |
| دفعة مختلطة (سليم + كلية غير موجودة) | `inserted=1, skipped=1` بسبب واضح، ولم يُنشأ مستخدم للصف الفاشل |
| DELETE طالب (بـ external_id) | `status=0`، ثم إعادة الإرسال أرجعته `status=1` |
| DELETE محاضر (بـ academic_number) | `status=0` |
| طالب بلا إيميل | `skipped=1` ("missing required field 'email'") — لا يُنشأ حساب |

تم تنظيف كل بيانات الاختبار (المستخدمون/الطلاب/المحاضرون/المراجع/السجلات/العميل).

**ملاحظات:**
- كلمة مرور الحساب الجديد = `12345678` (تُغيّر عند أول دخول) — مطابقة للنظام الحالي.
- **الإيميل إلزامي ويأتي ضمن البيانات المُرسَلة فقط** (لا نُولّده تلقائياً)، حتى لو اختلف عن صيغتنا.
- الجنس اختياري (افتراضي 0 عند الغياب)؛ `full_name` و`academic_number` و`email` إلزامية.

---

### I2 — البيانات المرجعية الهيكلية ✅ (2026-10-17)
**المحرّك العام (config-driven):** يعمل على **الجداول الفعلية** بكل أعمدتها. إضافة أي جدول = مقطع في `config/integration.php` فقط.

**الميزات المُنفّذة:**
- Upsert بالمفتاح الطبيعي، حل المفاتيح بالأكواد (`foreign`)، **مراجع متداخلة `parents`** (كائن الأب بمفتاحه الطبيعي — يدعم التداخل: semester←level←program).
- نجاح جزئي صف-بصف، استرجاع المحذوف ناعماً، تعطيل/حذف.
- **المعرّف الخارجي `external_id`** مفعّل: إن أرسلوه يُخزَّن في `integration_entity_refs`، ويمكن التحديث/الحذف بمعرّفهم.
- جدول الربط `integration_entity_refs` يُعبّأ تلقائياً لكل سجل.

**الموارد المُفعّلة (10):** `colleges, departments, programs, levels, semesters, academic_titles, buildings, classrooms, days, periods`.

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| إدخال كل الموارد العشرة بالتسلسل | الكل `inserted=1` |
| `levels` بمرجع متداخل لبرنامج (بلا كود) | حُلّ `program_id` بنجاح |
| `semesters` بمرجع متداخل level←program | حُلّ `level_id` بنجاح |
| إعادة إدخال نفس المستوى | `updated=1` (لا تكرار) |
| `academic_titles` (بعد تصحيح `_` في المسار) | `inserted=1` |
| حذف day/classroom/period | حذف ناعم ناجح |
| جدول الربط | 10 كيانات مسجّلة |

تم تنظيف كل بيانات الاختبار. (الملاحظة: اسم المورد `academic_titles` يُستخدم بشرطة سفلية، والمسار يقبل `_` و`-`.)

---

### I2 — الدفعة الأولى (محرّك عام + Colleges/Departments/Programs) ✅ (2026-10-17)
**الفكرة:** محرّك تكامل **عام (config-driven)** يعمل على **الجداول الفعلية** بكل أعمدتها وصفوفها — لا جداول موازية. لإضافة أي جدول لاحقاً: مقطع في `config/integration.php` فقط (بلا كود إضافي).

**أُنشئ:**
- Migration + Model: `integration_entity_refs` — جدول **ربط** يُعبّأ **تلقائياً** عند كل مزامنة (ليس فراغاً بلا فائدة): يربط معرّف النظام الخارجي (إن أُرسل) أو الكود الطبيعي بالسجل عندنا (`internal_id` + `internal_key`).
- `config/integration.php` — وصف الموارد: الجدول، المفتاح، الأعمدة القابلة للكتابة، الإلزامي، الافتراضيات، enum، التحويلات، حل المفاتيح، مفتاح التطابق، استراتيجية الحذف.
- `App\Services\Integration\IntegrationSyncService` — Upsert بالمفتاح الطبيعي + حل المفاتيح بالأكواد + نجاح جزئي صف-بصف + استرجاع المحذوف ناعماً + تسجيل الربط + تعطيل/حذف.
- تحديث `IntegrationController`: `upsert` (POST/PUT/PATCH) و`deactivate` (DELETE).
- مسارات: `POST|PUT|PATCH /api/external/v1/{resource}` و`DELETE /api/external/v1/{resource}`.

**دلالات الحذف (لكل جدول حسب أعمدته):** إن وُجد `is_active` → تعطيل (`is_active=0`)؛ وإلا → حذف ناعم (`deleted_at`). (colleges/departments بلا is_active → حذف ناعم؛ programs → is_active=0.)

**الاختبار (HTTP فعلي):**
| السيناريو | النتيجة |
|---|---|
| إدخال كليات (2) | inserted=2 |
| دفعة مختلطة (تحديث+إدخال+صف ناقص) | updated=1, inserted=1, skipped=1 بسبب واضح |
| إدخال أقسام مع حل `college_code` | inserted=1، والمرجع المجهول skipped |
| إدخال برامج مع حل `department_code` + افتراضيات/booleans | inserted=2 |
| مورد غير معروف | 404 |
| حذف كلية (ناعم) | deactivated=1 |
| حذف برنامج (is_active=0) | deactivated=1 |
| إعادة إدخال كلية محذوفة ناعماً | updated=1 + **استُرجعت** |
| حذف سجل غير موجود | skipped=1 "record not found" |
| `integration_entity_refs` | سُجّلت كل الصفوف بـ `internal_key` = الكود الطبيعي |

تم تنظيف كل بيانات الاختبار بعد التجربة.

---

### I1 — الأساس الأمني ✅ (2026-10-17)
**أُنشئ:**
- Migrations: `2026_10_17_000001_create_integration_clients_table`, `2026_10_17_000002_create_integration_logs_table` (تم تشغيلهما بنجاح).
- Models: `App\Models\IntegrationClient` (توكن hash sha256 + قوائم JSON)، `App\Models\IntegrationLog`.
- Middleware: `App\Http\Middleware\AuthenticateIntegrationClient` (كان ٣ عوامل: Bearer → MAC → IP، + تسجيل كل طلب؛ **أُلغي فحص MAC لاحقاً** فأصبح عاملين: Bearer → IP).
- Controller: `App\Http\Controllers\Api\External\IntegrationController` (`ping`).
- Route group: `/api/external/v1` مع `integration.auth` + `throttle:120,1`.
- Alias `integration.auth` في `bootstrap/app.php`.
- Command: `php artisan integration:client {name} --ips=* [--rate-limit=] [--rotate|--enable|--disable]` (لا يوجد خيار `--macs` بعد إلغاء فحص MAC).
- ردود أخطاء موحّدة لمسارات `api/external/*`: 405 (طريقة غير مدعومة) و404 (مسار غير معروف).

**الاختبار (HTTP فعلي عبر `artisan serve`):**
| السيناريو | النتيجة |
|---|---|
| بدون توكن | 401 |
| توكن خاطئ | 401 |
| توكن صحيح بلا MAC | 403 |
| MAC خاطئ | 403 |
| توكن + MAC صحيح | 200 `{status:success, client:"test-client"}` |
| GET بدل POST | 405 (صيغة موحّدة) |
| مسار غير معروف | 404 (صيغة موحّدة) |

> ⚠️ جدول التجربة أعلاه هو ما حدث **آنذاك** وكانت MAC إلزامية. بعد إلغاء الفحص صار الحال: **توكن صحيح بلا MAC = 200**، وما زال بلا توكن = 401، وبلا IP مسموح = 403، وMAC خاطئ/مفقود = لا أثر له.

**ملاحظات:**
- التوكن يُعرض مرّة واحدة عند الإنشاء ويُخزَّن كـ hash فقط.
- القائمة الفارغة من IPs = رفض العميل (افتراضي متشدّد).
- تم تنظيف بيانات الاختبار (العميل والسجلات) بعد التجربة.
- نُفِّذ لاحقاً في المرحلة I6: Idempotency، وRate limiting لكل عميل، وسياسة الاحتفاظ بالسجلات والتقاط الحمولة.

