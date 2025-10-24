<?php
namespace App\Http\Requests\V1\MakeupLecture;
use Illuminate\Foundation\Http\FormRequest;

class ReviewMakeupLectureRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'status' => ['required', 'in:approved,rejected'], // approved by academic affairs
            'notes' => ['nullable', 'string'],
        ];
    }
}