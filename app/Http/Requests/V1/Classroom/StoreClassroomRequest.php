<?php
namespace App\Http\Requests\V1\Classroom;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'classroom_name' => ['required', 'string', 'max:100'],
            'building_id' => ['required', 'integer', 'exists:buildings,building_id'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
            'floor' => ['required', 'integer'],
            'capacity' => ['required', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'allowed_distance' => ['nullable', 'numeric'],
            'classroom_type' => ['required', 'integer'],
            'windows_count' => ['nullable', 'integer', 'min:0'],
            'has_computer' => ['nullable', 'boolean'],
            'display_type' => ['nullable', 'in:none,screen,projector,smart_board'],
            'notes' => ['nullable', 'string'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'remote_id' => ['nullable', 'string', 'max:100', 'unique:classrooms,remote_id'],
        ];
    }
}