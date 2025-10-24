<?php
namespace App\Http\Requests\V1\Building;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'building_name' => ['required', 'string', 'max:100'],
            'floors_count' => ['required', 'integer', 'min:1'],
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
        ];
    }
}