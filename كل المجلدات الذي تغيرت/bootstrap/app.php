<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Middleware\HasPermission;
use App\Http\Middleware\LogUserActivity;
use App\Http\Middleware\CheckUserType;
use App\Http\Middleware\CheckIpRestrictions;
use App\Http\Middleware\AuthenticateIntegrationClient;
use App\Http\Middleware\ThrottleIntegrationClient;
use App\Http\Middleware\EnsureIdempotency;

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
            'integration.auth' => AuthenticateIntegrationClient::class, // مصادقة عملاء التكامل الخارجي
            'integration.throttle' => ThrottleIntegrationClient::class, // حدّ المعدّل لكل عميل
            'integration.idempotency' => EnsureIdempotency::class,      // إعادة المحاولة الآمنة
        ]);

        // 2. إضافة ميدل وير في بداية كل طلبات الـ API (مثل فحص IP)
        $middleware->api(prepend: [
            CheckIpRestrictions::class,
        ]);

        // 3. مسار الجذب للزوار:
        //    طلبات API لا تحتاج تحويلًا (ترجع 401 بصيغة JSON) ،
        //    وهذا يمنع ظهور خطأ "Route [login] not defined" الذي كان يتحول لخطأ 500
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*')) {
                return null;
            }

            return \Illuminate\Support\Facades\Route::has('login') ? route('login') : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // إرجاع JSON دائماً لمسارات API حتى لو لم يرسل العميل رأس Accept: application/json
        // (وإلا يعيد الخادم صفحة HTML عند الخطأ، فيفشل تحليل الاستجابة في الواجهة)
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // الطلب غير الموثّق في API يرجع 401 بصيغة JSON،
        // بدل التحويل إلى مسار login غير المعرّف الذي يسبب خطأ 500 "Route [login] not defined"
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // واجهات التكامل الخارجي: ردود أخطاء موحّدة بنفس نسق المصادقة
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/external/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only POST, PUT, PATCH and DELETE requests are allowed.',
                    'timestamp' => now()->toDateTimeString(),
                ], 405);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/external/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unknown integration endpoint.',
                    'timestamp' => now()->toDateTimeString(),
                ], 404);
            }
        });
    })
    ->create();