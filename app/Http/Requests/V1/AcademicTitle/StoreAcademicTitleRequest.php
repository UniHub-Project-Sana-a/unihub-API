<?php

namespace App\Http\Requests\V1\AcademicTitle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicTitleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title_name' => ['required','string','max:100'],
            'title_code' => [
                'required','string','max:50',
                // فريد داخل نفس الكلية مع تجاهل المحذوفين
                Rule::unique('academic_titles', 'title_code')
                    ->where(fn($q) => $q->where('college_id', $this->college_id)->whereNull('deleted_at')),
            ],
            'hourly_price'  => ['nullable','numeric','min:0'],
            'college_id'    => ['required','integer','exists:colleges,college_id'],
        ];
    }
}