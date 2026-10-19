<?php

namespace App\Http\Requests\V1\Period;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Models\Period;

class UpdatePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'college_id'  => ['sometimes','integer','exists:colleges,college_id'],
            'period_name' => ['sometimes','string','max:50'],
            'start_time'  => ['sometimes','date_format:H:i'],
            'end_time'    => ['sometimes','date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('start_time') || $validator->errors()->has('end_time')) {
                return;
            }

            $period = $this->route('period');
            $start = $this->input('start_time', $period instanceof Period ? substr($period->start_time, 0, 5) : null);
            $end = $this->input('end_time', $period instanceof Period ? substr($period->end_time, 0, 5) : null);

            if (is_string($start) && is_string($end) && $start >= $end) {
                $validator->errors()->add('end_time', 'يجب أن يكون وقت النهاية بعد وقت البداية.');
            }
        });
    }
}