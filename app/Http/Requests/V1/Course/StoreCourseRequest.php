<?php
namespace App\Http\Requests\V1\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'course_name' => ['required', 'string', 'max:150'],
            'course_code' => ['required', 'string', 'max:50', 'unique:courses,course_code'],
            'course_type' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}