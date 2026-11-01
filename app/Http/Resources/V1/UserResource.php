<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    private static array $permissionsCache = [];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // ✅ منطق جلب الصلاحيات مع كاش لتجنب تكرار استعلام قاعدة البيانات (N+1 Query)
        $userTypeCode = $this->userType->user_type_code ?? null;
        $cacheKey = ($this->user_type_id ?? 0) . '_' . ($this->college_id ?? 0) . '_' . ($userTypeCode ?? '');

        if (!isset(self::$permissionsCache[$cacheKey])) {
            if ($userTypeCode === 'admin' || $userTypeCode === 'presidency') {
                self::$permissionsCache[$cacheKey] = DB::table('permissions')->pluck('permission_key')->merge(['*'])->values()->all();
            } else {
                $permissionsQuery = DB::table('user_type_permissions')
                    ->join('permissions', 'user_type_permissions.permission_id', '=', 'permissions.permission_id')
                    ->where('user_type_permissions.user_type_id', $this->user_type_id);

                if ($this->college_id) {
                    $permissionsQuery->where('user_type_permissions.college_id', $this->college_id);
                }

                self::$permissionsCache[$cacheKey] = $permissionsQuery->pluck('permissions.permission_key')->values()->all();
            }
        }

        $permissions = self::$permissionsCache[$cacheKey];

        // التحقق الذكي من الصفة التدريسية للكادر الأكاديمي
        $lecturer = $this->lecturer;
        $isLecturer = !is_null($lecturer);

        return [
            'user_id'         => $this->user_id,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'academic_number' => $this->academic_number,
            'gender'          => (int) $this->gender,
            'user_type_id'    => (int) $this->user_type_id,
            'user_type_code'  => $this->userType->user_type_code ?? null,
            'created_at'      => optional($this->created_at)->toDateTimeString(),
            'updated_at'      => optional($this->updated_at)->toDateTimeString(),
            'status'          => $this->status, // تأكد أن العمود موجود في الجدول
            'college_id'      => $this->college_id,

            // ✅ الصفة الأكاديمية / التدريسية (Persona Inheritance)
            'is_lecturer'     => $isLecturer,
            'lecturer_id'     => $lecturer?->lecturer_id,
            'lecturer'        => $lecturer ? [
                'lecturer_id'   => $lecturer->lecturer_id,
                'college_id'    => $lecturer->college_id,
                'department_id' => $lecturer->department_id,
                'title_id'      => $lecturer->title_id,
            ] : null,

            // العلاقات المحملة
            'user_type' => $this->whenLoaded('userType', function () {
                return [
                    'user_type_id'   => $this->userType->user_type_id,
                    'user_type_name' => $this->userType->user_type_name,
                    'user_type_code' => $this->userType->user_type_code,
                ];
            }),

            'college' => $this->whenLoaded('college', function () {
                return [
                    'college_id'   => $this->college->college_id,
                    'college_name' => $this->college->college_name,
                    'college_code' => $this->college->college_code,
                ];
            }),

            // ✅ قائمة الصلاحيات الجديدة
            'permissions' => $permissions,
        ];
    }
}