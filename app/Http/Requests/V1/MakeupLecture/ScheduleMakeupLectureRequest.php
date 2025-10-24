<?php
namespace App\Http\Requests\V1\MakeupLecture;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleMakeupLectureRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'classroom_id' => ['required', 'exists:classrooms,classroom_id'],
            'day_id' => ['required', 'exists:days,day_id'],
            'period_id' => ['required', 'exists:periods,period_id'],
        ];
    }
}