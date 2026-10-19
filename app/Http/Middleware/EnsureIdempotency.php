<?php

namespace App\Http\Middleware;

use App\Models\IntegrationIdempotency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * إعادة المحاولة الآمنة (Idempotency) عبر هيدر `Idempotency-Key` (اختياري).
 *
 * - نفس المفتاح + نفس الجسم  → يُعاد الرد المخزَّن حرفياً (Idempotent-Replay: true).
 * - نفس المفتاح + جسم مختلف  → 409.
 * - بلا هيدر                → تمرير طبيعي.
 * - صلاحية السجل: config('integration.idempotency_ttl_hours')؛ المنتهي يُحذف ويُعامَل كطلب جديد.
 */
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $ttlHours = (int) config('integration.idempotency_ttl_hours', 24);
        $key = trim((string) $request->header('Idempotency-Key'));

        // معطّل، أو لا مفتاح، أو لا عميل → تمرير
        $client = $request->attributes->get('integration_client');
        if ($ttlHours <= 0 || $key === '' || !$client) {
            return $next($request);
        }

        if (mb_strlen($key) > 191) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Idempotency-Key is too long (max 191 characters).',
                'timestamp' => now()->toDateTimeString(),
            ], 400);
        }

        $hash = hash('sha256', $request->getMethod() . '|' . $request->path() . '|' . $request->getContent());

        $existing = IntegrationIdempotency::where('client_id', $client->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing) {
            if ($existing->expires_at && $existing->expires_at->isPast()) {
                $existing->delete();
            } else {
                if (!hash_equals($existing->request_hash, $hash)) {
                    return response()->json([
                        'status'    => 'error',
                        'message'   => 'Idempotency-Key already used with a different request body.',
                        'timestamp' => now()->toDateTimeString(),
                    ], 409);
                }

                return response()->json($existing->response, (int) $existing->http_status)
                    ->header('Idempotent-Replay', 'true');
            }
        }

        $response = $next($request);

        // لا نخزّن أخطاء الخادم (5xx) لإتاحة إعادة المحاولة
        if ($response->getStatusCode() < 500) {
            try {
                IntegrationIdempotency::updateOrCreate(
                    ['client_id' => $client->id, 'idempotency_key' => $key],
                    [
                        'request_hash' => $hash,
                        'http_status'  => $response->getStatusCode(),
                        'response'     => json_decode($response->getContent(), true),
                        'created_at'   => now(),
                        'expires_at'   => now()->addHours($ttlHours),
                    ]
                );
            } catch (\Throwable $e) {
                // لا نُفشل الطلب بسبب فشل التخزين
            }
        }

        return $response;
    }
}
