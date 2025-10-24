<?php
namespace App\Http\Requests\V1\Course;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('course');
        return [
            'course_name' => ['sometimes', 'string', 'max:150'],
            'course_code' => ['sometimes', 'string', 'max:50', 'unique:courses,course_code,' . $id . ',course_id'],
            'course_type' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}