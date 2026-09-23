# 📚 UniHub API - Complete Documentation

> Laravel REST API for University Management System

**Last Updated:** 2026-08-18 07:06:32

---

## 📖 Table of Contents

1. [Quick Start](#quick-start)
2. [API Documentation](#api-documentation)
3. [Controllers Reference](#controllers-reference)
4. [Models & Database](#models--database)
5. [Authentication](#authentication)
6. [Common Errors](#common-errors)

---

## 🚀 Quick Start

### Installation

```bash
# Clone repository
git clone [repository-url]
cd unihub-API

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=unihub20
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate --seed

# Start development server
php artisan serve
```

### Base URL

```
http://192.168.8.105/unihub-api/api/v1
```

---

## 📡 API Documentation

**Complete API Reference:** [docs/API_ROUTES_DETAILED.md](docs/API_ROUTES_DETAILED.md)

This file contains:
- ✅ All API endpoints
- ✅ HTTP methods (GET, POST, PUT, DELETE)
- ✅ Request body examples (JSON)
- ✅ Success response examples
- ✅ Error response examples
- ✅ Required middleware
- ✅ Authentication requirements

### Quick Example: Login

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

---

## 🎮 Controllers Reference

**Complete Controllers Guide:** [docs/CONTROLLERS_DETAILED.md](docs/CONTROLLERS_DETAILED.md)

This file contains:
- ✅ All controller classes
- ✅ Every public method with description
- ✅ Method parameters and return types
- ✅ Which routes use each method
- ✅ Purpose of each function

---

## 📦 Models & Database

**Complete Models Reference:** [docs/MODELS_DETAILED.md](docs/MODELS_DETAILED.md)

This file contains:
- ✅ All Eloquent models
- ✅ Database table mappings
- ✅ Fillable and hidden fields
- ✅ Type casts
- ✅ Relationships (hasMany, belongsTo, etc.)
- ✅ Query scopes
- ✅ Usage examples

---

## 🔐 Authentication

This API uses **Laravel Sanctum** for authentication.

### Getting Access Token

```bash
POST /api/v1/auth/login
```

### Using Token

```bash
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## ⚠️ Common Errors

| Status Code | Meaning |
|-------------|----------|
| 200 | Success |
| 401 | Unauthorized (invalid/missing token) |
| 403 | Forbidden (no permission) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 📊 Project Statistics

- **Total Routes:** 316
- **API Routes:** 297
- **Controllers:** 59
- **Models:** 62

---

*📝 Documentation auto-generated with full details*




```
unihub-API
├─ .dockerignore
├─ .env
├─ .env.example
├─ .phpunit.result.cache
├─ app
│  ├─ Console
│  │  └─ Commands
│  │     └─ AutoCloseSessions.php
│  ├─ Http
│  │  ├─ Controllers
│  │  │  ├─ Admin
│  │  │  │  └─ RoutesController.php
│  │  │  ├─ Api
│  │  │  │  └─ V1
│  │  │  │     ├─ AcademicTitlesController.php
│  │  │  │     ├─ Admin
│  │  │  │     │  ├─ IpRestrictionController.php
│  │  │  │     │  ├─ SettingsController.php
│  │  │  │     │  └─ SystemController.php
│  │  │  │     ├─ AssessmentMethodController.php
│  │  │  │     ├─ AuthController.php
│  │  │  │     ├─ AuthPasswordController.php
│  │  │  │     ├─ BlockController.php
│  │  │  │     ├─ BuildingsController.php
│  │  │  │     ├─ ClassroomsController.php
│  │  │  │     ├─ CollegesController.php
│  │  │  │     ├─ CourseAssessmentController.php
│  │  │  │     ├─ CourseAssignmentController.php
│  │  │  │     ├─ CourseDescriptionController.php
│  │  │  │     ├─ CourseLearningOutcomeController.php
│  │  │  │     ├─ CourseOutcomeMappingController.php
│  │  │  │     ├─ CoursePolicyController.php
│  │  │  │     ├─ CourseReferenceController.php
│  │  │  │     ├─ CoursesController.php
│  │  │  │     ├─ CourseTopicController.php
│  │  │  │     ├─ DashboardController.php
│  │  │  │     ├─ DaysController.php
│  │  │  │     ├─ DepartmentsController.php
│  │  │  │     ├─ FinancialController.php
│  │  │  │     ├─ LectureAttachmentsController.php
│  │  │  │     ├─ Lecturer
│  │  │  │     ├─ LecturerAttendanceController.php
│  │  │  │     ├─ LecturerGradebookController.php
│  │  │  │     ├─ LecturersController.php
│  │  │  │     ├─ LectureSessionController.php
│  │  │  │     ├─ LevelsController.php
│  │  │  │     ├─ LookupsController.php
│  │  │  │     ├─ MakeupLecturesController.php
│  │  │  │     ├─ NotificationsController.php
│  │  │  │     ├─ PeriodsController.php
│  │  │  │     ├─ ProgramLearningOutcomeController.php
│  │  │  │     ├─ ProgramOptionAuditController.php
│  │  │  │     ├─ ProgramsController.php
│  │  │  │     ├─ QA
│  │  │  │     │  ├─ Admin
│  │  │  │     │  │  ├─ QaCampaignsController.php
│  │  │  │     │  │  └─ QaManagerController.php
│  │  │  │     │  ├─ Reports
│  │  │  │     │  │  ├─ CourseExecutionReportController.php
│  │  │  │     │  │  └─ QaAnalysisController.php
│  │  │  │     │  └─ Student
│  │  │  │     │     └─ QaEvaluationController.php
│  │  │  │     ├─ QrCodesController.php
│  │  │  │     ├─ QualityAssuranceController.php
│  │  │  │     ├─ ReportsController.php
│  │  │  │     ├─ SemestersController.php
│  │  │  │     ├─ StudentAttendanceController.php
│  │  │  │     ├─ StudentExcusesController.php
│  │  │  │     ├─ StudentGroupsController.php
│  │  │  │     ├─ StudentsController.php
│  │  │  │     ├─ SyncController.php
│  │  │  │     ├─ TeachingStrategyController.php
│  │  │  │     ├─ TimetableController.php
│  │  │  │     ├─ TopicQuestionController.php
│  │  │  │     ├─ UniversityReportController.php
│  │  │  │     ├─ UserDevicesController.php
│  │  │  │     ├─ UsersController.php
│  │  │  │     ├─ UserTypeController.php
│  │  │  │     └─ UserTypePermissionController.php
│  │  │  └─ Controller.php
│  │  ├─ Middleware
│  │  │  ├─ CheckIpRestrictions.php
│  │  │  ├─ CheckUserType.php
│  │  │  ├─ HasPermission.php
│  │  │  └─ LogUserActivity.php
│  │  ├─ Requests
│  │  │  ├─ Auth
│  │  │  │  ├─ ForgotPasswordRequest.php
│  │  │  │  ├─ LoginRequest.php
│  │  │  │  └─ ResetPasswordRequest.php
│  │  │  └─ V1
│  │  │     ├─ AcademicTitle
│  │  │     │  ├─ StoreAcademicTitleRequest.php
│  │  │     │  └─ UpdateAcademicTitleRequest.php
│  │  │     ├─ AppVersion
│  │  │     │  ├─ StoreAppVersionRequest.php
│  │  │     │  └─ UpdateAppVersionRequest.php
│  │  │     ├─ Attendance
│  │  │     │  └─ ScanAttendanceRequest.php
│  │  │     ├─ Building
│  │  │     │  ├─ StoreBuildingRequest.php
│  │  │     │  └─ UpdateBuildingRequest.php
│  │  │     ├─ Classroom
│  │  │     │  ├─ StoreClassroomRequest.php
│  │  │     │  └─ UpdateClassroomRequest.php
│  │  │     ├─ College
│  │  │     │  ├─ StoreCollegeRequest.php
│  │  │     │  └─ UpdateCollegeRequest.php
│  │  │     ├─ Course
│  │  │     │  ├─ StoreCourseRequest.php
│  │  │     │  └─ UpdateCourseRequest.php
│  │  │     ├─ Day
│  │  │     │  ├─ StoreDayRequest.php
│  │  │     │  └─ UpdateDayRequest.php
│  │  │     ├─ Department
│  │  │     │  ├─ StoreDepartmentRequest.php
│  │  │     │  └─ UpdateDepartmentRequest.php
│  │  │     ├─ Device
│  │  │     │  └─ VerifyOtpRequest.php
│  │  │     ├─ Excuse
│  │  │     │  └─ StoreExcuseRequest.php
│  │  │     ├─ LectureSession
│  │  │     │  ├─ StoreLectureSessionRequest.php
│  │  │     │  └─ UpdateLectureSessionRequest.php
│  │  │     ├─ Level
│  │  │     │  ├─ StoreLevelRequest.php
│  │  │     │  └─ UpdateLevelRequest.php
│  │  │     ├─ MakeupLecture
│  │  │     │  ├─ ReviewMakeupLectureRequest.php
│  │  │     │  ├─ ScheduleMakeupLectureRequest.php
│  │  │     │  └─ StoreMakeupLectureRequest.php
│  │  │     ├─ Notification
│  │  │     │  └─ StoreNotificationRequest.php
│  │  │     ├─ Period
│  │  │     │  ├─ StorePeriodRequest.php
│  │  │     │  └─ UpdatePeriodRequest.php
│  │  │     ├─ Program
│  │  │     │  ├─ StoreProgramRequest.php
│  │  │     │  └─ UpdateProgramRequest.php
│  │  │     ├─ QrCode
│  │  │     │  └─ StoreQrCodeRequest.php
│  │  │     ├─ QRRefreshOption
│  │  │     │  ├─ StoreQRRefreshOptionRequest.php
│  │  │     │  └─ UpdateQRRefreshOptionRequest.php
│  │  │     ├─ Semester
│  │  │     │  ├─ StoreSemesterRequest.php
│  │  │     │  └─ UpdateSemesterRequest.php
│  │  │     ├─ StoreUserRequest.php
│  │  │     ├─ StudentGroup
│  │  │     │  ├─ StoreStudentGroupRequest.php
│  │  │     │  └─ UpdateStudentGroupRequest.php
│  │  │     ├─ Timetable
│  │  │     │  ├─ StoreTimetableRequest.php
│  │  │     │  └─ UpdateTimetableRequest.php
│  │  │     ├─ UpdateUserRequest.php
│  │  │     └─ UserType
│  │  │        ├─ StoreUserTypeRequest.php
│  │  │        └─ UpdateUserTypeRequest.php
│  │  └─ Resources
│  │     └─ V1
│  │        └─ UserResource.php
│  ├─ Jobs
│  │  └─ TimetableImportJob.php
│  ├─ Models
│  │  ├─ AcademicTitle.php
│  │  ├─ AppVersion.php
│  │  ├─ AssessmentMethod.php
│  │  ├─ Block.php
│  │  ├─ BlockRelation.php
│  │  ├─ Building.php
│  │  ├─ Classroom.php
│  │  ├─ College.php
│  │  ├─ Course.php
│  │  ├─ CourseAssessment.php
│  │  ├─ CourseAssignment.php
│  │  ├─ CourseDescription.php
│  │  ├─ CourseLearningOutcome.php
│  │  ├─ CoursePolicy.php
│  │  ├─ CoursePrerequisite.php
│  │  ├─ CourseReference.php
│  │  ├─ CourseTopic.php
│  │  ├─ Day.php
│  │  ├─ Department.php
│  │  ├─ DepartmentProgram.php
│  │  ├─ FinancialCycle.php
│  │  ├─ IpRestriction.php
│  │  ├─ LearningOutcome.php
│  │  ├─ LectureAttachment.php
│  │  ├─ Lecturer.php
│  │  ├─ LecturerAttendance.php
│  │  ├─ LecturerGroupNotification.php
│  │  ├─ LecturerPayout.php
│  │  ├─ LectureSession.php
│  │  ├─ Level.php
│  │  ├─ MakeupLecturesRequest.php
│  │  ├─ OtpDeviceVerification.php
│  │  ├─ PayoutAdjustment.php
│  │  ├─ Period.php
│  │  ├─ Permission.php
│  │  ├─ Program.php
│  │  ├─ ProgramLearningOutcome.php
│  │  ├─ ProgramOptionAudit.php
│  │  ├─ QA
│  │  │  ├─ QaAnswer.php
│  │  │  ├─ QaCampaign.php
│  │  │  ├─ QaCampaignAssignment.php
│  │  │  ├─ QaDomain.php
│  │  │  ├─ QaForm.php
│  │  │  ├─ QaQuestion.php
│  │  │  └─ QaSubmission.php
│  │  ├─ QaQuestion.php
│  │  ├─ QaQuestionOption.php
│  │  ├─ QrCode.php
│  │  ├─ Semester.php
│  │  ├─ Student.php
│  │  ├─ StudentAttendance.php
│  │  ├─ StudentExcuseSubmission.php
│  │  ├─ StudentGrade.php
│  │  ├─ StudentGroup.php
│  │  ├─ StudentGroupMember.php
│  │  ├─ TeachingStrategy.php
│  │  ├─ Timetable.php
│  │  ├─ TopicQuestion.php
│  │  ├─ User.php
│  │  ├─ UserActivity.php
│  │  ├─ UserDevice.php
│  │  ├─ UserType.php
│  │  └─ UserTypePermission.php
│  ├─ Notifications
│  │  ├─ ResetPasswordNotification.php
│  │  └─ SendOtpNotification.php
│  ├─ Policies
│  │  └─ UserDevicePolicy.php
│  ├─ Providers
│  │  └─ AppServiceProvider.php
│  ├─ Rules
│  │  └─ UniqueGroupInPath.php
│  └─ Services
│     ├─ ConflictDetector.php
│     └─ ScheduleResolver.php
├─ artisan
├─ bootstrap
│  ├─ app.php
│  ├─ cache
│  │  ├─ config.php
│  │  ├─ packages.php
│  │  ├─ routes-v7.php
│  │  └─ services.php
│  └─ providers.php
├─ composer.json
├─ composer.lock
├─ config
│  ├─ app.php
│  ├─ auth.php
│  ├─ cache.php
│  ├─ cors.php
│  ├─ database.php
│  ├─ filesystems.php
│  ├─ logging.php
│  ├─ mail.php
│  ├─ passport.php
│  ├─ queue.php
│  ├─ services.php
│  └─ session.php
├─ database
│  ├─ database.sqlite
│  ├─ factories
│  │  └─ UserFactory.php
│  ├─ migrations
│  │  ├─ 2025_10_21_192024_create_oauth_auth_codes_table.php
│  │  ├─ 2025_10_21_192025_create_oauth_access_tokens_table.php
│  │  ├─ 2025_10_21_192026_create_oauth_refresh_tokens_table.php
│  │  ├─ 2025_10_21_192027_create_oauth_clients_table.php
│  │  ├─ 2025_10_21_192028_create_oauth_device_codes_table.php
│  │  ├─ 2025_10_21_220233_create_password_reset_tokens_table.php
│  │  ├─ 2025_10_22_144503_create_cache_table.php
│  │  ├─ 2025_10_23_095837_create_settings_table.php
│  │  ├─ 2025_10_26_182508_create_university_schema.php
│  │  ├─ 2025_12_19_155452_drop_strict_unique_indexes_from_timetable.php
│  │  ├─ 2025_12_29_165656_add_details_to_lecture_sessions_table.php
│  │  ├─ 2026_01_05_172318_remove_attendance_columns_from_lecture_sessions_table.php
│  │  ├─ 2026_01_07_154740_create_course_assessment_tables.php
│  │  ├─ 2026_01_08_212450_create_ip_restrictions_table.php
│  │  ├─ 2026_01_18_160543_update_makeup_lectures_requests_table.php
│  │  ├─ 2026_01_27_150833_create_notification_reads_and_modify_excuse_image.php
│  │  ├─ 2026_02_01_201759_create_quality_assurance_tables.php
│  │  ├─ 2026_02_02_190151_update_qa_campaigns_structure.php
│  │  ├─ 2026_02_02_213019_add_timetable_id_to_qa_campaigns.php
│  │  ├─ 2026_02_11_214751_restructure_qa_campaigns.php
│  │  ├─ 2026_02_23_021027_enhance_lecture_management_schema.php
│  │  ├─ 2026_02_25_005743_add_time_tracking_columns.php
│  │  ├─ 2026_02_26_024031_add_device_identifier_and_path.php
│  │  ├─ 2026_04_08_211118_add_system_columns_to_programs_table.php
│  │  ├─ 2026_04_09_233234_create_blocks_table.php
│  │  ├─ 2026_04_09_233239_create_block_relations_table.php
│  │  ├─ 2026_04_15_222411_enhance_courses_table_for_all_systems.php
│  │  ├─ 2026_04_18_213310_remove_is_elective_from_courses.php
│  │  ├─ 2026_04_21_215943_create_course_specification_tables.php
│  │  ├─ 2026_04_26_184525_drop_unused_columns_from_course_descriptions.php
│  │  ├─ 2026_08_19_000001_add_program_scope_and_audit_to_course_options.php
│  │  ├─ 2026_08_21_000000_align_course_assessments_for_course_specifications.php
│  │  ├─ 2026_08_21_000001_allow_course_level_question_bank.php
│  │  ├─ 2026_08_21_000001_make_course_assessment_context_optional.php
│  │  ├─ 2026_08_21_000002_backfill_question_course_part.php
│  │  ├─ 2026_08_22_000000_enhance_buildings_and_classrooms_for_field_survey.php
│  │  ├─ 2026_08_23_000001_update_student_path_for_program_variants.php
│  │  ├─ 2026_08_23_000010_add_max_students_to_student_groups_table.php
│  │  ├─ 2026_08_23_000020_make_student_level_nullable_for_credit_programs.php
│  │  ├─ 2026_08_23_100000_add_path_columns_to_timetable.php
│  │  ├─ 2026_08_23_120000_make_timetable_path_columns_nullable.php
│  │  └─ 2026_08_24_000001_create_session_topics_covered_table.php
│  ├─ seeders
│  │  ├─ DatabaseSeeder.php
│  │  ├─ DaysSeeder.php
│  │  ├─ InitialCollegeSeeder.php
│  │  ├─ PermissionsSeeder.php
│  │  ├─ SettingsSeeder.php
│  │  └─ UserTypesSeeder.php
│  └─ sql
│     └─ unihub20.sql
├─ Dockerfile
├─ docs
│  ├─ API_DOCUMENTATION.md
│  ├─ API_DOCUMENTATION_FIXED.md
│  ├─ generate_docs.py
│  ├─ parse_routes.py
│  └─ routes_parsed.json
├─ INSTALLATION_GUIDE.md
├─ package-lock.json
├─ package.json
├─ public
│  ├─ .htaccess
│  ├─ favicon.ico
│  ├─ index.php
│  ├─ logo.png
│  └─ storage
│     ├─ colleges
│     └─ lecture_files
├─ README.md
├─ render-start.sh
├─ resources
│  ├─ css
│  │  └─ app.css
│  ├─ js
│  │  ├─ app.js
│  │  └─ bootstrap.js
│  └─ views
│     ├─ admin
│     │  └─ routes
│     │     └─ index.blade.php
│     p
├─ routes
│  ├─ api.php
│  ├─ console.php
│  └─ web.php
├─ storage
│  ├─ app
│  │  ├─ private
│  │  │  └─ imports
│  │  └─ public
│  │     ├─ colleges
│  │     └─ lecture_files
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  ├─ sessions
│  │  │  ├─ 1FbJqoEV5kLLBlt8q35gECRBzNtXhUXXdbiPxtpy
│  │  │  ├─ 1xQdeHuOEvC4sclGsVpVzvbs9c5uOuftjqguMyvh
│  │  │  ├─ 6Rhi1NjYLZRVlWXRxv1JY6ZVemRHXGz7pt4sPM2E
│  │  │  ├─ 7ZaAWbfmXSad0H4XtNHLS7tY3RPTlsxyQPnmpnqb
│  │  │  ├─ 8AuOhGBrRV13OtV2SkZKFVskRx4LsyPpUevBpQmd
│  │  │  ├─ AfpizfKrAwSj8yeIp70B9dvzf8ts8IeeVAAYS1Qy
│  │  │  ├─ B0BzEcZNI5sBZKfkzdT8LIUJTsdwtKg5ceOEgZzo
│  │  │  ├─ beizv5mUw6rSC6EQTDJ7gAPYA2vuCPuZ35IZebeW
│  │  │  ├─ DXFgeYUccI9x9ey3AjIauC3B3Eyo1pgcqafvTtlA
│  │  │  ├─ Eeks0h55115YVoNH61URBK8wqkAzUOfqCC1TBWEg
│  │  │  ├─ Gnn3AjWvwVSl1uf2Y4av5GSmVcxotQLfiQFquVM6
│  │  │  ├─ INKxQnYiAMA1dDOmyg8ufvgCaptl4rOWKQLvnKC9
│  │  │  ├─ ja8YCU9qiNhx06HZziU7FbT2HdTtCtMmrEt84HjY
│  │  │  ├─ jdGmoRt2IkgV757vlOZADUAOC19Y4jYudJXwCHAH
│  │  │  ├─ Jn9yx8v7JudUsxEE3h2w3ZcqRszSym5S8buLrmgF
│  │  │  ├─ kuzv5q0eBHNKIS4VOHrlMuq9Tlwe9bMZPgsDAbVI
│  │  │  ├─ lYXxoTvatS9Wdx69w2eKGUu0yRWi0WZfApYGRvVQ
│  │  │  ├─ mBj3FnBks2qXuSEyd37ZFkbGKQgbrYvFkY5mHW4n
│  │  │  ├─ mQYGIt1zxxQQ1vvcuqgMEKFu8NJCX5xq98Y8GxJi
│  │  │  ├─ NIuhFTshjUDTUnChATNKzy8ikibW1SMiXEHf1v5b
│  │  │  ├─ oyoyIvV0C5y1Ay0rI3GcfLKgAqZwph7NK6c2gdVs
│  │  │  ├─ Q1ASHPF1uaAhBtnM5CFRkV7SArXENXBAus95BnMC
│  │  │  ├─ QygfiLJrN8dHXsQduwvlzlHJjkAvzlT95nXGr7Pl
│  │  │  ├─ smmx4BmhNGXWmECrDAN5Algajh4cLIGQjGPVjQDJ
│  │  │  ├─ UsbQn9VRrqWvYo0Zpb7sM9RTgVqxY49oxpYwUiVn
│  │  │  ├─ W9RSeMoOm8noR40aCsfIVzeEKQX7mo01G58jP3A5
│  │  │  ├─ X4yhGnKGD8AoTW5EAe1qRtZXJKVXujGjN83cbTEH
│  │  │  ├─ XvlVtqoDZ3H0XrvxeaMHElKl7wS0x0FsROSujftO
│  │  │  └─ ZSYbtkzGYwghjjdFlnduWoJyNq15nI8JXbosz0CZ
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 10c6b6e3f14f393ad5e26cd5440ebf37.php
│  │     ├─ 119b1f01b04343b4965faeb8b39e5e79.php
│  │     ├─ 1242ae89d4cf4a5746fe61b4c2e5ce1b.php
│  │     ├─ 1575c544dc7358ff84995dd86f2f8a6b.php
│  │     ├─ 1933e41b0b774070e042cd34dd025cea.php
│  │     ├─ 19ceafb63ba38f5abcf387224bb5d7c9.php
│  │     ├─ 1aec0f8241fdc7086c7198b2e77301d7.php
│  │     ├─ 1e41331a8189f381e7009b0e0105e010.php
│  │     ├─ 1fb948e864ab0b0d8b4c276a89190399.php
│  │     ├─ 2996e30a699670a4aabe5784cfbd151a.php
│  │     ├─ 2d202fb9e7e14705c55fe7bc8b2093d0.php
│  │     ├─ 3638dfc03ab870f31eec1d49e23f0e9c.php
│  │     ├─ 3ca55e4a49ade9f278d774e3406604d6.php
│  │     ├─ 3db2de9d984248a27f241be366b74ab2.php
│  │     ├─ 411acf664b311a8a582575713f5cc25d.php
│  │     ├─ 46654273c74e85125a2213d692f3846c.php
│  │     ├─ 4821db30a4f4891f6021b6a13f291357.php
│  │     ├─ 499040a8426be034538cc58fc3caacbe.php
│  │     ├─ 4a910e932aa13003c89fb134ddb27bb5.php
│  │     ├─ 542b206c6119c4a325b1e83d54f37a46.php
│  │     ├─ 5477eeddbe14ad7e9a7925a2e2ffaead.php
│  │     ├─ 59c84a759b1377e392036c449fc9aa48.php
│  │     ├─ 5aef0c6da982b2729923a78179fc46cd.php
│  │     ├─ 6099d91c5495bda34b888af7fd730036.php
│  │     ├─ 60bd7b7fed819f0ec184b209d518368a.php
│  │     ├─ 637bab6463aad6ae192c7c24e2cc957d.php
│  │     ├─ 63ab3ef3259b148588ee07e467a7d43d.php
│  │     ├─ 63b7930bf45c2357caf4da313b1911b2.php
│  │     ├─ 6a3e1339ce56a7976c3b6bed18dda41f.php
│  │     ├─ 6b9fe322d129561897123ce354184aec.php
│  │     ├─ 6de07d26d3be09ee4f2b600b37cabec5.php
│  │     ├─ 6f2e6a18da650398a7df5d85bb163d8a.php
│  │     ├─ 70fd1788db11e9186c4c20baa48a5849.php
│  │     ├─ 74fee4eb47194885ae1889cd12b11581.php
│  │     ├─ 776589c4eaa49b939e11a93513a199f8.php
│  │     ├─ 788b7d660a1b91f2c346eaa81fbdfba3.php
│  │     ├─ 81c8a9970a0909b9418d6a5751cf9d05.php
│  │     ├─ 820815eba683341cd4eb68df68fc5cc0.php
│  │     ├─ 8238716a9d633c8ae35b9f56ad4fd069.php
│  │     ├─ 83adc364e9be9d11c67f1ccc8520b60b.php
│  │     ├─ 8d3f10b7177f228e81686da5e4d68283.php
│  │     ├─ 917fea6fb5bf1b80cce3b57ffa6bab33.php
│  │     ├─ 926f89cebfecfa94578858514b216a8e.php
│  │     ├─ 94f74de1198815ca94cdcec2a5680eec.php
│  │     ├─ 96159d3def55b0623ee4a0ee16d08337.php
│  │     ├─ 993059b935584d33b060e01b927c8210.php
│  │     ├─ 9e854f54609446bf8ee02796cb9e7552.php
│  │     ├─ a13980d9e1cc70d99c2333af60567166.php
│  │     ├─ a5ca59a1a36d2d5ceced6b3b36374cb9.php
│  │     ├─ b7e0627183f331818fea5ccfc92191d1.php
│  │     ├─ bb1ba840286035233e6cfe0c83ebdae1.php
│  │     ├─ c68385e93be72f875f2fbfd692acb66b.php
│  │     ├─ c90be4a9fe8d53ba2124fee546498574.php
│  │     ├─ cab76132ee25e63055b13d7083a9abfd.php
│  │     ├─ cccc1ac38c0baec9b3cee8f4dec81947.php
│  │     ├─ d7d7f011a557e5744211ddfbc2ddda58.php
│  │     ├─ d855e5694fad76d06660ce0779e33fb0.php
│  │     ├─ d8abaceefa96c3d6c4fff308c29bd115.php
│  │     ├─ d9e8c35e6e9f251dcf3378268c4c24ce.php
│  │     ├─ db84ab2a9b2210db98371dcbc8c83749.php
│  │     ├─ ecd5b581679cd85e74bdf2bcb74511b7.php
│  │     ├─ f17607d6c4d9555110fc502495931deb.php
│  │     ├─ fa1879c3cdfba0443b3a466e72824c81.php
│  │     ├─ fa8e56240f624b7e48a9bd4ed6770a7a.php
│  │     └─ fca4ad83ddaa312d6847bdae65bbdca4.php
│  ├─ logs
│  │  └─ laravel.log
│  ├─ oauth-private.key
│  └─ oauth-public.key
│
└─ vite.config.js

```

