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
├─ .editorconfig
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
│  │  ├─ packages.php
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
│  │  └─ 2026_08_19_000001_add_program_scope_and_audit_to_course_options.php
│  ├─ seeders
│  │  ├─ DatabaseSeeder.php
│  │  ├─ DaysSeeder.php
│  │  ├─ InitialCollegeSeeder.php
│  │  ├─ PermissionsSeeder.php
│  │  ├─ SettingsSeeder.php
│  │  └─ UserTypesSeeder.php
│  └─ sql
│     └─ unihub20 (1).sql
├─ docs
│  ├─ API_ROUTES.md
│  ├─ API_ROUTES_DETAILED.md
│  ├─ CONTROLLERS_DETAILED.md
│  ├─ CONTROLLERS_LIST.md
│  ├─ DATABASE_SCHEMA.md
│  ├─ MODELS_DETAILED.md
│  └─ MODELS_DOCUMENTATION.md
├─ login.json
├─ nullable()
├─ package-lock.json
├─ package.json
├─ phpunit.xml
├─ public
│  ├─ .htaccess
│  ├─ favicon.ico
│  ├─ index.php
│  ├─ logo.png
│  ├─ robots.txt
│  ├─ T2  نهائي قالب توصيف مقرر دراسي.docx
│  ├─ test.txt
│  └─ unihub-ca.crt
├─ README.md
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
│     └─ welcome.blade.php
├─ routes
│  ├─ api.php
│  ├─ console.php
│  └─ web.php
├─ routes.txt
├─ storage
│  ├─ app
│  │  ├─ private
│  │  │  └─ imports
│  │  │     └─ job_1.pdf
│  │  └─ public
│  │     ├─ colleges
│  │     │  ├─ 1.jpeg
│  │     │  ├─ 1.jpg
│  │     │  ├─ 1.png
│  │     │  ├─ 2.jpeg
│  │     │  ├─ 2.png
│  │     │  ├─ 3.jpg
│  │     │  ├─ 3.png
│  │     │  └─ 4.jpeg
│  │     └─ lecture_files
│  │        ├─ kNUNvA18s8C8cvFkxZn2M35Ssoaux5iSdqNJP6mA.pdf
│  │        ├─ OYKEur2PH4LYPpSoSAiCD3Pk1GMihdA7tXdDazQ8.pdf
│  │        └─ wib96ynLSQmhKtqygpBl3kqAICCJdJ7qCDqSch3j.pptx
│  ├─ framework
│  │  ├─ cache
│  │  │  └─ data
│  │  ├─ sessions
│  │  │  ├─ 1FbJqoEV5kLLBlt8q35gECRBzNtXhUXXdbiPxtpy
│  │  │  ├─ 1xQdeHuOEvC4sclGsVpVzvbs9c5uOuftjqguMyvh
│  │  │  ├─ 6Rhi1NjYLZRVlWXRxv1JY6ZVemRHXGz7pt4sPM2E
│  │  │  ├─ 8AuOhGBrRV13OtV2SkZKFVskRx4LsyPpUevBpQmd
│  │  │  ├─ AfpizfKrAwSj8yeIp70B9dvzf8ts8IeeVAAYS1Qy
│  │  │  ├─ B0BzEcZNI5sBZKfkzdT8LIUJTsdwtKg5ceOEgZzo
│  │  │  ├─ beizv5mUw6rSC6EQTDJ7gAPYA2vuCPuZ35IZebeW
│  │  │  ├─ DXFgeYUccI9x9ey3AjIauC3B3Eyo1pgcqafvTtlA
│  │  │  ├─ INKxQnYiAMA1dDOmyg8ufvgCaptl4rOWKQLvnKC9
│  │  │  ├─ ja8YCU9qiNhx06HZziU7FbT2HdTtCtMmrEt84HjY
│  │  │  ├─ jdGmoRt2IkgV757vlOZADUAOC19Y4jYudJXwCHAH
│  │  │  ├─ Jn9yx8v7JudUsxEE3h2w3ZcqRszSym5S8buLrmgF
│  │  │  ├─ kuzv5q0eBHNKIS4VOHrlMuq9Tlwe9bMZPgsDAbVI
│  │  │  ├─ mQYGIt1zxxQQ1vvcuqgMEKFu8NJCX5xq98Y8GxJi
│  │  │  ├─ oyoyIvV0C5y1Ay0rI3GcfLKgAqZwph7NK6c2gdVs
│  │  │  ├─ Q1ASHPF1uaAhBtnM5CFRkV7SArXENXBAus95BnMC
│  │  │  ├─ QygfiLJrN8dHXsQduwvlzlHJjkAvzlT95nXGr7Pl
│  │  │  ├─ smmx4BmhNGXWmECrDAN5Algajh4cLIGQjGPVjQDJ
│  │  │  ├─ W9RSeMoOm8noR40aCsfIVzeEKQX7mo01G58jP3A5
│  │  │  ├─ X4yhGnKGD8AoTW5EAe1qRtZXJKVXujGjN83cbTEH
│  │  │  └─ XvlVtqoDZ3H0XrvxeaMHElKl7wS0x0FsROSujftO
│  │  ├─ testing
│  │  └─ views
│  │     ├─ 1575c544dc7358ff84995dd86f2f8a6b.php
│  │     ├─ 1923e574c2d1b5ce32823a3026dcf3cd.php
│  │     ├─ 19528e616f652b37ff47c8c7a87d6b97.php
│  │     ├─ 2464030b47bd040a9511ae1fab688574.php
│  │     ├─ 3192216f126ec63cf99745b7e96c0e16.php
│  │     ├─ 48a5304429eb1feeb2027eb6a10695d5.php
│  │     ├─ 5477eeddbe14ad7e9a7925a2e2ffaead.php
│  │     ├─ 67633f7480ecc845e246d2ca206d152a.php
│  │     ├─ 776589c4eaa49b939e11a93513a199f8.php
│  │     ├─ a3a7d3c90d1e97ba062ac502d82267ff.php
│  │     ├─ a86040d0348f5768ef7683f0e21a9a47.php
│  │     ├─ b75a0f1a3ab383169f72b85fd0299854.php
│  │     └─ fb53c9dd6b1a3eba076c8efd062dca45.php
│  └─ logs
├─ tests
│  ├─ Feature
│  │  └─ ExampleTest.php
│  ├─ TestCase.php
│  └─ Unit
│     └─ ExampleTest.php
├─ token.txt
└─ vite.config.js

```
