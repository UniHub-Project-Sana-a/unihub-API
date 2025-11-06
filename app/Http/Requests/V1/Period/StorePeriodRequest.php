<?php

namespace App\Http\Requests\V1\Period;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'college_id'  => ['required','integer','exists:colleges,college_id'],
            'period_name' => [
                'required','string','max:50',
                Rule::unique('periods','period_name')->where(fn($q) => $q->where('college_id', $this->college_id)->whereNull('deleted_at')),
            ],
            'start_time'  => ['required','date_format:H:i'],
            'end_time'    => ['required','date_format:H:i','after:start_time'],
            'session_type'=> ['required','string','max:10'], // مثال: LECTURE/LAB/SEMINAR
        ];
    }
}