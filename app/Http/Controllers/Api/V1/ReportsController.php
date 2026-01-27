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
use App\Models\CourseAssessment;

class ReportsController extends Controller
{
    /**
       * عرض لوحة التقارير الشاملة (المالية، المحاضرين، المقررات)
    */
    public function index(Request $request, $collegeId)
    {
        try {
            // 1. استلام الفلاتر من الواجهة
            $year = $request->get('academic_year');
            $month = $request->get('month');
            
            $semesterId = $request->get('semester_id');
            $departmentId = $request->get('department_id');
            $programId = $request->get('program_id');
            $levelId = $request->get('level_id');

            // دالة مساعدة (Closure) لتطبيق فلتر الشهر عند الحاجة
            $applyMonthFilter = function($query) use ($month) {
                if ($month && $month !== 'all') {
                    $query->whereMonth('session_date', $month);
                }
            };

            // ----------------------------------------------------------------
            // أولاً: الفلاتر الهيكلية المشتركة (Scope) لتصفية الجداول الدراسية
            // ----------------------------------------------------------------
            $timetableScope = function($q) use ($collegeId, $year, $semesterId, $departmentId, $programId, $levelId) {
                $q->where('college_id', $collegeId);
                
                // فلترة بالسنة الدراسية
                if ($year && $year !== 'all') {
                    $q->where('academic_year', $year);
                }

                // فلترة بالهيكل الأكاديمي
                if ($departmentId) $q->where('department_id', $departmentId);
                if ($levelId) $q->where('level_id', $levelId);

                // فلترة عبر المواد (للبرنامج والترم)
                $q->whereHas('course', function($c) use ($programId, $semesterId) {
                    if ($programId) $c->where('program_id', $programId);
                    if ($semesterId) $c->where('semester_id', $semesterId);
                });
            };

            // ----------------------------------------------------------------
            // القسم (أ): التقارير المالية والمؤشرات (Financial KPIs)
            // ----------------------------------------------------------------
            
            // استعلام أساسي للجلسات (Lecture Sessions) بناءً على فلاتر الجدول + الشهر
            $sessionsQuery = \App\Models\LectureSession::whereHas('timetable', $timetableScope)
                ->tap($applyMonthFilter);

            // 1. المحاضرات المعتمدة (إجمالي الجلسات المجدولة في الفترة)
            $approvedSessions = (clone $sessionsQuery)->count();

            // 2. المحاضرات المنفذة (تم التحضير فيها)
            $executedSessions = (clone $sessionsQuery)->where('status', 1)->count();

            // 3. المحاضرات الفائتة/الغياب (لم تنفذ وانتهى وقتها)
            $missedSessions = (clone $sessionsQuery)
                ->where('status', 0)
                ->where('session_date', '<', \Carbon\Carbon::now())
                ->count();

            // 4. التعويض المالي المقدر (من واقع سجلات الحضور المعتمدة)
            // نستخدم جدول LecturerAttendance لأنه الأدق مالياً
            $compensation = \App\Models\LecturerAttendance::whereHas('timetable', $timetableScope)
                ->when($month && $month !== 'all', function($q) use ($month) {
                    $q->whereMonth('attendance_date', $month);
                })
                ->sum('lecture_rate_at_attendance');


            // ----------------------------------------------------------------
            // القسم (ب): تقرير أداء ومستحقات المحاضرين (Instructors)
            // ----------------------------------------------------------------
            
            $instructorsQuery = \App\Models\Lecturer::where('college_id', $collegeId);

            if ($departmentId) {
                $instructorsQuery->where('department_id', $departmentId);
            }

            $instructors = $instructorsQuery
                ->with(['user:user_id,full_name', 'department:department_id,department_name', 'academicTitle'])
                ->get() // نأخذ الكل لحساب الإجماليات بدقة، أو يمكن استخدام paginate في جداول منفصلة
                ->map(function ($lecturer) use ($year, $applyMonthFilter) {
                    
                    // 1. تحديد جداول هذا المحاضر للسنة المحددة
                    $timetableIds = DB::table('timetable')
                        ->where('lecturer_id', $lecturer->lecturer_id)
                        ->when($year && $year !== 'all', function($q) use ($year) {
                            $q->where('academic_year', $year);
                        })
                        ->pluck('timetable_id');

                    // 2. حساب إحصائيات الجلسات (مع تطبيق فلتر الشهر)
                    $stats = DB::table('lecture_sessions')
                        ->whereIn('timetable_id', $timetableIds)
                        ->tap($applyMonthFilter)
                        ->selectRaw('
                            COUNT(*) as approved,
                            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as delivered,
                            SUM(CASE WHEN status = 0 AND session_date < NOW() THEN 1 ELSE 0 END) as absences
                        ')
                        ->first();

                    // 3. حساب الساعات الفعلية (Total Hours) بدقة
                    // يجب ضرب عدد الجلسات المنفذة لكل مقرر في عدد ساعات ذلك المقرر
                    $totalHours = 0;
                    
                    // نجلب الجداول المرتبطة بهذا المحاضر
                    $timetables = DB::table('timetable')
                        ->whereIn('timetable_id', $timetableIds)
                        ->select('timetable_id', 'lecture_hours')
                        ->get();

                    foreach($timetables as $tt) {
                        // نعد الجلسات المنفذة لهذا الجدول بالتحديد (في الشهر المحدد)
                        $count = DB::table('lecture_sessions')
                            ->where('timetable_id', $tt->timetable_id)
                            ->where('status', 1)
                            ->tap($applyMonthFilter)
                            ->count();
                        
                        $totalHours += ($count * (float)$tt->lecture_hours);
                    }

                    // 4. البيانات المالية (السعر والمبلغ)
                    $academicTitle = $lecturer->academicTitle;
                    
                    // محاولة جلب الرتبة يدوياً إذا لم تأتِ مع العلاقة
                    if (!$academicTitle && $lecturer->title_id) {
                        $academicTitle = DB::table('academic_titles')->where('title_id', $lecturer->title_id)->first();
                    }

                    $hourlyPrice = $academicTitle ? (float)$academicTitle->hourly_price : 0;
                    $totalAmount = $totalHours * $hourlyPrice;
                    $rankName = $academicTitle ? ($academicTitle->title_name ?? 'محاضر') : 'محاضر';

                    // 5. نسبة الالتزام
                    $approvedCount = $stats->approved ?? 0;
                    $deliveredCount = $stats->delivered ?? 0;
                    $complianceRate = ($approvedCount > 0) ? ($deliveredCount / $approvedCount) * 100 : 0;

                    // تجاهل المحاضرين الذين ليس لديهم أي نشاط في الفترة المحددة (اختياري لتنظيف التقرير)
                    // if ($approvedCount == 0) return null; 

                    return [
                        'id' => $lecturer->lecturer_id,
                        'name' => $lecturer->user->full_name ?? 'غير معروف',
                        'academic_rank' => $rankName,
                        'department' => $lecturer->department->department_name ?? '-',
                        
                        'approved' => $approvedCount,
                        'delivered' => $deliveredCount,
                        'absences' => $stats->absences ?? 0,
                        'makeups' => 0, // يمكن ربطه بجدول التعويضات لاحقاً
                        
                        'total_hours' => $totalHours,
                        'compliance_rate' => round($complianceRate, 1),
                        
                        'hourly_price' => $hourlyPrice,
                        'total_amount' => $totalAmount,
                        'employment_status' => 'متفرغ' // أو $lecturer->employment_type
                    ];
                })
                ->filter() // إزالة القيم الفارغة (nulls) إذا فعلنا شرط الإخفاء
                ->values(); // إعادة فهرسة المصفوفة


            // ----------------------------------------------------------------
            // القسم (ج): تقرير المقررات الدراسية (Courses)
            // ----------------------------------------------------------------
            
            $coursesQuery = \App\Models\Course::query()->where('college_id', $collegeId);

            // تطبيق الفلاتر الهرمية
            if ($departmentId) $coursesQuery->where('department_id', $departmentId);
            if ($programId) $coursesQuery->where('program_id', $programId);
            if ($levelId) $coursesQuery->where('level_id', $levelId);
            if ($semesterId) $coursesQuery->where('semester_id', $semesterId);

            $courses = $coursesQuery
                ->with(['department', 'program', 'level', 'semester'])
                ->withCount(['timetable as total_sessions_count' => function($q) use ($year) {
                     if ($year && $year !== 'all') $q->where('academic_year', $year);
                }])
                ->take(50) // تحديد العدد للأداء
                ->get()
                ->map(function ($course) use ($year) {
                    
                    // لحساب نسبة الحضور التقريبية للمقرر
                    // (ملاحظة: الحساب الدقيق يتم عند النقر على المقرر في التقرير التفصيلي)
                    $attendanceRate = 0;
                    
                    return [
                        'course_id' => $course->course_id,
                        'course_name' => $course->course_name,
                        'course_code' => $course->course_code,
                        'notes' => $course->notes,
                        
                        'department_name' => optional($course->department)->department_name ?? 'عام',
                        'program_name' => optional($course->program)->program_name ?? '-',
                        'level_name' => optional($course->level)->level_name ?? (optional($course->level)->level_number ?? '-'),
                        'semester_name' => optional($course->semester)->semester_name ?? ('الترم ' . optional($course->semester)->term_number ?? '-'),
                        
                        'total_lectures' => $course->total_sessions_count ?? 0,
                        'attendance_rate' => $attendanceRate,
                        'students_count' => 0 // يتطلب استعلاماً ثقيلاً، يفضل جلبه عند الطلب
                    ];
                });

            // ----------------------------------------------------------------
            // النهاية: إرجاع البيانات
            // ----------------------------------------------------------------
            
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

        } catch (\Exception $e) {
            return response()->json([
                'status' => false, 
                'message' => 'Server Error: ' . $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ], 500);
        }
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

    // إضافة دالة جديدة لجلب مجموعات مقرر معين مع الإحصائيات
    public function getCourseGroups(Request $request, $collegeId)
    {
        try {
            $courseId = $request->course_id;
            if (!$courseId) return response()->json(['status' => true, 'data' => []]);

            $year = $request->academic_year;

            // 1. الحصول على IDs المجموعات الفريدة المرتبطة بهذا المقرر (لمنع التكرار)
            $groupIds = DB::table('timetable')
                ->where('college_id', $collegeId)
                ->where('course_id', $courseId)
                ->when($year, function ($q) use ($year) {
                    $q->where('academic_year', $year);
                })
                ->distinct()
                ->pluck('group_id');

            // 2. جلب بيانات المجموعات وحساب الإحصائيات المجمعة
            $groupsData = [];

            foreach ($groupIds as $groupId) {
                // أ) اسم المجموعة
                $groupName = DB::table('student_groups')
                    ->where('group_id', $groupId)
                    ->value('group_name');

                if (!$groupName) continue;

                // ب) عدد الطلاب في المجموعة
                $studentsCount = DB::table('student_group_members')
                    ->where('group_id', $groupId)
                    ->count();

                // ج) العثور على كل IDs الجدول (timetable_ids) لهذه المجموعة والمقرر
                // لأن المجموعة قد يكون لها أكثر من موعد (أحد، ثلاثاء) أو سجلات متعددة
                $timetableIds = DB::table('timetable')
                    ->where('college_id', $collegeId)
                    ->where('course_id', $courseId)
                    ->where('group_id', $groupId)
                    ->when($year, function ($q) use ($year) {
                        $q->where('academic_year', $year);
                    })
                    ->pluck('timetable_id');

                // د) حساب الجلسات المنفذة (status = 1)
                // نبحث في جدول الجلسات عن أي جلسة تابعة لأي من timetable_ids هذه
                $executedSessionsCount = DB::table('lecture_sessions')
                    ->whereIn('timetable_id', $timetableIds)
                    ->where('status', 1) // منفذة
                    ->count();

                // هـ) حساب حضور الطلاب الفعلي
                // نبحث في جدول حضور الطلاب عن أي سجل تابع لأي من timetable_ids هذه
                $totalStudentAttendance = DB::table('student_attendance')
                    ->whereIn('timetable_id', $timetableIds)
                    ->where('status', 1) // حاضر
                    ->count();

                // و) حساب النسبة
                $percentage = 0;
                $totalPossibleAttendance = $executedSessionsCount * $studentsCount;
                
                if ($totalPossibleAttendance > 0) {
                    $percentage = ($totalStudentAttendance / $totalPossibleAttendance) * 100;
                }

                $groupsData[] = [
                    'group_id' => $groupId,
                    'group_name' => $groupName,
                    'students_count' => $studentsCount,
                    'attendance_percentage' => round($percentage, 1),
                    'sessions_count' => $executedSessionsCount
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $groupsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false, 
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // دالة لجلب تفاصيل حضور الطلاب في مجموعة ومقرر معين
    public function getGroupStudentsAttendance(Request $request, $collegeId)
    {
        try {
            $courseId = $request->course_id;
            $groupId = $request->group_id;
            $year = $request->academic_year;

            if (!$courseId || !$groupId) {
                return response()->json(['status' => false, 'message' => 'Missing parameters'], 400);
            }

            // 1. تحديد معرفات الجدول (Timetable IDs) ذات الصلة
            // هذا هو الرابط الأساسي بين الطالب والمقرر في هذه السنة
            $timetableIds = DB::table('timetable')
                ->where('college_id', $collegeId)
                ->where('course_id', $courseId)
                ->where('group_id', $groupId)
                ->when($year, function ($q) use ($year) {
                    $q->where('academic_year', $year);
                })
                ->pluck('timetable_id');

            if ($timetableIds->isEmpty()) {
                return response()->json(['status' => true, 'data' => []]);
            }

            // 2. إحصائيات الجلسات العامة لهذه المجموعة
            // المعتمدة: كل الجلسات الموجودة في الجدول (سواء نفذت أو لا)
            $approvedSessionsCount = DB::table('lecture_sessions')
                ->whereIn('timetable_id', $timetableIds)
                ->count();

            // المنفذة: الجلسات التي تمت بالفعل (status = 1)
            $executedSessionsCount = DB::table('lecture_sessions')
                ->whereIn('timetable_id', $timetableIds)
                ->where('status', 1)
                ->count();

            // 3. جلب الطلاب وإحصائياتهم
            $students = DB::table('student_group_members')
                ->join('students', 'student_group_members.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('student_group_members.group_id', $groupId)
                ->select(
                    'students.student_id',
                    'users.full_name as student_name',
                    'users.academic_number'
                )
                ->orderBy('users.full_name')
                ->get()
                ->map(function ($student) use ($timetableIds, $executedSessionsCount, $approvedSessionsCount) {
                    
                    // جلب سجلات الحضور لهذا الطالب في هذه الجلسات
                    $attendanceStats = DB::table('student_attendance')
                        ->whereIn('timetable_id', $timetableIds)
                        ->where('student_id', $student->student_id)
                        ->selectRaw('count(*) as total, sum(case when status = 1 then 1 else 0 end) as present')
                        ->first();

                    $presentCount = $attendanceStats->present ?? 0;
                    
                    // الغياب = (عدد الجلسات المنفذة - عدد مرات الحضور)
                    // ملاحظة: نعتمد على الجلسات المنفذة لحساب الغياب بدقة
                    $absentCount = max(0, $executedSessionsCount - $presentCount);

                    // حساب النسبة
                    $percentage = 0;
                    if ($executedSessionsCount > 0) {
                        $percentage = ($presentCount / $executedSessionsCount) * 100;
                    }

                    return [
                        'student_id' => $student->student_id,
                        'name' => $student->student_name,
                        'academic_number' => $student->academic_number,
                        'total_sessions_approved' => $approvedSessionsCount, // المجدولة
                        'total_sessions_executed' => $executedSessionsCount, // المنفذة فعلياً
                        'present_count' => $presentCount,
                        'absent_count' => $absentCount,
                        'attendance_percentage' => round($percentage, 1)
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $students
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * بيانات لوحة التحكم الرئيسية (Dashboard)
    */
    public function dashboard($collegeId)
    {
        try {
            $today = Carbon::today()->toDateString();
            $now = Carbon::now()->format('H:i:s');
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            // 1. Counts
            $counts = [
                'departments' => DB::table('departments')->where('college_id', $collegeId)->count(),
                'classrooms'  => DB::table('classrooms')
                                    ->join('buildings', 'classrooms.building_id', '=', 'buildings.building_id')
                                    ->where('buildings.college_id', $collegeId)
                                    ->count(),
                'programs'    => DB::table('programs')
                                    ->join('departments', 'programs.department_id', '=', 'departments.department_id')
                                    ->where('departments.college_id', $collegeId)
                                    ->count(),
                'staff'       => DB::table('lecturers')->where('college_id', $collegeId)->count(),
            ];

            // 2. Financials
            // أ) مصروفات الشهر الحالي
            $monthKey = sprintf("%02d-%s", $currentMonth, $currentYear);
            
            // استخدام DB::raw للتعامل مع net_amount بأمان
            $currentMonthExpense = DB::table('lecturer_payouts')
                ->join('financial_cycles', 'lecturer_payouts.cycle_id', '=', 'financial_cycles.cycle_id')
                ->where('financial_cycles.college_id', $collegeId)
                ->where('financial_cycles.month_year', $monthKey)
                ->sum('lecturer_payouts.net_amount'); // تأكد أن العمود موجود

            // ب) آخر 6 أشهر
            $lastSixMonths = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $m = $date->month;
                $y = $date->year;
                $key = sprintf("%04d-%02d", $y, $m);
                $dbKey = sprintf("%02d-%s", $m, $y);

                $amount = DB::table('financial_cycles')
                    ->where('college_id', $collegeId)
                    ->where('month_year', $dbKey)
                    ->value('total_payout') ?? 0;

                $lastSixMonths[] = [
                    'month_key' => $key,
                    'total_amount' => $amount
                ];
            }

            // ج) أعلى المنفقين
            $topSpenders = DB::table('lecturer_payouts')
                ->join('lecturers', 'lecturer_payouts.lecturer_id', '=', 'lecturers.lecturer_id')
                ->join('users', 'lecturers.user_id', '=', 'users.user_id')
                ->join('departments', 'lecturers.department_id', '=', 'departments.department_id')
                ->join('financial_cycles', 'lecturer_payouts.cycle_id', '=', 'financial_cycles.cycle_id')
                ->where('financial_cycles.college_id', $collegeId)
                ->select(
                    'users.full_name as name',
                    'departments.department_name as department',
                    DB::raw('SUM(lecturer_payouts.total_hours) as hours'),
                    DB::raw('SUM(lecturer_payouts.net_amount) as amount')
                )
                ->groupBy('users.full_name', 'departments.department_name')
                ->orderByDesc('amount')
                ->limit(5)
                ->get();

            // 3. Quick Stats
            
            // استعلام أساسي للجلسات
            $baseSessionQuery = DB::table('lecture_sessions')
                ->join('timetable', 'lecture_sessions.timetable_id', '=', 'timetable.timetable_id')
                ->where('timetable.college_id', $collegeId)
                ->where('lecture_sessions.session_date', $today);

            $todaySessions = (clone $baseSessionQuery)->count();
            
            $todayAttendance = (clone $baseSessionQuery)
                ->where('lecture_sessions.status', 1)
                ->count();

            $todayAbsence = (clone $baseSessionQuery)
                ->where('lecture_sessions.status', 0)
                ->where('lecture_sessions.end_time', '<', $now)
                ->count();

            $busyRooms = (clone $baseSessionQuery)
                ->where('lecture_sessions.start_time', '<=', $now)
                ->where('lecture_sessions.end_time', '>=', $now)
                ->distinct('lecture_sessions.actual_classroom_id')
                ->count('lecture_sessions.actual_classroom_id');

            return response()->json([
                'status' => true,
                'data' => [
                    'counts' => $counts,
                    'financials' => [
                        'current_month' => $currentMonthExpense,
                        'last_six_months' => $lastSixMonths,
                        'top_spenders' => $topSpenders
                    ],
                    'quick_stats' => [
                        'today_sessions' => $todaySessions,
                        'today_attendance' => $todayAttendance,
                        'today_absence' => $todayAbsence,
                        'busy_rooms' => $busyRooms
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    public function getGroupGradesReport(Request $request, $collegeId)
    {
        $request->validate([
            'course_id' => 'required|integer',
            'group_id' => 'required|integer',
            'academic_year' => 'required|string',
        ]);
    
        $courseId = $request->course_id;
        $groupId = $request->group_id;
        $academicYear = $request->academic_year;
    
        // 1. جلب أعمدة التقييم (بدون تحديد الترم لتجنب التعقيد، نعتمد على المجموعة والسنة)
        // يمكننا جلب semester_id من المجموعة للتأكد
        $group = DB::table('student_groups')->where('group_id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Group not found'], 404);
    
        $assessments = CourseAssessment::where([
                ['course_id', '=', $courseId],
                ['group_id', '=', $groupId],
                ['academic_year', '=', $academicYear],
            ])
            ->select('assessment_id', 'name', 'max_score', 'weight')
            ->get();
    
        // 2. جلب الطلاب
        $students = DB::table('student_group_members')
            ->join('students', 'student_group_members.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('student_group_members.group_id', $groupId)
            ->select('students.student_id', 'users.full_name', 'users.academic_number')
            ->orderBy('users.full_name')
            ->get();
    
        // 3. جلب الدرجات
        $assessmentIds = $assessments->pluck('assessment_id')->toArray();
        $grades = [];
        if (!empty($assessmentIds)) {
            $grades = DB::table('student_grades')
                ->whereIn('assessment_id', $assessmentIds)
                ->select('student_id', 'assessment_id', 'score')
                ->get();
        }
        $gradesCollection = collect($grades);
    
        // 4. جلب إحصائيات الحضور (اختياري للتقرير، لكن مفيد)
        // نستخدم timetable بدون semester_id كما اتفقنا سابقاً
        $timetableIds = DB::table('timetable')
            ->where('course_id', $courseId)
            ->where('group_id', $groupId)
            ->where('academic_year', $academicYear)
            ->pluck('timetable_id')
            ->toArray();
    
        $attendanceStats = [];
        $totalSessions = 0;
        
        if (!empty($timetableIds)) {
            $totalSessions = DB::table('lecture_sessions')
                ->whereIn('timetable_id', $timetableIds)
                ->where('status', '!=', 0)
                ->count();
    
            $attendanceRaw = DB::table('student_attendance')
                ->whereIn('timetable_id', $timetableIds)
                ->where('status', 1)
                ->select('student_id', DB::raw('count(*) as count'))
                ->groupBy('student_id')
                ->get();
                
            foreach($attendanceRaw as $row) {
                $attendanceStats[$row->student_id] = $row->count;
            }
        }
    
        // 5. تجميع البيانات
        $reportData = $students->map(function ($student) use ($assessments, $gradesCollection, $attendanceStats, $totalSessions) {
            $studentGrades = [];
            $totalScore = 0;
    
            foreach ($assessments as $col) {
                $gradeRecord = $gradesCollection->where('student_id', $student->student_id)
                                                ->where('assessment_id', $col->assessment_id)
                                                ->first();
                $score = $gradeRecord ? $gradeRecord->score : 0; // نعتبر غير المرصود صفراً في التقرير
                $studentGrades[$col->assessment_id] = $score;
                $totalScore += $score;
            }
    
            $attended = $attendanceStats[$student->student_id] ?? 0;
            $attendancePerc = $totalSessions > 0 ? round(($attended / $totalSessions) * 100) : 0;
    
            return [
                'student_id' => $student->student_id,
                'academic_number' => $student->academic_number,
                'full_name' => $student->full_name,
                'grades' => $studentGrades,
                'total_score' => $totalScore,
                'attendance_percent' => $attendancePerc
            ];
        });
    
        return response()->json([
            'columns' => $assessments,
            'students' => $reportData,
            'meta' => [
                'total_sessions' => $totalSessions,
                'course_name' => DB::table('courses')->where('course_id', $courseId)->value('course_name'),
                'group_name' => $group->group_name
            ]
        ]);
    }
}