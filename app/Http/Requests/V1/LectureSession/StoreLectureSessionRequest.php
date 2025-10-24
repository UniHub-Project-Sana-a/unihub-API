<?php
namespace App\Http\Requests\V1\LectureSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreLectureSessionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'timetable_id' => ['required', 'integer', 'exists:timetable,timetable_id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'actual_classroom_id' => ['nullable', 'integer', 'exists:classrooms,classroom_id'],
            'session_code' => ['required', 'string', 'max:50', 'unique:lecture_sessions,session_code'],
        ];
    }
}