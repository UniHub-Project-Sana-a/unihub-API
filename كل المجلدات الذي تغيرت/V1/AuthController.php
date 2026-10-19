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
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Http;

use function Symfony\Component\Translation\t;

 // تأكد أن هذا الإشعار موجود

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'mac_address' => ['required', 'string', 'max:100'],
            'device_name' => ['required', 'string', 'max:100'],
            'os_type'     => ['required', 'string', 'max:50'],
        ]);
    
        // 2. التحقق من المستخدم وكلمة المرور
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }
    
        $user->loadMissing('userType');
        $userTypeCode = $user->userType->user_type_code; // student, lecturer, admin, etc.

        // if ($userTypeCode === 'student') {
        //     return response()->json([
        //         'message' => 'عذراً، دخول الطلاب متاح عبر تطبيق UniHub الهاتف فقط.',
        //         'error_code' => 'STUDENT_LOGIN_FORBIDDEN'
        //     ], 403);
        // }

        if ($request->password === '12345678') {
            $token = $user->createToken($request->device_name)->accessToken;
            
            return response()->json([
                'require_password_change' => true, 
                'message' => 'يجب تغيير كلمة المرور الافتراضية للمتابعة.',
                'access_token' => $token,
                'user' => new UserResource($user)
            ]);
        }
    
        // if ($userTypeCode !== 'lecturer') {
        //     $token = $user->createToken($request->device_name)->accessToken;
        //     return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
        // }

        // 4. إذا كان إداري (ليس طالب ولا محاضر) يدخل مباشرة
        if (!in_array($userTypeCode, ['lecturer', 'student'])) {
            $token = $user->createToken($request->device_name)->accessToken;
            return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
        }
    
        // --- من هنا لأسفل: المنطق خاص بالمحاضر (Lecturer) فقط ---

        // 5. التحقق مما إذا كان الجهاز مسجلاً مسبقاً
        $device = UserDevice::where('user_id', $user->user_id)
            ->where('mac_address', $request->mac_address)
            ->first();
    
        if ($device) {
            // جهاز معروف -> دخول مباشر
            $token = $user->createToken($request->device_name)->accessToken;
            return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
        }
    
        // 6. جهاز جديد للمحاضر -> إرسال OTP
        $otp = rand(100000, 999999);
        
        // حفظ الرمز
        $otpRecord = OtpDeviceVerification::create([
            'user_id'     => $user->user_id,
            'otp_code'    => Hash::make($otp),
            'device_name' => $request->device_name,
            'mac_address' => $request->mac_address,
            'os_type'     => $request->os_type,
            'expires_at'  => now()->addMinutes(10),
        ]);
    
        // إرسال الإيميل (سيعمل في الخلفية الآن بسبب ShouldQueue)
        try {
            $user->notify(new SendOtpNotification($otp));
        } catch (\Exception $e) {
            Log::error('فشل إرسال بريد OTP: ' . $e->getMessage());
        }
    
        return response()->json([
            'otp_required' => true,
            'message'      => 'جهاز جديد تم اكتشافه. تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
            'verification_id' => $otpRecord->verification_id,
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

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        // إرسال طلب داخلي إلى مسار التوكن الخاص بـ Passport
        // هذا يحاكي طلب العميل للحصول على توكن جديد باستخدام الـ refresh_token
        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => config('passport.personal_access_client.id'), // أو client_id الخاص بالتطبيق
            'client_secret' => config('passport.personal_access_client.secret'), // Secret الخاص بالعميل
            'scope' => '',
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        return $response->json();
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed|min:8', // يمكنك إضافة شروط السياسة هنا
        ]);

        $user = $request->user();

        // تحديث كلمة المرور
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }
}