<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HasPermission;
use App\Http\Middleware\LogUserActivity;
use App\Http\Middleware\CheckUserType;
use App\Http\Middleware\CheckIpRestrictions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. تسجيل الأسماء المستعارة (Aliases) لاستخدامها في الراوتات
        $middleware->alias([
            'permission'     => HasPermission::class,      // استخدام: middleware('perm:users.create')
            'activity' => LogUserActivity::class,    // استخدام: middleware('activity:action_name')
            'type'     => CheckUserType::class,      // استخدام: middleware('type:admin')
        ]);

        // 2. إضافة ميدل وير في بداية كل طلبات الـ API (مثل فحص IP)
        $middleware->api(prepend: [
            CheckIpRestrictions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();