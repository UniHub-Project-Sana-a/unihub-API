<?php
namespace App\Http\Requests\V1\QRRefreshOption;
use Illuminate\Foundation\Http\FormRequest;

class StoreQRRefreshOptionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'interval_seconds' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}