<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\IpRestriction;
use Symfony\Component\HttpFoundation\IpUtils;

class CheckIpRestrictions
{
    public function handle(Request $request, Closure $next): Response
    {
        // تجاوز الفحص في وضع التطوير المحلي إذا أردت (اختياري)
        // if (app()->environment('local')) return $next($request);

        $clientIp = $request->ip();

        // 1. جلب القواعد النشطة من الكاش أو القاعدة
        // يفضل استخدام الكاش هنا للأداء العالي
        $restrictions = IpRestriction::where('is_active', true)->get();

        $blacklist = $restrictions->where('type', 'blacklist')->pluck('ip_address')->toArray();
        $whitelist = $restrictions->where('type', 'whitelist')->pluck('ip_address')->toArray();

        // 2. فحص القائمة السوداء (ممنوع فوراً)
        if (IpUtils::checkIp($clientIp, $blacklist)) {
            return response()->json(['message' => 'Access Denied: Your IP is blacklisted.'], 403);
        }

        // 3. فحص القائمة البيضاء (إذا وجدت، يُمنع أي شخص ليس فيها)
        if (count($whitelist) > 0) {
            if (!IpUtils::checkIp($clientIp, $whitelist)) {
                return response()->json(['message' => 'Access Denied: Restricted Network.'], 403);
            }
        }

        return $next($request);
    }
}