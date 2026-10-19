<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use App\Models\IntegrationLog;
use App\Support\ClientIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * مصادقة عملاء التكامل الخارجي (System-to-System).
 *
 * تفرض عاملين مجتمعين، بنفس نموذج الحماية المعتمد:
 *   1) Bearer Token  (يُخزَّن hash ويُقارَن بزمن ثابت)
 *   2) عنوان IP المصدر ضمن القائمة المسموحة (يدعم CIDR)
 *
 * (تم إلغاء فحص هيدر X-Client-MAC — الترويسة، إن أُرسلت، تُسجَّل فقط ولا تُتحقّق منها).
 *
 * كما تسجّل كل طلب في جدول integration_logs للتتبّع.
 */
class AuthenticateIntegrationClient
{
    /** الطرق المسموحة لواجهات الدفع. */
    private const ALLOWED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        // 0) Preflight (CORS) يمرّ دون فحص
        if ($request->getMethod() === 'OPTIONS') {
            return $next($request);
        }

        $start = microtime(true);

        // 1) نوع الطلب
        if (!in_array($request->getMethod(), self::ALLOWED_METHODS, true)) {
            return $this->fail($request, 405, 'Only POST, PUT, PATCH and DELETE requests are allowed.', $start);
        }

        // 2) التوكن (Bearer)
        $token = $request->bearerToken();
        if (!$token) {
            return $this->fail($request, 401, 'Access unauthorized: Invalid or missing security token.', $start);
        }

        $client = IntegrationClient::where('token_hash', IntegrationClient::hashToken($token))->first();

        if (!$client || !hash_equals($client->token_hash, IntegrationClient::hashToken($token))) {
            return $this->fail($request, 401, 'Access unauthorized: Invalid or missing security token.', $start);
        }

        if (!$client->is_active) {
            return $this->fail($request, 403, 'Access forbidden: Integration client is disabled.', $start, $client);
        }

        // 3) عنوان IP (من REMOTE_ADDR غير القابل للتزوير افتراضياً)
        $clientIp = ClientIp::resolve($request);
        $allowedIps = $client->allowed_ips ?? [];
        if (count($allowedIps) === 0 || !IpUtils::checkIp($clientIp, $allowedIps)) {
            return $this->fail($request, 403, 'Access forbidden: Unauthorized IP address (' . $clientIp . ').', $start, $client);
        }

        // اجتياز الفحوص: نضع العميل على الطلب ليستخدمه الكونترولر
        $request->attributes->set('integration_client', $client);

        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        $response = $next($request);

        $this->writeLog($client, $request, $response->getStatusCode(), $start, [], $response);

        return $response;
    }

    /**
     * رد خطأ موحّد + تسجيل المحاولة الفاشلة.
     */
    private function fail(Request $request, int $code, string $message, float $start, ?IntegrationClient $client = null): Response
    {
        $this->writeLog($client, $request, $code, $start, ['error' => $message]);

        return response()->json([
            'status' => 'error',
            'message' => $message,
            'timestamp' => now()->toDateTimeString(),
        ], $code);
    }

    private function writeLog(?IntegrationClient $client, Request $request, int $status, float $start, array $summary = [], ?Response $response = null): void
    {
        try {
            $maxBytes = (int) config('integration.log_payload_max_bytes', 65535);
            $raw = (string) $request->getContent();
            $requestBytes = strlen($raw);
            $payload = $requestBytes > 0 ? mb_strcut($raw, 0, $maxBytes) : null;

            if (!$summary && $response) {
                $summary = $this->summarize($response);
            }

            IntegrationLog::create([
                'client_id' => $client?->id,
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'ip' => ClientIp::resolve($request),
                'mac' => (string) $request->header('X-Client-MAC'),
                'request_payload' => $payload,
                'request_bytes' => $requestBytes,
                'http_status' => $status,
                'summary' => $summary ?: null,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'response_bytes' => $response ? strlen((string) $response->getContent()) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // لا نُفشل الطلب بسبب فشل التسجيل
        }
    }

    /**
     * يبني ملخّصاً مختصراً من رد الكونترولر (أعداد/حالة + أسباب مختصرة).
     */
    private function summarize(Response $response): array
    {
        $data = json_decode((string) $response->getContent(), true);
        if (!is_array($data)) {
            return [];
        }

        $out = array_intersect_key($data, array_flip([
            'status', 'message',
            'inserted_records', 'updated_records',
            'skipped_records', 'deactivated_records',
        ]));

        if (isset($data['skipped_reasons']) && is_array($data['skipped_reasons'])) {
            $out['skipped_reasons'] = array_slice(array_map(
                fn ($r) => mb_strcut((string) $r, 0, 300),
                $data['skipped_reasons']
            ), 0, 25);
            $out['skipped_reasons_total'] = count($data['skipped_reasons']);
        }

        return $out;
    }
}
