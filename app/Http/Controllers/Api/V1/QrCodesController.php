<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QrCode;
use App\Models\TimetableEntry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QrCodesController extends Controller
{
    public function startSession(Request $request)
    {
        // ✅ 1. تحديث قاعدة التحقق من الصحة
        $validator = Validator::make($request->all(), [
            'timetable_id'     => 'required|integer|exists:timetable,timetable_id', // <-- تم التصحيح هنا
            'valid_minutes'    => 'required|integer|min:1',
            'interval_seconds' => 'sometimes|integer|min:5',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'allowed_distance' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $validatedData = $validator->validated();
        $user = Auth::user();

        // التأكد من أن المستخدم الحالي هو محاضر
        if (!$user || !$user->lecturer) {
            return response()->json(['message' => 'Unauthorized. Not a lecturer.'], 403);
        }

        // إيقاف أي جلسات QR نشطة أخرى لنفس المحاضرة
        QrCode::where('timetable_id', $validatedData['timetable_id'])
              ->where('is_active', true)
              ->update(['is_active' => false]);
              
        // 2. إنشاء أول رمز QR للجلسة
        try {
            $expiresAt = '2025-11-27 04:20:07';// استخدام قيمة افتراضية مؤقتة;
            
            // توليد قيمة فريدة للرمز
            $qrValue = uniqid('qr_') . '_' . time();

            $qrCode = QrCode::create([
                'timetable_id'      => $validatedData['timetable_id'],
                'qr_code_value'     => $qrValue,
                'expires_at'        => $expiresAt,
                'created_by'        => $user->lecturer->lecturer_id,
                'latitude'          => $validatedData['latitude'],
                'longitude'         => $validatedData['longitude'],
                'allowed_distance'  => $validatedData['allowed_distance'],
                'is_active'         => true,
            ]);

            // إرجاع بيانات الرمز الأول للواجهة
            return response()->json([
                'status'  => true,
                'message' => 'QR session started successfully.',
                'data'    => $qrCode
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to start QR session.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function refresh(QrCode $qrCode, Request $request)
{
    // التأكد من أن الرمز لا يزال نشطًا
    if (!$qrCode->is_active) {
        return response()->json(['message' => 'This QR session has already ended.'], 410); // 410 Gone
    }

    $qrCode->update([
        'qr_code_value' => uniqid('qr_') . '_' . time(),
        'expires_at' => Carbon::now()->addMinutes($request->input('valid_minutes', 2)) // استخدم القيمة القادمة أو قيمة افتراضية
    ]);

    return response()->json([
        'status' => true,
        'data' => $qrCode
    ]);
}

/**
 * يقوم بإنهاء جلسة QR بتحديث is_active إلى false.
 */
public function endSession(QrCode $qrCode)
{
    $qrCode->update([
        'is_active' => false,
        'expires_at' => Carbon::now(),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'QR session ended successfully.'
    ]);
}
}