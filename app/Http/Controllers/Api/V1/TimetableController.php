<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
// --- ⬇️ أضف هذه الأسطر ⬇️ ---
use App\Models\LectureSession;
use App\Models\Period;
use App\Models\StudentGroup;
use Illuminate\Support\Str;
use App\Models\CourseTopic;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
// --- ⬆️ نهاية الإضافة ⬆️ ---

class TimetableController extends Controller
{
    private function resolveProgramPathRules(?Program $program): array
    {
        $rules = [
            'program_id' => ['nullable', 'integer', 'exists:programs,program_id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,level_id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,semester_id'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
        ];

        if (!$program) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
            return $rules;
        }

        if ($program->academic_system === 'semester' && $program->block_based) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            return $rules;
        }

        if ($program->academic_system === 'semester') {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
            return $rules;
        }

        if ($program->academic_system === 'credit' && $program->block_based) {
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            return $rules;
        }

        $rules['program_id'] = ['required', 'integer', 'exists:programs,program_id'];
        $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
        $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
        $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];

        return $rules;
    }

    private function normalizeProgramPath(array $data): array
    {
        $programId = $data['program_id'] ?? null;
        if (!$programId) {
            return $data;
        }

        $program = Program::find($programId);
        if (!$program) {
            return $data;
        }

        if ($program->academic_system === 'semester' && $program->block_based) {
            $data['semester_id'] = null;
            return array_filter($data, fn($value) => $value !== null && $value !== '');
        }

        if ($program->academic_system === 'semester') {
            $data['block_id'] = null;
            return array_filter($data, fn($value) => $value !== null && $value !== '');
        }

        if ($program->academic_system === 'credit' && $program->block_based) {
            $data['level_id'] = null;
            $data['semester_id'] = null;
            return array_filter($data, fn($value) => $value !== null && $value !== '');
        }

        $data['level_id'] = null;
        $data['semester_id'] = null;
        $data['block_id'] = null;

        return array_filter($data, fn($value) => $value !== null && $value !== '');
    }

    /**
     * Display a listing of the resource.
     * يدعم الفلترة والتحميل المسبق للعلاقات.
     * e.g., /api/v1/timetable?college_id=1&department_id=2&with=course,lecturer.user
     */
    public function index(Request $request)
    {
        $query = Timetable::query();

        // تطبيق الفلاتر بناءً على الطلب
        if ($request->has('college_id')) {
            $query->where('college_id', $request->college_id);
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->has('level_id')) {
            $query->where('level_id', $request->level_id);
        }
        if ($request->has('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        if ($request->has('block_id')) {
            $query->where('block_id', $request->block_id);
        }

        // ✅ --- التعديل هنا: إضافة فلتر المحاضر --- ✅
        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }
        // ✅ --- نهاية التعديل --- ✅

        // التحميل المسبق للعلاقات (Eager Loading)
        if ($request->has('with')) {
            try {
                $relations = explode(',', $request->with);
                $query->with($relations);
            } catch (\Exception $e) {
                // تجاهل في حال وجود خطأ في أسماء العلاقات
            }
        }

        // الآن سيتم جلب السجلات المفلترة فقط
        $timetableEntries = $query->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $timetableEntries
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
        /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)  
    {
        $program = $request->filled('program_id') ? Program::find($request->program_id) : null;

        $rules = [
            'course_id'     => 'required|integer|exists:courses,course_id',
            'lecturer_id'   => 'required|integer|exists:lecturers,lecturer_id',
            'group_id'      => 'nullable|integer|exists:student_groups,group_id',
            'group_ids'     => 'nullable|array',
            'group_ids.*'   => 'integer|exists:student_groups,group_id',
            'classroom_id'  => 'required|integer|exists:classrooms,classroom_id',
            'day_id'        => 'required|integer|exists:days,day_id',
            'period_id'     => 'required|integer|exists:periods,period_id',
            'lecture_type'  => 'required|integer|in:0,1,2',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'academic_year' => 'required|string|max:20',
            'college_id'    => 'required|integer|exists:colleges,college_id',
            'department_id' => 'required|integer|exists:departments,department_id',
            'lecture_hours' => 'required|numeric|min:0',
            'gender_type'   => 'required|integer|in:0,1,2',
            'status'        => 'required|integer|in:0,1',
            'allowance_minutes' => 'required|integer|min:0|max:60',
        ];

        $rules = array_merge($rules, $this->resolveProgramPathRules($program));

        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'خطأ في التحقق', 'errors' => $validator->errors()], 422);
        }
        
        $validatedData = $this->normalizeProgramPath($validator->validated());

        // استخراج جميع المجموعات المستهدفة (سواء تم إرسال group_id فردي أو مصفوفة group_ids لمجموعات متعددة)
        $targetGroupIds = [];
        if (!empty($validatedData['group_ids']) && is_array($validatedData['group_ids'])) {
            $targetGroupIds = array_values(array_unique(array_map('intval', $validatedData['group_ids'])));
        } elseif (!empty($validatedData['group_id'])) {
            $targetGroupIds = [(int)$validatedData['group_id']];
        }

        if (empty($targetGroupIds)) {
            return response()->json([
                'status' => false,
                'message' => 'يجب تحديد مجموعة طلابية واحدة على الأقل.'
            ], 422);
        }

        unset($validatedData['group_ids']);

        // 2. التحقق من التعارضات لكل مجموعة ولكل من القاعة والمحاضر
        $allConflicts = [];
        foreach ($targetGroupIds as $gId) {
            $checkData = array_merge($validatedData, ['group_id' => $gId]);
            $conflicts = $this->checkForConflicts($request->day_id, $request->period_id, $checkData);
            if (!empty($conflicts)) {
                $allConflicts = array_merge($allConflicts, $conflicts);
            }
        }
        
        if (!empty($allConflicts)) {
            $hasTrashConflict = collect($allConflicts)->contains('in_trash', true);
            return response()->json([
                'status' => false,
                'has_trash_conflict' => $hasTrashConflict,
                'message' => $hasTrashConflict
                    ? 'يوجد تعارض مع محاضرة في سلة المحذوفات. يرجى الذهاب إلى سلة المحذوفات وتأكيد الحذف النهائي أولاً لإتاحة القاعة.'
                    : 'يوجد تعارض في الجدول.',
                'conflicts' => $allConflicts
            ], 409);
        }
        
        // 3. إنشاء السجلات لكل مجموعة وإنشاء الجلسات المرتبطة
        try {
            DB::beginTransaction();

            $createdEntries = [];
            $period = Period::find($validatedData['period_id']);

            foreach ($targetGroupIds as $gId) {
                $entryData = array_merge($validatedData, ['group_id' => $gId]);
                $timetableEntry = Timetable::create($entryData);
                $createdEntries[] = $timetableEntry;

                // إنشاء جلسة تلقائية إذا كان تاريخ البدء هو نفسه تاريخ الانتهاء
                if ($validatedData['start_date'] === $validatedData['end_date']) {
                    // رمز جلسة فريد لكل مجموعة لتجنب خرق القيد الفريد session_code
                    $groupSessionCode = 'SESS-' . Str::random(8) . '-' . time() . '-' . $gId;
                    LectureSession::create([
                        'timetable_id' => $timetableEntry->timetable_id,
                        'lecturer_id' => $timetableEntry->lecturer_id,
                        'session_date' => $timetableEntry->start_date,
                        'start_time' => $period ? $period->start_time : '08:00:00',
                        'end_time' => $period ? $period->end_time : '10:00:00',
                        'actual_classroom_id' => $timetableEntry->classroom_id,
                        'session_code' => $groupSessionCode,
                        'status' => 0,
                        'is_makeup' => 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => count($createdEntries) > 1
                    ? 'تم إنشاء الجدول لمحاضرة مشتركة تشمل (' . count($createdEntries) . ') مجموعات بنجاح.'
                    : 'تم إنشاء الجدول بنجاح.',
                'data' => $createdEntries[0],
                'entries' => $createdEntries
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'فشل إنشاء الجدول: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Timetable $timetable)
    {
        // تحميل العلاقات لعرض كافة التفاصيل
        $timetable->load('course', 'lecturer.user', 'group', 'classroom', 'day', 'period', 'college', 'department', 'level');
        
        return response()->json([
            'status' => true,
            'data' => $timetable
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timetable $timetable)
    {
        // 1. التحقق من صحة البيانات (استخدام 'sometimes' للسماح بالتحديث الجزئي)
        $program = $request->filled('program_id') ? Program::find($request->program_id) : $timetable->program;

        $rules = [
            'course_id'     => 'sometimes|required|integer|exists:courses,course_id',
            'lecturer_id'   => 'sometimes|required|integer|exists:lecturers,lecturer_id',
            'group_id'      => 'sometimes|required|integer|exists:student_groups,group_id',
            'classroom_id'  => 'sometimes|required|integer|exists:classrooms,classroom_id',
            'day_id'        => 'sometimes|required|integer|exists:days,day_id',
            'period_id'     => 'sometimes|required|integer|exists:periods,period_id',
            'lecture_type'  => 'sometimes|required|integer|in:0,1,2',
            'status'        => 'sometimes|required|integer|in:0,1',
            'start_date'    => 'sometimes|required|date',
            'end_date'      => 'sometimes|required|date|after_or_equal:start_date',
            'academic_year' => 'sometimes|required|string|max:20',
            'college_id'    => 'sometimes|required|integer|exists:colleges,college_id',
            'department_id' => 'sometimes|required|integer|exists:departments,department_id',
            'gender_type'   => 'sometimes|required|integer|in:0,1,2',
            'lecture_hours' => 'sometimes|required|numeric|min:0',
        ];

        $rules = array_merge($rules, $this->resolveProgramPathRules($program));

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'خطأ في التحقق', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $this->normalizeProgramPath($validator->validated());
        
        // 2. التحقق من التعارضات، مع استثناء السجل الحالي
        $day_id = $validatedData['day_id'] ?? $timetable->day_id;
        $period_id = $validatedData['period_id'] ?? $timetable->period_id;
        
        $conflicts = $this->checkForConflicts($day_id, $period_id, $validatedData, $timetable->timetable_id);
        if (!empty($conflicts)) {
            $hasTrashConflict = collect($conflicts)->contains('in_trash', true);
            return response()->json([
                'status' => false,
                'has_trash_conflict' => $hasTrashConflict,
                'message' => $hasTrashConflict
                    ? 'يوجد تعارض مع محاضرة في سلة المحذوفات عند التحديث. يرجى مراجعة سلة المحذوفات أولاً.'
                    : 'تم اكتشاف تعارض عند التحديث.',
                'conflicts' => $conflicts
            ], 409);
        }

        // 3. تحديث السجل
        $timetable->update($validatedData);

        // ✅ مزامنة الجلسات المرتبطة بهذا الجدول لتحديث القاعة أو المحاضر أو التوقيت
        $sessionUpdates = [];
        if (isset($validatedData['classroom_id'])) {
            $sessionUpdates['actual_classroom_id'] = $validatedData['classroom_id'];
        }
        if (isset($validatedData['lecturer_id'])) {
            $sessionUpdates['lecturer_id'] = $validatedData['lecturer_id'];
        }
        if (isset($validatedData['period_id'])) {
            $period = Period::find($validatedData['period_id']);
            if ($period) {
                $sessionUpdates['start_time'] = $period->start_time;
                $sessionUpdates['end_time'] = $period->end_time;
            }
        }
        if (!empty($sessionUpdates)) {
            LectureSession::where('timetable_id', $timetable->timetable_id)
                ->where('status', 0)
                ->update($sessionUpdates);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بند الجدول بنجاح.',
            'data' => $timetable->fresh(['course', 'lecturer.user', 'group', 'classroom', 'day', 'period'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timetable $timetable)
    {
        try {
            DB::transaction(function () use ($timetable) {
                // نقل الجلسات المرتبطة بهذا البند أيضاً إلى سلة المحذوفات
                LectureSession::where('timetable_id', $timetable->timetable_id)->delete();
                $timetable->delete();
            });
            return response()->json(['status' => true, 'message' => 'تم نقل المحاضرة وجلساتها إلى سلة المحذوفات بنجاح.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل حذف البند.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دالة مساعدة للتحقق من التعارضات.
     * @param int $day_id
     * @param int $period_id
     * @param array $data - البيانات القادمة من الطلب.
     * @param int|null $ignoreId - معرف السجل الذي يجب تجاهله (يستخدم في التحديث).
     * @return array
     */
    private function checkForConflicts(int $day_id, int $period_id, array $data, ?int $ignoreId = null): array
    {
        $conflicts = [];
    
        // 1. التأكد من وجود التواريخ لأنها جوهر الفحص
        if (!isset($data['start_date']) || !isset($data['end_date'])) {
            return [];
        }
    
        $newStart = $data['start_date'];
        $newEnd   = $data['end_date'];
    
        // دالة مساعدة (Closure) لتطبيق شرط تداخل التواريخ
        // المنطق: (بداية الجديد <= نهاية القديم) AND (نهاية الجديد >= بداية القديم)
        $dateScope = function ($query) use ($newStart, $newEnd) {
            $query->where(function ($q) use ($newStart, $newEnd) {
                $q->where('start_date', '<=', $newEnd)
                  ->where('end_date', '>=', $newStart);
            });
        };
    
        // 2. تجهيز العلاقات لجلب أسماء المواد والمحاضرين لعرض تفاصيل الخطأ
        $relations = ['course', 'lecturer.user', 'studentGroup', 'classroom'];
    
        // --- أ) التحقق من تعارض القاعة (Classroom Conflict) ---
        // يشمل السجلات في سلة المحذوفات للتنبيه بضرورة تأكيد الحذف النهائي أو استرجاعها
        if (isset($data['classroom_id'])) {
            $clashes = Timetable::withTrashed()
                ->with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('classroom_id', $data['classroom_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $isTrashed = $clash->trashed();
                $classroomName = $clash->classroom->classroom_name ?? 'غير معروف';
                $courseName = $clash->course->course_name ?? 'مقرر';

                if ($isTrashed) {
                    $conflicts[] = [
                        'type' => 'classroom_trash',
                        'in_trash' => true,
                        'entity' => $classroomName,
                        'message' => "يوجد تعارض مع محاضرة في سلة المحذوفات لنفس القاعة ({$classroomName}) في نفس الوقت لمقرر ({$courseName}). يرجى الذهاب إلى سلة المحذوفات وتأكيد الحذف النهائي أولاً لإتاحة القاعة.",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                } else {
                    $conflicts[] = [
                        'type' => 'classroom',
                        'in_trash' => false,
                        'entity' => $classroomName,
                        'message' => "القاعة محجوزة لمادة ({$courseName}) من تاريخ {$clash->start_date} إلى {$clash->end_date}",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                }
            }
        }
    
        // --- ب) التحقق من تعارض المحاضر (Lecturer Conflict) ---
        if (isset($data['lecturer_id'])) {
            $clashes = Timetable::withTrashed()
                ->with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('lecturer_id', $data['lecturer_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $isTrashed = $clash->trashed();
                $lecturerName = $clash->lecturer->user->full_name ?? 'المحاضر';
                $courseName = $clash->course->course_name ?? 'مقرر';
                $classroomName = $clash->classroom->classroom_name ?? 'القاعة';

                if ($isTrashed) {
                    $conflicts[] = [
                        'type' => 'lecturer_trash',
                        'in_trash' => true,
                        'entity' => $lecturerName,
                        'message' => "المحاضر ({$lecturerName}) لديه محاضرة في سلة المحذوفات لمقرر ({$courseName}) في نفس الوقت. يرجى تأكيد الحذف النهائي من سلة المحذوفات أولاً.",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                } else {
                    $conflicts[] = [
                        'type' => 'lecturer',
                        'in_trash' => false,
                        'entity' => $lecturerName,
                        'message' => "المحاضر ({$lecturerName}) لديه محاضرة أخرى ({$courseName}) في القاعة ({$classroomName})",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                }
            }
        }
    
        // --- ج) التحقق من تعارض المجموعة الطلابية (Student Group Conflict) ---
        if (isset($data['group_id'])) {
            $clashes = Timetable::withTrashed()
                ->with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('group_id', $data['group_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $isTrashed = $clash->trashed();
                $groupName = $clash->studentGroup->group_name ?? 'المجموعة';
                $courseName = $clash->course->course_name ?? 'مقرر';

                if ($isTrashed) {
                    $conflicts[] = [
                        'type' => 'group_trash',
                        'in_trash' => true,
                        'entity' => $groupName,
                        'message' => "المجموعة ({$groupName}) لديها محاضرة في سلة المحذوفات لمقرر ({$courseName}) في نفس الوقت. يرجى تأكيد الحذف النهائي من سلة المحذوفات أولاً.",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                } else {
                    $conflicts[] = [
                        'type' => 'group',
                        'in_trash' => false,
                        'entity' => $groupName,
                        'message' => "المجموعة ({$groupName}) لديها محاضرة أخرى ({$courseName}) في نفس الوقت.",
                        'conflict_with_id' => $clash->timetable_id,
                        'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                    ];
                }
            }
        }
    
        return $conflicts;
    }

    public function getTopicsStatus($timetableId, Request $request)
    {
        $timetable = Timetable::with('course')->findOrFail($timetableId);
        $courseId = $timetable->course_id;

        $lectureTypeMap = [
            0 => 'نظري',
            1 => 'عملي',
            2 => 'تمارين',
            3 => 'سريري',
        ];

        $requestedPart = trim((string) ($request->input('part') ?? ''));
        $resolvedPart = $requestedPart !== '' ? $requestedPart : ($lectureTypeMap[(int) $timetable->lecture_type] ?? 'نظري');

        $normalizePart = function (?string $value): string {
            return strtolower(str_replace([' ', '\t', '\n', '\r'], '', trim((string) ($value ?? ''))));
        };

        $baseQuery = CourseTopic::where('course_id', $courseId)
            ->orderBy('week')
            ->orderBy('order')
            ->get([
                'topic_id',
                'part',
                'week',
                'unit_name',
                'subtopics',
                'is_exam',
                'exam_type',
            ]);

        $matchingTopics = $baseQuery->filter(function ($topic) use ($resolvedPart, $normalizePart) {
            if ($normalizePart($topic->part) === $normalizePart($resolvedPart)) {
                return true;
            }

            $partAliases = [
                'نظري' => ['نظري', 'نظريه', 'theory', 'theoretical'],
                'عملي' => ['عملي', 'practical', 'practice'],
                'تمارين' => ['تمارين', 'training', 'exercise', 'exercises'],
                'سريري' => ['سريري', 'clinical', 'clinic'],
            ];

            $expected = $partAliases[$resolvedPart] ?? [$resolvedPart];
            foreach ($expected as $alias) {
                if ($normalizePart($topic->part) === $normalizePart($alias)) {
                    return true;
                }
            }

            return false;
        });

        if ($matchingTopics->isEmpty() && $baseQuery->isNotEmpty()) {
            $matchingTopics = $baseQuery;
        }

        $coveredTopicIds = DB::table('session_topics_covered')
            ->join('lecture_sessions', 'session_topics_covered.session_id', '=', 'lecture_sessions.session_id')
            ->join('course_topics', 'session_topics_covered.topic_id', '=', 'course_topics.topic_id')
            ->where('lecture_sessions.timetable_id', $timetableId)
            ->pluck('session_topics_covered.topic_id')
            ->toArray();

        $result = $matchingTopics->map(function ($topic) use ($coveredTopicIds) {
            $subtopics = is_array($topic->subtopics)
                ? $topic->subtopics
                : (json_decode($topic->subtopics ?? '[]', true) ?: []);

            return [
                'topic_id' => $topic->topic_id,
                'title' => $topic->unit_name ?: 'وحدة بدون عنوان',
                'unit_name' => $topic->unit_name ?: 'وحدة بدون عنوان',
                'part' => $topic->part,
                'week' => $topic->week,
                'subtopics' => $subtopics,
                'is_exam' => (bool) $topic->is_exam,
                'exam_type' => $topic->exam_type,
                'is_covered' => in_array($topic->topic_id, $coveredTopicIds, true),
            ];
        });

        return response()->json([
            'data' => $result,
            'part' => $resolvedPart,
            'lecture_type' => (int) ($timetable->lecture_type ?? 0),
            'course_id' => $courseId,
        ]);
    }

    /**
     * سلة المحذوفات (Soft Delete) للمحاضرات والجداول.
     * POST /api/v1/timetable/trash
     */
    public function trash(Request $request)
    {
        // ضمان وجود أعمدة الحذف الناعم في قاعدة البيانات بشكل فوري وآمن
        try {
            if (Schema::hasTable('timetable') && !Schema::hasColumn('timetable', 'deleted_at')) {
                Schema::table('timetable', function ($table) {
                    $table->softDeletes();
                });
            }
            if (Schema::hasTable('lecture_sessions') && !Schema::hasColumn('lecture_sessions', 'deleted_at')) {
                Schema::table('lecture_sessions', function ($table) {
                    $table->softDeletes();
                });
            }
        } catch (\Throwable $e) {
            Log::warning('Ensure timetable softDeletes columns error: ' . $e->getMessage());
        }

        try {
            $validated = $request->validate([
                'action' => 'required|in:list,restore,force_delete',
                'college_id' => 'nullable|integer|min:1',
                'type' => 'required_if:action,restore,force_delete|in:timetable,lecture_sessions',
                'id' => 'required_if:action,restore,force_delete|integer|min:1',
            ]);

            $collegeId = $request->filled('college_id') ? (int) $request->college_id : null;

            $queries = [
                'timetable' => function () use ($collegeId) {
                    $q = Timetable::onlyTrashed()
                        ->with([
                            'course:course_id,course_name,course_code',
                            'lecturer.user:user_id,full_name',
                            'group:group_id,group_name',
                            'classroom:classroom_id,classroom_name',
                            'day:day_id,day_name',
                            'period:period_id,start_time,end_time'
                        ]);
                    if ($collegeId) {
                        $q->where('college_id', $collegeId);
                    }
                    return $q;
                },
                'lecture_sessions' => function () use ($collegeId) {
                    $q = LectureSession::onlyTrashed()
                        ->with([
                            'timetable' => fn($t) => $t->withTrashed()->with([
                                'course:course_id,course_name,course_code',
                                'group:group_id,group_name',
                                'lecturer.user:user_id,full_name'
                            ]),
                            'actualClassroom:classroom_id,classroom_name',
                            'actualLecturer.user:user_id,full_name'
                        ]);
                    if ($collegeId) {
                        $q->whereHas('timetable', fn($t) => $t->withTrashed()->where('college_id', $collegeId));
                    }
                    return $q;
                },
            ];

            if ($validated['action'] === 'list') {
                $labels = [
                    'timetable' => 'محاضرات الجدول الرئيسي',
                    'lecture_sessions' => 'جلسات المحاضرات المحذوفة',
                ];

                $groups = [];
                foreach ($queries as $type => $queryFactory) {
                    $records = [];
                    try {
                        $records = $queryFactory()->get()->map(function ($record) use ($type) {
                            $data = $record->getAttributes();
                            $deletedAt = null;
                            if ($record->deleted_at) {
                                try {
                                    $deletedAt = Carbon::parse($record->deleted_at)->toIso8601String();
                                } catch (\Throwable) {
                                    $deletedAt = (string) $record->deleted_at;
                                }
                            }
                            $data['deleted_at'] = $deletedAt;

                            $name = '';
                            if ($type === 'timetable') {
                                $courseName = $record->course?->course_name ?? 'مقرر';
                                $dayName = $record->day?->day_name ?? '';
                                $groupName = $record->group?->group_name ?? '';
                                $name = "{$courseName} - {$groupName}" . ($dayName ? " ({$dayName})" : "");
                            } else {
                                $courseName = $record->timetable?->course?->course_name ?? 'جلسة محاضرة';
                                $sessionDate = substr((string) ($record->session_date ?? ''), 0, 10);
                                $startTime = substr((string) ($record->start_time ?? ''), 0, 5);
                                $name = "{$courseName}" . ($sessionDate ? " (تاريخ: {$sessionDate} - {$startTime})" : "");
                            }

                            return [
                                'id' => $record->getKey(),
                                'name' => $name ?: ('#' . $record->getKey()),
                                'deleted_at' => $deletedAt,
                                'data' => $data,
                            ];
                        })->values();
                    } catch (\Throwable $qe) {
                        Log::warning("Error fetching trashed {$type}: " . $qe->getMessage());
                    }

                    $groups[] = [
                        'type' => $type,
                        'label' => $labels[$type],
                        'records' => $records,
                    ];
                }

                return response()->json(['groups' => $groups]);
            }

            $type = $validated['type'];
            $record = $queries[$type]()->whereKey((int) $validated['id'])->firstOrFail();

            DB::transaction(function () use ($record, $validated, $type) {
                if ($validated['action'] === 'restore') {
                    $record->restore();
                    if ($type === 'timetable') {
                        LectureSession::onlyTrashed()->where('timetable_id', $record->getKey())->restore();
                    }
                } else {
                    if ($type === 'timetable') {
                        LectureSession::withTrashed()->where('timetable_id', $record->getKey())->forceDelete();
                    }
                    $record->forceDelete();
                }
            });

            return response()->json([
                'message' => $validated['action'] === 'restore'
                    ? 'تمت استعادة المحاضرة بنجاح'
                    : 'تم تأكيد الحذف النهائي بنجاح',
            ]);
        } catch (\Throwable $e) {
            Log::error('Timetable trash error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            if ($request->input('action') === 'list') {
                return response()->json([
                    'groups' => [
                        ['type' => 'timetable', 'label' => 'محاضرات الجدول الرئيسي', 'records' => []],
                        ['type' => 'lecture_sessions', 'label' => 'جلسات المحاضرات المحذوفة', 'records' => []],
                    ],
                    'warning' => $e->getMessage(),
                ]);
            }
            return response()->json([
                'message' => 'تعذر تنفيذ العملية: ' . $e->getMessage(),
            ], 500);
        }
    }

}