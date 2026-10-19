<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogUserActivity
{
    public function handle(Request $request, Closure $next, string $module = 'api')
    {
        $response = $next($request);

        try {
            if ($request->user()) {
                DB::table('user_activities')->insert([
                    'user_id'            => $request->user()->user_id,
                    'action_type'        => $request->method(), // GET/POST/PUT/DELETE
                    'action_description' => $request->route()?->getName() ?? $request->path(),
                    'module_name'        => $module,
                    'created_at'         => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // لا تعيق الطلب في حال فشل التسجيل
        }

        return $response;
    }
}