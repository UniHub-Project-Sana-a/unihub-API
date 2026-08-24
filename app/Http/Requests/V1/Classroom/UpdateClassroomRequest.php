<?php
namespace App\Http\Requests\V1\Classroom;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'classroom_name' => ['sometimes', 'string', 'max:100'],
            'building_id' => ['sometimes', 'integer', 'exists:buildings,building_id'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
            'floor' => ['sometimes', 'integer'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'allowed_distance' => ['nullable', 'numeric'],
            'classroom_type' => ['sometimes', 'integer'],
            'windows_count' => ['nullable', 'integer', 'min:0'],
            'has_computer' => ['nullable', 'boolean'],
            'display_type' => ['nullable', 'in:none,screen,projector,smart_board'],
            'notes' => ['nullable', 'string'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'remote_id' => ['nullable', 'string', 'max:100', 'unique:classrooms,remote_id,' . $this->route('classroom')?->classroom_id . ',classroom_id'],
        ];
    }
}