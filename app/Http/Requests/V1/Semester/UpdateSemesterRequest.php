<?php
namespace App\Http\Requests\V1\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Semester;

class UpdateSemesterRequest extends FormRequest {
    public function authorize(): bool { return true; }
public function rules(): array {
  $sem = $this->route('semester');
  $semesterId = $sem instanceof Semester ? $sem->semester_id : $sem;
  $levelId = $this->input('level_id', $sem instanceof Semester ? $sem->level_id : null);

  return [
    'level_id'      => ['sometimes','integer','exists:levels,level_id'],
    'term_number'   => [
      'sometimes','integer','min:1',
      Rule::unique('semesters')
        ->ignore($semesterId, 'semester_id')
        ->where(fn($q) => $q->where('level_id', $levelId)->whereNull('deleted_at')),
    ],
    'semester_name' => ['nullable','string','max:50'],
    'academic_year' => ['nullable','string','max:20'],
  ];
}
}