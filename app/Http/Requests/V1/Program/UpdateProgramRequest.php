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
  
  // جلب القيم الحالية لضمان دقة التحقق الفريد أثناء التحديث
  $deptId = $this->input('department_id') ?? ($routeParam instanceof Program ? $routeParam->department_id : null);
  $system = $this->input('academic_system') ?? ($routeParam instanceof Program ? $routeParam->academic_system : null);
  $isBlock = $this->input('block_based') ?? ($routeParam instanceof Program ? $routeParam->block_based : null);

  return [
    'program_name' => [
      'sometimes', 'string', 'max:50',
      Rule::unique('programs', 'program_name')
        ->ignore($programId, 'program_id')
        ->where(fn($q) => $q->whereNull('deleted_at')
            ->where('department_id', $deptId)
            ->where('academic_system', $system)
            ->where('block_based', $isBlock)
        ),
    ],
    'academic_system' => ['sometimes', Rule::in(['semester', 'credit'])],
    'block_based'     => ['sometimes', 'boolean'],
    'total_hours'     => ['nullable', 'integer', 'min:0'], // الحقل الجديد
    'is_active'       => ['sometimes', 'boolean'],
  ];
}
}