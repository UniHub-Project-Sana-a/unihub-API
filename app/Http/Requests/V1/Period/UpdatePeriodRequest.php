<?php

namespace App\Http\Requests\V1\Period;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Period;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $period = $this->route('period'); // قد يكون ID أو Model
        $periodId   = $period instanceof Period ? $period->period_id : $period;
        $collegeId  = $this->input('college_id', $period instanceof Period ? $period->college_id : null);

        return [
            'college_id'  => ['sometimes','integer','exists:colleges,college_id'],
            'period_name' => [
                'sometimes','string','max:50',
                Rule::unique('periods','period_name')
                    ->ignore($periodId, 'period_id')
                    ->where(fn($q) => $q->where('college_id',$collegeId)->whereNull('deleted_at')),
            ],
            'start_time'  => ['sometimes','date_format:H:i'],
            'end_time'    => ['sometimes','date_format:H:i','after:start_time'],
            'session_type'=> ['sometimes','string','max:10'],
        ];
    }
}