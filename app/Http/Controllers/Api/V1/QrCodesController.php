<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LectureSession;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrCodesController extends Controller
{
    /**
     * يقوم بتحديث رمز QR، حيث يبطل القديم وينشئ واحدًا جديدًا.
     */
    public function refreshQrCode(Request $request)
    {
        $request->validate([
            'session_code' => ['required', 'string', 'exists:lecture_sessions,session_code'],
            'old_qr_code' => ['required', 'string', 'exists:qr_codes,qr_code_value'],
        ]);

        // إبطال الرمز القديم
        QrCode::where('qr_code_value', $request->old_qr_code)->update(['is_active' => false]);

        $session = LectureSession::where('session_code', $request->session_code)->with('timetable.qrCode.refreshOption')->firstOrFail();

        $interval = $session->timetable?->qrCode?->refreshOption?->interval_seconds ?? 15;

        // إنشاء رمز جديد
        $newQrCode = $this->generateNewQrCode($session, $request, $interval);

        return response()->json($newQrCode);
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
}