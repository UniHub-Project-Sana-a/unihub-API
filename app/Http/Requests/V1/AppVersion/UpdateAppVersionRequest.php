<?php
namespace App\Http\Requests\V1\AppVersion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppVersionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'package_name' => ['sometimes', 'string', 'max:50'],
            'version_number' => ['sometimes', 'string', 'max:20'],
            'release_date' => ['sometimes', 'date'],
            'is_mandatory_update' => ['sometimes', 'boolean'],
            'platform' => ['sometimes', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ];
    }
}