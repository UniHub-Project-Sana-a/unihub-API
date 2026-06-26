<?php

namespace App\Http\Requests\V1\Course;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->course_id : $course;

        // ✅ الحصول على البرنامج لمعرفة نوع النظام
        $program = null;
        if ($this->has('program_id')) {
            $program = \App\Models\Program::find($this->program_id);
        } elseif ($course instanceof Course) {
            $program = $course->program;
        }

        $rules = [
            'course_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')->ignore($courseId, 'course_id')
            ],
            'course_name' => ['sometimes', 'string', 'max:150'],
            'credit_hours' => ['sometimes', 'integer', 'min:1'],
            
            'college_id' => ['sometimes', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,department_id'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,program_id'],
            
            'course_parts' => ['sometimes', 'array'],
            'course_parts.*.name' => ['required_with:course_parts', 'string', Rule::in(['نظري', 'عملي', 'تمارين', 'سريري'])],
            'course_parts.*.actual_hours' => ['required_with:course_parts', 'numeric', 'min:0'],
            'course_parts.*.rate' => ['required_with:course_parts', 'numeric', 'min:0', 'max:1'],
            
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'category' => [
                'sometimes',
                Rule::in([
                    'متطلب جامعة',
                    'متطلب كلية',
                    'متطلب تخصص إجباري',
                    'متطلب تخصص اختياري'
                ])
            ],
            'teaching_language' => [
                'sometimes',
                Rule::in(['العربية', 'الإنجليزية', 'ثنائي اللغة'])
            ],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'course_type' => ['sometimes', 'integer'],
            
            'prerequisites' => ['sometimes', 'array'],
            'prerequisites.*' => ['integer', 'exists:courses,course_id'],
            'corequisites' => ['sometimes', 'array'],
            'corequisites.*' => ['integer', 'exists:courses,course_id'],
        ];

        // ✅ التحقق حسب نوع النظام الأكاديمي
        if ($program) {
            if ($program->academic_system === 'semester') {
                if ($program->block_based) {
                    // ✅ نظام الفصول + بلوكات
                    $rules['level_id'] = ['sometimes', 'integer', 'exists:levels,level_id'];
                    $rules['block_id'] = ['sometimes', 'integer', 'exists:blocks,id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                } else {
                    // ✅ نظام الفصول فقط
                    $rules['level_id'] = ['sometimes', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['sometimes', 'integer', 'exists:semesters,semester_id'];
                    $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
                }
            } else {
                // نظام الساعات المعتمدة
                if ($program->block_based) {
                    // ✅ ساعات + بلوكات
                    $rules['block_id'] = ['sometimes', 'integer', 'exists:blocks,id'];
                    $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                } else {
                    // ✅ ساعات فقط - لا نحتاج أي منها
                    $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
                    $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
                    $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
                }
            }
        } else {
            // حالة آمنة: إذا لم نتمكن من الحصول على البرنامج، نسمح بـ null
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
            'credit_hours.required' => 'الساعات المعتمدة مطلوبة',
            'credit_hours.integer' => 'الساعات المعتمدة يجب أن تكون رقماً صحيحاً',
            'credit_hours.min' => 'الساعات المعتمدة يجب أن تكون على الأقل 1',
            'level_id.required' => 'المستوى مطلوب لهذا النظام',
            'semester_id.required' => 'الفصل الدراسي مطلوب لهذا النظام',
            'block_id.required' => 'البلوك مطلوب لهذا النظام',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // ✅ التحقق من البرنامج والنظام
            $program = null;
            if ($this->has('program_id')) {
                $program = \App\Models\Program::find($this->program_id);
            } elseif ($course = $this->route('course')) {
                $program = $course->program;
            }
            
            if ($program) {
                if ($program->academic_system === 'semester') {
                    if ($program->block_based) {
                        if ($this->has('level_id') && !$this->level_id) {
                            $validator->errors()->add('level_id', 'المستوى مطلوب');
                        }
                        if ($this->has('block_id') && !$this->block_id) {
                            $validator->errors()->add('block_id', 'البلوك مطلوب');
                        }
                    } else {
                        if ($this->has('level_id') && !$this->level_id) {
                            $validator->errors()->add('level_id', 'المستوى مطلوب');
                        }
                        if ($this->has('semester_id') && !$this->semester_id) {
                            $validator->errors()->add('semester_id', 'الفصل الدراسي مطلوب');
                        }
                    }
                } else {
                    if ($program->block_based) {
                        if ($this->has('block_id') && !$this->block_id) {
                            $validator->errors()->add('block_id', 'البلوك مطلوب');
                        }
                    }
                }
            }
    
            // ✅ التحقق من توازن الساعات (مع تسامح أكبر)
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
                    $validator->errors()->add(
                        'credit_hours',
                        "الساعات المعتمدة المدخلة ({$creditHours}) لا تطابق المحسوبة من الأجزاء ({$calculated})"
                    );
                }
            }
    
            // ✅ التحقق من المتطلبات
            if ($this->has('prerequisites') && $this->prerequisites) {
                $course = $this->route('course');
                $courseId = $course instanceof \App\Models\Course ? $course->course_id : $course;
                
                if (in_array($courseId, $this->prerequisites)) {
                    $validator->errors()->add('prerequisites', 'لا يمكن أن يكون المقرر متطلباً سابقاً لنفسه');
                }
            }
    
            if ($this->has('corequisites') && $this->corequisites) {
                $course = $this->route('course');
                $courseId = $course instanceof \App\Models\Course ? $course->course_id : $course;
                
                if (in_array($courseId, $this->corequisites)) {
                    $validator->errors()->add('corequisites', 'لا يمكن أن يكون المقرر متطلباً مصاحباً لنفسه');
                }
            }
        });
    }
}