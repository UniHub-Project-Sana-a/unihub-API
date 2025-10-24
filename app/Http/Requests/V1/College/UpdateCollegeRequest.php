<?php
namespace App\Http\Requests\V1\College;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('college');
        return [
            'college_name' => ['sometimes', 'string', 'max:100', 'unique:colleges,college_name,' . $id . ',college_id'],
            'college_code' => ['nullable', 'string', 'max:20', 'unique:colleges,college_code,' . $id . ',college_id'],
        ];
    }
}