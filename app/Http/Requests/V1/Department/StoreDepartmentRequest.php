<?php
namespace App\Http\Requests\V1\Department;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'department_name' => ['required', 'string', 'max:100'],
            'department_code' => ['nullable', 'string', 'max:20', 'unique:departments,department_code'],
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
        ];
    }
}