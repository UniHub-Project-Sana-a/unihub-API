<?php
namespace App\Http\Requests\V1\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'semester_id'  => ['required','integer','exists:semesters,semester_id'],
            'course_code'  => ['required','string','max:50','unique:courses,course_code'],
            'course_name'  => ['required','string','max:150'],
            'credit_hours' => ['required','integer','min:0'],
            'is_elective'  => ['sometimes','boolean'],
            
            // ✅ التعديلات:
            'department_id'=> ['nullable','integer','exists:departments,department_id'],
            // إضافة college_id كحقل إجباري الآن
            'college_id'   => ['required','integer','exists:colleges,college_id'],
            // إضافة الحقول الجديدة كحقول اختيارية (nullable)
            'program_id'   => ['nullable','integer','exists:programs,program_id'],
            'level_id'     => ['nullable','integer','exists:levels,level_id'],
            
            'notes'        => ['nullable','string','max:500'],
            'course_type'  => ['sometimes','integer'],
            'is_active'    => ['sometimes','boolean'],
        ];
    }
}