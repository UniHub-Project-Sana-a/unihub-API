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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthPasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request)
    {
        // بما أن الريكويست تحقق من الوجود، يمكننا جلبه مباشرة
        $user = User::where('email', $request->email)->first();

        // 1. توليد التوكن (نص عشوائي)
        $token = Str::random(60);

        // 2. التخزين في جدول password_reset_tokens
        // نحذف القديم أولاً لتجنب التكرار
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token), // نشفره في القاعدة
            'created_at' => now()
        ]);

        // 3. محاولة الإرسال
        try {
            // نرسل التوكن الأصلي (غير المشفر)
            $user->notify(new ResetPasswordNotification($token));
        } catch (\Exception $e) {
            Log::error("Failed to send reset email to {$user->email}: " . $e->getMessage());
            // لن نوقف العملية، سنعيد التوكن للواجهة في كل الأحوال
        }

        // 4. الرد للواجهة (React)
        return response()->json([
            'message' => 'تم إرسال رمز التحقق (أو تم إنشاؤه للتطوير).',
            'token'   => $token, // ✅ ضروري للتوجيه المباشر في React
            'email'   => $user->email
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