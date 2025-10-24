<?php
namespace App\Http\Requests\V1\AppVersion;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppVersionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'package_name' => ['required', 'string', 'max:50'],
            'version_number' => ['required', 'string', 'max:20'],
            'release_date' => ['required', 'date'],
            'is_mandatory_update' => ['required', 'boolean'],
            'platform' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ];
    }
}