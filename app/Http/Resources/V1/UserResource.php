<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
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
            'status' => $this->status,
            'college_id' => $this->college_id,
        ];
    }
}