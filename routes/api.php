<?php

use Illuminate\Support\Facades\Route;

// --- Controllers الموجودة لديك ---
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
use App\Http\Controllers\Api\V1\TimetablesImportController;
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
use App\Http\Controllers\Api\V1\Lecturer\ScheduleController;

// --- Controllers الجديدة (تصحيح الـ Namespace) ---
use App\Http\Controllers\Api\V1\TimetableSetController;
use App\Http\Controllers\Api\V1\TimetableEntryController;
// لا حاجة لـ LectureSessionController جديد إذا كان القديم يفي بالغرض مع الـ alias

// use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::get('/debug/password-algo', function () {
    $u = User::first();
    if (! $u) return 'no user';

    $hash = $u->password;
    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') || str_starts_with($hash, '$2a$')) {
        return 'bcrypt';
    }
    if (str_starts_with($hash, '$argon2id$') || str_starts_with($hash, '$argon2i$')) {
        return 'argon2';
    }
    return 'unknown';
});


// ========================== V1 Routes ==========================
Route::prefix('v1')->group(function () {
    // --- Public Routes ---
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('auth/forgot-password', [AuthPasswordController::class, 'forgot']);
    Route::post('auth/reset-password', [AuthPasswordController::class, 'reset'])->middleware('throttle:reset');
    Route::get('app-versions/latest', [AppVersionsController::class, 'latest']);
    Route::get('admin/security/policy', [SettingsController::class, 'getPolicy']);
    
    // --- Protected Routes ---
    Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])->group(function () {
        
        // --- Auth & User ---
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // --- Lookups ---
        Route::get('lookups/user-types', [LookupsController::class, 'userTypes']);
        Route::get('lookups/permissions', [LookupsController::class, 'permissions']);
        Route::get('lookups/colleges', [LookupsController::class, 'colleges']);
           Route::post('attendance/finalize', [StudentAttendanceController::class, 'finalizeSession'])->name('attendance.finalize');
    
    // مسار جلب طلاب المجموعة
    Route::get('groups/{studentGroup}/students', [StudentAttendanceController::class, 'getGroupStudents'])->name('groups.students');

    // مسارات الـ QR
    Route::post('qr-codes/start-session', [QrCodesController::class, 'startSession']);
    Route::patch('qr-codes/{qrCode}/refresh', [QrCodesController::class, 'refresh']);
    Route::patch('qr-codes/{qrCode}/end', [QrCodesController::class, 'endSession']);

        // --- Core CRUD Resources ---
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
        Route::apiResource('students', StudentsController::class)->only(['index']);
        Route::apiResource('student-groups', StudentGroupsController::class);
        Route::apiResource('lecturers', LecturersController::class);
        Route::apiResource('user-types', UserTypeController::class)->except(['index', 'show']);
        Route::apiResource('timetable', TimetableController::class);
        Route::post('sessions/finalize-attendance', [StudentAttendanceController::class, 'finalizeSession'])->name('sessions.finalize');
    Route::apiResource('student-attendance', StudentAttendanceController::class);
    Route::apiResource('lecturer-attendance', LecturerAttendanceController::class);
        
        // مسار لمصادقة الجلسة وإنشاء سجلات الحضور
        
        
        // (اختياري) مسار لإنشاء سجل حضور المحاضر
        Route::post('/lecturer-attendance', [LecturerAttendanceController::class, 'store']);

        // --- QR & Attendance ---
        Route::patch('/qr-codes/{qrCode}/refresh', [QrCodesController::class, 'refresh']);
        Route::apiResource('qr-refresh-options', QRRefreshOptionsController::class);
        Route::post('qr-codes/start-session', [QrCodesController::class, 'startSession']); // <-- المسار الجديد
        Route::patch('/qr-codes/{qrCode}/end', [QrCodesController::class, 'endSession']);
        Route::post('attendance/students/scan', [StudentAttendanceController::class, 'scan']);
        Route::post('attendance/students/manual', [StudentAttendanceController::class, 'manualEntry']);
        Route::post('attendance/lecturers/check-in', [LecturerAttendanceController::class, 'checkIn']);
        Route::post('attendance/finalize', [LecturerAttendanceController::class, 'finalizeSession']);

        // --- Student Attendance ---
        Route::post('attendance/scan', [StudentAttendanceController::class, 'scan']);
        Route::post('attendance/finalize', [LecturerAttendanceController::class, 'finalizeSession']);
        // --- Lecturers ---
        
        Route::post('lecturers/import-csv', [LecturersController::class, 'importCsv']);
        Route::get('lecturer/schedule', [ScheduleController::class, 'getSchedule']);
 
 
        // --- Students & Groups ---
        
        Route::get('student-groups/{student_group}/students', [StudentGroupsController::class, 'students']);
        Route::delete('student-groups/{group}/students', [StudentGroupsController::class, 'detachStudent']);
        Route::post('student-groups/upsert-and-attach', [StudentGroupsController::class, 'upsertAndAttach']);
        Route::post('student-groups/import-csv', [StudentGroupsController::class, 'importCsv']);
        Route::post('student-groups/import-external', [StudentGroupsController::class, 'importExternal']);

        // --- Timetable & Sessions (القديم لديك) ---
        
        
        Route::apiResource('lecture-sessions', LectureSessionsController::class);
        Route::post('lecture-sessions/start', [LectureSessionsController::class, 'startSession']);

        // --- QR & Attendance ---
        Route::apiResource('qr-refresh-options', QRRefreshOptionsController::class);
        Route::post('qr-codes/refresh', [QrCodesController::class, 'refreshQrCode']);
        Route::post('attendance/students/scan', [StudentAttendanceController::class, 'scan']);
        Route::post('attendance/students/manual', [StudentAttendanceController::class, 'manualEntry']);
        Route::post('attendance/lecturers/check-in', [LecturerAttendanceController::class, 'checkIn']);
        
        // --- Devices ---
        Route::put('devices/{device}/enable-auto-attendance', [UserDevicesController::class, 'enableAutoAttendance']);
        Route::put('devices/{device}/disable-auto-attendance', [UserDevicesController::class, 'disableAutoAttendance']);
        Route::delete('devices/{device}', [UserDevicesController::class, 'destroy']);

        // --- Notifications & Excuses ---
        Route::post('makeup-lectures', [MakeupLecturesController::class, 'store']);
        Route::put('makeup-lectures/{makeupLecture}/review', [MakeupLecturesController::class, 'review']);
        Route::put('makeup-lectures/{makeupLecture}/approve', [MakeupLecturesController::class, 'approve']);
        Route::put('makeup-lectures/{makeupLecture}/schedule', [MakeupLecturesController::class, 'schedule']);
        Route::post('student-excuses', [StudentExcusesController::class, 'store']);
        Route::put('student-excuses/{excuse}/approve-by-head', [StudentExcusesController::class, 'approveByHead']);
        Route::put('student-excuses/{excuse}/approve-by-lecturer', [StudentExcusesController::class, 'approveByLecturer']);
        Route::post('notifications', [NotificationsController::class, 'store']);
        
        // --- UserTypes & Permissions ---
        Route::apiResource('user-types', UserTypeController::class)->except(['index', 'show']);
        Route::get('user-types/{userTypeId}/permissions', [UserTypePermissionController::class, 'index']);
        Route::post('user-types/{userType}/permissions/bulk-assign', [UserTypePermissionController::class, 'bulkAssign']);

        // --- Admin ---
        Route::prefix('admin')->group(function () {
            Route::get('sessions', [SystemController::class, 'sessions']);
            Route::post('sessions/revoke', [SystemController::class, 'revokeSession']);
            Route::get('audit-logs', [SystemController::class, 'auditLogs']);
            Route::put('security/policy', [SettingsController::class, 'updatePolicy']);
        });

        // ===================================
        // --- Timetable (التقسيم الجديد: Sets + Entries) ---
        // طبق الحماية هنا: فقط الأدوار المحددة يمكنها الوصول
        Route::middleware('type:admin,dean,presidency')->group(function () { // <-- أضف كل أنواع المستخدمين الإداريين
            // Sets
            Route::get('timetable-sets',  [TimetableSetController::class, 'index']);
            Route::post('timetable-sets', [TimetableSetController::class, 'store']);

            // Entries
            Route::get('timetable-entries',        [TimetableEntryController::class, 'index']);
            Route::post('timetable-entries',       [TimetableEntryController::class, 'store']);
            Route::post('timetable-entries/bulk',  [TimetableEntryController::class, 'bulk']);

        });
        // ===================================
    });
});

// ========== Aliases خارج /v1 ولكن محمية بنفس الـ Middleware (مطلوبة للواجهة الحالية) ==========
Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])->group(function () {
    // مسارات خاصة بالمحاضر
    Route::middleware('type:lecturer')->group(function () {
        // مثال: GET /lecturer-data -> LecturerController@getData
        // ضع هنا أي مسارات خاصة بواجهة المحاضر
    });

    // مسارات خاصة بالمدراء (محمية من المحاضر)
    Route::middleware('type:admin,dean,presidency')->group(function () { // <-- أضف كل الأنواع الإدارية
        // Timetable aliases متوافقة مع TimetableModule
        Route::post('timetable',      [TimetableEntryController::class, 'storeAlias']);
        Route::post('timetable/bulk', [TimetableEntryController::class, 'bulkAlias']);

        // Lecture sessions aliases (يمكن تركها هنا أو نقلها للمجموعة العامة إذا كان المحاضر يستخدمها)
        Route::get('lecture-sessions',  [LectureSessionsController::class, 'index']);
        Route::post('lecture-sessions', [LectureSessionsController::class, 'store']);
    });
});