<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HasPermission
{
    /**
     * التحقق من الصلاحية (Permission Middleware)
     */
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 1. تجاوز للمشرفين (Super Admin Bypass)
        // إذا كان المستخدم مشرفاً أو رئاسة جامعة، يمر فوراً
        $user->loadMissing('userType'); // تأكد من تحميل العلاقة
        if ($user->userType && in_array($user->userType->user_type_code, ['admin', 'presidency'])) {
            return $next($request);
        }

        // دعم التحقق من صلاحيات متعددة مفصولة بفواصل (أو مصفوفة)
        $keys = array_filter(array_map('trim', explode(',', $permissionKey)));
        if (empty($keys)) {
            $keys = [$permissionKey];
        }

        // 2. التحقق من الصلاحية في قاعدة البيانات
        $query = DB::table('user_type_permissions')
            ->join('permissions', 'user_type_permissions.permission_id', '=', 'permissions.permission_id')
            ->where('user_type_permissions.user_type_id', $user->user_type_id)
            ->whereIn('permissions.permission_key', $keys);

        // 3. التحقق من سياق الكلية (Context Check)
        // إذا كان المستخدم مرتبطاً بكلية، تقبل الصلاحية إذا كانت محددة لكليته أو ممنوحة عامة لدوره
        if ($user->college_id) {
            $query->where(function ($q) use ($user) {
                $q->where('user_type_permissions.college_id', $user->college_id)
                  ->orWhereNull('user_type_permissions.college_id');
            });
        }

        if (!$query->exists()) {
            return response()->json([
                'message' => 'عذراً، ليس لديك الصلاحية للقيام بهذا الإجراء.',
                'required_permission' => $permissionKey
            ], 403);
        }

        return $next($request);
    }
}