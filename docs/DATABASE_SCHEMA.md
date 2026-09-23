# وثيقة قاعدة بيانات UniHub API

هذا الملف يشرح هيكل قاعدة بيانات مشروع UniHub API المستند إلى ملفات الـ migrations داخل مجلد `database/migrations`.

> ملاحظة: هذا التوثيق يعكس الجداول الأساسية والحقول المهمة في المشروع، ويُعد مرجعًا عمليًا لفهم العلاقات بين النماذج والواجهة.

---

## 1. نظرة عامة

قاعدة البيانات مقسمة إلى مجالات رئيسية:

- المستخدمون والأدوار والصلاحيات
- الكليات والأقسام والبرامج
- المواد والمقررات والجداول
- الحضور والغياب
- التقييمات وQA
- المواصفات الأكاديمية
- المالية والدفع
- الجداول المساعدة والتسجيلات

---

## 2. الجداول الأساسية (Core Tables)

### 2.1 colleges
الكلية

- college_id: المفتاح الأساسي
- college_name: اسم الكلية
- college_code: رمز الكلية
- created_at, updated_at
- deleted_at

### 2.2 user_types
أنواع المستخدمين

- user_type_id: المفتاح الأساسي
- user_type_name: اسم النوع
- user_type_code: رمز النوع
- created_at, updated_at
- deleted_at

### 2.3 permissions
الصلاحيات

- permission_id
- permission_key: مفتاح الصلاحية (مثلاً: view_courses)
- permission_name: اسم الصلاحية
- description: الوصف
- created_at, updated_at
- deleted_at

### 2.4 user_type_permissions
ترابط أنواع المستخدمين بالصلاحيات

- user_type_id
- college_id
- permission_id
- created_at, updated_at
- المفتاح الأساسي: (user_type_id, permission_id, college_id)

### 2.5 users
جدول المستخدمين العام

- user_id
- full_name
- email
- phone
- college_id
- password
- academic_number
- gender
- user_type_id
- created_at, updated_at
- deleted_at

العلاقات:
- user_type_id -> user_types.user_type_id
- college_id -> colleges.college_id

---

## 3. الهيكل الأكاديمي

### 3.1 departments
الأقسام

- department_id
- department_name
- department_code
- college_id
- created_at, updated_at
- deleted_at

### 3.2 programs
البرامج الأكاديمية

- program_id
- department_id
- program_name
- is_active
- created_at, updated_at
- deleted_at

### 3.3 levels
المستويات الدراسية

- level_id
- level_name
- program_id
- level_number
- created_at, updated_at
- deleted_at

### 3.4 semesters
الفصول الدراسية

- semester_id
- semester_name
- academic_year
- level_id
- term_number
- created_at, updated_at
- deleted_at

### 3.5 academic_titles
الألقاب العلمية / الدرجات الوظيفية

- title_id
- college_id
- title_name
- title_code
- hourly_price
- created_at, updated_at
- deleted_at

### 3.6 lecturers
أعضاء هيئة التدريس

- lecturer_id
- user_id
- college_id
- department_id
- title_id
- hire_date
- status
- can_teach_externally
- created_at, updated_at
- deleted_at

### 3.7 students
الطلاب

- student_id
- user_id
- college_id
- department_id
- level_id
- program_id
- semester_id
- block_id
- status
- created_at, updated_at
- deleted_at

### 3.8 student_groups
مجموعات الطلاب

- group_id
- college_id
- department_id
- program_id
- level_id
- semester_id
- block_id
- group_name
- created_at, updated_at
- deleted_at

### 3.9 student_group_members
أعضاء المجموعات

- student_id
- group_id
- created_at, updated_at

المفتاح الأساسي:
- (student_id, group_id)

---

## 4. المباني والقاعات والأوقات

### 4.1 buildings
المباني

- building_id
- building_name
- floors_count
- college_id
- created_at, updated_at
- deleted_at

### 4.2 classrooms
القاعات

- classroom_id
- classroom_name
- building_id
- floor
- capacity
- latitude
- longitude
- allowed_distance
- classroom_type
- created_at, updated_at
- deleted_at

