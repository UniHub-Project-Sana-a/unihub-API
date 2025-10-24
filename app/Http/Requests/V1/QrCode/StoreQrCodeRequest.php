<?php
namespace App\Http\Requests\V1\QrCode;
use Illuminate\Foundation\Http\FormRequest;

class StoreQrCodeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'timetable_id' => ['required', 'integer', 'exists:timetable,timetable_id'],
            'refresh_option_id' => ['nullable', 'integer', 'exists:qr_refresh_options,option_id'],
            'expires_at' => ['required', 'date'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'allowed_distance' => ['required', 'numeric'],
        ];
    }
}