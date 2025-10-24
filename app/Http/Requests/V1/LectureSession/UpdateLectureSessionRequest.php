<?php
namespace App\Http\Requests\V1\LectureSession;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLectureSessionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('lecture_session');
        return [
            'session_date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'actual_classroom_id' => ['nullable', 'integer', 'exists:classrooms,classroom_id'],
            'actual_attendance_count' => ['nullable', 'integer'],
            'session_code' => ['sometimes', 'string', 'max:50', 'unique:lecture_sessions,session_code,' . $id . ',session_id'],
            'status' => ['sometimes', 'integer'],
            'attendance_overage_alert' => ['sometimes', 'boolean'],
            'system_attendance_count' => ['sometimes', 'integer'],
        ];
    }
}