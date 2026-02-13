<?php



use Illuminate\Support\Facades\Route;

use App\Models\User;



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
use App\Http\Controllers\Api\V1\QrCodesController;
use App\Http\Controllers\Api\V1\StudentAttendanceController;
use App\Http\Controllers\Api\V1\LecturerAttendanceController;
use App\Http\Controllers\Api\V1\MakeupLecturesController;
use App\Http\Controllers\Api\V1\StudentExcusesController;
use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\UserDevicesController;
use App\Http\Controllers\Api\V1\Admin\SystemController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\ReportsController;
use App\Http\Controllers\Api\V1\FinancialController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\LecturerGradebookController;
use App\Http\Controllers\Api\V1\Admin\IpRestrictionController;

use App\Http\Controllers\Api\V1\QA\Admin\QaManagerController;
use App\Http\Controllers\Api\V1\QA\Admin\QaCampaignsController;
use App\Http\Controllers\Api\V1\QA\Student\QaEvaluationController;
use App\Http\Controllers\Api\V1\QA\Reports\QaAnalysisController;
use App\Http\Controllers\Api\V1\QualityAssuranceController;
use App\Http\Controllers\Api\V1\QA\Reports\CourseExecutionReportController;

Route::get('/debug/password-algo', function () {

    $u = User::first();

    if (!$u) return 'no user';

    $hash = $u->password;

    if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2b$') || str_starts_with($hash, '$2a$')) return 'bcrypt';

    if (str_starts_with($hash, '$argon2id$') || str_starts_with($hash, '$argon2i$')) return 'argon2';

    return 'unknown';

});



