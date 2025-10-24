<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate; // <-- 1. إضافة الاستيراد
use App\Models\UserDevice; // <-- 2. إضافة الاستيراد
use App\Policies\UserDevicePolicy; // <-- 3. إضافة الاستيراد

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Login limiter
        RateLimiter::for('login', function (Request $request) {
            // ... كود RateLimiter ...
        });

        // محددات لمسارات أخرى
        RateLimiter::for('forgot', fn(Request $r) => [Limit::perMinutes(15, 3)->by($r->ip())]);
        RateLimiter::for('reset',  fn(Request $r) => [Limit::perMinutes(30, 5)->by($r->ip())]);

        // 4. تسجيل الـ Policy يدويًا
        Gate::policy(UserDevice::class, UserDevicePolicy::class);
    }
}