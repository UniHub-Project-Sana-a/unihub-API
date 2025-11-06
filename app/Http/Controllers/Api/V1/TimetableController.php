<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\LectureSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TimetableController extends Controller
{
    /**
     * عرض الجدول الزمني مع الفلاتر
     * GET /api/v1/timetable
     */
    public function index(Request $request)
    {
        $query = Timetable::query()
            ->with([
                'course:course_id,course_name,course_code',
                'lecturer.user:user_id,full_name',
                'group:group_id,group_name',
                'classroom:classroom_id,classroom_name',
                'day:day_id,day_name',
                'period:period_id,start_time,end_time,period_name',
            ])
            ->select(['timetable_id','course_id','lecturer_id','group_id','classroom_id','day_id','period_id','academic_year','college_id','department_id']);

        if ($request->filled('college_id'))    $query->where('college_id', (int)$request->college_id);
        if ($request->filled('department_id')) $query->where('department_id', (int)$request->department_id);
        if ($request->filled('program_id'))    $query->whereHas('course.semester.level.program', fn($q)=>$q->where('program_id', (int)$request->program_id));
        if ($request->filled('level_id'))      $query->whereHas('course.semester.level', fn($q)=>$q->where('level_id', (int)$request->level_id));
        if ($request->filled('semester_id'))   $query->whereHas('course.semester', fn($q)=>$q->where('semester_id', (int)$request->semester_id));

        return response()->json($query->get());
    }

    /**
     * إنشاء سجل جدول زمني جديد وتوليد جلساته الأسبوعية
     * POST /api/v1/timetable
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id'     => ['required', 'integer', 'exists:courses,course_id'],
            'lecturer_id'   => ['required', 'integer', 'exists:lecturers,lecturer_id'],
            'group_id'      => ['required', 'integer', 'exists:student_groups,group_id'],
            'classroom_id'  => ['required', 'integer', 'exists:classrooms,classroom_id'],
            'day_id'        => ['required', 'integer', 'exists:days,day_id'],
            'period_id'     => ['required', 'integer', 'exists:periods,period_id'],
            'lecture_type'  => ['required', 'integer'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'academic_year' => ['required', 'string'],
            'college_id'    => ['required', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
        ]);
        
        // حساب ساعات المحاضرة تلقائيًا من الفترة
        $period = \App\Models\Period::find($data['period_id']);
        $data['lecture_hours'] = (strtotime($period->end_time) - strtotime($period->start_time)) / 3600;

        DB::beginTransaction();
        try {
            // 1. إنشاء سجل الجدول الزمني
            $timetable = Timetable::create($data);

            // 2. توليد الجلسات الأسبوعية بين تاريخ البداية والنهاية
            $dayName = \App\Models\Day::find($data['day_id'])->day_name; // e.g., 'Sunday'
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            // ابدأ من أول يوم يطابق اليوم المحدد بعد تاريخ البداية (أو في نفس اليوم)
            $currentDate = $startDate->copy();
            if (strtolower($currentDate->englishDayOfWeek) !== strtolower($dayName)) {
                $currentDate->next($dayName);
            }

            while ($currentDate->lte($endDate)) {
                LectureSession::create([
                    'timetable_id' => $timetable->timetable_id,
                    'session_date' => $currentDate->toDateString(),
                    'start_time'   => $period->start_time,
                    'end_time'     => $period->end_time,
                    'session_code' => Str::random(10) . '_' . $currentDate->format('Ymd'),
                ]);
                $currentDate->addWeek();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // يمكنك استخدام `report($e);` لتسجيل الخطأ في ملف اللوج
            return response()->json(['message' => 'فشل إنشاء الجدول: ' . $e->getMessage()], 500);
        }

        return response()->json($timetable, 201);
    }
}