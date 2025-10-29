<?php
namespace App\Http\Requests\V1\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Level;

class UpdateLevelRequest extends FormRequest {
    public function authorize(): bool { return true; }
public function rules(): array {
  $routeParam = $this->route('level');
  $level = $routeParam instanceof Level ? $routeParam : Level::find($routeParam);
  $programId = $this->input('program_id', $level?->program_id);

  return [
    'program_id'   => ['sometimes','integer','exists:programs,program_id'],
    'level_number' => [
      'sometimes','integer','min:1',
      Rule::unique('levels')
        ->ignore($level?->level_id, 'level_id')
        ->where(fn($q) => $q->where('program_id', $programId)->whereNull('deleted_at')),
    ],
    'level_name'   => ['nullable','string','max:50'],
  ];
}
}