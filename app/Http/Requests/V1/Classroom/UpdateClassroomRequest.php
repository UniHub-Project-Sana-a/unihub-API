<?php
namespace App\Http\Requests\V1\Classroom;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
            'classroom_name' => ['sometimes', 'string', 'max:100'],
            'building_id' => ['sometimes', 'integer', 'exists:buildings,building_id'],
            'floor' => ['sometimes', 'integer'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'allowed_distance' => ['nullable', 'numeric'],
            'classroom_type' => ['sometimes', 'integer'],
        ];
    }
}