### 4.3 days
أيام الأسبوع

- day_id
- day_name
- created_at, updated_at
- deleted_at

### 4.4 periods
الفترات الزمنية

- period_id
- college_id
- period_name
- start_time
- end_time
- session_type
- created_at, updated_at
- deleted_at

---

## 5. الجداول الأكاديمية: المقررات والجدول

### 5.1 courses
المقررات

- course_id
- course_name
- course_code
- course_type
- is_active
- college_id
- department_id
- program_id
- level_id
- semester_id
- block_id
- credit_hours
- is_elective
- notes
- course_parts
- weight
- category
- teaching_language
- created_at, updated_at
- deleted_at

### 5.2 course_prerequisites
متطلبات المقررات السابقة/المصاحبة

- id
- course_id
- prerequisite_course_id
- type: prerequisite أو corequisite
- created_at, updated_at

### 5.3 blocks
البلوكات الأكاديمية (خطة أو نظام تدريبي)

- id
- block_name
- block_number
- weight
- credit_hours
- weeks
- type: compulsory أو elective
- program_id
- level_id
- description
- created_at, updated_at

### 5.4 block_relations
علاقات بين البلوكات

- id
- block_id
- related_block_id
- relation_type: prerequisite, concurrent, next
- created_at, updated_at

### 5.5 timetable
جدول المحاضرات

- timetable_id
- course_id
- lecturer_id
- group_id
- level_id
- classroom_id
- day_id
- period_id
- lecture_type
- status
- start_date
- end_date
- academic_year
- college_id
- department_id
- gender_type
- lecture_hours
- created_at, updated_at

القيود الرئيسية:
- classroom_id + day_id + period_id unique
- lecturer_id + day_id + period_id unique
- group_id + day_id + period_id unique

---

## 6. الجلسات، الحضور، والQR

### 6.1 lecture_sessions
جلسات المحاضرة الفعلية

- session_id
- timetable_id
- session_date
- start_time
- end_time
- actual_classroom_id
- actual_attendance_count
- session_code
- status
- attendance_overage_alert
- system_attendance_count
- actual_end_time
- end_latitude
- end_longitude
- is_ended_remotely
- created_at, updated_at

### 6.2 lecture_attachments
مرفقات الجلسات

- attachment_id
- session_id
- type: video, file, link
- title
- url
- file_size
- created_at, updated_at

### 6.3 lecturer_attendance
حضور المحاضرين

- attendance_id
- lecturer_id
- timetable_id
- attendance_date
- status
- notification_status
- college_id
- lecture_hours
- hourly_rate_at_attendance
- lecture_rate_at_attendance
- session_code
- created_at, updated_at

### 6.4 student_attendance
حضور الطلاب

- attendance_id
- student_id
- timetable_id
- level_id
- attendance_date
- status
- notification_status
- college_id
- department_id
- session_code
- solved
- created_at, updated_at

### 6.5 qr_codes
رموز الاستجابة السريعة (QR)

- qr_id
- timetable_id
- session_id
- qr_code_value
- generated_at
- expires_at
- is_active
- created_by
- latitude
- longitude
- allowed_distance
- created_at, updated_at
- deleted_at

### 6.6 session_topics_covered
محتوى الموضوعات التي تم تغطيتها في الجلسة

- session_id
- topic_id
- coverage_status
- created_at, updated_at

المفتاح الأساسي:
- (session_id, topic_id)

---

## 7. الطلبات والإشعارات

### 7.1 lecturer_group_notifications
إشعارات مجموعات المحاضرين

- notification_id
- lecturer_user_id
- subject
- message_body
- send_at
- group_id
- is_sent
- is_seen
- created_at, updated_at

### 7.2 notification_reads
قراءة الإشعارات

- read_id
- user_id
- notification_id
- read_at

### 7.3 makeup_lectures_requests
طلبات المحاضرات الاستدراكية

- request_id
- lecturer_id
- course_id
- group_id
- requested_date
- status
- notification_status
- created_at, updated_at

