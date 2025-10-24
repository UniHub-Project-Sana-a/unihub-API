<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Attendance\ScanAttendanceRequest;
use App\Models\StudentAttendance;
use App\Models\QrCode;
use App\Models\LectureSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    /**
     * يعالج طلب مسح رمز QR من الطالب.
     */
    public function scan(ScanAttendanceRequest $request)
    {
        $qrCode = QrCode::where('qr_code_value', $request->qr_code)
            ->where('is_active', true)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$qrCode) {
            return response()->json(['message' => 'الرمز غير صالح أو منتهي الصلاحية. امسح من جديد.'], 422);
        }

        $distance = $this->calculateDistance(
            $request->latitude, $request->longitude,
            $qrCode->latitude, $qrCode->longitude
        );
        if ($distance > $qrCode->allowed_distance) {
            return response()->json(['message' => 'أنت بعيد جدًا عن القاعة'], 403);
        }

        $session = LectureSession::where('session_code', $request->session_code)->firstOrFail();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $attendance = StudentAttendance::firstOrCreate(
            [
                'student_id' => $user->student->student_id,
                'session_code' => $request->session_code,
            ],
            [
                'timetable_id' => $session->timetable_id,
                'attendance_date' => $session->session_date,
                'status' => 1, // Present
                'college_id' => $session->timetable->college_id,
                'department_id' => $session->timetable->department_id,
            ]
        );

        $session->increment('system_attendance_count');

        return response()->json(['message' => 'تم تسجيل حضورك بنجاح'], 201);
    }

    /**
     * تسجيل حضور طالب يدويًا من قبل المحاضر.
     */
    public function manualEntry(Request $request)
    {
        $request->validate([
            'session_code' => ['required', 'exists:lecture_sessions,session_code'],
            'academic_number' => ['required', 'exists:users,academic_number'],
        ]);

        /** @var \App\Models\User $user */
        $user = \App\Models\User::where('academic_number', $request->academic_number)->firstOrFail();
        $session = LectureSession::where('session_code', $request->session_code)->firstOrFail();

        StudentAttendance::firstOrCreate(
            ['student_id' => $user->student->student_id, 'session_code' => $request->session_code],
            ['timetable_id' => $session->timetable_id, 'attendance_date' => $session->session_date, 'status' => 1, 'college_id' => $session->timetable->college_id, 'department_id' => $session->timetable->department_id]
        );

        $session->increment('system_attendance_count');
        
        return response()->json(['message' => 'تم تسجيل حضور الطالب بنجاح']);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return ($miles * 1.609344 * 1000);
    }
}