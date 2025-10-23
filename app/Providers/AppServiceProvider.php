<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Login limiter حسب إعداد settings.security.login إن وُجد، وإلا افتراضيات
        RateLimiter::for('login', function (Request $request) {
            $policy = Cache::remember('security.login.policy', 300, function () {
                $raw = DB::table('settings')->where('key', 'security.login')->value('value');
                $cfg = $raw ? json_decode($raw, true) : [];
                return [
                    'max_failed_attempts' => (int)($cfg['max_failed_attempts'] ?? 5),
                    'lockout_duration'    => (int)($cfg['lockout_duration']    ?? 10), // دقائق
                ];
            });

            $by = strtolower((string)$request->input('email')).'|'.$request->ip();
            return [Limit::perMinutes($policy['lockout_duration'], $policy['max_failed_attempts'])->by($by)];
        });

        // محددات لمسارات أخرى
        RateLimiter::for('forgot', fn(Request $r) => [Limit::perMinutes(15, 3)->by($r->ip())]);
        RateLimiter::for('reset',  fn(Request $r) => [Limit::perMinutes(30, 5)->by($r->ip())]);
    }
}