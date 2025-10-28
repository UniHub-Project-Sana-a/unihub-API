<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'       => ['required','string','max:100'],
            'email'           => ['required','email','max:100','unique:users,email'],
            'phone'           => ['required','string','max:20','unique:users,phone'],
            'password'        => ['required','string','min:6'],
            'academic_number' => ['required','string','max:50','unique:users,academic_number'],
            'gender'          => ['required','integer','in:0,1'],
            'user_type_id'    => ['required','integer','exists:user_types,user_type_id'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
        ];
    }
}