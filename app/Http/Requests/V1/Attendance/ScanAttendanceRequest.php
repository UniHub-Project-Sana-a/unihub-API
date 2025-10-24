<?php
namespace App\Http\Requests\V1\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'session_code' => ['required', 'string'],
            'qr_code' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ];
    }
}