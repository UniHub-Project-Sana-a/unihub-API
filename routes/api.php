<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthPasswordController;

use App\Http\Controllers\Api\V1\UsersController;
use App\Http\Controllers\Api\V1\LookupsController;
use App\Http\Controllers\Api\V1\UserTypeController;
use App\Http\Controllers\Api\V1\UserTypePermissionController;
use App\Http\Controllers\Api\V1\DaysController;
use App\Http\Controllers\Api\V1\SemestersController;
use App\Http\Controllers\Api\V1\LevelsController;
use App\Http\Controllers\Api\V1\ProgramsController;
use App\Http\Controllers\Api\V1\BuildingsController;
use App\Http\Controllers\Api\V1\ClassroomsController;
use App\Http\Controllers\Api\V1\PeriodsController;
use App\Http\Controllers\Api\V1\CoursesController;
use App\Http\Controllers\Api\V1\StudentGroupsController;
use App\Http\Controllers\Api\V1\TimetableController;
use App\Http\Controllers\Api\V1\LectureSessionsController;
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

use App\Http\Controllers\Api\V1\CollegesController;
use App\Http\Controllers\Api\V1\DepartmentsController;

Route::prefix('v1')->group(function () {

    Route::get('app-versions/latest', [AppVersionsController::class, 'latest']);

    // Auth
    Route::post('auth/login',           [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [AuthPasswordController::class, 'forgot'])->middleware('throttle:forgot');
    Route::post('auth/reset-password',  [AuthPasswordController::class, 'reset'])->middleware('throttle:reset');

    // المسارات التي كانت محمية سابقًا
    // Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])->group(function () {
    Route::middleware(['throttle:60,1'])->group(function () {

        // Me/Logout (سيسبب خطأ لأنه لا يوجد مستخدم)
        // Route::get('auth/me', [AuthController::class, 'me']);
        // Route::post('auth/logout',[AuthController::class, 'logout']);

        // Lookups (للقوائم المنسدلة فقط)
        Route::get('lookups/user-types', [LookupsController::class, 'userTypes']);
        Route::get('lookups/permissions', [LookupsController::class, 'permissions']);
        Route::get('lookups/colleges', [LookupsController::class, 'colleges']);

        // CRUD Resources
        Route::apiResource('users', UsersController::class);
        Route::apiResource('colleges', CollegesController::class);
        Route::apiResource('departments', DepartmentsController::class);
        Route::apiResource('programs', ProgramsController::class);
        Route::apiResource('levels', LevelsController::class);
        Route::apiResource('semesters',  SemestersController::class);
        Route::apiResource('days', DaysController::class);
        Route::apiResource('buildings', BuildingsController::class);
        Route::apiResource('classrooms', ClassroomsController::class);
        Route::apiResource('periods', PeriodsController::class);
        Route::apiResource('courses', CoursesController::class);
        Route::apiResource('student-groups', StudentGroupsController::class);
        Route::apiResource('timetables', TimetableController::class);
        Route::apiResource('lecture-sessions', LectureSessionsController::class);
        Route::apiResource('app-versions', AppVersionsController::class);

        // QR & Attendance (سيسبب خطأ لأنه لا يوجد مستخدم)
        // Route::apiResource('qr-refresh-options', QRRefreshOptionsController::class);
        // Route::post('lecture-sessions/start', [LectureSessionsController::class, 'startSession']);
        // Route::post('qr-codes/refresh', [QrCodesController::class, 'refreshQrCode']);
        // Route::post('attendance/students/scan', [StudentAttendanceController::class, 'scan']);
        // Route::post('attendance/students/manual', [StudentAttendanceController::class, 'manualEntry']);
        // Route::post('attendance/lecturers/check-in', [LecturerAttendanceController::class, 'checkIn']);
        // Route::put('devices/{device}/enable-auto-attendance', [UserDevicesController::class, 'enableAutoAttendance']);
        // Route::put('devices/{device}/disable-auto-attendance', [UserDevicesController::class, 'disableAutoAttendance']);
        // Route::delete('devices/{device}', [UserDevicesController::class, 'destroy']);

        // Notifications & Excuses (سيسبب خطأ لأنه لا يوجد مستخدم)
        // Route::post('makeup-lectures', [MakeupLecturesController::class, 'store']);
        // Route::put('makeup-lectures/{makeupLecture}/review', [MakeupLecturesController::class, 'review']);
        // Route::put('makeup-lectures/{makeupLecture}/approve', [MakeupLecturesController::class, 'approve']);
        // Route::put('makeup-lectures/{makeupLecture}/schedule', [MakeupLecturesController::class, 'schedule']);
        // Route::post('student-excuses', [StudentExcusesController::class, 'store']);
        // Route::put('student-excuses/{excuse}/approve-by-head', [StudentExcusesController::class, 'approveByHead']);
        // Route::put('student-excuses/{excuse}/approve-by-lecturer', [StudentExcusesController::class, 'approveByLecturer']);
        // Route::post('notifications', [NotificationsController::class, 'store']);

        // UserTypes & Permissions
        Route::post('user-types', [UserTypeController::class, 'store']);
        Route::put('user-types/{userType}', [UserTypeController::class, 'update']);
        Route::delete('user-types/{userType}', [UserTypeController::class, 'destroy']);
        Route::get('user-types/{userTypeId}/permissions', [UserTypePermissionController::class, 'index']);
        Route::post('user-types/{userType}/permissions/bulk-assign', [UserTypePermissionController::class, 'bulkAssign']);

        // Admin
        Route::get('admin/sessions', [SystemController::class, 'sessions']);
        Route::post('admin/sessions/revoke', [SystemController::class, 'revokeSession']);
        Route::get('admin/audit-logs', [SystemController::class, 'auditLogs']);
        Route::get('admin/security/policy', [SettingsController::class, 'getPolicy']);
        Route::put('admin/security/policy', [SettingsController::class, 'updatePolicy']);
    });
});