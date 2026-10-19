<?php

namespace App\Http\Controllers\Api\V1\QA\Reports;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseExecutionReportController extends Controller
{
    // 1. أضف هذه الدالة الجديدة
    public function getFiltersMeta(Request $request)
    {
        $collegeId = $request->college_id;
    
        // جلب السنوات الدراسية الفريدة من الجدول
        $academicYears = Timetable::where('college_id', $collegeId)
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
    
        // جلب الأقسام
        $departments = \App\Models\Department::where('college_id', $collegeId)
            ->select('department_id', 'department_name')
            ->get();
    
        // جلب البرامج
        $programs = \App\Models\Program::whereHas('department', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            })
            ->select('program_id', 'program_name')
            ->get();
    
        return response()->json([
            'academic_years' => $academicYears,
            'departments' => $departments,
            'programs' => $programs
        ]);
    }

    /**
     * 1. عرض قائمة الجداول مع الفلترة
     */
    public function index(Request $request)
    {
        $query = Timetable::query()
            ->with(['course', 'lecturer.user', 'group', 'department', 'program'])
            ->where('college_id', $request->college_id);
    
        // تطبيق الفلاتر
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        
        if ($request->filled('semester_id')) {
            // إذا كان لديك عمود semester_id في Timetable
            $query->where('semester_id', $request->semester_id);
        }
    
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
    
        if ($request->filled('program_id')) {
            // نفترض أن الجدول مرتبط ببرنامج عبر student_groups أو مباشرة
            // أو نفلتر عبر العلاقة
            $query->where('department_id', function($q) use ($request) {
                 // منطق مخصص حسب هيكلتك، أو إذا كان العمود موجود مباشرة
            });
            // للتسهيل، سنفترض وجود علاقة program أو نكتفي بالقسم حالياً
        }

        $timetables = $query->get()->map(function ($t) {
            return [
                'timetable_id' => $t->timetable_id,
                'course_name' => $t->course->course_name,
                'course_code' => $t->course->course_code,
                'lecturer_name' => $t->lecturer->user->full_name,
                'group_name' => $t->group->group_name,
                'program_name' => $t->program->program_name ?? '-',
                'level_name' => $t->level_id ?? '-', // أو جلب الاسم من العلاقة
                'students_count' => DB::table('student_group_members')->where('group_id', $t->group_id)->count(),
                // نسبة الإنجاز العامة (عدد الجلسات المنفذة)
                'sessions_held' => DB::table('lecture_sessions')->where('timetable_id', $t->timetable_id)->where('status', 1)->count()
            ];
        });

        return response()->json($timetables);
    }

    /**
     * 2. التقرير التفصيلي (العميق)
     */
    public function show($id)
    {
        $timetable = Timetable::with(['course.topics', 'lecturer.user', 'group'])->findOrFail($id);

        // أ. إحصائيات الجلسات والمواضيع
        $sessions = \App\Models\LectureSession::where('timetable_id', $id)
            ->where('status', 1) // المنفذة فقط
            ->orderBy('session_date')
            ->get();

        $sessionDetails = [];
        $totalTopicsCovered = 0;

        foreach ($sessions as $session) {
            // جلب المواضيع التي تم شرحها في هذه الجلسة
            $topics = DB::table('session_topics_covered')
                ->join('course_topics', 'session_topics_covered.topic_id', '=', 'course_topics.topic_id')
                ->where('session_topics_covered.session_id', $session->session_id)
                ->select('course_topics.title', 'session_topics_covered.coverage_status')
                ->get();

            // جلب إحصائيات أسئلة الطلاب في هذه الجلسة (التقييم الفوري)
            $questionsStats = DB::table('student_lecture_answers')
                ->where('session_id', $session->session_id)
                ->selectRaw('count(distinct student_id) as participating_students')
                ->selectRaw('sum(is_correct) as correct_answers')
                ->selectRaw('count(*) as total_answers')
                ->first();

            $successRate = $questionsStats->total_answers > 0 
                ? round(($questionsStats->correct_answers / $questionsStats->total_answers) * 100, 1) 
                : 0;

            $sessionDetails[] = [
                'session_date' => $session->session_date,
                'topics' => $topics,
                'attendance_count' => $session->actual_attendance_count ?? 0, // أو من جدول الحضور
                'quiz_stats' => [
                    'participants' => $questionsStats->participating_students,
                    'success_rate' => $successRate
                ]
            ];

            $totalTopicsCovered += $topics->count();
        }

        // ب. نسبة تغطية المقرر (Topics Coverage)
        $totalCourseTopics = DB::table('course_topics')->where('course_id', $timetable->course_id)->count();
        $coveragePercentage = $totalCourseTopics > 0 ? round(($totalTopicsCovered / $totalCourseTopics) * 100, 1) : 0;

        return response()->json([
            'header' => [
                'course' => $timetable->course->course_name,
                'lecturer' => $timetable->lecturer->user->full_name,
                'group' => $timetable->group->group_name,
                'total_sessions' => $sessions->count(),
                'topics_coverage' => $coveragePercentage . '%',
            ],
            'sessions_log' => $sessionDetails,
            'topics_master_list' => $timetable->course->topics // لعرض ما لم يتم شرحه
        ]);
    }
}