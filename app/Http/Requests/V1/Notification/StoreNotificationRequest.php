<?php
namespace App\Http\Requests\V1\Notification;
use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'message_body' => ['required', 'string'],
            'group_id' => ['nullable', 'exists:student_groups,group_id'],
            'student_id' => ['nullable', 'exists:students,student_id'],
        ];
    }
}