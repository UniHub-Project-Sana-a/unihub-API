<?php
namespace App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function getPolicy() {
    $policy = Cache::rememberForever('security.policy', function () {
        $password = DB::table('settings')->where('key', 'security.password')->value('value');
        $login = DB::table('settings')->where('key', 'security.login')->value('value');
        return [
            'password' => json_decode($password, true),
            'login'    => json_decode($login, true),
        ];
    });
    return response()->json($policy);
}

    public function updatePolicy(Request $request) {
    // التحقق من سياسة كلمة المرور
    $password_policy = $request->validate([
        'password.min_length' => ['required', 'integer', 'min:6'],
        'password.require_uppercase' => ['required', 'boolean'],
        'password.require_lowercase' => ['required', 'boolean'],
        'password.require_numbers' => ['required', 'boolean'],
        'password.require_symbols' => ['required', 'boolean'],
    ]);
    
    // التحقق من سياسة المصادقة
    $login_policy = $request->validate([
        'login.max_failed_attempts' => ['required', 'integer', 'min:3'],
        'login.lockout_duration'    => ['required', 'integer', 'min:1'],
    ]);

    // تخزين كل سياسة في سجل منفصل
    DB::table('settings')->updateOrInsert(
        ['key' => 'security.password'],
        ['value' => json_encode($password_policy['password'])]
    );
    DB::table('settings')->updateOrInsert(
        ['key' => 'security.login'],
        ['value' => json_encode($login_policy['login'])]
    );

    Cache::forget('security.policy'); // امسح الكاش ليتم تحديثه
    return $this->getPolicy();
}
}