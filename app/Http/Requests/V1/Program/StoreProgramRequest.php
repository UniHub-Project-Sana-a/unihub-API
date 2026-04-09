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
      'required', 'string', 'max:50',
      Rule::unique('programs', 'program_name')
        ->where(fn($q) => $q->whereNull('deleted_at')
            ->where('department_id', $this->department_id)
            ->where('academic_system', $this->academic_system) // أضف هذا
            ->where('block_based', $this->block_based)       // وأضف هذا
        ),
    ],
    'department_id' => ['required','integer','exists:departments,department_id'],
    'academic_system' => ['required', Rule::in(['semester', 'credit'])], // التأكد من القيم المسموحة
    'block_based'     => ['required', 'boolean'],
    'total_hours'     => ['nullable', 'integer', 'min:0'],
    'is_active'     => ['sometimes','boolean'],
  ];
}
}