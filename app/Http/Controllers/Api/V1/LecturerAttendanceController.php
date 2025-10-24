<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\LecturerAttendance;
use Illuminate\Http\Request;

class LecturerAttendanceController extends Controller {
    public function checkIn(Request $request) {
        $request->validate([
            'session_code' => ['required', 'string', 'exists:lecture_sessions,session_code'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $session = \App\Models\LectureSession::where('session_code', $request->session_code)->firstOrFail();

        $attendance = LecturerAttendance::firstOrCreate(
            [
                'lecturer_id' => $user->lecturer->lecturer_id,
                'session_code' => $request->session_code,
            ],
            [
                'timetable_id' => $session->timetable_id,
                'attendance_date' => $session->session_date,
                'status' => 1, // Present
                'college_id' => $session->timetable->college_id,
                'lecture_hours' => $session->timetable->lecture_hours,
            ]
        );

        return response()->json($attendance, 201);
    }
}