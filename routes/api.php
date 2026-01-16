<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;

// --- Controllers Imports ---
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthPasswordController;
use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\LookupsController;
use App\Http\Controllers\Api\V1\UserTypeController;
use App\Http\Controllers\Api\V1\UserTypePermissionController;
use App\Http\Controllers\Api\V1\DaysController;
use App\Http\Controllers\Api\V1\PeriodsController;
use App\Http\Controllers\Api\V1\CollegesController;
use App\Http\Controllers\Api\V1\DepartmentsController;
use App\Http\Controllers\Api\V1\ProgramsController;
use App\Http\Controllers\Api\V1\LevelsController;
use App\Http\Controllers\Api\V1\SemestersController;
use App\Http\Controllers\Api\V1\CoursesController;
use App\Http\Controllers\Api\V1\BuildingsController;
use App\Http\Controllers\Api\V1\ClassroomsController;
use App\Http\Controllers\Api\V1\AcademicTitlesController;
use App\Http\Controllers\Api\V1\LecturersController;
use App\Http\Controllers\Api\V1\StudentsController;
use App\Http\Controllers\Api\V1\StudentGroupsController;
use App\Http\Controllers\Api\V1\TimetableController;
use App\Http\Controllers\Api\V1\LectureSessionController as LectureSessionsController;
use App\Http\Controllers\Api\V1\QRRefreshOptionsController;
use App\Http\Controllers\Api\V1\QrCodesController;
use App\Http\Controllers\Api\V1\StudentAttendanceController;
use App\Http\Controllers\Api\V1\LecturerAttendanceController;
use App\Http\Controllers\Api\V1\MakeupLecturesController;
use App\Http\Controllers\Api\V1\StudentExcusesController;
use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\AppVersionsController;
use App\Http\Controllers\Api\V1\UserDevicesController;
use App\Http\Controllers\Api\V1\Admin\SystemController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Lecturer\ScheduleController;
use App\Http\Controllers\Api\V1\TimetableSetController;
use App\Http\Controllers\Api\V1\TimetableEntryController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\ReportsController;
use App\Http\Controllers\Api\V1\FinancialController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\QualityAssuranceController;
use App\Http\Controllers\Api\V1\LecturerGradebookController;
use App\Http\Controllers\Api\V1\Admin\IpRestrictionController;

// --- Debug Route (Optional) ---
Route::get('/debug/password-algo', function () {
    $u = User::first();
    if (!$u) return 'no user';
    $hash = $u->password;
    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') || str_starts_with($hash, '$2a$')) return 'bcrypt';
    if (str_starts_with($hash, '$argon2id$') || str_starts_with($hash, '$argon2i$')) return 'argon2';
    return 'unknown';
});

