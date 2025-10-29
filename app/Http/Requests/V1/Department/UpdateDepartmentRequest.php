<?php
namespace App\Http\Requests\V1\Department;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'department_name' => ['sometimes', 'string', 'max:100'],
            'department_code' => ['nullable', 'string', 'max:20'],
            'college_id' => ['sometimes', 'integer', 'exists:colleges,college_id'],
        ];
    }
}