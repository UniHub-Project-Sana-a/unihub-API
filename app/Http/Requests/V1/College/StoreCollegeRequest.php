<?php

namespace App\Http\Requests\V1\College;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    
    public function rules(): array {
        return [
            'college_name' => ['required', 'string', 'max:100', 'unique:colleges,college_name'],
            'college_code' => ['nullable', 'string', 'max:20', 'unique:colleges,college_code'],
            'college_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
        ];
    }
}