### 7.4 student_excuse_submissions
تقديم الأعذار من الطلاب

- submission_id
- student_user_id
- request_date
- reason
- course_id
- lecturer_user_id
- is_lecturer_notified
- response_status
- lecturer_comment
- excuse_image
- created_at, updated_at

---

## 8. التقييمات وQA

### 8.1 course_assessments
تقييمات المقرر (رأس التقييم)

- assessment_id
- college_id
- course_id
- group_id
- semester_id
- created_by
- academic_year
- name
- max_score
- weight
- created_at, updated_at
- deleted_at

### 8.2 student_grades
درجات الطلاب لكل تقييم

- grade_id
- assessment_id
- student_id
- score
- notes
- created_at, updated_at

### 8.3 qa_forms
نماذج تقييم الجودة

- form_id
- title
- description
- target_type
- is_active
- college_id
- academic_year
- created_at, updated_at
- deleted_at

### 8.4 qa_domains
مجالات الاستبيان

- domain_id
- form_id
- domain_name
- sort_order
- created_at, updated_at

### 8.5 qa_questions
أسئلة الاستبيان

- question_id
- domain_id
- question_text
- sort_order
- created_at, updated_at

### 8.6 qa_campaigns
حملات تقييم الجودة

- campaign_id
- campaign_name
- form_id
- semester_id
- start_date
- end_date
- is_published
- target_percentage
- created_at, updated_at

### 8.7 qa_campaign_assignments
تخصيص الحملات إلى جداول معينة

- assignment_id
- campaign_id
- timetable_id
- created_at, updated_at

### 8.8 qa_submissions
استجابات الطلاب للتقييم

- submission_id
- campaign_id
- student_id
- lecturer_id
- course_id
- submission_date_timestamp
- is_practical
- created_at, updated_at

### 8.9 qa_answers
تفاصيل الإجابات

- answer_id
- submission_id
- question_id
- rating_value
- created_at, updated_at

---

## 9. المواصفات الأكاديمية للمقرر

### 9.1 program_learning_outcomes
مخرجات تعلم البرنامج (PLOs)

- plo_id
- program_id
- code
- domain
- description
- weight
- is_active
- order
- created_at, updated_at
- deleted_at

### 9.2 course_descriptions
وصف المقرر

- id
- course_id
- description
- goals
- word_count
- goals_count
- is_completed
- is_approved
- approved_by
- approval_date
- created_at, updated_at

### 9.3 course_learning_outcomes
مخرجات تعلم المقرر (CLOs)

- clo_id
- course_id
- code
- domain
- description
- weight
- plo_id
- plo_weight
- order
- is_active
- created_at, updated_at
- deleted_at

### 9.4 course_topics
موضوعات المقرر

- topic_id
- course_id
- part
- week
- unit_name
- subtopics
- is_exam
- exam_type
- hours
- clo_ids
- order
- notes
- created_at, updated_at
- deleted_at

### 9.5 topic_questions
أسئلة الموضوعات

- question_id
- topic_id
- subtopic
- question_text
- question_type
- difficulty_level
- clo_code
- options
- correct_answer
- is_used_in_exam
- is_active
- usage_count
- order
- created_at, updated_at
- deleted_at

### 9.6 course_assignments
تكاليف/واجبات المقرر

- assignment_id
- course_id
- part
- title
- description
- week
- grade
- clo_ids
- assignment_type
- is_mandatory
- notes
- created_at, updated_at

### 9.7 teaching_strategies
استراتيجيات التدريس

- id
- program_id
- name
- created_at, updated_at

### 9.8 assessment_methods
طرق التقييم

- id
- program_id
- name
- created_at, updated_at

### 9.9 outcome_teaching_strategy
ربط مخرجات التعلم باستراتيجيات التدريس

- outcome_id
- teaching_strategy_id
- plo_id أو clo_id

### 9.10 outcome_assessment_method
ربط مخرجات التعلم بطرق التقييم

- outcome_id
- assessment_method_id
- plo_id أو clo_id

