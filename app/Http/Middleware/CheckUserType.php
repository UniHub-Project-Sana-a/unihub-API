<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$types  // مصفوفة من أكواد أنواع المستخدمين المسموح لهم
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$types)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // إذا لم يكن المستخدم مسجلاً، دعه يمر (Middleware المصادقة سيتعامل معه)
        if (!$user) {
            return $next($request);
        }

        // احصل على كود نوع المستخدم
        $userTypeCode = $user->userType->user_type_code ?? null;

        // إذا كان نوع المستخدم ضمن قائمة الأنواع المسموح بها، اسمح له بالمرور
        if (in_array($userTypeCode, $types)) {
            return $next($request);
        }

        // إذا لم يكن مسموحًا له، قم بإعادة توجيهه أو أرجع خطأ
        // الخيار 1: إرجاع خطأ 403 Forbidden (الأفضل للـ API)
        return response()->json(['message' => 'ليس لديك الصلاحية للوصول إلى هذا المورد.'], 403);
        
        // الخيار 2: إعادة توجيه (أقل شيوعًا في الـ API)
        // if ($userTypeCode === 'lecturer') {
        //     return redirect('/lecturer'); 
        // }
        // return redirect('/');
    }
}