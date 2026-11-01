<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate; // <-- 1. إضافة الاستيراد
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\UserDevice; // <-- 2. إضافة الاستيراد
use App\Policies\UserDevicePolicy; // <-- 3. إضافة الاستيراد
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::ignoreMigrations();
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

        // حماية أولية لعناوين IP قبل مصادقة عميل التكامل (مضاعف الافتراضي)
        RateLimiter::for('integration-ip', function (Request $request) {
            $perMinute = max(1, (int) config('integration.default_rate_limit', 600)) * 2;
            return Limit::perMinute($perMinute)->by(\App\Support\ClientIp::resolve($request));
        });

        // 4. تسجيل الـ Policy يدويًا
        Gate::policy(UserDevice::class, UserDevicePolicy::class);
        // Passport::routes();

        // 5. تهيئة جداول Neon Postgres تلقائياً عند أول تشغيل
        if (env('DB_CONNECTION') === 'pgsql' || config('database.default') === 'pgsql') {
            try {
                if (!Schema::hasTable('users')) {
                    Artisan::call('migrate', ['--force' => true]);
                    Artisan::call('db:seed', ['--force' => true]);
                }
            } catch (\Throwable $e) {
                Log::warning('Neon auto-migration error: ' . $e->getMessage());
            }
        }
    }
}