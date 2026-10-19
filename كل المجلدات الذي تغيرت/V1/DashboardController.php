<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\College;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\LectureSession;
// use App\Models\LecturerPayout; // تأكد أن هذا المودل موجود أو استخدم DB كما في الكود بالأسفل
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $today = Carbon::today()->toDateString();
            $yesterday = Carbon::yesterday()->toDateString();
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            // 1. المؤشرات العليا
            $totalColleges = College::count();
            $totalStudents = Student::count();
            $totalStaff = Lecturer::count();

            // حساب نسبة الانضباط اليومي
            $todaySessionsTotal = LectureSession::where('session_date', $today)->count();
            $todaySessionsExecuted = LectureSession::where('session_date', $today)
                                        ->where('status', 1) // هنا لا مشكلة لأنه Eloquent مباشر
                                        ->count();
            
            $dailyAttendanceRate = 0;
            if ($todaySessionsTotal > 0) {
                $dailyAttendanceRate = round(($todaySessionsExecuted / $todaySessionsTotal) * 100);
            }

            // --- ب) حساب Trends (النصوص الديناميكية) ---
        
            // 1. trend الكليات (الجديدة هذا العام)
            $newCollegesCount = College::whereYear('created_at', $currentYear)->count();
            $collegesTrendText = $newCollegesCount > 0 ? "+{$newCollegesCount} هذا العام" : "لا تغيير هذا العام";
    
            // 2. trend الطلاب (المسجلين هذا الشهر)
            $newStudentsCount = Student::whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count();
            $studentsTrendText = $newStudentsCount > 0 ? "+{$newStudentsCount} جدد هذا الشهر" : "مستقر هذا الشهر";
    
            // 3. trend المحاضرين (التعيينات الجديدة هذا الشهر)
            $newStaffCount = Lecturer::whereMonth('created_at', $currentMonth)->whereYear('created_at', $currentYear)->count();
            $staffTrendText = $newStaffCount > 0 ? "+{$newStaffCount} تعيين جديد" : "لا تعيينات حديثة";
    
            // 4. trend الحضور (مقارنة بالأمس)
            $yesterdayTotal = LectureSession::where('session_date', $yesterday)->count();
            $yesterdayExecuted = LectureSession::where('session_date', $yesterday)->where('status', 1)->count();
            $yesterdayRate = ($yesterdayTotal > 0) ? round(($yesterdayExecuted / $yesterdayTotal) * 100) : 0;
            
            $diff = $dailyAttendanceRate - $yesterdayRate;
            $sign = $diff >= 0 ? '+' : ''; // إضافة علامة موجب إذا كان الرقم إيجابياً
            $attendanceTrendText = "{$sign}{$diff}% عن الأمس";

            // 2. أداء الكليات
            $collegesPerformance = College::withCount(['departments', 'lecturers'])
                ->get()
                ->map(function ($college) use ($currentMonth, $currentYear) {
                    
                    // أ) حساب نسبة الالتزام لهذا الشهر
                    $monthSessions = DB::table('lecture_sessions')
                        ->join('timetable', 'lecture_sessions.timetable_id', '=', 'timetable.timetable_id')
                        ->where('timetable.college_id', $college->college_id)
                        ->whereMonth('lecture_sessions.session_date', $currentMonth)
                        ->whereYear('lecture_sessions.session_date', $currentYear)
                        ->whereDate('lecture_sessions.session_date', '<=', Carbon::now()->toDateString())
                        
                        ->selectRaw('count(*) as total, sum(case when lecture_sessions.status = 1 then 1 else 0 end) as executed')
                        ->first();

                    $attendanceRate = 0;
                    if ($monthSessions->total > 0) {
                        $attendanceRate = round(($monthSessions->executed / $monthSessions->total) * 100);
                    }

                    // ب) الميزانية التقديرية
                    // نتحقق أولاً إذا كان الجدول موجوداً لتجنب خطأ عدم وجود الجدول إذا لم تقم بتهجيره بعد
                    $budget = 0;
                    // تأكد أن الجداول financial_cycles و lecturer_payouts موجودة في الداتابيز
                    try {
                        $budget = DB::table('lecturer_payouts')
                            ->join('financial_cycles', 'lecturer_payouts.cycle_id', '=', 'financial_cycles.cycle_id')
                            ->where('financial_cycles.college_id', $college->college_id)
                            ->where('financial_cycles.month_year', sprintf("%02d-%s", $currentMonth, $currentYear))
                            ->sum('lecturer_payouts.net_amount');
                    } catch (\Exception $e) {
                        // تجاهل خطأ الميزانية مؤقتاً إذا كانت الجداول فارغة أو غير موجودة
                        $budget = 0; 
                    }

                    return [
                        'id' => $college->college_id,
                        'name' => $college->college_name,
                        'attendance' => $attendanceRate,
                        'sessions' => $monthSessions->executed ?? 0,
                        'total_sessions' => $monthSessions->total ?? 0,
                        'budget' => (float)$budget
                    ];
                });

            // 3. التنبيهات الذكية
            $alerts = [];
            foreach ($collegesPerformance as $cp) {
                if ($cp['attendance'] < 75 && $cp['attendance'] > 0) {
                    $alerts[] = [
                        'id' => $cp['id'],
                        'college' => $cp['name'],
                        'msg' => "انخفاض نسبة الالتزام عن المعدل المقبول ({$cp['attendance']}%)",
                        'type' => 'warning'
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'kpis' => [
                        'total_colleges' => $totalColleges,
                        'total_students' => $totalStudents,
                        'total_staff' => $totalStaff,
                        'daily_attendance_rate' => $dailyAttendanceRate,

                        'trends' => [
                            'colleges' => $collegesTrendText,
                            'students' => $studentsTrendText,
                            'staff' => $staffTrendText,
                            'attendance' => $attendanceTrendText
                        ]
                    ],
                    'colleges_performance' => $collegesPerformance,
                    'alerts' => $alerts
                ]
            ]);

        } catch (\Exception $e) {
            // هذا الكود سيعرض لك سبب الخطأ الحقيقي في الـ Response
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ], 500);
        }
    }
}