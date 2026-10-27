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
use Illuminate\Support\Facades\Http;

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

        // 3. التحقق من كلمة المرور الافتراضية
        if ($request->password === '12345678') {
            $token = $user->createToken($request->device_name)->accessToken;
            
            return response()->json([
                'require_password_change' => true, 
                'message' => 'يجب تغيير كلمة المرور الافتراضية للمتابعة.',
                'access_token' => $token,
                'user' => new UserResource($user)
            ]);
        }
    
        // 4. الدخول المباشر دائماً دون أي OTP أو قيود أجهزة
        $token = $user->createToken($request->device_name)->accessToken;
        return response()->json([
            'access_token' => $token, 
            'user' => new UserResource($user)
        ]);
    }
    
    public function verifyOtp(Request $request)
    {
        return response()->json(['message' => 'خاصية التحقق برمز OTP معطلة نهائياً.'], 410);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->load('college', 'userType');
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

        $response = Http::asForm()->post(config('app.url') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => config('passport.personal_access_client.id'),
            'client_secret' => config('passport.personal_access_client.secret'),
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
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $request->user();

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }
}