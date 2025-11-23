<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrCode;
use App\Models\Lecturer; // ✅ استيراد موديل المحاضر
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth; // ✅ استيراد الواجهة للتوثيق

class QrCodesController extends Controller
{
    public function startSession(Request $request)
    {
        $request->validate([
            'timetable_id' => 'required|integer',
            'session_id'   => 'required|integer|exists:lecture_sessions,session_id',
            'latitude'     => 'required|numeric',
            'longitude'    => 'required|numeric',
            'allowed_distance' => 'required|numeric',
        ]);

        // ✅ إصلاح مشكلة created_by:
        // 1. الحصول على المستخدم الحالي
        $user = Auth::user(); 
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // 2. البحث عن سجل المحاضر المرتبط بهذا المستخدم
        // نفترض أن user_id هو مفتاح الربط
        $lecturer = Lecturer::where('user_id', $user->user_id)->first();

        if (!$lecturer) {
            return response()->json(['message' => 'عذراً، المستخدم الحالي ليس مسجلاً كمحاضر.'], 403);
        }

        // تنظيف الجلسات السابقة
        QrCode::where('session_id', $request->session_id)
              ->where('is_active', true)
              ->update(['is_active' => false, 'expires_at' => Carbon::now()]);

        $randomCode = Str::random(64);

        // ✅ تحديد تاريخ انتهاء بعيد (مثلاً: يوم كامل من الآن) بدلاً من null
        // يمكنك جعله addYears(1) إذا أردت مدة أطول
        $farExpiryDate = Carbon::now()->addDay(); 

        $qrCode = QrCode::create([
            'timetable_id'      => $request->timetable_id,
            'session_id'        => $request->session_id,
            'refresh_option_id' => null,
            'qr_code_value'     => $randomCode,
            'generated_at'      => Carbon::now(),
            'expires_at'        => $farExpiryDate, // ✅ تم التعديل: قيمة بعيدة
            'is_active'         => true,
            'created_by'        => $lecturer->lecturer_id, // ✅ تم التعديل: استخدام ID المحاضر الصحيح
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'allowed_distance'  => $request->allowed_distance,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم بدء جلسة الـ QR بنجاح',
            'data' => $qrCode
        ]);
    }

    public function refresh(Request $request, $id)
    {
        $qrCode = QrCode::findOrFail($id);

        if (!$qrCode->is_active) {
            return response()->json(['status' => false, 'message' => 'الجلسة منتهية بالفعل'], 400);
        }

        $newCode = Str::random(64);
        $qrCode->update([
            'qr_code_value' => $newCode,
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'qr_id' => $qrCode->qr_id,
                'qr_code_value' => $newCode
            ]
        ]);
    }

    public function endSession(Request $request, $id)
    {
        $qrCode = QrCode::findOrFail($id);

        $qrCode->update([
            'is_active' => false,
            'expires_at' => Carbon::now(), // عند الانتهاء نضع الوقت الفعلي
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنهاء الجلسة بنجاح'
        ]);
    }
}