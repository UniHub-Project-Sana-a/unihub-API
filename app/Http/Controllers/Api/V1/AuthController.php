<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use App\Models\UserDevice;
use App\Models\OtpDeviceVerification;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request) {
    $request->validate([
        'mac_address' => ['required', 'string'],
        'device_name' => ['required', 'string'],
        'os_type' => ['required', 'string'],
    ]);

    $user = User::where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // 1. التحقق من نوع المستخدم
    $userTypeCode = $user->userType->user_type_code;
    if (!in_array($userTypeCode, ['student', 'lecturer', 'lecter'])) { // lecter احتياطًا
        // الأدوار الإدارية تسجل الدخول مباشرة
        $token = $user->createToken($request->device_name)->accessToken;
        return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
    }

    // 2. التحقق من الجهاز (للطلاب والمحاضرين فقط)
    $device = UserDevice::where('user_id', $user->user_id)
        ->where('mac_address', $request->mac_address)
        ->first();

    if ($device) {
        $token = $user->createToken($request->device_name)->accessToken;
        return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
    } else {
        $otp = rand(100000, 999999);
        OtpDeviceVerification::create([
            'user_id' => $user->user_id,
            'otp_code' => $otp,
            'device_name' => $request->device_name,
            'mac_address' => $request->mac_address,
            'os_type' => $request->os_type,
            'expires_at' => now()->addMinutes(10),
        ]);
        
        return response()->json([
            'message' => 'New device detected. OTP sent.',
            'otp' => (config('app.env') !== 'production') ? $otp : null,
        ], 403);
    }
}

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request)
{
    /** @var Token $token */
    $token = $request->user()->token();
    $token->revoke();

    return response()->json(['message' => 'Logged out']);
}
}