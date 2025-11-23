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
            'timetable_id'        => 'required|integer|exists:timetable,timetable_id',
            'session_id'          => 'required|integer|exists:lecture_sessions,session_id',
            'group_id'            => 'required|integer|exists:student_groups,group_id',
            'present_student_ids' => 'present|array', // present تعني يجب إرسال المصفوفة حتى لو فارغة
            'present_student_ids.*' => 'integer|exists:students,student_id', // تأكد أن IDs هي student_id وليس academic_number
        ]);

        return DB::transaction(function () use ($request) {
            // 2. جلب بيانات الجدول الدراسي مع المحاضر ورتبته الأكاديمية (للسعر)
            $timetable = Timetable::with(['lecturer.academicTitle', 'course'])
                                  ->findOrFail($request->timetable_id);

            // 3. جلب بيانات الجلسة الحالية (للحصول على session_code والتاريخ)
            $session = LectureSession::findOrFail($request->session_id);
            
            $sessionCode = $session->session_code;
            $attendanceDate = $session->session_date;

            // ---------------------------------------------------------
            // أولاً: معالجة حضور الطلاب (حاضر وغائب)
            // ---------------------------------------------------------
            
            // أ) جلب جميع معرفات الطلاب في هذه المجموعة
            $allGroupMembers = DB::table('student_group_members')
                                 ->where('group_id', $request->group_id)
                                 ->pluck('student_id'); // مصفوفة بكل الطلاب

            // ب) تحويل قائمة الحضور القادمة من الفرونت إلى Collection لسهولة البحث
            $presentIdsCollection = collect($request->present_student_ids);

            foreach ($allGroupMembers as $studentId) {
                // جلب بيانات الطالب (للكلية والقسم)
                // ملاحظة: لتحسين الأداء، يمكن جلب الطلاب دفعة واحدة خارج الـ loop باستخدام whereIn
                $student = Student::find($studentId);
                
                if (!$student) continue;

                // تحديد الحالة: إذا كان الـ ID موجود في مصفوفة الحاضرين = 1، غير ذلك = 0
                $status = $presentIdsCollection->contains($studentId) ? 1 : 0;

                StudentAttendance::updateOrCreate(
                    [
                        'student_id'   => $studentId,
                        'session_code' => $sessionCode, // المفتاح لعدم تكرار التسجيل لنفس الجلسة
                    ],
                    [
                        'timetable_id'        => $timetable->timetable_id,
                        'level_id'            => $timetable->level_id,
                        'attendance_date'     => $attendanceDate,
                        'status'              => $status,
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
            
            // جلب سعر الساعة من الرتبة الأكاديمية
            // ملاحظة: تأكد أن العلاقة academicTitle موجودة في موديل Lecturer
            $hourlyRate = 0;
            if ($lecturer->academicTitle) {
                $hourlyRate = $lecturer->academicTitle->hourly_price;
            }

            // جلب عدد ساعات المحاضرة من الجدول الدراسي
            $lectureHours = $timetable->lecture_hours ?? 0;

            // حساب إجمالي المبلغ لهذه الجلسة
            $totalSessionRate = $lectureHours * $hourlyRate;

            LecturerAttendance::updateOrCreate(
                [
                    'lecturer_id'  => $lecturer->lecturer_id,
                    'session_code' => $sessionCode,
                ],
                [
                    'timetable_id'        => $timetable->timetable_id,
                    'attendance_date'     => $attendanceDate,
                    'status'              => 1, // المحاضر حاضر لأنه هو من صادق
                    'notification_status' => 0,
                    'college_id'          => $timetable->college_id,
                    
                    // البيانات المالية
                    'lecture_hours'              => $lectureHours,
                    'hourly_rate_at_attendance'  => $hourlyRate,
                    'lecture_rate_at_attendance' => $totalSessionRate,
                ]
            );

            // ---------------------------------------------------------
            // ثالثاً: تحديث حالة الجلسة في جدول lecture_sessions
            // ---------------------------------------------------------
            
            $session->update([
                'status' => 1, // 1: مكتملة
                'system_attendance_count' => $presentIdsCollection->count(), // عدد الحاضرين الفعلي
            ]);

            return response()->json([
                'status' => true, 
                'message' => 'تمت مصادقة الجلسة وتسجيل الحضور والبيانات المالية بنجاح.',
                'data' => [
                    'present_count' => $presentIdsCollection->count(),
                    'absent_count'  => $allGroupMembers->count() - $presentIdsCollection->count(),
                    'lecturer_earnings' => $totalSessionRate
                ]
            ]);
        });
    }
}