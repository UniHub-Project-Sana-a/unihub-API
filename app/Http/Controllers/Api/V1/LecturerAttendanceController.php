<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LecturerAttendance;
use App\Models\StudentAttendance;
use App\Models\LectureSession;
use App\Models\Timetable;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LecturerAttendanceController extends Controller
{
    /**
     * يعرض قائمة بحضور المحاضر.
     * يمكن فلترته باستخدام lecturer_id.
     */
    public function index(Request $request)
    {
        $query = LecturerAttendance::query();

        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }

        $attendance = $query->latest()->get();

        return response()->json(['data' => $attendance]);
    }

    /**
     * يقوم بإنشاء سجل حضور جديد للمحاضر.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lecturer_id'     => 'required|integer|exists:lecturers,lecturer_id',
            'timetable_id'    => 'required|integer|exists:timetable,timetable_id',
            'attendance_date' => 'required|date',
            'status'          => 'required|integer',
            'college_id'      => 'required|integer|exists:colleges,college_id',
            'lecture_hours'   => 'required|numeric',
            'session_code'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('lecturer_attendance')->where(function ($query) use ($request) {
                    return $query->where('lecturer_id', $request->lecturer_id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attendance = LecturerAttendance::create($validator->validated());

        return response()->json(['data' => $attendance], 201);
    }

    /**
     * يعرض سجل حضور معين للمحاضر.
     */
    public function show(LecturerAttendance $lecturerAttendance)
    {
        return response()->json(['data' => $lecturerAttendance]);
    }

    /**
     * يقوم بتحديث سجل حضور موجود للمحاضر.
     */
    public function update(Request $request, LecturerAttendance $lecturerAttendance)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|integer',
            'notification_status' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lecturerAttendance->update($validator->validated());

        return response()->json(['data' => $lecturerAttendance]);
    }

    /**
     * يقوم بحذف سجل حضور للمحاضر.
     */
    public function destroy(LecturerAttendance $lecturerAttendance)
    {
        $lecturerAttendance->delete();
        return response()->json(['message' => 'Record deleted successfully.']);
    }

    /**
     * ✅ الدالة التي تم نقلها إلى هنا
     * المصادقة على جلسة حضور كاملة وتسجيل الحاضرين والغياب.
     */
    public function finalizeSession(Request $request)
    {
        // 1. التحقق من صحة البيانات القادمة
        $request->validate([
            'timetable_id'  => 'required|integer|exists:timetable,timetable_id',
            'session_id'    => 'required|integer|exists:lecture_sessions,session_id',
            'group_id'      => 'required|integer|exists:student_groups,group_id',
            
            // ✅ التعديل: استقبال مصفوفة كائنات (student_id + attendance_method)
            'students_data' => 'present|array', 
            'students_data.*.student_id' => 'required|integer|exists:students,student_id',
            'students_data.*.attendance_method' => 'required|integer|in:0,1', // 0: QR, 1: Manual
        ]);

        return DB::transaction(function () use ($request) {
            // 2. جلب البيانات الأساسية (الجدول، المحاضر، الجلسة)
            $timetable = Timetable::with(['lecturer.academicTitle', 'course'])
                                  ->findOrFail($request->timetable_id);

            $session = LectureSession::findOrFail($request->session_id);
            
            $sessionCode = $session->session_code;
            $attendanceDate = $session->session_date;

            // ✅ جلب جميع الجلسات النظيرة المشتركة لنفس المحاضرة (نفس المقرر، المحاضر، والتوقيت)
            $peerSessions = LectureSession::where('session_date', $session->session_date)
                ->where('start_time', $session->start_time)
                ->where('end_time', $session->end_time)
                ->whereHas('timetable', function($q) use ($timetable) {
                    $q->where('course_id', $timetable->course_id)
                      ->where('lecturer_id', $timetable->lecturer_id);
                })
                ->with('timetable')
                ->get();

            if ($peerSessions->isEmpty()) {
                $peerSessions = collect([$session]);
            }

            // خريطة لربط كل مجموعة بالجلسة وجدول المحاضرات الخاص بها
            $groupSessionMap = [];
            $involvedGroupIds = [];
            foreach ($peerSessions as $pSession) {
                if ($pSession->timetable && $pSession->timetable->group_id) {
                    $gId = $pSession->timetable->group_id;
                    $groupSessionMap[$gId] = $pSession;
                    $involvedGroupIds[] = $gId;
                }
            }
            if (empty($involvedGroupIds)) {
                $involvedGroupIds = [$request->group_id];
            }
            $involvedGroupIds = array_unique($involvedGroupIds);

            // ---------------------------------------------------------
            // أولاً: معالجة حضور الطلاب لجميع المجموعات المشتركة
            // ---------------------------------------------------------
            
            // أ) جلب جميع معرفات الطلاب في جميع المجموعات المشتركة
            $allGroupMembers = DB::table('student_group_members')
                                 ->whereIn('group_id', $involvedGroupIds)
                                 ->pluck('student_id');

            // ب) تحويل البيانات القادمة إلى Collection ليسهل البحث فيها عن طريق student_id
            $presentStudentsMap = collect($request->students_data)->keyBy('student_id');

            foreach ($allGroupMembers as $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;

                // هل الطالب موجود في قائمة الحاضرين؟
                $attendanceData = $presentStudentsMap->get($studentId);
                $isPresent = $attendanceData ? true : false;

                // تحديد القيم
                $status = $isPresent ? 1 : 0;
                $method = $isPresent ? $attendanceData['attendance_method'] : 0; 

                // ربط الطالب بجدوله وجلسته المحددة لمجموعته
                $assignedSession = $groupSessionMap[$student->group_id] ?? $session;
                $assignedTimetable = $assignedSession->timetable ?? $timetable;

                StudentAttendance::updateOrCreate(
                    [
                        'student_id'   => $studentId,
                        'session_code' => $assignedSession->session_code, // المفتاح الفريد لجلسة مجموعة الطالب
                    ],
                    [
                        'timetable_id'        => $assignedTimetable->timetable_id,
                        'level_id'            => $assignedTimetable->level_id ?? $student->level_id ?? 1,
                        'attendance_date'     => $attendanceDate,
                        'status'              => $status,
                        'attendance_method'   => $method, // ✅ تم حفظ الطريقة هنا
                        'notification_status' => 0,
                        'college_id'          => $student->college_id,
                        'department_id'       => $student->department_id,
                    ]
                );
            }

            // ---------------------------------------------------------
            // ثانياً: معالجة حضور المحاضر والحسابات المالية
            // ---------------------------------------------------------
            
            $lecturer = $timetable->lecturer;
            
            $hourlyRate = 0;
            if ($lecturer && $lecturer->academicTitle) {
                $hourlyRate = $lecturer->academicTitle->hourly_price;
            }

            $lectureHours = $timetable->lecture_hours ?? 0;
            $totalSessionRate = $lectureHours * $hourlyRate;

            LecturerAttendance::updateOrCreate(
                [
                    'lecturer_id'  => $lecturer->lecturer_id,
                    'session_code' => $sessionCode,
                ],
                [
                    'timetable_id'        => $timetable->timetable_id,
                    'attendance_date'     => $attendanceDate,
                    'status'              => 1,
                    'notification_status' => 0,
                    'college_id'          => $timetable->college_id,
                    
                    // البيانات المالية
                    'lecture_hours'              => $lectureHours,
                    'hourly_rate_at_attendance'  => $hourlyRate,
                    'lecture_rate_at_attendance' => $totalSessionRate,
                ]
            );

            // ---------------------------------------------------------
            // ثالثاً: تحديث حالة جميع الجلسات النظيرة المشتركة
            // ---------------------------------------------------------
            
            foreach ($peerSessions as $pSession) {
                $pCount = StudentAttendance::where('session_code', $pSession->session_code)->where('status', 1)->count();
                $pSession->update([
                    'status' => 2, // مكتملة
                    'system_attendance_count' => $pCount,
                    'actual_attendance_count' => $pCount
                ]);
            }

            return response()->json([
                'status' => true, 
                'message' => 'تمت مصادقة الجلسة وحفظ طريقة الحضور بنجاح لجميع المجموعات المشاركة.',
                'data' => [
                    'present_count' => $presentStudentsMap->count(),
                    'absent_count'  => $allGroupMembers->count() - $presentStudentsMap->count(),
                    'lecturer_earnings' => $totalSessionRate
                ]
            ]);
        });
    }
}