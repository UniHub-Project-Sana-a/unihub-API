<?php
namespace App\Http\Requests\V1\Semester;
use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'semester_name' => ['required', 'string', 'max:50'],
            'academic_year' => ['required', 'string', 'max:20'],
            'level_id' => ['required', 'integer', 'exists:levels,level_id'],
        ];
    }
}