### 9.11 course_references
مراجع المقرر

- id
- course_id
- title
- author
- source
- created_at, updated_at

### 9.12 course_policies
سياسات المقرر

- id
- course_id
- policy_name
- policy_value
- created_at, updated_at

---

## 10. المالية والدفع

### 10.1 financial_cycles
دورات الدفع

- cycle_id
- college_id
- month_year
- start_date
- end_date
- status
- created_by
- total_payout
- lecturers_count
- created_at, updated_at
- deleted_at

### 10.2 lecturer_payouts
مستحقات المحاضرين

- payout_id
- cycle_id
- lecturer_id
- total_hours
- hourly_rate
- base_amount
- total_bonuses
- total_deductions
- tax_amount
- net_amount
- status
- notes
- created_at, updated_at

### 10.3 payout_adjustments
تعديلات الدفع

- adjustment_id
- payout_id
- type: bonus, deduction, tax
- amount
- reason
- is_automatic
- created_at, updated_at

---

## 11. الأجهزة والتحقق

### 11.1 user_devices
أجهزة المستخدمين

- device_id
- user_id
- device_name
- mac_address
- os_type
- is_auto_attendance_enabled
- registered_at
- last_login_at

### 11.2 otp_device_verifications
التحقق من OTP للجهاز

- verification_id
- user_id
- otp_code
- device_name
- mac_address
- os_type
- delivery_status
- is_verified
- expires_at
- created_at, updated_at

### 11.3 user_activities
سجل أنشطة المستخدم

- activity_id
- user_id
- action_type
- action_description
- module_name
- created_at, updated_at

### 11.4 ip_restrictions
قيود IP

- id
- ip_address
- description
- is_active
- created_at, updated_at

---

## 12. الجداول النظامية لـ Laravel

هذه الجداول تُنشئها Laravel نفسها وتستخدم في المشروع بشكل أساسي:

- oauth_access_tokens
- oauth_refresh_tokens
- oauth_auth_codes
- oauth_clients
- oauth_device_codes
- password_reset_tokens
- cache
- cache_locks
- settings

---

## 13. أهم العلاقات في المشروع

1. المستخدم -> النوع -> الكلية
2. الكلية -> الأقسام -> البرامج -> المستويات -> الفصول
3. المقرر -> الكلية/القسم/البرنامج/المستوى/الفصل
4. المحاضر -> جدول timetable -> lecture_sessions
5. الطلاب -> student_groups -> student_attendance
6. المقرر -> course_assessments -> student_grades
7. QA -> qa_forms -> qa_domains -> qa_questions
8. QA -> qa_campaigns -> qa_submissions -> qa_answers
9. البرنامج -> PLOs -> CLOs -> course_topics
10. المحاضر -> lecturer_payouts -> payout_adjustments

---

## 14. ملاحظات تصميمية مهمة

- تستخدم معظم الجداول `softDeletes` في النظام الأساسي.
- هناك قيم `status` و`notification_status` كثيرة تُستخدم لتتبع حالة الطلب، الحضور، التقييم، وعمليات الدفع.
- العلاقة بين الجداول غالبًا تعتمد على `unsignedInteger` و`foreign` keys.
- الجداول الكبيرة مثل `timetable` و `student_attendance` و `qa_submissions` تُستخدم مركزياً للتقارير والتحليل.
- بعض الجداول تم تعديلها لاحقًا عبر migrations مثل `programs`, `courses`, `students`, `student_groups`, `qa_campaigns`, و `lecture_sessions`.

---

## 15. الملف المرجعي

تم استخراج هذا التوثيق من ملفات:

- `database/migrations/*.php`
- `app/Models/*.php`

إذا أردت، يمكنني أيضًا إعداد نسخة ثانية من هذا الملف بصيغة أكثر تفصيلاً تحتوي على:

- جدول لكل جدول مع أسماء الأعمدة + نوع العمود + مثال بيانات
- ER Diagram بصيغة Mermaid
- تصدير CSV/Markdown من الجداول فقط
- نسخة عربية/إنجليزية متوازنة
