<?php
namespace App\Http\Requests\V1\StudentGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentGroupRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'group_name' => ['required', 'string', 'max:100', 'unique:student_groups,group_name'],
        ];
    }
}