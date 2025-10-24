<?php
namespace App\Http\Requests\V1\Day;
use Illuminate\Foundation\Http\FormRequest;

class StoreDayRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'day_name' => ['required', 'string', 'max:20', 'unique:days,day_name'],
        ];
    }
}