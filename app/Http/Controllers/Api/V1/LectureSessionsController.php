<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LectureSession;
use App\Models\LecturerAttendance;
use App\Models\QrCode;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LectureSessionsController extends Controller
{
    /**
     * يبدأ جلسة محاضرة جديدة، ويسجل حضور المحاضر، وينشئ أول QR Code.
     * هذا هو الـ endpoint الذي يستدعيه المحاضر من التطبيق.
     */
    public function startSession(Request $request)
    {
        $request->validate([
            'timetable_id' => ['required', 'exists:timetable,timetable_id'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'refresh_option_id' => ['nullable', 'exists:qr_refresh_options,option_id'],
        ]);

        $timetable = Timetable::with('classroom')->findOrFail($request->timetable_id);
        $classroom = $timetable->classroom;

        // 1. التحقق من موقع المحاضر
        $distance = $this->calculateDistance(
            $request->latitude, $request->longitude,
            $classroom->latitude, $classroom->longitude
        );

        if ($distance > $classroom->allowed_distance) {
            return response()->json(['message' => 'أنت بعيد جدًا عن القاعة المحددة.'], 403);
        }

        // 2. إنشاء جلسة جديدة أو الحصول عليها
        $session = LectureSession::firstOrCreate(
            [
                'timetable_id' => $timetable->timetable_id,
                'session_date' => today(),
            ],
            [
                'start_time' => now()->toTimeString(),
                'session_code' => Str::random(10),
                'status' => 1, // Active
            ]
        );

        /** @var \App\Models\User $user */
        $user = $request->user();

        // 3. تسجيل حضور المحاضر
        LecturerAttendance::firstOrCreate(
            [
                'lecturer_id' => $user->lecturer->lecturer_id,
                'session_code' => $session->session_code,
            ],
            [
                'timetable_id' => $timetable->timetable_id,
                'attendance_date' => today(),
                'status' => 1, // Present
                'college_id' => $timetable->college_id,
                'lecture_hours' => $timetable->lecture_hours,
            ]
        );

        // 4. إنشاء أول رمز QR
        $interval = $request->refresh_option_id
            ? \App\Models\QrRefreshOption::find($request->refresh_option_id)->interval_seconds ?? 15
            : 300;

        $qrCode = $this->generateNewQrCode($session, $request, $interval);

        return response()->json([
            'session' => $session,
            'qr_code' => $qrCode,
        ], 201);
    }

    private function generateNewQrCode(LectureSession $session, Request $request, int $seconds = 15): QrCode
    {
        $classroom = $session->timetable->classroom;
        /** @var \App\Models\User $user */
        $user = $request->user();

        return QrCode::create([
            'timetable_id' => $session->timetable_id,
            'qr_code_value' => Str::random(40),
            'expires_at' => now()->addSeconds($seconds),
            'created_by' => $user->lecturer->lecturer_id,
            'latitude' => $classroom->latitude,
            'longitude' => $classroom->longitude,
            'allowed_distance' => $classroom->allowed_distance,
        ]);
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