<?php
namespace App\Http\Requests\V1\Level;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLevelRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'level_name' => ['sometimes', 'string', 'max:50'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,department_id'],
        ];
    }
}