<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QrCode;
use App\Models\StudentAttendance;
use App\Models\Student;
use Carbon\Carbon;
// use App\Events\StudentAttended; // لإرسال الحدث إلى WebSocket

class StudentAttendanceController extends Controller
{
    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_code_value' => 'required|string',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $student = $user->student; // افترض وجود علاقة `student` في موديل User

        if (!$student) {
            return response()->json(['message' => 'User is not a student.'], 403);
        }

        // 1. ابحث عن الرمز
        $qrCode = QrCode::where('qr_code_value', $data['qr_code_value'])->first();

        // 2. التحقق من صلاحية الرمز
        if (!$qrCode || !$qrCode->is_active || $qrCode->expires_at->isPast()) {
            return response()->json(['message' => 'رمز التحضير غير صالح أو منتهي الصلاحية.'], 422);
        }

        // 3. التحقق من المسافة الجغرافية (اختياري لكن مهم)
        $distance = $this->calculateDistance(
            $data['latitude'], $data['longitude'],
            $qrCode->latitude, $qrCode->longitude
        );

        if ($distance > $qrCode->allowed_distance) {
            return response()->json(['message' => "أنت خارج النطاق الجغرافي المسموح به للمحاضرة. المسافة: " . round($distance) . " متر."], 422);
        }

        // 4. تحقق من عدم تسجيل الحضور مسبقًا لنفس الجلسة
        // نحن نعتمد على entry_id لتحديد الجلسة
        $alreadyAttended = StudentAttendance::where('entry_id', $qrCode->entry_id)
            ->where('student_id', $student->student_id)
            ->whereDate('attendance_date', today())
            ->exists();
            
        if ($alreadyAttended) {
            return response()->json(['message' => 'لقد قمت بتسجيل حضورك مسبقًا لهذه المحاضرة.'], 409);
        }

        // 5. تسجيل الحضور
        $attendanceRecord = StudentAttendance::create([
            'student_id'    => $student->student_id,
            'entry_id'      => $qrCode->entry_id,
            'attendance_date' => today(),
            'status'        => 1, // 1 = حاضر
            'college_id'    => $student->college_id,
            'department_id' => $student->department_id,
            'session_code'  => 'QR-' . $qrCode->entry_id . '-' . today()->format('Y-m-d'), // كود جلسة بسيط
        ]);
        
        // 6. إرسال حدث WebSocket لإعلام المحاضر (مهم جدًا)
        // broadcast(new StudentAttended($attendanceRecord))->toOthers();

        return response()->json(['message' => 'تم تسجيل حضورك بنجاح.']);
    }

    // دالة لحساب المسافة بين نقطتين
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // بالأمتار
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}