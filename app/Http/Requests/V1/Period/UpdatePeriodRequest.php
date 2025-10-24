<?php
namespace App\Http\Requests\V1\Period;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'college_id' => ['sometimes', 'integer', 'exists:colleges,college_id'],
            'period_name' => ['sometimes', 'string', 'max:50'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'session_type' => ['sometimes', 'string', 'max:10'],
        ];
    }
}