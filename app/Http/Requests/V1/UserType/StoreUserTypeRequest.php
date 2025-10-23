<?php
namespace App\Http\Requests\V1\UserType;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserTypeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'user_type_name' => ['required','string','max:50','unique:user_types,user_type_name'],
            'user_type_code' => ['required','string','max:30','unique:user_types,user_type_code'],
        ];
    }
}