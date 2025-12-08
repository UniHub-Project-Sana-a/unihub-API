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
        // 1. تحديد كود الجلسة (session_code)
        // نسمح بالبحث إما عن طريق كود الجلسة مباشرة (الأدق) أو عن طريق المحاضرة (الأحدث)
        $sessionCode = null;

        if ($request->filled('session_code')) {
            // ✅ الحالة 1 (الجديدة): البحث المباشر بكود الجلسة
            $sessionCode = $request->session_code;
        } 
        elseif ($request->filled('timetable_id')) {
            // الحالة 2 (القديمة): البحث عن آخر جلسة للمحاضرة
            $latestSession = LectureSession::where('timetable_id', $request->timetable_id)
                                           ->latest()
                                           ->first();
            if ($latestSession) {
                $sessionCode = $latestSession->session_code;
            }
        }

        // إذا لم نجد كود جلسة، نرجع مصفوفة فارغة
        if (!$sessionCode) {
            return response()->json(['data' => []]);
        }

        // 2. جلب سجلات الحضور
        $attendanceRecords = StudentAttendance::where('session_code', $sessionCode)
                                              ->with('student.user') // Eager Loading للبيانات
                                              ->get();

        // (اختياري) تحديث عداد النظام في جدول الجلسات ليكون متزامن دائماً
        LectureSession::where('session_code', $sessionCode)
                      ->update(['system_attendance_count' => $attendanceRecords->count()]);

        // 3. تنسيق البيانات (Mapping) لتناسب واجهة React
        $formattedRecords = $attendanceRecords->map(function ($record) {
            // حماية ضد البيانات المحذوفة أو الناقصة
            if (!$record->student || !$record->student->user) {
                return null;
            }

            return [
                'attendance_id' => $record->attendance_id, 
                'student_id'    => $record->student_id, // مهم للمقارنة في الفرونت إند
                'studentName'   => $record->student->user->full_name,
                'studentId'     => $record->student->user->academic_number, // الرقم الجامعي
                'status'        => $record->status, // 1 حاضر
                
                // تنسيق الوقت كما طلبت
                'scanTime'      => $record->created_at ? $record->created_at->format('H:i:s') : 'N/A',
                'method'        => 'QR', // أو يمكنك جلبه من العمود إذا أضفته لاحقاً
            ];
        })->filter()->values(); // filter لإزالة الـ null، values لإعادة ترتيب المصفوفة

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