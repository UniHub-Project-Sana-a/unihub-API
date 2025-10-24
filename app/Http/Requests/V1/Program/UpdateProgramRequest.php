<?php
namespace App\Http\Requests\V1\Program;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('program');
        return [
            'program_name' => ['sometimes', 'string', 'max:50', 'unique:programs,program_name,' . $id . ',program_id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}