// ========================== V1 Routes ==========================
Route::prefix('v1')->group(function () {

    Route::post('sync/bulk', [SyncController::class, 'bulkSync']); 
    
    Route::controller(AuthController::class)->group(function () {
        Route::post('auth/login', 'login')->middleware('throttle:login');
        Route::post('auth/verify-otp', 'verifyOtp');
    });

    Route::controller(AuthPasswordController::class)->group(function () {
        Route::post('auth/forgot-password', 'forgot');
        Route::post('auth/reset-password', 'reset')->middleware('throttle:reset');
    });

    Route::get('app-versions/latest', [AppVersionsController::class, 'latest']);
    Route::get('admin/security/policy', [SettingsController::class, 'getPolicy']);

    Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])->group(function () {

        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);

        Route::controller(LookupsController::class)->prefix('lookups')->group(function () {
            Route::get('user-types', 'userTypes');
            Route::get('permissions', 'permissions');
            Route::get('colleges', 'colleges');
            Route::get('academic-years', 'academicYears'); 
        });

        Route::apiResource('users', UsersController::class);
        Route::apiResource('colleges', CollegesController::class);
        Route::apiResource('departments', DepartmentsController::class);
        Route::apiResource('programs', ProgramsController::class);
        Route::apiResource('levels', LevelsController::class);
        Route::apiResource('semesters', SemestersController::class);
        Route::apiResource('courses', CoursesController::class);
        Route::apiResource('buildings', BuildingsController::class);
        Route::apiResource('classrooms', ClassroomsController::class);
        Route::apiResource('periods', PeriodsController::class);
        Route::apiResource('days', DaysController::class);
        Route::apiResource('academic-titles', AcademicTitlesController::class);
        Route::get('colleges/{college}/dashboard', [CollegesController::class, 'dashboard']);
        
        Route::get('dashboard/university-overview', [DashboardController::class, 'index']);
        Route::get('colleges/{college}/reports', [ReportsController::class, 'index']);
        Route::get('colleges/{college}/reports/course-groups', [ReportsController::class, 'getCourseGroups']);
        Route::get('colleges/{college}/reports/group-students-attendance', [ReportsController::class, 'getGroupStudentsAttendance']);
        Route::get('colleges/{college}/reports/group-grades', [ReportsController::class, 'getGroupGradesReport']);
        Route::get('colleges/{college}/reports/qa-performance', [ReportsController::class, 'getQAPerformanceReport']);
        Route::get('colleges/{college}/reports/qa-details', [ReportsController::class, 'getQACourseDetails']);
        Route::get('colleges/{college}/reports/qa-detailed', [ReportsController::class, 'getQADetailedReport']);
        Route::get('colleges/{college}/dashboard', [ReportsController::class, 'dashboard']);
        
        Route::get('colleges/{college}/reports/detailed', [ReportsController::class, 'detailedReport']);
        
        Route::post('colleges/{college}/reports/builder', [ReportsController::class, 'customBuilder']);
        Route::get('colleges/{college}/reports/lecturer/{lecturer}', [ReportsController::class, 'lecturerDetails']);
        
        Route::apiResource('user-types', UserTypeController::class)->except(['index', 'show']);
        Route::get('user-types/{userTypeId}/permissions', [UserTypePermissionController::class, 'index']);
        Route::post('user-types/{userType}/permissions/bulk-assign', [UserTypePermissionController::class, 'bulkAssign']);

        Route::post('lecturers/import-csv', [LecturersController::class, 'importCsv']);
        Route::apiResource('lecturers', LecturersController::class);
        Route::get('lecturer/schedule', [ScheduleController::class, 'getSchedule']);

        // ✅ --- بداية الإضافة: راوتات درجات أعمال الفصل --- ✅
        Route::controller(LecturerGradebookController::class)->prefix('lecturer')->group(function () {
            Route::get('my-courses', 'getMyCourses');
            Route::get('gradebook', 'getGradebookData');
            Route::post('assessments', 'addAssessmentColumn');
            Route::delete('assessments/{id}', 'deleteAssessmentColumn');
            Route::post('grades/update', 'updateStudentGrade');
        });
        // ✅ --- نهاية الإضافة --- ✅

        Route::apiResource('students', StudentsController::class)->only(['index']);
        
        Route::controller(StudentGroupsController::class)->prefix('student-groups')->group(function () {
            Route::post('upsert-and-attach', 'upsertAndAttach');
            Route::post('import-csv', 'importCsv');
            Route::post('import-external', 'importExternal');
            Route::delete('{group}/students', 'detachStudent');
            Route::get('{student_group}/students', 'students');
        });
        Route::get('groups/{studentGroup}/students', [StudentGroupsController::class, 'students']); 
        Route::apiResource('student-groups', StudentGroupsController::class);

        Route::apiResource('timetable', TimetableController::class);

        Route::middleware('type:admin,dean,presidency')->group(function () {
            Route::get('timetable-sets',  [TimetableSetController::class, 'index']);
            Route::post('timetable-sets', [TimetableSetController::class, 'store']);
            
            Route::get('timetable-entries',        [TimetableEntryController::class, 'index']);
            Route::post('timetable-entries',       [TimetableEntryController::class, 'store']);
            Route::post('timetable-entries/bulk',  [TimetableEntryController::class, 'bulk']);
            
            Route::post('timetable-alias',      [TimetableEntryController::class, 'storeAlias']);
            Route::post('timetable/bulk', [TimetableEntryController::class, 'bulkAlias']);
        });

        Route::controller(LectureSessionsController::class)->prefix('lecture-sessions')->group(function () {
            Route::post('bulk', 'storeBulk');
            Route::post('start', 'startSession'); // Generic start
        });
        Route::get('/schedulable-lectures', [LectureSessionsController::class, 'getSchedulableLectures']);
        Route::apiResource('lecture-sessions', LectureSessionsController::class);

        Route::apiResource('qr-refresh-options', QRRefreshOptionsController::class);
        
        Route::controller(QrCodesController::class)->prefix('qr-codes')->group(function () {
            Route::post('start-session', 'startSession');
            Route::patch('{qrCode}/refresh', 'refresh');
            Route::patch('{qrCode}/end', 'endSession');   
        });
        Route::patch('qr-codes/{qrCode}/extend', [QrCodesController::class, 'extendDuration']);

        Route::controller(StudentAttendanceController::class)->prefix('attendance/students')->group(function () {
            Route::post('scan', 'scan');
            Route::post('manual', 'manualEntry');
        });
        Route::post('attendance/scan', [StudentAttendanceController::class, 'scan']); 
        Route::apiResource('student-attendance', StudentAttendanceController::class);

        Route::controller(LecturerAttendanceController::class)->group(function () {
            Route::post('attendance/lecturers/check-in', 'checkIn');
            Route::post('lecturer-attendance', 'store');
            
            Route::post('attendance/finalize', 'finalizeSession')->name('sessions.finalize');
        });
        Route::apiResource('lecturer-attendance', LecturerAttendanceController::class);

        Route::controller(MakeupLecturesController::class)->prefix('makeup-lectures')->group(function () {
            Route::post('/', 'store');
            Route::put('{makeupLecture}/review', 'review');
            Route::put('{makeupLecture}/approve', 'approve');
            Route::put('{makeupLecture}/schedule', 'schedule');
        });

        Route::controller(StudentExcusesController::class)->prefix('student-excuses')->group(function () {
            Route::post('/', 'store');
            Route::put('{excuse}/approve-by-head', 'approveByHead');
            Route::put('{excuse}/approve-by-lecturer', 'approveByLecturer');
        });

        Route::post('notifications', [NotificationsController::class, 'store']);

        Route::controller(UserDevicesController::class)->prefix('devices')->group(function () {
            Route::put('{device}/enable-auto-attendance', 'enableAutoAttendance');
            Route::put('{device}/disable-auto-attendance', 'disableAutoAttendance');
            Route::delete('{device}', 'destroy');
        });

        Route::prefix('admin')->group(function () {
            
            // 1. إدارة الجلسات (SystemController)
            Route::get('sessions', [SystemController::class, 'sessions']);
            Route::post('sessions/revoke', [SystemController::class, 'revokeSession']);
            
            // 2. سجلات النظام (SystemController)
            Route::get('audit-logs', [SystemController::class, 'auditLogs']);
            
            // 3. سياسات الأمان (SettingsController)
            // لاحظ أن الرابط النهائي سيصبح: /api/v1/admin/security/policy
            Route::get('security/policy', [SettingsController::class, 'getPolicy']);
            Route::put('security/policy', [SettingsController::class, 'updatePolicy']);
            Route::apiResource('ip-restrictions', IpRestrictionController::class)->only(['index', 'store', 'destroy']);
        });
    });

    Route::prefix('colleges/{college}/financial')->group(function () {
    
        Route::get('cycle', [FinancialController::class, 'getCycleByMonth']);
        
        Route::post('generate', [FinancialController::class, 'generateCycle']);
        
        Route::post('payouts/{payout}/adjustments', [FinancialController::class, 'addAdjustment']);
        
        Route::put('cycles/{cycle}/status', [FinancialController::class, 'updateStatus']);
    });

    Route::get('courses/{course}/qa-data', [QualityAssuranceController::class, 'getCourseQaData']);
    Route::get('timetable/{timetable}/topics-status', [TimetableController::class, 'getTopicsStatus']);

    Route::post('qa/outcomes', [QualityAssuranceController::class, 'storeOutcome']);
    Route::put('qa/outcomes/{id}', [QualityAssuranceController::class, 'updateOutcome']);
    Route::delete('qa/outcomes/{id}', [QualityAssuranceController::class, 'destroyOutcome']);

    Route::post('qa/topics', [QualityAssuranceController::class, 'storeTopic']);
    Route::put('qa/topics/{id}', [QualityAssuranceController::class, 'updateTopic']);
    Route::delete('qa/topics/{id}', [QualityAssuranceController::class, 'destroyTopic']);

    Route::post('qa/questions', [QualityAssuranceController::class, 'storeQuestion']);
    Route::put('qa/questions/{id}', [QualityAssuranceController::class, 'updateQuestion']);
    Route::delete('qa/questions/{id}', [QualityAssuranceController::class, 'destroyQuestion']);

});