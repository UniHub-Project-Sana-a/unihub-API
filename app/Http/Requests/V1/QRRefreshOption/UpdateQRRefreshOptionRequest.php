<?php
namespace App\Http\Requests\V1\QRRefreshOption;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQRRefreshOptionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'interval_seconds' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}