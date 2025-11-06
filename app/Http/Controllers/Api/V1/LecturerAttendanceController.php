<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LecturerAttendance;
use App\Models\TimetableEntry; // استيراد الموديل الجديد
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LecturerAttendanceController extends Controller
{
    /**
     * دالة قديمة، يمكن الإبقاء عليها للتوافق أو إزالتها لاحقًا
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'session_code' => ['required', 'string', 'exists:lecture_sessions,session_code'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        if (!$user->lecturer) {
            return response()->json(['message' => 'User is not a lecturer.'], 403);
        }

        $session = \App\Models\LectureSession::where('session_code', $request->session_code)->firstOrFail();

        // **تحديث لاستخدام entry_id**
        $entry = TimetableEntry::find($session->entry_id);
        if (!$entry) {
            return response()->json(['message' => 'Timetable entry not found.'], 404);
        }

        $attendance = LecturerAttendance::firstOrCreate(
            [
                'lecturer_id' => $user->lecturer->lecturer_id,
                'session_code' => $request->session_code,
            ],
            [
                'entry_id' => $session->entry_id, // <-- استخدام entry_id
                'attendance_date' => $session->session_date,
                'status' => 1, // Present
                'college_id' => $entry->schedule->college_id, // الحصول عليها من العلاقة
                'lecture_hours' => $entry->lecture_hours,
            ]
        );

        return response()->json($attendance, 201);
    }

    /**
     * دالة جديدة لإنهاء الجلسة والمصادقة على الحضور
     */
    public function finalizeSession(Request $request)
    {
        $data = $request->validate([
            'entry_id' => 'required|integer|exists:timetable_entries,entry_id',
            'records'  => 'required|array',
            'records.*.studentId' => 'required|string', // قد يكون academic_number
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->lecturer) {
            return response()->json(['message' => 'User is not a lecturer.'], 403);
        }
        $lecturer = $user->lecturer;

        $entry = TimetableEntry::with('schedule')->findOrFail($data['entry_id']);

        // حماية: تأكد أن المحاضر هو مالك هذا البند
        if ($entry->lecturer_id !== $lecturer->lecturer_id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // استخدام transaction لضمان تنفيذ كل العمليات معًا أو لا شيء
        DB::beginTransaction();
        try {
            // 1. تسجيل حضور المحاضر نفسه
            $sessionCode = 'QR-' . $entry->entry_id . '-' . today()->format('Y-m-d');
            LecturerAttendance::updateOrCreate(
                [
                    'lecturer_id' => $lecturer->lecturer_id,
                    'entry_id'    => $entry->entry_id,
                    'attendance_date' => today(),
                ],
                [
                    'status' => 1, // حاضر
                    'college_id' => $entry->schedule->college_id,
                    'lecture_hours' => $entry->lecture_hours,
                    'session_code' => $sessionCode,
                ]
            );

            // 2. تحديث/تسجيل حضور الطلاب المصادق عليهم
            $studentAcademicNumbers = array_column($data['records'], 'studentId');
            
            // ابحث عن كل الطلاب مرة واحدة لتحسين الأداء
            $students = \App\Models\Student::whereIn(
                'user.academic_number', // افترض أن studentId هو academic_number
                $studentAcademicNumbers
            )->join('users', 'students.user_id', '=', 'users.user_id')->get();

            foreach ($students as $student) {
                \App\Models\StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $student->student_id,
                        'entry_id'   => $entry->entry_id,
                        'attendance_date' => today(),
                    ],
                    [
                        'status'        => 1, // حاضر
                        'college_id'    => $student->college_id,
                        'department_id' => $student->department_id,
                        'session_code'  => $sessionCode,
                    ]
                );
            }
            
            // 3. إلغاء تفعيل كل رموز QR لهذه الجلسة
            \App\Models\QrCode::where('entry_id', $entry->entry_id)->update(['is_active' => false]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to finalize attendance session: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء حفظ الحضور، يرجى المحاولة مرة أخرى.'], 500);
        }

        return response()->json(['message' => 'تم إنهاء الجلسة والمصادقة على الحضور بنجاح.']);
    }
}