<?php

namespace App\Http\Requests\V1\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        // ✅ الحصول على البرنامج
        $program = null;
        if ($this->has('program_id')) {
            $program = \App\Models\Program::find($this->program_id);
        }

        $rules = [
            'course_code' => [
                'required',
                'string',
                'max:50',
                'unique:courses,course_code'
            ],
            'course_name' => ['required', 'string', 'max:150'],
            'credit_hours' => ['required', 'integer', 'min:1'],
            
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['required', 'integer', 'exists:departments,department_id'],
            'program_id' => ['required', 'integer', 'exists:programs,program_id'],
            
            'course_parts' => ['sometimes', 'array'],
            'course_parts.*.name' => ['required_with:course_parts', 'string', Rule::in(['نظري', 'عملي', 'تمارين', 'سريري'])],
            'course_parts.*.actual_hours' => ['required_with:course_parts', 'numeric', 'min:0'],
            'course_parts.*.rate' => ['required_with:course_parts', 'numeric', 'min:0', 'max:1'],
            
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'category' => [
                'required',
                Rule::in([
                    'متطلب جامعة',
                    'متطلب كلية',
                    'متطلب تخصص إجباري',
                    'متطلب تخصص اختياري'
                ])
            ],
            'teaching_language' => [
                'required',
                Rule::in(['العربية', 'الإنجليزية', 'ثنائي اللغة'])
            ],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'course_type' => ['sometimes', 'integer'],
            
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'exists:courses,course_id'],
            'corequisites' => ['nullable', 'array'],
            'corequisites.*' => ['integer', 'exists:courses,course_id'],
        ];

        // ✅ التحقق حسب نوع النظام
        if ($program) {
            if ($program->academic_system === 'semester') {
                if ($program->block_based) {
                    $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
                    $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                } else {
                    $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
                    $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
                }
            } else {
                if ($program->block_based) {
                    $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
                    $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                } else {
                    $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                    $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
                }
            }
        } else {
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'course_code.unique' => 'رمز المقرر مستخدم مسبقاً',
            'course_code.required' => 'رمز المقرر مطلوب',
            'course_name.required' => 'اسم المقرر مطلوب',
            'credit_hours.required' => 'الساعات المعتمدة مطلوبة',
            'category.required' => 'نوع المتطلب مطلوب',
            'teaching_language.required' => 'لغة التدريس مطلوبة',
            'level_id.required' => 'المستوى مطلوب لهذا النظام',
            'semester_id.required' => 'الفصل الدراسي مطلوب لهذا النظام',
            'block_id.required' => 'البلوك مطلوب لهذا النظام',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $program = \App\Models\Program::find($this->program_id);
            
            
            // ✅ نظام الساعات + بلوكات
            if ($program->academic_system === 'credit' && $program->block_based) {
                if (!$this->block_id) {
                    $validator->errors()->add('block_id', 'البلوك مطلوب');
                    return;
                }
                
                // ✅ جلب البلوك والمقررات الموجودة
                $block = \App\Models\Block::find($this->block_id);
                if (!$block) {
                    $validator->errors()->add('block_id', 'البلوك غير موجود');
                    return;
                }
                
                // ✅ جلب مجموع ساعات المقررات الموجودة في البلوك
                $currentCourseHours = \App\Models\Course::where('block_id', $this->block_id)
                    ->where('course_id', '!=', $this->route('course')?->course_id ?? null)
                    ->sum('credit_hours');
                
                $newTotal = $currentCourseHours + (int)$this->credit_hours;
                $blockCapacity = $block->credit_hours ?? 0;
                Log::info("📦 فحص ساعات البلوك", [
                    'block_name' => $block->block_name,
                    'block_capacity' => $blockCapacity,
                    'current_hours' => $currentCourseHours,
                    'new_course_hours' => $this->credit_hours,
                    'total_after_add' => $newTotal
                ]);
                
                // ✅ منع الإضافة إذا تجاوزت ساعات البلوك
                if ($newTotal > $blockCapacity) {
                    $remainingHours = $blockCapacity - $currentCourseHours;
                    $validator->errors()->add(
                        'credit_hours',
                        "إضافة {$this->credit_hours} ساعة ستتجاوز حد البلوك '{$block->block_name}'. "
                        . "الساعات المستخدمة: {$currentCourseHours}، السعة: {$blockCapacity}. "
                        . "المتبقي فقط: {$remainingHours} ساعة."
                    );
                }
            }
            // ✅ نظام الساعات العادي
            else if ($program->academic_system === 'credit' && !$program->block_based) {
                $currentTotal = \App\Models\Course::where('program_id', $program->program_id)
                    ->sum('credit_hours');
                
                $newTotal = $currentTotal + (int)$this->credit_hours;
                $maxHours = $program->total_hours ?? 0;
                
                if ($newTotal > $maxHours) {
                    $validator->errors()->add(
                        'credit_hours',
                        "إضافة {$this->credit_hours} ساعة ستتجاوز الحد الأقصى. "
                        . "الساعات المضافة: {$currentTotal}، المسموح: {$maxHours}. "
                        . "يرجى تعديل ساعات البرنامج قبل الإضافة."
                    );
                }
            }
            // الأنظمة الأخرى
            else if ($program->academic_system === 'semester') {
                if ($program->block_based) {
                    if (!$this->level_id) {
                        $validator->errors()->add('level_id', 'المستوى مطلوب');
                    }
                    if (!$this->block_id) {
                        $validator->errors()->add('block_id', 'البلوك مطلوب');
                    }
                } else {
                    if (!$this->level_id) {
                        $validator->errors()->add('level_id', 'المستوى مطلوب');
                    }
                    if (!$this->semester_id) {
                        $validator->errors()->add('semester_id', 'الفصل الدراسي مطلوب');
                    }
                }
            }
            
    
            // ✅ التحقق من توازن الساعات
            if ($this->has('course_parts') && $this->course_parts && count($this->course_parts) > 0) {
                $calculated = 0;
                foreach ($this->course_parts as $part) {
                    $actual_hours = (float)($part['actual_hours'] ?? 0);
                    $rate = (float)($part['rate'] ?? 1);
                    $calculated += round($actual_hours * $rate * 100) / 100;
                }
                $calculated = round($calculated);
                $creditHours = (int)$this->credit_hours;
                
                if (abs($calculated - $creditHours) > 0.01) {
                    Log::warning("Course parts mismatch", [
                        'course_name' => $this->course_name,
                        'calculated' => $calculated,
                        'stated' => $creditHours
                    ]);
                }
            }

            // ✅ مجموع أوزان المقررات لا يتجاوز مجموع أوزان مخرجات البرنامج المضافة
            if ($program) {
                $programWeight = (float) \App\Models\ProgramLearningOutcome::where('program_id', $program->program_id)
                    ->sum('weight');
                $currentCourseWeight = (float) \App\Models\Course::where('program_id', $program->program_id)
                    ->sum('weight');
                $remainingWeight = max(0, $programWeight - $currentCourseWeight);

                if ((float) ($this->weight ?? 0) > $remainingWeight) {
                    $validator->errors()->add(
                        'weight',
                        "وزن المقرر يتجاوز الرصيد المتاح. أوزان مخرجات البرنامج: {$programWeight}%. المتبقي للمقررات: {$remainingWeight}%."
                    );
                }
            }
        });
    }
}