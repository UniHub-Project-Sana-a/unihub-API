<?php
namespace App\Http\Requests\V1\Timetable;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,course_id'],
            'lecturer_id' => ['required', 'integer', 'exists:lecturers,lecturer_id'],
            'group_id' => ['required', 'integer', 'exists:student_groups,group_id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,classroom_id'],
            'day_id' => ['required', 'integer', 'exists:days,day_id'],
            'period_id' => ['required', 'integer', 'exists:periods,period_id'],
            'lecture_type' => ['required', 'integer'],
            'status' => ['sometimes', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_year' => ['required', 'string', 'max:20'],
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
            'gender_type' => ['required', 'integer'],
            'lecture_hours' => ['required', 'numeric'],
        ];
    }
}