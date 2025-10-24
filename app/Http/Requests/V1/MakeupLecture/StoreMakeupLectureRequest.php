<?php
namespace App\Http\Requests\V1\MakeupLecture;
use Illuminate\Foundation\Http\FormRequest;

class StoreMakeupLectureRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'course_id' => ['required', 'exists:courses,course_id'],
            'group_id' => ['required', 'exists:student_groups,group_id'],
            'reason' => ['required', 'string'],
        ];
    }
}