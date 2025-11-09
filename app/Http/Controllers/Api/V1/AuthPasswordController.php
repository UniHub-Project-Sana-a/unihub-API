<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthPasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        // إنشاء التوكن وتخزينه
        $token = Password::createToken($user);

        // حاول إرسال البريد، وإن فشل نرجّع التوكن في بيئة التطوير
        $sent = true;
        // ✅ 2. إرسال الإشعار المخصص بدلاً من النظام الافتراضي
        try {
            $user->notify(new ResetPasswordNotification($token));
        } catch (\Exception $e) {
            Log::error('فشل إرسال بريد إعادة التعيين: ' . $e->getMessage());
            // يمكنك إرجاع استجابة خطأ للـ API هنا إذا أردت
            return response()->json(['message' => 'فشل إرسال البريد الإلكتروني، يرجى المحاولة مرة أخرى.'], 500);
        }

        return response()->json([
            'message' => 'تم إرسال رابط/رمز إعادة التعيين إلى بريدك إن وجد.',
            'token'   => app()->environment('local') && !$sent ? $token : null, // لسهولة الاختبار محلياً
        ]);
    }

   public function reset(ResetPasswordRequest $request)
    {
        // 1) جلب سياسة كلمة المرور من الكاش أو الداتابيز
        $policy = Cache::rememberForever('security.policy', function () {
            $data = DB::table('settings')->where('key', 'security.password')->value('value');
            return json_decode($data, true) ?: [ // قيم افتراضية لو لم يوجد إعداد
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
            ];
        });
    
        // 2) بناء قواعد التحقق الديناميكية
        $rule = PasswordRule::min($policy['min_length'] ?? 8);
        if ($policy['require_uppercase'] ?? false) $rule->letters();
        if ($policy['require_lowercase'] ?? false) $rule->mixedCase();
        if ($policy['require_numbers'] ?? false) $rule->numbers();
        if ($policy['require_symbols'] ?? false) $rule->symbols();
    
        // 3) التحقق من الطلب
        $request->validate([
            'email'    => ['required', 'email', 'exists:users,email'],
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', $rule],
        ]);
    
        // 4) محاولة إعادة تعيين كلمة المرور
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
    
                // مستحسن: إلغاء كل الجلسات السابقة للمستخدم
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            }
        );
    
        // 5) إرسال الاستجابة
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح.']);
        }
    
        // في حال فشل، غالبًا تكون الرسالة "الرمز غير صالح"
        return response()->json(['message' => __($status)], 422);
    }
}