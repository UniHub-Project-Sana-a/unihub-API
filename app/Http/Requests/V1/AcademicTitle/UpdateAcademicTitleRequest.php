<?php

namespace App\Http\Requests\V1\AcademicTitle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AcademicTitle;

class UpdateAcademicTitleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $routeParam = $this->route('academic_title'); // قد يكون Model أو ID
        $title = $routeParam instanceof AcademicTitle ? $routeParam : AcademicTitle::find($routeParam);
        $titleId = $title?->title_id;
        $collegeId = $this->input('college_id', $title?->college_id);

        return [
            'title_name' => ['sometimes','string','max:100'],
            'title_code' => [
                'sometimes','string','max:50',
                Rule::unique('academic_titles', 'title_code')
                    ->ignore($titleId, 'title_id')
                    ->where(fn($q) => $q->where('college_id', $collegeId)->whereNull('deleted_at')),
            ],
            'hourly_price'  => ['nullable','numeric','min:0'],
            'lecture_price' => ['nullable','numeric','min:0'],
            'college_id'    => ['sometimes','integer','exists:colleges,college_id'],
        ];
    }
}