<?php
namespace App\Http\Requests\V1\StudentGroup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentGroupRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('student_group');
        return [
            'group_name' => ['sometimes', 'string', 'max:100', 'unique:student_groups,group_name,' . $id . ',group_id'],
        ];
    }
}