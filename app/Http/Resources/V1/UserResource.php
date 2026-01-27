<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // ✅ منطق جلب الصلاحيات
        // نبحث عن الصلاحيات المرتبطة بدور المستخدم الحالي
        $permissionsQuery = DB::table('user_type_permissions')
            ->join('permissions', 'user_type_permissions.permission_id', '=', 'permissions.permission_id')
            ->where('user_type_permissions.user_type_id', $this->user_type_id);

        // فلترة الصلاحيات حسب الكلية (إذا كان المستخدم تابعاً لكلية)
        // إذا كان college_id = null (مشرف عام)، فسيجلب الصلاحيات العامة (التي college_id لها null أو 0)
        // أو يمكنك جعل المشرف يرى كل شيء برمجياً، لكن هنا نلتزم بالقاعدة
        if ($this->college_id) {
            $permissionsQuery->where('user_type_permissions.college_id', $this->college_id);
        }

        // الحصول على مصفوفة المفاتيح فقط: ['users.create', 'reports.view']
        $permissions = $permissionsQuery->pluck('permissions.permission_key');

        return [
            'user_id'         => $this->user_id,
            'full_name'       => $this->full_name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'academic_number' => $this->academic_number,
            'gender'          => (int) $this->gender,
            'user_type_id'    => (int) $this->user_type_id,
            'created_at'      => optional($this->created_at)->toDateTimeString(),
            'updated_at'      => optional($this->updated_at)->toDateTimeString(),
            'status'          => $this->status, // تأكد أن العمود موجود في الجدول
            'college_id'      => $this->college_id,

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