<?php
namespace App\Http\Requests\V1\Program;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Program;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool { return true; }

public function rules(): array {
  $routeParam = $this->route('program');
  $programId = $routeParam instanceof Program ? $routeParam->program_id : $routeParam;
  $deptId = $routeParam instanceof Program ? $routeParam->department_id : $this->input('department_id');

  return [
    'program_name' => [
      'sometimes','string','max:50',
      Rule::unique('programs','program_name')
        ->ignore($programId,'program_id')
        ->where(fn($q) => $q->whereNull('deleted_at')->where('department_id', $deptId)),
    ],
    'is_active' => ['sometimes','boolean'],
  ];
}
}