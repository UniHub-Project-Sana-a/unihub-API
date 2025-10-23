<?php
namespace App\Http\Requests\V1\UserType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserTypeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('userType');
        return [
            'user_type_name' => ['sometimes','string','max:50','unique:user_types,user_type_name,'.$id.',user_type_id'],
            'user_type_code' => ['sometimes','string','max:30','unique:user_types,user_type_code,'.$id.',user_type_id'],
        ];
    }
}