<?php
namespace App\Http\Requests\V1\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreLevelRequest extends FormRequest {
    public function authorize(): bool { return true; }
public function rules(): array {
  return [
    'program_id'   => ['required','integer','exists:programs,program_id'],
    'level_number' => [
      'required','integer','min:1',
      Rule::unique('levels')->where(fn($q) => $q->where('program_id', $this->program_id)->whereNull('deleted_at')),
    ],
    'level_name'   => ['nullable','string','max:50'],
  ];
}
}