<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('user');
        return [
            'full_name'       => ['sometimes','string','max:100'],
            'email'           => ['sometimes','email','max:100','unique:users,email,'.$id.',user_id'],
            'phone'           => ['sometimes','string','max:20','unique:users,phone,'.$id.',user_id'],
            'password'        => ['nullable','string','min:6'],
            'academic_number' => ['sometimes','string','max:50','unique:users,academic_number,'.$id.',user_id'],
            'gender'          => ['sometimes','integer','in:0,1'],
            'user_type_id'    => ['sometimes','integer','exists:user_types,user_type_id'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
        ];
    }
}