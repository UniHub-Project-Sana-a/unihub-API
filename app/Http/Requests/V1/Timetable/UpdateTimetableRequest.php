<?php
namespace App\Http\Requests\V1\Timetable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTimetableRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'course_id' => ['sometimes', 'integer', 'exists:courses,course_id'],
            'lecturer_id' => ['sometimes', 'integer', 'exists:lecturers,lecturer_id'],
            'group_id' => ['sometimes', 'integer', 'exists:student_groups,group_id'],
            'classroom_id' => ['sometimes', 'integer', 'exists:classrooms,classroom_id'],
            'day_id' => ['sometimes', 'integer', 'exists:days,day_id'],
            'period_id' => ['sometimes', 'integer', 'exists:periods,period_id'],
            'lecture_type' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'college_id' => ['sometimes', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,department_id'],
            'gender_type' => ['sometimes', 'integer'],
            'lecture_hours' => ['sometimes', 'numeric'],
        ];
    }
}