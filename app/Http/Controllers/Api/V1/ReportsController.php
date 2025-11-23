<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lecturer;
use App\Models\LectureSession;
use App\Models\LecturerAttendance;
use App\Models\Course;
use App\Models\StudentAttendance;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * 1. التقرير الرئيسي للوحة التحكم (Dashboard Data)
     */
    public function index(Request $request, $collegeId)
    {
        $year = $request->get('year', date('Y'));
        // يمكن إضافة فلتر للفصل الدراسي هنا إذا توفر في قاعدة البيانات

        // --- أ. التقارير المالية (Financial KPIs) ---
        
        // 1. المحاضرات المعتمدة (إجمالي الجلسات المجدولة لهذا العام)
        $approvedSessions = LectureSession::whereHas('timetable', function($q) use ($collegeId) {
            $q->where('college_id', $collegeId);
        })->whereYear('session_date', $year)->count();

        // 2. المحاضرات المنفذة (status = 1)
        $executedSessions = LectureSession::whereHas('timetable', function($q) use ($collegeId) {
            $q->where('college_id', $collegeId);
        })->where('status', 1)->whereYear('session_date', $year)->count();

        // 3. التعويض المقدر (مجموع المبالغ من جدول حضور المحاضر)
        $compensation = LecturerAttendance::where('college_id', $collegeId)
            ->whereYear('attendance_date', $year)
            ->sum('lecture_rate_at_attendance');

        // 4. الغياب/التأخير (جلسات فائتة ولم تنفذ)
        $missedSessions = LectureSession::whereHas('timetable', function($q) use ($collegeId) {
            $q->where('college_id', $collegeId);
        })
        ->where('status', 0)
        ->where('session_date', '<', Carbon::now())
        ->whereYear('session_date', $year)
        ->count();


        // --- ب. تقرير حضور المحاضرين (Instructor Attendance) ---
        $instructors = Lecturer::where('college_id', $collegeId)
            ->with(['user:user_id,full_name', 'department:department_id,department_name'])
            ->withCount([
                // 1. المعتمدة
                'lectureSessions as approved_count' => function ($q) use ($year) {
                    $q->whereYear('lecture_sessions.session_date', $year);
                },
                
                // 2. المنفذة
                'lectureSessions as delivered_count' => function ($q) use ($year) {
                    $q->where('lecture_sessions.status', 1)
                      ->whereYear('lecture_sessions.session_date', $year);
                },

                // 3. الغياب
                'lectureSessions as absences_count' => function ($q) use ($year) {
                    $q->where('lecture_sessions.status', 0)
                      ->where('lecture_sessions.session_date', '<', Carbon::now())
                      ->whereYear('lecture_sessions.session_date', $year);
                },
                
                // 4. التعويضية
                'lectureSessions as makeups_count' => function ($q) use ($year) {
                    $q->where('lecture_sessions.status', 1)
                      ->whereYear('lecture_sessions.session_date', $year)
                      ->whereHas('timetable', function($t) {
                          // هنا لا نحتاج لتحديد الجدول لأن lecture_type موجود فقط في timetable
                          $t->where('lecture_type', 1);
                      });
                }
            ])
            ->get()
            ->map(function ($lecturer) {
                // ... (باقي كود الـ map كما هو بدون تغيير) ...
                // جلب أسماء القاعات...
                $rooms = DB::table('lecture_sessions')
                    ->join('timetable', 'lecture_sessions.timetable_id', '=', 'timetable.timetable_id')
                    ->join('classrooms', 'timetable.classroom_id', '=', 'classrooms.classroom_id')
                    ->where('timetable.lecturer_id', $lecturer->lecturer_id)
                    ->where('lecture_sessions.status', 1)
                    ->distinct()
                    ->pluck('classrooms.classroom_name')
                    ->take(3)
                    ->implode(', ');

                return [
                    'id' => $lecturer->lecturer_id,
                    'name' => $lecturer->user->full_name ?? 'غير معروف',
                    'department' => $lecturer->department->department_name ?? '-',
                    'approved' => $lecturer->approved_count,
                    'delivered' => $lecturer->delivered_count,
                    'absences' => $lecturer->absences_count,
                    'makeups' => $lecturer->makeups_count,
                    'rooms' => $rooms ?: '-'
                ];
            });


        // --- ج. تقرير المقررات (Course Attendance) ---
        $courses = Course::whereHas('timetable', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            })
            ->withCount('timetable as total_lectures') // عدد مرات ظهورها في الجدول
            ->take(6)
            ->get()
            ->map(function ($course) {
                // حساب نسبة الحضور (منطق تقريبي للعرض)
                // في الواقع تحتاج لحساب: (مجموع حضور الطلاب / (عدد الطلاب المسجلين * عدد الجلسات))
                $avgAttendance = 85; // قيمة افتراضية للعرض
                $studentsCount = 45; // قيمة افتراضية

                return [
                    'course' => $course->course_name,
                    'total' => $course->total_lectures * 12, // إجمالي المحاضرات بالفصل
                    'attendance' => $avgAttendance,
                    'students' => $studentsCount
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'financial' => [
                    'approved' => $approvedSessions,
                    'executed' => $executedSessions,
                    'compensation' => $compensation,
                    'missed' => $missedSessions
                ],
                'instructors' => $instructors,
                'courses' => $courses
            ]
        ]);
    }

    /**
     * 2. الوصول السريع للتقارير التفصيلية (Quick Detailed Reports)
     */
        /**
     * 2. التقارير التفصيلية + التصدير (CSV Export)
     */
    public function detailedReport(Request $request, $collegeId)
    {
        $type = $request->get('type');
        $isExport = $request->get('export') === 'true';
        $year = $request->get('year', date('Y')); // استخدام السنة المرسلة أو الحالية

        $data = [];

        switch ($type) {
            // 1. تقرير التعويضات المالية
            case 'compensation':
                $query = LecturerAttendance::where('college_id', $collegeId)
                    ->whereYear('attendance_date', $year)
                    ->with('lecturer.user')
                    ->select('lecturer_id', DB::raw('SUM(lecture_rate_at_attendance) as total'), DB::raw('SUM(lecture_hours) as hours'))
                    ->groupBy('lecturer_id')
                    ->get();
                
                $data = $query->map(fn($item) => [
                    'المحاضر' => $item->lecturer->user->full_name ?? 'غير معروف',
                    'الساعات' => $item->hours,
                    'المستحقات (ر.ي)' => $item->total
                ])->toArray();
                break;

            // 2. تقرير المحاضرات التعويضية
            case 'makeups':
                $query = LectureSession::whereHas('timetable', function($q) use ($collegeId) {
                        $q->where('college_id', $collegeId)->where('lecture_type', 1);
                    })
                    ->with(['timetable.course', 'timetable.lecturer.user'])
                    ->where('status', 1)
                    ->whereYear('session_date', $year)
                    ->get();

                $data = $query->map(fn($item) => [
                    'التاريخ' => $item->session_date,
                    'الوقت' => $item->start_time,
                    'المادة' => $item->timetable->course->course_name,
                    'المحاضر' => $item->timetable->lecturer->user->full_name
                ])->toArray();
                break;

            // ✅ 3. (الجديد) ملخص حضور المحاضرين الشامل
                        case 'instructors_summary':
                $data = Lecturer::where('college_id', $collegeId)
                    ->with(['user', 'department'])
                    ->withCount([
                        // ✅ تم تحديد اسم الجدول lecture_sessions بدقة لمنع الخطأ 500
                        'lectureSessions as approved_count' => fn($q) => $q->whereYear('lecture_sessions.session_date', $year),
                        
                        'lectureSessions as delivered_count' => fn($q) => $q->where('lecture_sessions.status', 1)
                                                                            ->whereYear('lecture_sessions.session_date', $year),
                        
                        'lectureSessions as absences_count' => fn($q) => $q->where('lecture_sessions.status', 0)
                                                                           ->where('lecture_sessions.session_date', '<', now())
                                                                           ->whereYear('lecture_sessions.session_date', $year),
                        
                        'lectureSessions as makeups_count' => fn($q) => $q->where('lecture_sessions.status', 1)
                                                                          ->whereYear('lecture_sessions.session_date', $year)
                                                                          ->whereHas('timetable', fn($t) => $t->where('lecture_type', 1))
                    ])
                    ->get()
                    ->map(fn($lec) => [
                        'الاسم' => $lec->user->full_name ?? 'غير معروف',
                        'القسم' => $lec->department->department_name ?? '-',
                        'المعتمدة' => $lec->approved_count,
                        'المنفذة' => $lec->delivered_count,
                        'الغياب' => $lec->absences_count,
                        'التعويضية' => $lec->makeups_count
                    ])->toArray();
                break;
        }

        // توليد ملف CSV عند الطلب
        if ($isExport && count($data) > 0) {
            $fileName = "report_{$type}_" . date('Y-m-d') . ".csv";
            
            return response()->streamDownload(function () use ($data) {
                $handle = fopen('php://output', 'w');
                // BOM لدعم اللغة العربية في Excel
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // كتابة العناوين (من مفاتيح أول صف)
                fputcsv($handle, array_keys($data[0]));

                // كتابة البيانات
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * 3. منشئ التقارير المخصصة (Custom Builder)
     */
    public function customBuilder(Request $request, $collegeId)
    {
        // هذا محاكي لمنشئ التقارير، يمكن توسيعه
        $source = $request->input('source'); // instructors, students, financial
        $fields = $request->input('fields', []); // ['name', 'department', 'total']
        $filters = $request->input('filters', []); 

        $query = null;
        $result = [];

        if ($source === 'instructors') {
            $query = Lecturer::where('college_id', $collegeId)->with('user');
            // تطبيق الفلاتر البسيطة
            if (isset($filters['department'])) {
                $query->where('department_id', $filters['department']);
            }
            
            $result = $query->get()->map(function($lec) use ($fields) {
                $row = [];
                if (in_array('name', $fields)) $row['name'] = $lec->user->full_name;
                if (in_array('department', $fields)) $row['department'] = $lec->department_id; // يحتاج علاقة للقسم
                // ... باقي الحقول
                return $row;
            });
        } elseif ($source === 'financial') {
            $query = LecturerAttendance::where('college_id', $collegeId);
            // ... منطق مشابه
            $result = $query->take(20)->get();
        }

        return response()->json([
            'status' => true,
            'message' => 'تم توليد التقرير المخصص',
            'data' => $result
        ]);
    }

    public function lecturerDetails(Request $request, $collegeId, $lecturerId)
    {
        $year = $request->get('year', date('Y'));

        // 1. جلب البيانات الأساسية للمحاضر
        $lecturer = Lecturer::with(['user', 'department', 'academicTitle'])->findOrFail($lecturerId);

        // 2. الملخص المالي (من جدول الحضور المالي)
        $financialSummary = LecturerAttendance::where('lecturer_id', $lecturerId)
            ->where('college_id', $collegeId)
            ->whereYear('attendance_date', $year)
            ->selectRaw('
                SUM(lecture_hours) as total_hours,
                SUM(lecture_rate_at_attendance) as total_earned,
                COUNT(attendance_id) as total_sessions
            ')
            ->first();

        // 3. سجل المحاضرات التفصيلي (تاريخي)
        $historyQuery = LecturerAttendance::where('lecturer_id', $lecturerId)
            ->where('college_id', $collegeId)
            ->whereYear('attendance_date', $year)
            ->with('timetable.course') // لجلب اسم المادة
            ->orderBy('attendance_date', 'desc');

        // إذا لم يكن تصدير، نكتفي بآخر 20 سجل للعرض في الواجهة
        if ($request->get('export') !== 'true') {
            $historyQuery->take(20);
        }

        // جلب البيانات وتحويلها لصيغة مقروءة
        $history = $historyQuery->get()->map(function ($record) {
            return [
                'date' => $record->attendance_date->format('Y-m-d'),
                'course' => $record->timetable->course->course_name ?? 'غير محدد',
                'type' => ($record->timetable->lecture_type ?? 0) == 1 ? 'تعويضي' : 'أساسي',
                'hours' => $record->lecture_hours,
                'amount' => $record->lecture_rate_at_attendance,
                'status' => 'مدفوع'
            ];
        });

        // ---------------------------------------------
        // ✅ حالة التصدير (CSV Export)
        // ---------------------------------------------
        if ($request->get('export') === 'true') {
            // اسم الملف المقترح
            $fileName = "Statement_" . str_replace(' ', '_', $lecturer->user->full_name) . "_" . date('Y-m-d') . ".csv";
            
            return response()->streamDownload(function () use ($history, $lecturer, $financialSummary, $year) {
                $handle = fopen('php://output', 'w');
                
                // إضافة BOM ليدعم Excel اللغة العربية بشكل صحيح
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); 
                
                // 1. ترويسة التقرير (Header Info)
                fputcsv($handle, ['كشف حساب محاضر', '']);
                fputcsv($handle, ['الاسم', $lecturer->user->full_name]);
                fputcsv($handle, ['القسم', $lecturer->department->department_name ?? '-']);
                fputcsv($handle, ['الرتبة العلمية', $lecturer->academicTitle->title_name ?? '-']);
                fputcsv($handle, ['السنة', $year]);
                fputcsv($handle, []); // سطر فارغ للفصل
                
                // 2. ملخص مالي
                fputcsv($handle, ['ملخص المستحقات', '']);
                fputcsv($handle, ['إجمالي الساعات', $financialSummary->total_hours ?? 0]);
                fputcsv($handle, ['عدد الجلسات', $financialSummary->total_sessions ?? 0]);
                fputcsv($handle, ['إجمالي المبلغ (ر.ي)', $financialSummary->total_earned ?? 0]);
                fputcsv($handle, []); // سطر فارغ
                
                // 3. جدول التفاصيل (Table Headers)
                fputcsv($handle, ['التاريخ', 'المادة', 'نوع المحاضرة', 'عدد الساعات', 'المبلغ المستحق']);

                // 4. تعبئة البيانات (Rows)
                foreach ($history as $row) {
                    fputcsv($handle, [
                        $row['date'],
                        $row['course'],
                        $row['type'],
                        $row['hours'],
                        $row['amount']
                    ]);
                }
                
                fclose($handle);
            }, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$fileName\""
            ]);
        }

        // ---------------------------------------------
        // ✅ حالة العرض العادي (JSON Response)
        // ---------------------------------------------
        return response()->json([
            'status' => true,
            'data' => [
                'info' => [
                    'name' => $lecturer->user->full_name,
                    'department' => $lecturer->department->department_name ?? '-',
                    'title' => $lecturer->academicTitle->title_name ?? 'غير محدد',
                    'hourly_rate' => $lecturer->academicTitle->hourly_price ?? 0,
                ],
                'stats' => [
                    'total_hours' => $financialSummary->total_hours ?? 0,
                    'total_earned' => $financialSummary->total_earned ?? 0,
                    'total_sessions' => $financialSummary->total_sessions ?? 0,
                ],
                'history' => $history
            ]
        ]);
    }
}