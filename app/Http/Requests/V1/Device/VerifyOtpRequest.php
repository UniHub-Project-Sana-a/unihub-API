<?php
namespace App\Http\Requests\V1\Device;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp_code' => ['required', 'string'],
            'mac_address' => ['required', 'string'],
        ];
    }
}