Route::prefix('v1')->group(function ()
{

    Route::get('admin/security/policy', [SettingsController::class, 'getPolicy']);



    Route::post('sync/bulk', [SyncController::class, 'bulkSync']);

   

    Route::controller(AuthController::class)->group(function () {

        Route::post('auth/login', 'login')->middleware('throttle:login');

        Route::post('auth/verify-otp', 'verifyOtp');

    });



    Route::controller(AuthPasswordController::class)->group(function () {

        Route::post('auth/forgot-password', 'forgot');

        Route::post('auth/reset-password', 'reset')->middleware('throttle:reset');

    });



   



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

        Route::get('/classrooms/availability', [ClassroomsController::class, 'checkAvailability']);
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
        Route::get('lecturers/financial-dues', [LecturersController::class, 'getFinancialDues']);
        Route::apiResource('lecturers', LecturersController::class);
        

        // Route::get('lecturer/schedule', [ScheduleController::class, 'getSchedule']);



        Route::controller(LecturerGradebookController::class)->prefix('lecturer')->group(function () {

            Route::get('my-courses', 'getMyCourses');

            Route::get('gradebook', 'getGradebookData');

            Route::post('assessments', 'addAssessmentColumn');

            Route::delete('assessments/{id}', 'deleteAssessmentColumn');

            Route::post('grades/update', 'updateStudentGrade');

        });



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



        Route::controller(LectureSessionsController::class)->prefix('lecture-sessions')->group(function () {

            Route::post('bulk', 'storeBulk');

            Route::post('start', 'startSession'); // Generic start

        });

        Route::get('/schedulable-lectures', [LectureSessionsController::class, 'getSchedulableLectures']);

        Route::apiResource('lecture-sessions', LectureSessionsController::class);

       

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

            Route::put('{makeupLecture}/review', 'approve');

            Route::put('{makeupLecture}/approve', 'approve');

            Route::put('{makeupLecture}/schedule', 'schedule');

        });

        Route::get('colleges/{college}/makeup-requests', [MakeupLecturesController::class, 'indexByCollege']);

        Route::put('makeup-lectures/{id}/review', [MakeupLecturesController::class, 'approve']);



        Route::controller(StudentExcusesController::class)->prefix('student-excuses')->group(function () {

            Route::post('/', 'store');

            Route::put('{excuse}/approve-by-head', 'approveByHead');

            Route::put('{excuse}/approve-by-lecturer', 'approveByLecturer');

            Route::put('{id}/status', 'updateStatus');

        });



        Route::post('notifications', [NotificationsController::class, 'store']);

        Route::get('notifications', [NotificationsController::class, 'index']);

        Route::put('notifications/{id}', [NotificationsController::class, 'update']);

        Route::delete('notifications/{id}', [NotificationsController::class, 'destroy']);



        Route::controller(UserDevicesController::class)->prefix('devices')->group(function () {

            Route::get('/', 'index');

            Route::put('{device}/enable-auto-attendance', 'enableAutoAttendance');

            Route::put('{device}/disable-auto-attendance', 'disableAutoAttendance');

            Route::delete('{device}', 'destroy');

        });



        Route::prefix('admin')->group(function () {

           

            Route::get('sessions', [SystemController::class, 'sessions']);

            Route::post('sessions/revoke', [SystemController::class, 'revokeSession']);

           

            Route::get('audit-logs', [SystemController::class, 'auditLogs']);

           

            Route::get('security/policy', [SettingsController::class, 'getPolicy']);

            Route::put('security/policy', [SettingsController::class, 'updatePolicy']);

            Route::apiResource('ip-restrictions', IpRestrictionController::class)->only(['index', 'store', 'destroy']);

        });



        Route::middleware(['auth:api', 'activity:admin', 'throttle:60,1'])

            ->prefix('/admin')

            ->group(function () {

       

       

                Route::controller(UserDevicesController::class)->prefix('devices')->group(function () {

                    Route::get('/', 'index');

                    Route::put('{device}/enable-auto-attendance', 'enableAutoAttendance');

                    Route::put('{device}/disable-auto-attendance', 'disableAutoAttendance');

                    Route::delete('{device}', 'destroy');

                    Route::put('{device}/toggle-attendance', 'toggleAutoAttendance');

                });

       

        });

        Route::prefix('qa/student')->group(function () {
            Route::controller(QaEvaluationController::class)->group(function () {
                // 1. جلب قائمة التقييمات المعلقة (المواد التي لم تُقيم)
                Route::get('pending', 'getPendingEvaluations');
                
                // 2. إرسال الإجابات
                Route::post('submit', 'submitEvaluation');

                
                // 3. جلب أسئلة نموذج معين للبدء في التقييم
                Route::get('form/{campaign}', 'getEvaluationForm');
            });
        });

    });



    Route::prefix('colleges/{college}/financial')->group(function () {

   

        Route::get('cycle', [FinancialController::class, 'getCycleByMonth']);

       

        Route::post('generate', [FinancialController::class, 'generateCycle']);

       

        Route::post('payouts/{payout}/adjustments', [FinancialController::class, 'addAdjustment']);

       

        Route::put('cycles/{cycle}/status', [FinancialController::class, 'updateStatus']);

    });

    // داخل Route::prefix('v1')->middleware(...)->group(function () { ...

    Route::prefix('qa')->group(function () {
        Route::controller(QaManagerController::class)->group(function () {
            Route::get('forms', 'index');
            Route::post('forms', 'store');
            Route::get('forms/{form}', 'show');
            Route::put('forms/{form}', 'update'); // هذا الرابط سيحفظ الهيكل كاملاً
            Route::delete('forms/{form}', 'destroy');
        });

            // ✅ روابط إدارة الحملات (جديد)
        Route::controller(QaCampaignsController::class)->group(function () {
            // ✅ 1. الراوتات المحددة (الثابتة) توضع في البداية
            Route::get('campaigns/create-meta', 'getCreationMeta');
            Route::get('campaigns/year-details', 'getYearDetails'); // تأكد أن هذا السطر قبل {campaign}
        
            // 2. الراوتات العامة
            Route::get('campaigns', 'index');
            Route::post('campaigns', 'store');
        
            // ⚠️ 3. الراوتات التي تحتوي على متغيرات {campaign} توضع في النهاية
            Route::put('campaigns/{campaign}', 'update');
            Route::delete('campaigns/{campaign}', 'destroy');
        });

            // ✅ روابط التقارير والتحليل
        Route::controller(QaAnalysisController::class)->group(function () {
            // جلب إحصائيات حملة معينة (ملخص + قائمة المحاضرين)
            Route::get('reports/campaign-summary', 'getCampaignSummary');
            Route::get('reports/campaign-timetables', 'getCampaignTimetables'); 
        });

        Route::controller(CourseExecutionReportController::class)->prefix('reports')->group(function () {
            Route::get('execution/list', 'index'); 
            Route::get('execution/details/{timetable}', 'show');
            Route::get('execution/filters-meta', 'getFiltersMeta');
        });
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