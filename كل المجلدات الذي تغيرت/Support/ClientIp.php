<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * تحديد عنوان IP الحقيقي للعميل بشكل غير قابل للتزوير.
 *
 * افتراضياً نعتمد REMOTE_ADDR (عنوان الاتصال الفعلي) بدل الترويسات القابلة
 * للتزوير مثل X-Forwarded-For — بنفس مبدأ SecurityGuard المرجعي.
 * إذا كان الخادم خلف reverse proxy موثوق، فعِّل INTEGRATION_TRUST_FORWARDED_IP=true
 * مع ضبط الوسطاء الموثوقين في Laravel (TrustProxies).
 */
class ClientIp
{
    public static function resolve(Request $request): string
    {
        if (config('integration.trust_forwarded_ip', false)) {
            return (string) $request->ip();
        }

        return (string) ($request->server('REMOTE_ADDR') ?: $request->ip());
    }
}
