<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Device\VerifyOtpRequest;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\OtpDeviceVerification;
use Illuminate\Http\Request;
use App\Http\Resources\V1\UserResource; 
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class UserDevicesController extends Controller {
    use AuthorizesRequests; 

        /**
     * عرض قائمة الأجهزة (مع دعم الفلترة المتقدمة)
     */
    public function index(Request $request) 
    {
        $query = UserDevice::with('user:user_id,full_name,email,academic_number,user_type_id'); // تأكد من جلب user_type_id

        // 1. فلتر البحث (Search)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('device_name', 'like', "%$search%")
                  ->orWhere('mac_address', 'like', "%$search%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('full_name', 'like', "%$search%")
                        ->orWhere('academic_number', 'like', "%$search%");
                  });
            });
        }

        // 2. فلتر نوع المستخدم (Student vs Lecturer)
        if ($request->has('user_type')) {
            $type = $request->user_type; // 'student' or 'lecturer'
            $query->whereHas('user', function($q) use ($type) {
                // نفترض أن لديك جدول user_types أو كود ثابت
                // سنستخدم العلاقة الموجودة في الميجريشن: users.user_type_id
                // ستحتاج لمعرفة ID الطالب والمحاضر في نظامك. 
                // للتبسيط هنا سأعتمد على وجود علاقة 'student' و 'lecturer' في مودل User
                if ($type === 'student') {
                    $q->has('student'); // المستخدم لديه سجل في جدول students
                } elseif ($type === 'lecturer') {
                    $q->has('lecturer'); // المستخدم لديه سجل في جدول lecturers
                }
            });
        }

        // 3. فلتر الكلية (College Filter)
        if ($request->has('college_id') && $request->college_id !== 'all') {
            $collegeId = $request->college_id;
            $query->whereHas('user', function($q) use ($collegeId) {
                // المستخدم مرتبط بكلية مباشرة
                $q->where('college_id', $collegeId);
            });
        }

        // الترتيب والتقسيم
        $devices = $query->latest('last_login_at')->paginate(50); // زدنا العدد قليلاً

        // 4. كشف التكرار (المشبوه) - مع تطبيق نفس الفلاتر
        // (إذا كنا نستعرض الطلاب، نبحث عن تكرار بين الطلاب فقط)
        $suspiciousDevices = [];
        
        // استعلام التكرار المتقدم
        $duplicateQuery = DB::table('user_devices')
            ->join('users', 'user_devices.user_id', '=', 'users.user_id')
            ->select('user_devices.mac_address', DB::raw('COUNT(DISTINCT user_devices.user_id) as user_count'))
            ->groupBy('user_devices.mac_address')
            ->having('user_count', '>', 1);

        // تطبيق فلتر الكلية والنوع على التكرار أيضاً
        if ($request->has('college_id') && $request->college_id !== 'all') {
            $duplicateQuery->where('users.college_id', $request->college_id);
        }
        
        // ملاحظة: فلتر النوع (student/lecturer) معقد قليلاً في SQL المباشر
        // لذا سنجلب التكرار العام، ثم نفلتر التفاصيل
        
        $duplicates = $duplicateQuery->get();

        if ($duplicates->isNotEmpty()) {
            $macs = $duplicates->pluck('mac_address');
            
            // جلب التفاصيل
            $suspiciousQuery = UserDevice::whereIn('mac_address', $macs)
                ->with('user:user_id,full_name,academic_number,user_type_id')
                ->orderBy('mac_address');

            // تطبيق الفلاتر على التفاصيل
            if ($request->has('user_type')) {
                $type = $request->user_type;
                $suspiciousQuery->whereHas('user', function($q) use ($type) {
                    if ($type === 'student') $q->has('student');
                    elseif ($type === 'lecturer') $q->has('lecturer');
                });
            }
            if ($request->has('college_id') && $request->college_id !== 'all') {
                $suspiciousQuery->whereHas('user', function($q) use ($request) {
                    $q->where('college_id', $request->college_id);
                });
            }

            $suspiciousDevices = $suspiciousQuery->get()->groupBy('mac_address');
            
            // تنظيف: إزالة المجموعات التي بقي فيها جهاز واحد بعد الفلترة
            $suspiciousDevices = $suspiciousDevices->filter(function ($group) {
                return $group->count() > 1;
            });
        }

        return response()->json([
            'status' => true,
            'data' => $devices,
            'suspicious' => $suspiciousDevices
        ]);
    }

    // دالة جديدة لتغيير حالة الحضور الآلي (Toggle)
    public function toggleAutoAttendance(Request $request, UserDevice $device)
    {
        // العكس المباشر للحالة الحالية
        $device->update([
            'is_auto_attendance_enabled' => !$device->is_auto_attendance_enabled
        ]);
        
        return response()->json([
            'message' => 'Status updated', 
            'status' => $device->is_auto_attendance_enabled
        ]);
    }

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
        $device->delete();
        return response()->json(['message' => 'Device deleted']);
    }
}