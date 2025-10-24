<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Device\VerifyOtpRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\OtpDeviceVerification;
use Illuminate\Http\Request;
use App\Http\Resources\V1\UserResource; // <-- 1. إضافة الاستيراد
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // <-- 2. إضافة الاستيراد

class UserDevicesController extends Controller {
    use AuthorizesRequests; // <-- 3. استخدام الـ trait

    public function verifyOtp(VerifyOtpRequest $request) {
        $user = User::where('email', $request->email)->firstOrFail();
        
        $otpVerification = OtpDeviceVerification::where('user_id', $user->user_id)
            ->where('mac_address', $request->mac_address)
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpVerification) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $device = UserDevice::create([
            'user_id' => $user->user_id,
            'device_name' => $otpVerification->device_name,
            'mac_address' => $otpVerification->mac_address,
            'os_type' => $otpVerification->os_type,
        ]);

        $otpVerification->delete();

        $token = $user->createToken($device->device_name)->accessToken;
        return response()->json(['access_token' => $token, 'user' => new UserResource($user)]);
    }

    public function enableAutoAttendance(Request $request, UserDevice $device) {
        if (UserDevice::where('user_id', $request->user()->id)->where('is_auto_attendance_enabled', true)->exists()) {
            return response()->json(['message' => 'You already have an active auto-attendance device.'], 422);
        }
        if (UserDevice::where('mac_address', $device->mac_address)->where('is_auto_attendance_enabled', true)->exists()) {
            return response()->json(['message' => 'This device is already activated by another user.'], 422);
        }
        $device->update(['is_auto_attendance_enabled' => true]);
        return response()->json($device);
    }

    public function disableAutoAttendance(Request $request, UserDevice $device) {
        $device->update(['is_auto_attendance_enabled' => false]);
        return response()->json($device);
    }

    public function destroy(UserDevice $device) {
        $this->authorize('delete', $device);
        $device->delete();
        return response()->json(['message' => 'Device deleted']);
    }
}