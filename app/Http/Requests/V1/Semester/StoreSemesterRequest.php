<?php
namespace App\Http\Requests\V1\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreSemesterRequest extends FormRequest {
    public function authorize(): bool { return true; }
public function rules(): array {
  return [
    'level_id'      => ['required','integer','exists:levels,level_id'],
    'term_number'   => [
      'required','integer','min:1',
      Rule::unique('semesters')->where(fn($q) => $q->where('level_id', $this->level_id)->whereNull('deleted_at')),
    ],
    'semester_name' => ['nullable','string','max:50'],
    'academic_year' => ['nullable','string','max:20'],
  ];
}
}