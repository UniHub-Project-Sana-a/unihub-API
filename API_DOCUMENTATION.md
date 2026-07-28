# UniHub API: Complete Technical Specification Manual (دليل المواصفات الفنية الكامل لـ UniHub)

تم تحديث هذا الدليل الفني ليكون مرجعاً تقنياً شاملاً ومفصلاً يُلحق بـ **عقد بيع ترخيص النظام (Sales & Licensing Contract)**. يعكس هذا الدليل القيمة البرمجية الحقيقية لجميع الأنظمة الفرعية والموديولات البرمجية في نظام **UniHub API**، ويشرح آليات التشغيل والدمج البرمجي لكل المكونات، مع حصر شامل لكافة مسارات الـ API (أكثر من 310 مسارات).

---

## فهرس الموديولات البرمجية (API Modules Index)
1. [المصادقة والأمان والتحقق المزدوج (Authentication & Identity)](#1-المصادقة-والأمان-والتحقق-المزدوج-authentication--identity)
2. [الهيكل الأكاديمي والبيانات الأساسية (Academic Metadata & Resources)](#2-الهيكل-الأكاديمي-والبيانات-الأساسية-academic-metadata--resources)
3. [الطلاب والمجموعات الدراسية (Students & Student Groups)](#3-الطلاب-والمجموعات-الدراسية-students--student-groups)
4. [الجدول الدراسي والتعويضات وجلسات المحاضرات (Timetable & Session Lifecycle)](#4-الجدول-الدراسي-والتعويضات-وجلسات-المحاضرات-timetable--session-lifecycle)
5. [أكواد الحضور الذكي والتحضير (QR Codes & Attendance System)](#5-أكواد-الحضور-الذكي-والتحضير-qr-codes--attendance-system)
6. [نظام الجودة والتقييمات الأكاديمية (Quality Assurance - QA)](#6-نظام-الجودة-والتقييمات-الأكاديمية-quality-assurance---qa)
7. [الرواتب وكشوف المستحقات المالية (Financial Cycles & Payouts)](#7-الرواتب-وكشوف-المستحقات-المالية-financial-cycles--payouts)
8. [الإشعارات والمرفقات الدراسية (Notifications & Lecture Attachments)](#8-الإشعارات-والمرفقات-الدراسية-notifications--lecture-attachments)
9. [التقارير الشاملة ولوحات التحكم (Dashboards & Comprehensive Reports)](#9-التقارير-الشاملة-ولوحات-التحكم-dashboards--comprehensive-reports)
10. [إشراف النظام والتحكم بالأجهزة والـ IPs (Admin System & Access Security)](#10-إشراف-النظام-والتحكم-بالأجهزة-والـ-ips-admin-system--access-security)
11. [سجل درجات الطلاب وأعمدة التقييم والأعذار (Gradebook & Student Excuses)](#11-سجل-درجات-الطلاب-وأعمدة-التقييم-والأعذار-gradebook--student-excuses)
12. [توصيف ومواصفات المقررات الأكاديمية (Course Specifications System)](#12-توصيف-ومواصفات-المقررات-الأكاديمية-course-specifications-system)
13. [الملحق الفني: سجل المسارات والروابط الكامل (Appendix: Route Catalog)](#13-الملحق-الفني-سجل-المسارات-والروابط-الكامل-appendix-route-catalog)

---

## 1. المصادقة والأمان والتحقق المزدوج (Authentication & Identity)

### 1.1 تسجيل الدخول ومصادقة المستخدم
- **المسار:** `POST /auth/login`
- **حماية الطلبات:** خاضع للـ Throttle للتصدي لهجمات التخمين (`throttle:login` حد أقصى 5 محاولات/دقيقة).
- **المدخلات (JSON):**
  - `email` (string|required|email) - البريد الأكاديمي.
  - `password` (string|required) - كلمة المرور.
  - `mac_address` (string|required) - عنوان الـ MAC أو الـ UUID الفريد للجهاز.
  - `device_name` (string|required) - طراز الهاتف.
  - `os_type` (string|required) - نظام التشغيل (مثل: `iOS`, `Android`).
- **الاستجابة:**
  - **جهاز معروف وموثق (200 OK):** يرجع الـ `access_token` وبيانات المستخدم كاملاً.
  - **جهاز جديد يتطلب الـ OTP (200 OK):** يرسل رمز التحقق للبريد ويرجع `otp_required: true`.

### 1.2 التحقق من الـ OTP (Verify OTP)
- **المسار:** `POST /auth/verify-otp`
- **المدخلات (JSON):**
  - `verification_id` (integer|required) - الرقم التعريفي للطلب.
  - `otp_code` (string|required|digits:6) - الرمز المرسل للبريد.

---

## 2. الهيكل الأكاديمي والبيانات الأساسية (Academic Metadata & Resources)

### 2.1 كشوف القوائم السريعة (Lookups)
تتطلب Bearer Token وتستخدم لملء القوائم المنسدلة بالفرونت إند:
- `GET /lookups/user-types` - استرجاع مسميات الأدوار ورموزها.
- `GET /lookups/permissions` - استرجاع مصفوفة الصلاحيات بالكامل.
- `GET /lookups/colleges` - كشف الكليات والرموز الأكاديمية لها.
- `GET /lookups/academic-years` - قائمة بالأعوام الأكاديمية النشطة.

### 2.2 واجهات إدارة المكونات الأكاديمية (Academic REST API Core)
تدعم جميع العمليات الأساسية (CRUD) وتخضع للصلاحيات:
* **المستخدمون (`/users`):** إدارة كادر الجامعة والطلاب والمشرفين.
* **الهيكل التنظيمي والأكاديمي:** الكليات (`/colleges`)، الأقسام (`/departments`)، البرامج (`/programs`)، المستويات (`/levels`)، الأترام الدراسي (`/semesters`)، المقررات (`/courses`).
* **الهيكل اللوجستي والقاعات:** المباني (`/buildings`)، القواطع (`/blocks`)، القاعات ومواقعها الجغرافية ومسافات الأمان (`/classrooms`).
* **السياسات والمالية الأكاديمية:** الفترات الزمنية (`/periods`)، الرتب العلمية وأسعار الساعة للمحاضرين (`/academic-titles`)، المحاضرون وعقودهم (`/lecturers`).

---

## 3. الطلاب والمجموعات الدراسية (Students & Student Groups)

### 3.1 إلحاق الطلاب بالشعب (Upsert and Attach Students)
- **المسار:** `POST /student-groups/upsert-and-attach`
- **المدخلات (JSON):**
```json
{
  "group_name": "شعبة أ - تكنولوجيا المعلومات",
  "college_id": 1,
  "department_id": 2,
  "level_id": 3,
  "semester_id": 2,
  "student_ids": [105, 106, 110, 115]
}
```

---

## 4. الجدول الدراسي والتعويضات وجلسات المحاضرات (Timetable & Session Management)

### 4.1 إنشاء الجدول الأكاديمي الأساسي (Timetable Scheduling)
- **المسار:** `POST /timetable`
- **القيود المفروضة:** يمنع تعارض القاعات أو المحاضرين أو المجموعات الطلابية في نفس اليوم والفترة.

### 4.2 إنشاء الجلسات دفعة واحدة (Bulk Session Generation)
- **المسار:** `POST /lecture-sessions/bulk`
- **المدخلات:** `timetable_id` (integer).

---

## 5. أكواد الحضور الذكي والتحضير (QR Codes & Attendance System)

### 5.1 بدء جلسة الحضور بالـ QR للمحاضرة
- **المسار:** `POST /qr-codes/start-session`
- **المدخلات:** `timetable_id`, `session_id`, `latitude`, `longitude`, `allowed_distance`.

### 5.2 تحضير الطالب الذاتي عبر كود الـ QR والـ GPS
- **المسار:** `POST /attendance/scan`
- **المدخلات:** إحداثيات الطالب وموقع الهاتف الجغرافي وقيمة كود الـ QR المتغير.
- **خوارزمية Haversine:** تحتسب المسافة الجغرافية الحقيقية بين الطالب والقاعة وتمنع تسجيل الحضور في حال تجاوز المسافة المسموحة (مثال: > 50 متر).

---

## 6. نظام الجودة والتقييمات الأكاديمية (Quality Assurance - QA)

### 6.1 منشئ النماذج والاستمارات التفاعلية
- **المسارات:** `GET|POST|PUT|DELETE /qa/forms`
- **المميزات:** تدعم بناء نماذج تقييم مقسمة لمجالات وأسئلة تفصيلية.

### 6.2 إدارة حملات التقييم للطلاب
- **المسارات:** `GET|POST|PUT|DELETE /qa/campaigns`
- **أهلية التقييم:** يتحقق النظام تلقائياً من نسبة حضور الطالب للمحاضر ومطابقتها للنسبة المحددة في الحملة قبل السماح له بالتقييم.

---

## 7. الرواتب وكشوف المستحقات المالية (Financial Cycles & Payouts)

### 7.1 توليد الدورة المالية (Generate Financial Cycle)
- **المسار:** `POST /colleges/{collegeId}/financial/generate`
- **آلية العمل:** يقوم النظام بفلترة المحاضرات المنفذة فعلياً فقط، وحساب ساعات العمل للمحاضر، وضربها في تسعيرة رتبته الأكاديمية لتوليد الكشوف، مع دعم كامل للتحويل بين التقويمين الهجري والميلادي.

---

## 8. الإشعارات والمرفقات الدراسية (Notifications & Lecture Attachments)

### 8.1 إرسال التنبيهات للشعب الطلابية (Group Notifications)
- **المسار:** `POST /notifications`
- **الاستخدام:** إرسال إشعارات جماعية فورية لهواتف الطلاب في شعبة محددة.

---

## 9. التقارير الشاملة ولوحات التحكم (Dashboards & Comprehensive Reports)

### 9.1 لوحة معلومات الكلية والجامعة (KPI Dashboards)
- **المسارات:**
  - `GET /colleges/{collegeId}/dashboard` - إحصائيات الكليات والمالية.
  - `GET /reports/university-comprehensive` - التقرير الشامل على مستوى الجامعة.

### 9.2 منشئ التقارير المخصصة والفلترة الديناميكية
- **المسار:** `POST /colleges/{collegeId}/reports/builder`

---

## 10. إشراف النظام والتحكم بالأجهزة والـ IPs (Admin System & Access Security)

### 10.1 سحب وإلغاء جلسات الأجهزة
- **المسار:** `POST /admin/sessions/revoke`
- **الاستخدام:** طرد الأجهزة وإلغاء توكنات الدخول بشكل فوري لحماية النظام.

---

## 11. سجل درجات الطلاب وأعمدة التقييم والأعذار (Gradebook & Student Excuses)

### 11.1 جلب شبكة دفتر الدرجات والحضور الشاملة
- **المسار:** `GET /lecturer/gradebook`
- **الاستخدام:** يعرض قائمة بكافة الطلاب المسجلين بالدورة والدرجات المرصودة في بنود التقييم ونسب الحضور الكلية وتفاصيل الأعذار الطبية المعتمدة.

### 11.2 رصد وتحديث درجات الطلاب
- **المسار:** `POST /lecturer/grades/update`

---

## 12. توصيف ومواصفات المقررات الأكاديمية (Course Specifications System)

نظام شامل ومتكامل تم تصميمه وفق المعايير الأكاديمية المعتمدة لمواصفات وتوصيف المقررات الأكاديمية لضمان الجودة والاعتماد.

### 12.1 مخرجات تعلم البرنامج والمقرر (PLOs & CLOs)
* **المسار:** `GET|POST|PUT|DELETE /program-learning-outcomes` - إدارة مخرجات التعلم الأكاديمية للبرنامج (Program Learning Outcomes).
* **المسار:** `GET|POST|PUT|DELETE /courses/{course_id}/learning-outcomes` - مخرجات تعلم المقرر (Course Learning Outcomes) مع دعم تصنيفها وتحديد أوزانها وربطها بمخرجات البرنامج.

### 12.2 خطة المواضيع الأسبوعية (Course Topics)
* **المسار:** `GET|POST|PUT|DELETE /courses/{course_id}/topics`
* **الاستخدام:** جدول المواضيع الأسبوعية مقسماً حسب الأجزاء (نظري، عملي، تمارين، سريري) وساعات الشرح الفعلية لكل أسبوع.

### 12.3 بنك الأسئلة المربوط بالمواضيع (Topic Questions)
* **المسار:** `GET|POST|PUT|DELETE /topics/{topic_id}/questions`
* **المواصفات:** تسجيل الأسئلة الاختيارية والمقالية لكل موضوع، وتحديد درجة الصعوبة ورمز مخرج التعلم المستهدف.

### 12.4 طرق التقييم واستراتيجيات التدريس (Assessments & Strategies)
* **المسار:** `GET|POST|PUT|DELETE /courses/{course_id}/assessments` - طرق تقييم المقرر وتوزيع أوزان الدرجات ديناميكياً مع التحقق من توازن الأوزان الكلية.
* **المسار:** `GET|POST|PUT|DELETE /teaching-strategies` - الدليل المرجعي لاستراتيجيات التدريس المعتمدة (المحاضرة، العمل التعاوني، إلخ).
* **المسار:** `GET|POST|PUT|DELETE /assessment-methods` - الدليل المرجعي لطرق التقييم (اختبار، واجب، عرض تقديمي).

---

## 13. الملحق الفني: سجل المسارات والروابط الكامل (Appendix: Route Catalog)

يحتوي هذا الجدول على قائمة بكافة مسارات الـ API النشطة في النظام بعد التحديث الأخير:

| الطريقة (Method) | المسار (Endpoint) | المتحكم والتابع (Controller Action) | الوصف الأكاديمي والوظيفي للمسار |
| :--- | :--- | :--- | :--- |
| **POST** | `/auth/login` | `AuthController@login` | تسجيل الدخول مع التحقق من معرّف الهواتف |
| **POST** | `/auth/verify-otp` | `AuthController@verifyOtp` | التحقق من كود الـ OTP وتسجيل الأجهزة |
| **GET** | `/auth/me` | `AuthController@me` | جلب بيانات حساب المستخدم وصلاحياته |
| **POST** | `/auth/logout` | `AuthController@logout` | تسجيل الخروج وإلغاء توكن الجلسة |
| **POST** | `/auth/change-password` | `AuthController@changePassword` | تغيير كلمة المرور للمستخدم الحالي |
| **POST** | `/auth/forgot-password` | `AuthPasswordController@forgot` | طلب رمز استعادة كلمة المرور المنسية |
| **POST** | `/auth/reset-password` | `AuthPasswordController@reset` | إعادة تعيين كلمة المرور بالتكامل مع التوكن |
| **GET** | `/lookups/user-types` | `LookupsController@userTypes` | كشف أدوار وصلاحيات المستخدمين المتاحة |
| **GET** | `/lookups/permissions` | `LookupsController@permissions` | جلب مصفوفة الأذونات والتصريحات |
| **GET** | `/lookups/colleges` | `LookupsController@colleges` | كشف الكليات المفعلة بالجامعة |
| **GET** | `/lookups/academic-years` | `LookupsController@academicYears` | كشف الأعوام الدراسية المتاحة بالجدول |
| **GET** | `/classrooms/availability` | `ClassroomsController@checkAvailability` | فحص شغور قاعة في يوم وفترة محددة |
| **POST** | `/student-groups/upsert-and-attach` | `StudentGroupsController@upsertAndAttach`| إنشاء المجموعات وتسكين الطلاب فيها |
| **POST** | `/student-groups/import-csv` | `StudentGroupsController@importCsv` | استيراد الطلاب مجمّعاً من ملفات CSV |
| **POST** | `/student-groups/import-external` | `StudentGroupsController@importExternal` | مزامنة واستيراد الشعب من أنظمة الجامعة |
| **DELETE**| `/student-groups/{group}/students`| `StudentGroupsController@detachStudent` | إزالة طالب معين من الشعبة |
| **GET** | `/student-groups/{group}/students`| `StudentGroupsController@students` | كشف الطلاب المسجلين بشعبة دراسية |
| **POST** | `/lecture-sessions/bulk` | `LectureSessionsController@storeBulk` | التوليد التلقائي لجلسات الفصل الدراسي |
| **POST** | `/sessions/{id}/finish` | `LectureSessionsController@finishLecture` | إنهاء الجلسة وتأكيد الموقع الجغرافي بالـ GPS |
| **GET** | `/schedulable-lectures` | `LectureSessionsController@getSchedulableLectures` | الجلسات التي يتاح جدولتها بالتواريخ |
| **POST** | `/qr-codes/start-session` | `QrCodesController@startSession` | توليد كود الحضور بالـ QR الجغرافي للمحاضرة |
| **PATCH** | `/qr-codes/{qrCode}/refresh` | `QrCodesController@refresh` | تحديث وتوليد قيمة QR جديدة نشطة للجلسة |
| **PATCH** | `/qr-codes/{qrCode}/end` | `QrCodesController@endSession` | إنهاء كود الـ QR فورياً لمنع إدخال الحضور |
| **PATCH** | `/qr-codes/{qrCode}/extend` | `QrCodesController@extendDuration` | تمديد صلاحية كود الـ QR لعدة دقائق إضافية |
| **POST** | `/attendance/scan` | `StudentAttendanceController@scan` | تسجيل حضور الطالب الذاتي بمطابقة الموقع |
| **POST** | `/attendance/students/manual` | `StudentAttendanceController@manualEntry` | رصد حضور طالب يدوياً من حساب الدكتور |
| **POST** | `/attendance/lecturers/check-in`| `LecturerAttendanceController@checkIn` | تسجيل تحضير الدكتور الذاتي للمحاضرة بالـ GPS |
| **POST** | `/attendance/finalize` | `LecturerAttendanceController@finalizeSession` | الاعتماد المالي النهائي للحاضرين وإغلاق الجلسة |
| **POST** | `/makeup-lectures` | `MakeupLecturesController@store` | طلب جدولة محاضرة تعويضية بديلة |
| **PUT** | `/makeup-lectures/{makeup}/schedule`| `MakeupLecturesController@schedule` | جدولة المحاضرة التعويضية بعد الموافقة |
| **PUT** | `/student-excuses/{id}/status` | `StudentExcusesController@updateStatus` | قبول أو رفض عذر غياب طبي مقيد برقم الإثبات |
| **POST** | `/notifications` | `NotificationsController@store` | إرسال إشعار فوري وتعميم لأجهزة الطلاب للشعبة |
| **GET** | `/devices` | `UserDevicesController@index` | كشف الهواتف والأجهزة المسجلة للمستخدم الحالي |
| **PUT** | `/devices/{device}/enable-auto-attendance`| `UserDevicesController@enableAutoAttendance`| تفعيل خاصية الحضور التلقائي للجهاز |
| **DELETE**| `/devices/{device}` | `UserDevicesController@destroy` | إلغاء اقتران وحذف الجهاز وتطلب تسجيل OTP |
| **GET** | `/admin/sessions` | `SystemController@sessions` | (أدمن) مراقبة جلسات وتوكنات المستخدمين الحالية |
| **POST** | `/admin/sessions/revoke` | `SystemController@revokeSession` | (أدمن) سحب الجلسة وطرد المستخدم فوراً |
| **GET** | `/admin/audit-logs` | `SystemController@auditLogs` | (أدمن) جلب سجلات المراقبة الأمنية للعمليات |
| **PUT** | `/admin/security/policy` | `SettingsController@updatePolicy` | (أدمن) تعديل سياسات الأمان وحظر الأجهزة |
| **POST** | `/admin/ip-restrictions` | `IpRestrictionController@store` | (أدمن) إضافة IP مسموح بالوصول لقاعدة البيانات |
| **GET** | `/qa/student/pending` | `QaEvaluationController@getPendingEvaluations` | جلب المقررات المتاحة لتقييم الجودة للطلاب |
| **POST** | `/qa/student/submit` | `QaEvaluationController@submitEvaluation`| إرسال التقييم وحفظ إجابات الاستبيان |
| **GET** | `/qa/student/form/{campaign}` | `QaEvaluationController@getEvaluationForm` | جلب أسئلة التقييم لحملة معينة للطالب |
| **POST** | `/colleges/{college}/financial/generate` | `FinancialController@generateCycle` | احتساب وتوليد الرواتب والمستحقات الشهرية |
| **POST** | `/colleges/{college}/financial/payouts/{payout}/adjustments`| `FinancialController@addAdjustment` | إضافة الخصومات أو المكافآت لصافي الراتب |
| **GET** | `/colleges/{college}/financial/cycle` | `FinancialController@getCycleByMonth` | جلب تفاصيل الدورة المالية الحالية للكلية |
| **PUT** | `/colleges/{college}/financial/cycles/{cycle}/status`| `FinancialController@updateStatus` | تحديث حالة الدورة المالية (مسودة/معتمدة/إلخ) |
| **GET** | `/qa/reports/campaign-summary` | `QaAnalysisController@getCampaignSummary`| تقارير الجودة ومعدلات رضا الطلاب للقسم |
| **GET** | `/qa/reports/campaign-timetables` | `QaAnalysisController@getCampaignTimetables`| جلب الجداول الأكاديمية المرتبطة بالحملة الحالية |
| **GET** | `/qa/reports/execution/list` | `CourseExecutionReportController@index` | تتبع نسب الالتزام بتنفيذ المحاضرات بالقسم |
| **GET** | `/qa/reports/execution/details/{timetable}`| `CourseExecutionReportController@show` | تفصيل مواضيع الشرح واستبيانات القاعات للجلسات |
| **GET** | `/qa/reports/execution/filters-meta`| `CourseExecutionReportController@getFiltersMeta`| جلب الفلاتر الأساسية لتقارير التنفيذ والجودة |
| **GET** | `/courses/{course}/qa-data` | `QualityAssuranceController@getCourseQaData` | جلب كافة بيانات مخرجات ومواضيع الجودة للمقرر |
| **GET** | `/timetable/{timetable}/topics-status` | `TimetableController@getTopicsStatus` | جلب حالة مواضيع خطة المقرر لجدول معين |
| **GET** | `/reports/university-comprehensive`| `UniversityReportController@index` | جلب التقرير الجامعي الشامل للأداء المالي والجودة |
| **GET** | `/program-learning-outcomes/{programId}`| `ProgramLearningOutcomeController@index` | جلب مخرجات تعلم البرنامج لقسم معين |
| **POST** | `/program-learning-outcomes` | `ProgramLearningOutcomeController@store` | إضافة مخرج تعلم جديد للبرنامج |
| **GET** | `/program-learning-outcomes/{programId}/{ploId}`| `ProgramLearningOutcomeController@show` | عرض مخرج تعلم البرنامج بالتفصيل |
| **PUT** | `/program-learning-outcomes/{ploId}` | `ProgramLearningOutcomeController@update` | تعديل مخرج تعلم البرنامج |
| **DELETE**| `/program-learning-outcomes/{ploId}` | `ProgramLearningOutcomeController@destroy` | حذف مخرج تعلم البرنامج |
| **GET** | `/courses/{course_id}/description` | `CourseDescriptionController@show` | جلب وصف وأهداف المقرر |
| **PUT** | `/courses/{course_id}/description` | `CourseDescriptionController@updateDescription`| تحديث وصف المقرر فقط |
| **PUT** | `/courses/{course_id}/goals` | `CourseDescriptionController@updateGoals` | تحديث أهداف المقرر فقط |
| **GET** | `/courses/{course_id}/learning-outcomes`| `CourseLearningOutcomeController@index` | جلب مخرجات تعلم المقرر |
| **POST** | `/courses/{course_id}/learning-outcomes`| `CourseLearningOutcomeController@store` | إضافة مخرج تعلم جديد للمقرر |
| **GET** | `/courses/{course_id}/learning-outcomes/{clo_id}`| `CourseLearningOutcomeController@show` | عرض مخرج تعلم المقرر بالتفصيل |
| **PUT** | `/courses/{course_id}/learning-outcomes/{clo_id}`| `CourseLearningOutcomeController@update` | تعديل مخرج تعلم المقرر |
| **DELETE**| `/courses/{course_id}/learning-outcomes/{clo_id}`| `CourseLearningOutcomeController@destroy`| حذف مخرج تعلم المقرر |
| **GET** | `/courses/{course_id}/learning-outcomes/stats`| `CourseLearningOutcomeController@stats` | جلب إحصائيات مخرجات تعلم المقرر |
| **GET** | `/courses/{course_id}/learning-outcomes/domain/{domain}`| `CourseLearningOutcomeController@byDomain`| تصفية مخرجات المقرر حسب المجال |
| **GET** | `/courses/{course_id}/topics` | `CourseTopicController@index` | جلب خطة مواضيع المقرر الأسبوعية |
| **POST** | `/courses/{course_id}/topics` | `CourseTopicController@store` | إضافة موضوع جديد لخطة المقرر الأسبوعية |
| **GET** | `/courses/{course_id}/topics/{topic_id}`| `CourseTopicController@show` | عرض تفاصيل موضوع معين في خطة المقرر |
| **PUT** | `/courses/{course_id}/topics/{topic_id}`| `CourseTopicController@update` | تعديل موضوع في خطة المقرر الأسبوعية |
| **DELETE**| `/courses/{course_id}/topics/{topic_id}`| `CourseTopicController@destroy` | حذف موضوع من خطة المقرر الأسبوعية |
| **GET** | `/courses/{course_id}/topics/by-part/{part}`| `CourseTopicController@byPart` | تصفية المواضيع حسب الجزء (نظري/عملي/تمارين) |
| **GET** | `/courses/{course_id}/topics/stats`| `CourseTopicController@stats` | جلب إحصائيات المواضيع والمحاضرات |
| **GET** | `/topics/{topic_id}/questions` | `TopicQuestionController@index` | جلب أسئلة موضوع معين |
| **POST** | `/topics/{topic_id}/questions` | `TopicQuestionController@store` | إضافة سؤال جديد لموضوع معين |
| **GET** | `/topics/{topic_id}/questions/{question_id}`| `TopicQuestionController@show` | عرض تفاصيل سؤال معين في بنك الأسئلة |
| **PUT** | `/topics/{topic_id}/questions/{question_id}`| `TopicQuestionController@update` | تعديل سؤال معين في بنك الأسئلة |
| **DELETE**| `/topics/{topic_id}/questions/{question_id}`| `TopicQuestionController@destroy` | حذف سؤال معين في بنك الأسئلة |
| **GET** | `/topics/{topic_id}/questions/by-type/{type}`| `TopicQuestionController@byType` | تصفية الأسئلة حسب النوع (اختياري/مقالي) |
| **GET** | `/topics/{topic_id}/questions/used-in-exams`| `TopicQuestionController@usedInExams` | جلب الأسئلة المستخدمة في الامتحانات للموضوع |
| **GET** | `/topics/{topic_id}/questions/stats`| `TopicQuestionController@stats` | جلب إحصائيات الأسئلة لموضوع معين |
| **GET** | `/courses/{course_id}/assignments` | `CourseAssignmentController@index` | جلب تكليفات وتمرينات المقرر |
| **POST** | `/courses/{course_id}/assignments` | `CourseAssignmentController@store` | إضافة تكليف جديد للمقرر |
| **GET** | `/courses/{course_id}/assignments/{assignment_id}`| `CourseAssignmentController@show` | عرض تفاصيل تكليف معين |
| **PUT** | `/courses/{course_id}/assignments/{assignment_id}`| `CourseAssignmentController@update` | تعديل تكليف معين للمقرر |
| **DELETE**| `/courses/{course_id}/assignments/{assignment_id}`| `CourseAssignmentController@destroy` | حذف تكليف معين للمقرر |
| **GET** | `/courses/{course_id}/assignments/by-part/{part}`| `CourseAssignmentController@byPart` | تصفية التكليفات حسب الجزء (نظري/عملي) |
| **GET** | `/courses/{course_id}/assignments/total-grade`| `CourseAssignmentController@totalGrade`| جلب إجمالي درجات التكليفات المضافة |
| **GET** | `/courses/{course_id}/assessments` | `CourseAssessmentController@index` | جلب طرق تقييم المقرر الحالية |
| **POST** | `/courses/{course_id}/assessments` | `CourseAssessmentController@store` | إضافة طريقة تقييم جديدة للمقرر |
| **GET** | `/courses/{course_id}/assessments/{assessment_id}`| `CourseAssessmentController@show` | عرض تفاصيل طريقة تقييم معينة للمقرر |
| **PUT** | `/courses/{course_id}/assessments/{assessment_id}`| `CourseAssessmentController@update` | تعديل طريقة تقييم معينة للمقرر |
| **DELETE**| `/courses/{course_id}/assessments/{assessment_id}`| `CourseAssessmentController@destroy`| حذف طريقة تقييم معينة للمقرر |
| **GET** | `/courses/{course_id}/assessments/by-type/{type}`| `CourseAssessmentController@byType` | تصفية طرق التقييم حسب نوع التقييم |
| **GET** | `/courses/{course_id}/assessments/stats`| `CourseAssessmentController@stats` | جلب إحصائيات التقييم وتوزيع الأوزان |
| **GET** | `/courses/{course_id}/assessments/balance-check`| `CourseAssessmentController@balanceCheck`| التحقق من توازن أوزان الدرجات والتقييمات |
| **GET** | `/courses/{course_id}/references` | `CourseReferenceController@index` | جلب مراجع ومصادر المقرر |
| **POST** | `/courses/{course_id}/references` | `CourseReferenceController@store` | إضافة مرجع جديد للمقرر |
| **GET** | `/courses/{course_id}/references/{reference_id}`| `CourseReferenceController@show` | عرض تفاصيل مرجع معين للمقرر |
| **PUT** | `/courses/{course_id}/references/{reference_id}`| `CourseReferenceController@update` | تعديل مرجع معين للمقرر |
| **DELETE**| `/courses/{course_id}/references/{reference_id}`| `CourseReferenceController@destroy`| حذف مرجع معين للمقرر |
| **GET** | `/courses/{course_id}/references/by-type/{type}`| `CourseReferenceController@byType` | تصفية المراجع حسب النوع (رئيسي/مساند/إلكتروني) |
| **GET** | `/courses/{course_id}/references/stats`| `CourseReferenceController@stats` | جلب إحصائيات المراجع المقررة |
| **GET** | `/courses/{course_id}/policies` | `CoursePolicyController@index` | جلب سياسات وضوابط المقرر |
| **POST** | `/courses/{course_id}/policies` | `CoursePolicyController@store` | إضافة ضابط/سياسة جديدة للمقرر |
| **GET** | `/courses/{course_id}/policies/{policy_id}`| `CoursePolicyController@show` | عرض تفاصيل سياسة معينة للمقرر |
| **PUT** | `/courses/{course_id}/policies/{policy_id}`| `CoursePolicyController@update` | تعديل سياسة معينة للمقرر |
| **DELETE**| `/courses/{course_id}/policies/{policy_id}`| `CoursePolicyController@destroy` | حذف سياسة معينة للمقرر |
| **GET** | `/courses/{course_id}/policies/fixed-template`| `CoursePolicyController@fixedTemplate` | جلب القالب الافتراضي للضوابط السبعة الأساسية |
| **GET** | `/courses/{course_id}/policies/fixed-only`| `CoursePolicyController@fixedOnly` | جلب الضوابط الأساسية المفعلة فقط |
| **GET** | `/courses/{course_id}/policies/additional-only`| `CoursePolicyController@additionalOnly`| جلب الضوابط والسياسات المضافة فقط |
| **GET** | `/teaching-strategies` | `TeachingStrategyController@index` | جلب جميع استراتيجيات التدريس المعتمدة |
| **POST** | `/teaching-strategies` | `TeachingStrategyController@store` | إضافة استراتيجية تدريس جديدة |
| **GET** | `/teaching-strategies/{id}` | `TeachingStrategyController@show` | عرض تفاصيل استراتيجية تدريس معينة |
| **PUT** | `/teaching-strategies/{id}` | `TeachingStrategyController@update` | تعديل استراتيجية تدريس معينة |
| **DELETE**| `/teaching-strategies/{id}` | `TeachingStrategyController@destroy` | حذف استراتيجية تدريس معينة |
| **GET** | `/teaching-strategies/by-category/{cat}`| `TeachingStrategyController@byCategory` | تصفية الاستراتيجيات حسب الفئة المرجعية |
| **GET** | `/teaching-strategies/active-only` | `TeachingStrategyController@activeOnly` | جلب الاستراتيجيات النشطة فقط |
| **GET** | `/assessment-methods` | `AssessmentMethodController@index` | جلب جميع طرق التقييم المرجعية بالجامعة |
| **POST** | `/assessment-methods` | `AssessmentMethodController@store` | إضافة طريقة تقييم جديدة للنظام |
| **GET** | `/assessment-methods/{id}` | `AssessmentMethodController@show` | عرض تفاصيل طريقة تقييم مرجعية معينة |
| **PUT** | `/assessment-methods/{id}` | `AssessmentMethodController@update` | تعديل طريقة تقييم مرجعية معينة |
| **DELETE**| `/assessment-methods/{id}` | `AssessmentMethodController@destroy` | حذف طريقة تقييم مرجعية معينة |
| **GET** | `/assessment-methods/by-category/{cat}`| `AssessmentMethodController@byCategory` | تصفية طرق التقييم حسب الفئة المرجعية |
| **GET** | `/assessment-methods/active-only` | `AssessmentMethodController@activeOnly` | جلب طرق التقييم المرجعية النشطة فقط |
| **GET** | `/blocks` | `BlockController@index` | جلب قائمة القواطع والكتل الأكاديمية |
| **POST** | `/blocks` | `BlockController@store` | إضافة كتلة/قاطع جديد بالمشروع |
| **GET** | `/blocks/{block}` | `BlockController@show` | عرض تفاصيل كتلة/قاطع معين |
| **PUT** | `/blocks/{block}` | `BlockController@update` | تعديل تفاصيل كتلة/قاطع معين |
| **DELETE**| `/blocks/{block}` | `BlockController@destroy` | حذف كتلة/قاطع معين من قاعدة البيانات |
| **POST** | `/qa/outcomes` | `QualityAssuranceController@storeOutcome`| إضافة مخرج تعلم جودة جديد |
| **PUT** | `/qa/outcomes/{id}` | `QualityAssuranceController@updateOutcome`| تعديل مخرج تعلم جودة معين |
| **DELETE**| `/qa/outcomes/{id}` | `QualityAssuranceController@destroyOutcome`| حذف مخرج تعلم جودة معين |
| **POST** | `/qa/topics` | `QualityAssuranceController@storeTopic` | إضافة موضوع جودة جديد للمقرر |
| **PUT** | `/qa/topics/{id}` | `QualityAssuranceController@updateTopic` | تعديل موضوع جودة معين للمقرر |
| **DELETE**| `/qa/topics/{id}` | `QualityAssuranceController@destroyTopic` | حذف موضوع جودة معين للمقرر |
| **POST** | `/qa/questions` | `QualityAssuranceController@storeQuestion`| إضافة سؤال جودة جديد للاستبيان |
| **PUT** | `/qa/questions/{id}` | `QualityAssuranceController@updateQuestion`| تعديل سؤال جودة معين للاستبيان |
| **DELETE**| `/qa/questions/{id}` | `QualityAssuranceController@destroyQuestion`| حذف سؤال جودة معين للاستبيان |
| **GET** | `/sessions/{sessionId}/attachments` | `LectureAttachmentsController@index` | جلب مرفقات وملفات المحاضرة الحالية |
| **POST** | `/attachments` | `LectureAttachmentsController@store` | رفع وحفظ ملف أو رابط مرفق للمحاضرة |
| **PUT** | `/attachments/{id}` | `LectureAttachmentsController@update` | تعديل بيانات المرفق الدراسي |
| **DELETE**| `/attachments/{id}` | `LectureAttachmentsController@destroy` | حذف المرفق الدراسي نهائياً من الجلسة |

---

*(نهاية دليل المواصفات الفنية الكامل لنظام UniHub API - ملحق العقد الفني لترخيص الاستخدام)*
