<?php
namespace App\Http\Requests\V1\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Course;

class UpdateCourseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $c = $this->route('course');
        $courseId = $c instanceof Course ? $c->course_id : $c;

        return [
            'semester_id'  => ['sometimes','integer','exists:semesters,semester_id'],
            'course_code'  => [
                'sometimes','string','max:50',
                Rule::unique('courses','course_code')->ignore($courseId,'course_id'),
            ],
            'course_name'  => ['sometimes','string','max:150'],
            'credit_hours' => ['sometimes','integer','min:0'],
            'is_elective'  => ['sometimes','boolean'],
            'department_id'=> ['nullable','integer','exists:departments,department_id'],
            'notes'        => ['nullable','string','max:500'],
            'course_type'  => ['sometimes','integer'],
            'is_active'    => ['sometimes','boolean'],
        ];
    }
}