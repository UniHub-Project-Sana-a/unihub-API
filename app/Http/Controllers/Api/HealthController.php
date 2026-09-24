<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
            'environment' => config('app.env'),
        ];

        try {
            // Test database connection
            DB::connection()->getPdo();
            $health['database'] = 'connected';
            
            // Get database info
            $health['database_type'] = DB::connection()->getDriverName();
            
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['database'] = 'disconnected';
            $health['error'] = $e->getMessage();
            
            return response()->json($health, 503);
        }

        return response()->json($health, 200);
    }

    /**
     * Detailed health check with more information
     * 
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
            'environment' => config('app.env'),
            'version' => '1.0.0', // يمكنك تغييرها
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['database'] = [
                'status' => 'connected',
                'type' => DB::connection()->getDriverName(),
                'name' => DB::connection()->getDatabaseName(),
            ];

            // Count some tables to verify structure
            $health['database']['tables'] = [
                'users' => DB::table('users')->count(),
                'students' => DB::table('students')->count(),
                'lecturers' => DB::table('lecturers')->count(),
                'courses' => DB::table('courses')->count(),
            ];

        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['database'] = [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
            ];
        }

        // Cache check
        try {
            cache()->put('health_check', 'ok', 60);
            $cacheTest = cache()->get('health_check');
            $health['cache'] = $cacheTest === 'ok' ? 'working' : 'failed';
        } catch (Exception $e) {
            $health['cache'] = 'failed';
        }

        // Storage check
        try {
            $health['storage'] = [
                'writable' => is_writable(storage_path()),
                'logs_writable' => is_writable(storage_path('logs')),
            ];
        } catch (Exception $e) {
            $health['storage'] = 'check_failed';
        }

        // PHP Info
        $health['php'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;
        return response()->json($health, $statusCode);
    }

    /**
     * Simple ping endpoint
     * 
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'message' => 'pong',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}