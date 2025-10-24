<?php
namespace App\Http\Requests\V1\Program;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'program_name' => ['required', 'string', 'max:50', 'unique:programs,program_name'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}