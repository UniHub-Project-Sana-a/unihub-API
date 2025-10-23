<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HasPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey, string $requireCollege = 'false')
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // college_id من الهيدر أو الكويري
        $collegeId = $request->header('X-College-Id') ?? $request->query('college_id');

        // لو الصلاحية مطلوبة على مستوى كلية وتغيب college_id => خطأ 400
        if ($requireCollege === 'true' && empty($collegeId)) {
            return response()->json(['message' => 'college_id is required for this permission'], 400);
        }

        $has = DB::table('user_type_permissions as utp')
            ->join('permissions as p', 'p.permission_id', '=', 'utp.permission_id')
            ->where('utp.user_type_id', $user->user_type_id)
            ->where('p.permission_key', $permissionKey)
            ->when($requireCollege === 'true', function ($q) use ($collegeId) {
                $q->where('utp.college_id', $collegeId);
            })
            ->exists();

        if (!$has) {
            return response()->json(['message' => 'Forbidden: missing required permission'], 403);
        }

        return $next($request);
    }
}