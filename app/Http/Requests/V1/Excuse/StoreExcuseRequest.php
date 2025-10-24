<?php
namespace App\Http\Requests\V1\Excuse;
use Illuminate\Foundation\Http\FormRequest;

class StoreExcuseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'course_id' => ['required', 'exists:courses,course_id'],
            'request_date' => ['required', 'date'],
            'reason' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,png'],
        ];
    }
}