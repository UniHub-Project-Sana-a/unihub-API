<?php
namespace App\Http\Requests\V1\MakeupLecture;
use Illuminate\Foundation\Http\FormRequest;

class ReviewMakeupLectureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'integer', 'in:1,2,3,4,5'], 
            'notes' => ['nullable', 'string'],
        ];
    }
}