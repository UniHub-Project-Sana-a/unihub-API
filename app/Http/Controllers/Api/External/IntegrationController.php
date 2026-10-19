<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * واجهات التكامل الخارجي (System-to-System) — Data Ingestion.
 *
 * محمية بالميدل وير integration.auth (Bearer Token + IP المصدر).
 * (لا يوجد فحص X-Client-MAC إطلاقاً — الترويسة إن أُرسلت تُسجَّل في السجل فقط).
 * كل الموارد تعمل على الجداول الفعلية وفق config/integration.php.
 */
class IntegrationController extends Controller
{
    public function __construct(private IntegrationSyncService $sync)
    {
    }

    /**
     * نقطة اختبار الاتصال والمصادقة (ليست واجهة بيانات).
     * POST /api/external/v1/ping
     */
    public function ping(Request $request): JsonResponse
    {
        $client = $request->attributes->get('integration_client');

        return response()->json([
            'status'    => 'success',
            'message'   => 'Integration endpoint reachable.',
            'timestamp' => now()->toDateTimeString(),
            'client'    => $client?->name,
        ]);
    }

    /**
     * إدخال/تحديث مجمّع (Upsert).
     * POST|PUT|PATCH /api/external/v1/{resource}
     * الجسم: { "data": [ { ... }, ... ] }
     */
    public function upsert(Request $request, string $resource): JsonResponse
    {
        if (!$this->sync->resource($resource)) {
            return $this->error('Unknown integration resource.', 404);
        }

        $data = $request->input('data');
        if (!is_array($data)) {
            return $this->error('Invalid request structure. "data" array is required.', 400);
        }

        $client = $request->attributes->get('integration_client');

        $summary = $this->sync->upsert($resource, array_values($data), $client);

        return response()->json(array_merge([
            'status'    => 'success',
            'message'   => "{$resource} synchronized successfully.",
            'timestamp' => now()->toDateTimeString(),
        ], $summary));
    }

    /**
     * تعطيل/حذف مجمّع وفق استراتيجية المورد.
     * DELETE /api/external/v1/{resource}
     * الجسم: { "data": [ { "college_code": "..." }, ... ] }
     */
    public function deactivate(Request $request, string $resource): JsonResponse
    {
        if (!$this->sync->resource($resource)) {
            return $this->error('Unknown integration resource.', 404);
        }

        $data = $request->input('data');
        if (!is_array($data)) {
            return $this->error('Invalid request structure. "data" array is required.', 400);
        }

        $client = $request->attributes->get('integration_client');

        $summary = $this->sync->deactivate($resource, array_values($data), $client);

        return response()->json(array_merge([
            'status'    => 'success',
            'message'   => "{$resource} deactivated successfully.",
            'timestamp' => now()->toDateTimeString(),
        ], $summary));
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status'    => 'error',
            'message'   => $message,
            'timestamp' => now()->toDateTimeString(),
        ], $status);
    }
}
