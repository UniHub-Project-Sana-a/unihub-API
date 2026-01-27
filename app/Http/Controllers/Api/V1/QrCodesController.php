<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\QrCode;
use App\Models\Lecturer;

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

        $user = Auth::user(); 
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $lecturer = Lecturer::where('user_id', $user->user_id)->first();

        if (!$lecturer) {
            return response()->json(['message' => 'عذراً، المستخدم الحالي ليس مسجلاً كمحاضر.'], 403);
        }

        // إبطال أي جلسة سابقة لنفس الجلسة الدراسية
        QrCode::where('session_id', $request->session_id)
              ->where('is_active', true)
              ->update(['is_active' => false, 'expires_at' => Carbon::now()]);

        $randomCode = Str::random(64);

        // ملاحظة: هنا نضع وقتاً طويلاً للصلاحية (يوم كامل) لضمان عدم انقطاع الجلسة من جهة السيرفر
        // الاعتماد الأساسي في الإنهاء يكون على المؤقت في التطبيق أو زر الإنهاء اليدوي
        $farExpiryDate = Carbon::now()->addDay(); 

        $qrCode = QrCode::create([
            'timetable_id'      => $request->timetable_id,
            'session_id'        => $request->session_id,
            'refresh_option_id' => null,
            'qr_code_value'     => $randomCode,
            'generated_at'      => Carbon::now(),
            'expires_at'        => $farExpiryDate, 
            'is_active'         => true,
            'created_by'        => $lecturer->lecturer_id, 
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'allowed_distance'  => $request->allowed_distance,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم بدء جلسة الـ QR وتوثيق المواضيع بنجاح',
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
        
        // عند التحديث، نجدد وقت انتهاء صلاحية الكود نفسه ولكن نحافظ على حالة الجلسة نشطة
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

    // ✅✅✅ الدالة الجديدة لتمديد الوقت ✅✅✅
    public function extendDuration(Request $request, $id)
    {
        $qrCode = QrCode::findOrFail($id);

        if (!$qrCode->is_active) {
            return response()->json(['status' => false, 'message' => 'الجلسة منتهية بالفعل'], 400);
        }

        // إضافة دقيقة واحدة لوقت الانتهاء الحالي
        $newExpiry = Carbon::parse($qrCode->expires_at)->addMinutes(1);
        
        $qrCode->update([
            'expires_at' => $newExpiry
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم تمديد الجلسة بنجاح',
            'new_expires_at' => $newExpiry
        ]);
    }

    public function endSession(Request $request, $id)
    {
        $qrCode = QrCode::findOrFail($id);

        $qrCode->update([
            'is_active' => false,
            'expires_at' => Carbon::now(), 
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنهاء الجلسة بنجاح'
        ]);
    }
}