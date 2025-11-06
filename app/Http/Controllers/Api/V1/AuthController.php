<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token;
use App\Models\UserDevice;
use App\Models\OtpDeviceVerification;
use App\Notifications\SendOtpNotification; // تأكد أن هذا الإشعار موجود

class AuthController extends Controller
{
   public function login(LoginRequest $request)
    {
        $request->validate([
            'mac_address' => ['required', 'string', 'max:100'],
            'device_name' => ['required', 'string', 'max:100'],
            'os_type'     => ['required', 'string', 'max:50'],
        ]);
    
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }
    
        $userTypeCode = $user->userType->user_type_code;
    
        // الأدوار الإدارية تسجل الدخول مباشرة
        if (!in_array($userTypeCode, ['student', 'lecturer'])) {
            $token = $user->createToken($request->device_name)->accessToken;
            return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
        }
    
        $device = UserDevice::where('user_id', $user->user_id)
            ->where('mac_address', $request->mac_address)
            ->first();
    
        if ($device) {
            $token = $user->createToken($request->device_name)->accessToken;
            return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
        }
    
        $otp = rand(100000, 999999);
        $otpRecord = OtpDeviceVerification::create([
            'user_id'     => $user->user_id,
            'otp_code'    => Hash::make($otp),
            'device_name' => $request->device_name,
            'mac_address' => $request->mac_address,
            'os_type'     => $request->os_type,
            'expires_at'  => now()->addMinutes(10),
        ]);
    
        // أرسل OTP (اختياري)
        // try { $user->notify(new SendOtpNotification($otp)); } catch (\Exception $e) { \Log::error('OTP Email failed: ' . $e->getMessage()); }
    
        // **التصحيح هنا**
        return response()->json([
            'otp_required' => true,
            'message'      => 'جهاز جديد تم اكتشافه. تم إرسال رمز التحقق.',
            'verification_id' => $otpRecord->verification_id, // <-- استخدم اسم المفتاح الأساسي الصحيح
            'user_id'      => $user->user_id,
            'otp_for_dev'  => (config('app.env') !== 'production') ? $otp : null,
        ]);
    }
    
    
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'verification_id' => 'required|integer|exists:otp_device_verifications,verification_id', // <-- تحقق من وجوده في العمود الصحيح
            'otp_code'        => 'required|string|digits:6',
        ]);
    
        // **التصحيح هنا**
        $otpRecord = OtpDeviceVerification::find($data['verification_id']);
    
        if (!$otpRecord || $otpRecord->is_verified || $otpRecord->expires_at->isPast()) {
            return response()->json(['message' => 'رمز التحقق غير صالح أو منتهي الصلاحية.'], 422);
        }
    
        if (!Hash::check($data['otp_code'], $otpRecord->otp_code)) {
            return response()->json(['message' => 'رمز التحقق غير صحيح.'], 422);
        }
    
        $otpRecord->update(['is_verified' => true]);
    
        UserDevice::create([
            'user_id'     => $otpRecord->user_id,
            'device_name' => $otpRecord->device_name,
            'mac_address' => $otpRecord->mac_address,
            'os_type'     => $otpRecord->os_type,
        ]);
        
        $user = User::find($otpRecord->user_id);
        $token = $user->createToken($otpRecord->device_name)->accessToken;
    
        return response()->json([
            'access_token' => $token,
            'user' => new UserResource($user)
        ]);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->load('college', 'userType'); // تحميل العلاقات اللازمة
        return new UserResource($user);
    }

    public function logout(Request $request)
    {
        /** @var Token $token */
        $token = $request->user()->token();
        $token->revoke();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }
}