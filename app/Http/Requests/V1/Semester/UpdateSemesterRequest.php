<?php
namespace App\Http\Requests\V1\Semester;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'semester_name' => ['sometimes', 'string', 'max:50'],
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'level_id' => ['sometimes', 'integer', 'exists:levels,level_id'],
        ];
    }
}