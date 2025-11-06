<?php

namespace App\Services;

use App\Models\TimetableSet;
use Illuminate\Support\Facades\DB;

class ScheduleResolver
{
    // يحاول إيجاد جدول منشور أساسي لنفس (الكلية + القسم اختياري + الترم). إن لم يجد، ينشئ واحداً.
    public function resolveOrCreate(array $ctx): TimetableSet
    {
        $collegeId    = (int)($ctx['college_id'] ?? 0);
        $departmentId = $ctx['department_id'] ?? null;
        $semesterId   = $ctx['semester_id'] ?? null;

        // إن لم يصل semester_id نحاول استنتاجه من course_id أو group_id
        if (!$semesterId && !empty($ctx['course_id'])) {
            $semesterId = DB::table('courses')->where('course_id', $ctx['course_id'])->value('semester_id');
        }
        if (!$semesterId && !empty($ctx['group_id'])) {
            $semesterId = DB::table('student_groups')->where('group_id', $ctx['group_id'])->value('semester_id');
        }
        if (!$semesterId) {
            throw new \InvalidArgumentException('semester_id غير معروف. أرسل semester_id أو course_id/group_id.');
        }

        $set = TimetableSet::query()
            ->where('college_id', $collegeId)
            ->where('semester_id', $semesterId)
            ->where(function($q) use ($departmentId) {
                if ($departmentId === null) $q->whereNull('department_id');
                else $q->where('department_id', $departmentId);
            })
            ->where('is_primary', true)
            ->where('status', 'published')
            ->orderByDesc('schedule_id')
            ->first();

        if ($set) return $set;

        // إنشاء جدول جديد منشور وأساسي
        $sem = DB::table('semesters')->select('academic_year','term_number')->where('semester_id', $semesterId)->first();
        $name = sprintf('جدول التدريس %s - الترم %s', $sem->academic_year ?? 'NA', $sem->term_number ?? '?');

        // يمكن استخدام تواريخ من الطلب وإلا افتراض 12 أسبوع من اليوم
        $start = $ctx['start_date'] ?? now()->toDateString();
        $end   = $ctx['end_date']   ?? now()->addWeeks(12)->toDateString();

        // تأكد أن لا يوجد أكثر من primary لنفس النطاق
        TimetableSet::where('college_id', $collegeId)
            ->where('semester_id', $semesterId)
            ->when($departmentId !== null, fn($q) => $q->where('department_id', $departmentId))
            ->update(['is_primary' => false]);

        return TimetableSet::create([
            'college_id' => $collegeId,
            'semester_id'=> (int)$semesterId,
            'department_id' => $departmentId,
            'name' => $name,
            'start_date' => $start,
            'end_date' => $end,
            'weeks_count' => 12,
            'status' => 'published',
            'is_primary' => true,
            'notes' => 'Auto-created by resolver',
        ]);
    }
}