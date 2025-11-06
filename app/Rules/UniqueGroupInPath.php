<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UniqueGroupInPath implements Rule
{
    private $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function passes($attribute, $value)
    {
        // بناء الاستعلام يدويًا لتجنب أي مشاكل في Rule::unique
        $exists = DB::table('student_groups')
            ->where('college_id', $this->request->input('college_id'))
            ->where('department_id', $this->request->input('department_id'))
            ->where('level_id', $this->request->input('level_id'))
            ->where('semester_id', $this->request->input('semester_id'))
            ->where('group_name', $value)
            ->whereNull('deleted_at')
            ->exists();

        return !$exists;
    }

    public function message()
    {
        return 'اسم المجموعة موجود بالفعل في هذا المسار الدراسي.';
    }
}