<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use App\Support\ClientIp;
use Symfony\Component\HttpFoundation\Response;

/**
 * حدّ المعدّل لكل عميل تكامل (بعد المصادقة).
 *
 * الحدّ من عمود `rate_limit_per_minute` للعميل، وإلا القيمة الافتراضية العامة.
 * عند التجاوز: 429 بصيغة موحّدة + Retry-After + ترويسات X-RateLimit-*.
 */
class ThrottleIntegrationClient
{
    public function __construct(private RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->attributes->get('integration_client');

        $limit = (int) ($client?->rate_limit_per_minute ?? config('integration.default_rate_limit', 600));

        // 0 أو أقل = بلا حدّ
        if ($limit <= 0) {
            return $next($request);
        }

        $key = 'integration:rate:' . ($client?->id ?? ClientIp::resolve($request));

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'status'    => 'error',
                'message'   => 'Too many requests. Please retry later.',
                'timestamp' => now()->toDateTimeString(),
            ], 429)->withHeaders([
                'Retry-After'           => $retryAfter,
                'X-RateLimit-Limit'     => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit'     => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - $this->limiter->attempts($key)),
        ]);
    }
}
