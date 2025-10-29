<?php
namespace App\Http\Requests\V1\Program;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool { return true; }

public function rules(): array {
  return [
    'program_name'  => [
      'required','string','max:50',
      Rule::unique('programs','program_name')
        ->where(fn($q) => $q->whereNull('deleted_at')->where('department_id', $this->department_id)),
    ],
    'department_id' => ['required','integer','exists:departments,department_id'],
    'is_active'     => ['sometimes','boolean'],
  ];
}
}