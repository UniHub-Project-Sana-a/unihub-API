<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'status'          => $this->status,
            'college_id'      => $this->college_id,

            // ========== الإضافات الجديدة هنا ==========

            // قم بتضمين كائن user_type كاملاً إذا تم تحميل العلاقة
            'user_type' => $this->whenLoaded('userType', function () {
                return [
                    'user_type_id'   => $this->userType->user_type_id,
                    'user_type_name' => $this->userType->user_type_name,
                    'user_type_code' => $this->userType->user_type_code,
                ];
            }),

            // قم بتضمين كائن الكلية إذا تم تحميل العلاقة
            'college' => $this->whenLoaded('college', function () {
                return [
                    'college_id'   => $this->college->college_id,
                    'college_name' => $this->college->college_name,
                    'college_code' => $this->college->college_code,
                ];
            }),
        ];
    }
}