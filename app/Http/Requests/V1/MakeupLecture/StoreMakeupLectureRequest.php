<?php
namespace App\Http\Requests\V1\MakeupLecture;
use Illuminate\Foundation\Http\FormRequest;

class StoreMakeupLectureRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            // البيانات الأساسية
            'lecturer_id' => ['required', 'exists:lecturers,lecturer_id'], // يرسل من الواجهة
            'course_id' => ['required', 'exists:courses,course_id'],
            'group_id' => ['required', 'exists:student_groups,group_id'],
            
            // بيانات الموعد (الجديدة)
            'original_date' => ['required', 'date'], // تاريخ الغياب
            'requested_date' => ['required', 'date', 'after_or_equal:today'], // تاريخ التعويض
            'start_time' => ['required'], // تنسيق الوقت
            'end_time' => ['required'],
            'classroom_id' => ['required', 'exists:classrooms,classroom_id'], // القاعة المقترحة
            
            // بيانات السبب
            'reason_type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}