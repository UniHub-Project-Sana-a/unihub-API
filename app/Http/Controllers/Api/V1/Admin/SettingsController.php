<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function getPolicy() 
    {
        // استخدام الكاش لتحسين الأداء لأن هذه الإعدادات تقرأ كثيراً وتتغير نادراً
        // ملاحظة: قمنا بتغيير مفتاح الكاش لضمان تحديث الهيكلية الجديدة
        $data = Cache::rememberForever('admin.settings.all', function () {
            
            // جلب القيم الخام من القاعدة
            $passwordRaw = DB::table('settings')->where('key', 'security.password')->value('value');
            $loginRaw = DB::table('settings')->where('key', 'security.login')->value('value');
            $sessionRaw = DB::table('settings')->where('key', 'security.session')->value('value');

            // فك التشفير مع قيم افتراضية آمنة
            $pass = json_decode($passwordRaw, true) ?? [];
            $login = json_decode($loginRaw, true) ?? [];
            $session = json_decode($sessionRaw, true) ?? [];

            return [
                // القسم الأول: الأمان (كلمة المرور + الدخول)
                'security' => [
                    'minPasswordLength' => $pass['min_length'] ?? 8,
                    'requireUppercase' => $pass['require_uppercase'] ?? true,
                    'requireNumbers' => $pass['require_numbers'] ?? true,
                    'maxFailedAttempts' => $login['max_failed_attempts'] ?? 5,
                    'lockoutDuration' => $login['lockout_duration'] ?? 30,
                ],
                // القسم الثاني: الجلسات
                'session' => [
                    'globalTimeout' => $session['global_timeout'] ?? 120,
                    'maxConcurrentSessions' => $session['max_concurrent_sessions'] ?? 3,
                    'rememberMeEnabled' => $session['remember_me_enabled'] ?? true,
                    'rememberMeDuration' => $session['remember_me_duration'] ?? 30,
                ]
            ];
        });

        return response()->json($data);
    }

    public function updatePolicy(Request $request) 
    {
        // تحديد نوع التحديث القادم من الواجهة (security أو session)
        $type = $request->input('type');

        if ($type === 'security') {
            // 1. تحديث إعدادات الأمان
            $validated = $request->validate([
                'minPasswordLength' => 'required|integer|min:6',
                'requireUppercase' => 'required|boolean',
                'requireNumbers' => 'required|boolean',
                'maxFailedAttempts' => 'required|integer|min:3',
                'lockoutDuration' => 'required|integer|min:1',
            ]);

            // تخزين سياسة كلمة المرور
            $passwordData = [
                'min_length' => $validated['minPasswordLength'],
                'require_uppercase' => $validated['requireUppercase'],
                'require_numbers' => $validated['requireNumbers'],
                'require_lowercase' => true, // قيم ثابتة للحفاظ على التوافق
                'require_symbols' => false,
            ];
            DB::table('settings')->updateOrInsert(
                ['key' => 'security.password'],
                ['value' => json_encode($passwordData), 'updated_at' => now()]
            );

            // تخزين سياسة الدخول
            $loginData = [
                'max_failed_attempts' => $validated['maxFailedAttempts'],
                'lockout_duration' => $validated['lockoutDuration']
            ];
            DB::table('settings')->updateOrInsert(
                ['key' => 'security.login'],
                ['value' => json_encode($loginData), 'updated_at' => now()]
            );

        } elseif ($type === 'session') {
            // 2. تحديث إعدادات الجلسة
            $validated = $request->validate([
                'globalTimeout' => 'required|integer|min:15',
                'maxConcurrentSessions' => 'required|integer|min:1',
                'rememberMeEnabled' => 'required|boolean',
                'rememberMeDuration' => 'required|integer|min:1',
            ]);

            $sessionData = [
                'global_timeout' => $validated['globalTimeout'],
                'max_concurrent_sessions' => $validated['maxConcurrentSessions'],
                'remember_me_enabled' => $validated['rememberMeEnabled'],
                'remember_me_duration' => $validated['rememberMeDuration'],
            ];

            DB::table('settings')->updateOrInsert(
                ['key' => 'security.session'],
                ['value' => json_encode($sessionData), 'updated_at' => now()]
            );
        }

        // مسح الكاش ليتم تحميل الإعدادات الجديدة في الطلب القادم
        Cache::forget('admin.settings.all');
        
        // مسح كاش السياسة القديم (لضمان عدم تعارض الأنظمة الأخرى)
        Cache::forget('security.policy');

        return response()->json(['message' => 'تم تحديث الإعدادات بنجاح']);
    }
}