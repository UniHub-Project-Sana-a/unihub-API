<?php
namespace App\Http\Requests\V1\Building;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'building_name' => ['sometimes', 'string', 'max:100'],
            'building_code' => ['nullable', 'string', 'max:50'],
            'floors_count' => ['sometimes', 'integer', 'min:1'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
        ];
    }
}