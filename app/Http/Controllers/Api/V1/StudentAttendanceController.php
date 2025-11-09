<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentAttendance;
use App\Models\LectureSession;
use App\Models\LecturerAttendance;
use App\Models\StudentGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    /**
     * يعرض قائمة الحضور لجلسة محاضرة معينة بناءً على timetable_id.
     * هذا هو الـ endpoint الرئيسي الذي تستدعيه الواجهة بعد انتهاء الجلسة.
     */
        public function index(Request $request)
    {
        $request->validate([
            'timetable_id' => 'required|integer|exists:timetable,timetable_id',
        ]);

        $timetableId = $request->timetable_id;
        $latestSession = LectureSession::where('timetable_id', $timetableId)->latest()->first();

        if (!$latestSession) {
            return response()->json(['data' => []]);
        }
        
        $sessionCode = $latestSession->session_code;
        $attendanceRecords = StudentAttendance::where('session_code', $sessionCode)
                                              ->with('student.user')
                                              ->get();

        if ($latestSession->system_attendance_count !== $attendanceRecords->count()) {
            $latestSession->system_attendance_count = $attendanceRecords->count();
            $latestSession->save();
        }

        $formattedRecords = $attendanceRecords->map(function ($record) {
            if (!$record->student || !$record->student->user) {
                return null;
            }

            // ✅ --- التعديل هنا: إضافة التحقق من وجود created_at --- ✅
            return [
                'studentName' => $record->student->user->full_name,
                'studentId' => $record->student->user->academic_number,
                // إذا كان created_at موجودًا، قم بتنسيقه. وإلا، أرجع نصًا بديلاً.
                'scanTime' => $record->created_at ? $record->created_at->format('H:i:s') : 'N/A',
                'method' => 'QR',
            ];
        })->filter();

        return response()->json(['data' => $formattedRecords]);
    }

    /**
     * جلب قائمة الطلاب الكاملة لمجموعة معينة.
     */
    public function getGroupStudents(StudentGroup $studentGroup)
    {
        try {
            // تأكد من أن موديل StudentGroup لديه علاقة 'members'
            // إذا كانت العلاقة اسمها مختلف، قم بتغيير 'members'
            $students = $studentGroup->members()->with('user')->get();
    
            if ($students->isEmpty()) {
                return response()->json(['data' => []]);
            }
    
            $formattedStudents = $students->map(function ($student) {
                // تحقق من وجود البيانات لتجنب الأخطاء
                if (!$student->user) {
                    return null;
                }
                return [
                    'student_id' => $student->student_id,
                    'name' => $student->user->full_name,
                    'academic_number' => $student->user->academic_number,
                ];
            })->filter(); // إزالة أي طلاب ليس لديهم مستخدم مرتبط
    
            return response()->json(['data' => $formattedStudents]);
    
        } catch (\Exception $e) {
            // في حال حدوث خطأ (مثل عدم وجود علاقة 'members')، أرجع خطأ واضحًا
            return response()->json([
                'message' => 'Failed to fetch students for the group.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * المصادقة على جلسة حضور كاملة.
     */
    public function finalizeSession(Request $request)
    {
        $validated = $request->validate([
            'timetable_id' => 'required|integer|exists:timetable,timetable_id',
            'absent_student_ids' => 'present|array',
            'absent_student_ids.*' => 'integer|exists:students,student_id',
        ]);

        // جلب الجلسة مع بيانات المحاضرة المرتبطة بها
        $latestSession = LectureSession::where('timetable_id', $validated['timetable_id'])
                                        ->with('timetable') // التحميل المسبق للعلاقة
                                        ->latest()
                                        ->first();

        // تحقق من وجود الجلسة
        if (!$latestSession) {
            return response()->json(['message' => 'لم يتم العثور على جلسة نشطة لهذه المحاضرة.'], 404);
        }

        // ✅ تحقق من وجود بيانات المحاضرة المرتبطة
        if (!$latestSession->timetable) {
            return response()->json(['message' => 'خطأ في البيانات: الجلسة غير مرتبطة بجدول زمني صحيح.'], 500);
        }

        $absentStudents = $validated['absent_student_ids'];
        $timetable = $latestSession->timetable;

        try {
            DB::transaction(function () use ($absentStudents, $latestSession, $timetable) {
                // 1. تسجيل الطلاب الغائبين
                foreach ($absentStudents as $studentId) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'session_code' => $latestSession->session_code,
                        ],
                        [
                            'timetable_id' => $timetable->timetable_id,
                            'level_id' => $timetable->level_id,
                            'attendance_date' => $latestSession->session_date,
                            'status' => 1, // 0 = غائب
                            'college_id' => $timetable->college_id,
                            'department_id' => $timetable->department_id,
                        ]
                    );
                }

                // 2. تسجيل حضور المحاضر (مع التأكد من عدم التكرار)
                LecturerAttendance::firstOrCreate(
                    [
                        'session_code' => $latestSession->session_code,
                        'lecturer_id' => $timetable->lecturer_id,
                    ],
                    [
                        'timetable_id' => $timetable->timetable_id,
                        'attendance_date' => $latestSession->session_date,
                        'status' => 1, // 1 = حاضر
                        'college_id' => $timetable->college_id,
                        'lecture_hours' => $timetable->lecture_hours,
                    ]
                );

                // 3. تحديث حالة الجلسة إلى "منفذة"
                $latestSession->status = 1; // 1 = منفذة
                $latestSession->save();
            });

        } catch (\Exception $e) {
            // إرجاع رسالة خطأ واضحة جدًا للواجهة
            return response()->json([
                'message' => 'فشلت عملية المصادقة بسبب خطأ في الخادم.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'تمت مصادقة جلسة الحضور بنجاح.']);
    }

    /**
     * Store a newly created resource in storage.
     * يستخدم لإنشاء سجل حضور يدوي.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id'      => 'required|integer|exists:students,student_id',
            'timetable_id'    => 'required|integer|exists:timetable,timetable_id',
            'level_id'        => 'required|integer|exists:levels,level_id',
            'attendance_date' => 'required|date',
            'status'          => 'required|integer',
            'college_id'      => 'required|integer|exists:colleges,college_id',
            'department_id'   => 'required|integer|exists:departments,department_id',
            'session_code'    => [
                'required',
                'string',
                'max:50',
                // التأكد من عدم وجود سجل مكرر لنفس الطالب في نفس الجلسة
                Rule::unique('student_attendance')->where(function ($query) use ($request) {
                    return $query->where('student_id', $request->student_id);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $attendance = StudentAttendance::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Student attendance record created successfully.',
            'data' => $attendance
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(StudentAttendance $studentAttendance)
    {
        // استخدام route model binding للحصول على السجل مباشرة
        return response()->json(['status' => true, 'data' => $studentAttendance->load('student.user')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudentAttendance $studentAttendance)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|integer',
            'notification_status' => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $studentAttendance->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Attendance record updated successfully.',
            'data' => $studentAttendance
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentAttendance $studentAttendance)
    {
        try {
            $studentAttendance->delete();
            return response()->json(['status' => true, 'message' => 'Attendance record deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to delete record.', 'error' => $e->getMessage()], 500);
        }
    }
}