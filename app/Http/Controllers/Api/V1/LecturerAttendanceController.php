<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LecturerAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// ✅ استيراد جميع الموديلات والفئات اللازمة
use App\Models\StudentAttendance;
use App\Models\LectureSession;
use App\Models\StudentGroup;
use Illuminate\Support\Facades\DB;

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
        // 1. التحقق من صحة البيانات القادمة من الواجهة
        $validated = $request->validate([
            'timetable_id' => 'required|integer|exists:timetable,timetable_id',
            'group_id' => 'required|integer|exists:student_groups,group_id',
            'present_student_ids' => 'present|array',
            'present_student_ids.*' => 'integer|exists:students,student_id',
        ]);

        // 2. البحث عن آخر جلسة تم إنشاؤها لهذه المحاضرة
        $latestSession = LectureSession::where('timetable_id', $validated['timetable_id'])
                                        ->with('timetable') // تحميل بيانات المحاضرة معها
                                        ->latest()
                                        ->first();

        if (!$latestSession) {
            return response()->json(['message' => 'لم يتم العثور على جلسة لهذه المحاضرة.'], 404);
        }

        // 3. التحقق من أن بيانات المحاضرة (timetable) موجودة
        $timetable = $latestSession->timetable;
        if (!$timetable) {
            return response()->json(['message' => 'خطأ في البيانات: الجلسة غير مرتبطة بجدول زمني صحيح.'], 500);
        }

        // 4. جلب جميع الطلاب في المجموعة وحساب الغائبين
        $allStudentsInGroup = StudentGroup::find($validated['group_id'])->members()->pluck('students.student_id');
        $presentStudentIds = collect($validated['present_student_ids']);
        $absentStudentIds = $allStudentsInGroup->diff($presentStudentIds);


        // 5. تنفيذ جميع عمليات الحفظ داخل Transaction لضمان الأمان
        try {
            DB::transaction(function () use ($presentStudentIds, $absentStudentIds, $latestSession, $timetable) {
                
                // 5a. تسجيل الطلاب الحاضرين (status = 0)
                foreach ($presentStudentIds as $studentId) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'session_code' => $latestSession->session_code,
                        ],
                        [
                            'timetable_id' => $timetable->timetable_id,
                            'level_id' => $timetable->level_id,
                            'attendance_date' => $latestSession->session_date,
                            'status' => 0, // 0 = حاضر
                            'college_id' => $timetable->college_id,
                            'department_id' => $timetable->department_id,
                        ]
                    );
                }

                // 5b. تسجيل الطلاب الغائبين (status = 1)
                foreach ($absentStudentIds as $studentId) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'session_code' => $latestSession->session_code,
                        ],
                        [
                            'timetable_id' => $timetable->timetable_id,
                            'level_id' => $timetable->level_id,
                            'attendance_date' => $latestSession->session_date,
                            'status' => 1, // 1 = غائب
                            'college_id' => $timetable->college_id,
                            'department_id' => $timetable->department_id,
                        ]
                    );
                }

                // 5c. تسجيل حضور المحاضر (مع التأكد من عدم التكرار)
                LecturerAttendance::updateOrCreate(
                    [
                        'lecturer_id'   => $timetable->lecturer_id,
                        'session_code'  => $latestSession->session_code,
                    ],
                    [
                        'timetable_id'    => $timetable->timetable_id,
                        'attendance_date' => $latestSession->session_date,
                        'status'          => 1, // 1 = حاضر
                        'college_id'      => $timetable->college_id,
                        'lecture_hours'   => $timetable->lecture_hours,
                    ]
                );

                // 5d. تحديث حالة الجلسة إلى "منفذة"
                $latestSession->status = 1;
                $latestSession->save();
            });

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ أثناء عمليات قاعدة البيانات
            return response()->json([
                'message' => 'فشلت عملية المصادقة بسبب خطأ في الخادم.',
                'error' => $e->getMessage()
            ], 500);
        }

        // 6. إرجاع رسالة نجاح
        return response()->json(['message' => 'تمت مصادقة جلسة الحضور بنجاح.']);
    }
}