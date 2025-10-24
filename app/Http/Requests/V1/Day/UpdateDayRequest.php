<?php
namespace App\Http\Requests\V1\Day;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDayRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('day');
        return [
            'day_name' => ['sometimes', 'string', 'max:20', 'unique:days,day_name,' . $id . ',day_id'],
        ];
    }
}