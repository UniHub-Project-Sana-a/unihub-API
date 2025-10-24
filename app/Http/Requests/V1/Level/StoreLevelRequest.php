<?php
namespace App\Http\Requests\V1\Level;
use Illuminate\Foundation\Http\FormRequest;

class StoreLevelRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'level_name' => ['required', 'string', 'max:50'],
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
        ];
    }
}