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

        // فلاتر إضافية لعرض الجداول في الواجهة
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->integer('group_id'));
        }
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->integer('classroom_id'));
        }
        if ($request->filled('day_id')) {
            $query->where('day_id', $request->integer('day_id'));
        }
        if ($request->filled('period_id')) {
            $query->where('period_id', $request->integer('period_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->integer('status'));
        }

        // بحث نصي شامل (المقرر/المحاضر/المجموعة/القاعة/اليوم/العام الجامعي)
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('academic_year', 'like', $like)
                    ->orWhereHas('course', function ($courseQuery) use ($like) {
                        $courseQuery->where('course_name', 'like', $like)
                            ->orWhere('course_code', 'like', $like);
                    })
                    ->orWhereHas('lecturer.user', function ($userQuery) use ($like) {
                        $userQuery->where('full_name', 'like', $like);
                    })
                    ->orWhereHas('group', function ($groupQuery) use ($like) {
                        $groupQuery->where('group_name', 'like', $like);
                    })
                    ->orWhereHas('classroom', function ($classroomQuery) use ($like) {
                        $classroomQuery->where('classroom_name', 'like', $like);
                    })
                    ->orWhereHas('day', function ($dayQuery) use ($like) {
                        $dayQuery->where('day_name', 'like', $like);
                    });
            });
        }

        // التحميل المسبق للعلاقات (Eager Loading)
        if ($request->has('with')) {
            try {
                $relations = explode(',', $request->with);
                $query->with($relations);
            } catch (\Exception $e) {
                // تجاهل في حال وجود خطأ في أسماء العلاقات
            }
        }

        // دعم الترقيم (Pagination) عند طلبه فقط، للحفاظ على توافق الاستخدامات القديمة
        $perPage = (int) $request->input('per_page', 0);
        if ($perPage > 0) {
            $paginator = $query->latest()->paginate(min($perPage, 100));

            return response()->json([
                'status' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
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
            'group_id'      => 'required|integer|exists:student_groups,group_id',
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

        // 2. التحقق من التعارضات (باستخدام الدالة الجديدة الشاملة)
        // نمرر البيانات كاملة لأن الدالة الجديدة تحتاج التواريخ والقاعة والمحاضر
        $conflicts = $this->checkForConflicts($request->day_id, $request->period_id, $validatedData);
        
        if (!empty($conflicts)) {
            return response()->json([
                'status' => false,
                'message' => 'يوجد تعارض في الجدول.',
                'conflicts' => $conflicts // سيرسل التفاصيل الكاملة التي برمجناها
            ], 409);
        }
        
        // 3. إنشاء السجل وإنشاء الجلسة التلقائية (كما عدلناها سابقاً)
        try {
            $timetableEntry = Timetable::create($validatedData);
    
            // إنشاء جلسة تلقائية إذا كان تاريخ البدء هو نفسه تاريخ الانتهاء
            if ($validatedData['start_date'] === $validatedData['end_date']) {
                $period = Period::find($timetableEntry->period_id);
                $studentGroup = StudentGroup::withCount('students')->find($timetableEntry->group_id);
    
                LectureSession::create([
                    'timetable_id' => $timetableEntry->timetable_id,
                    'lecturer_id' => $timetableEntry->lecturer_id,
                    'session_date' => $timetableEntry->start_date,
                    'start_time' => $period->start_time,
                    'end_time' => $period->end_time,
                    'actual_classroom_id' => $timetableEntry->classroom_id,
                    'session_code' => 'SESS-' . Str::random(10) . '-' . time(),
                    'status' => 0,
                    'is_makeup' => 0,
                ]);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء الجدول بنجاح.',
                'data' => $timetableEntry
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ غير متوقع.',
                'error' => $e->getMessage()
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
            return response()->json([
                'status' => false,
                'message' => 'تم اكتشاف تعارض عند التحديث.',
                'conflicts' => $conflicts
            ], 409);
        }

        // 3. تحديث السجل
        $timetable->update($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بند الجدول بنجاح.',
            'data' => $timetable->fresh() // إرجاع النسخة المحدثة
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * حذف ناعم للجدول مع كل جلساته التابعة (نقلها إلى سلة المحذوفات).
     */
    public function destroy(Timetable $timetable)
    {
        try {
            DB::transaction(function () use ($timetable) {
                // نقل كل جلسات هذا الجدول إلى سلة المحذوفات أولاً
                LectureSession::where('timetable_id', $timetable->timetable_id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => now(), 'updated_at' => now()]);

                $timetable->delete();
            });

            return response()->json(['status' => true, 'message' => 'تم حذف بند الجدول وجلساته بنجاح.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل حذف البند.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * سلة المحذوفات (Soft Delete) للجداول وجلسات المحاضرات.
     * POST /api/v1/timetable/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:timetable,lecture_sessions',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];

        if ($validated['action'] === 'list') {
            $timetableRows = Timetable::onlyTrashed()
                ->where('college_id', $collegeId)
                ->with([
                    'course' => fn ($q) => $q->withTrashed(),
                    'lecturer' => fn ($q) => $q->withTrashed(),
                    'lecturer.user' => fn ($q) => $q->withTrashed(),
                    'group' => fn ($q) => $q->withTrashed(),
                    'classroom' => fn ($q) => $q->withTrashed(),
                    'day',
                    'period' => fn ($q) => $q->withTrashed(),
                    'program' => fn ($q) => $q->withTrashed(),
                    'level' => fn ($q) => $q->withTrashed(),
                    'semester' => fn ($q) => $q->withTrashed(),
                ])
                ->orderByDesc('deleted_at')
                ->get();

            $sessionRows = LectureSession::onlyTrashed()
                ->join('timetable', 'timetable.timetable_id', '=', 'lecture_sessions.timetable_id')
                ->where('timetable.college_id', $collegeId)
                ->select('lecture_sessions.*')
                ->with([
                    'timetable' => function ($q) {
                        $q->withTrashed()->with([
                            'course' => fn ($c) => $c->withTrashed(),
                            'lecturer' => fn ($l) => $l->withTrashed(),
                            'lecturer.user' => fn ($u) => $u->withTrashed(),
                            'group' => fn ($g) => $g->withTrashed(),
                            'classroom' => fn ($c) => $c->withTrashed(),
                            'day',
                            'period' => fn ($p) => $p->withTrashed(),
                        ]);
                    },
                    'actualClassroom' => fn ($q) => $q->withTrashed(),
                ])
                ->orderByDesc('lecture_sessions.deleted_at')
                ->get();

            return response()->json([
                'groups' => [
                    [
                        'type' => 'timetable',
                        'label' => 'الجداول الدراسية',
                        'records' => $timetableRows->map(function (Timetable $row) {
                            $periodLabel = $row->period
                                ? trim(substr((string) $row->period->start_time, 0, 5) . ' - ' . substr((string) $row->period->end_time, 0, 5))
                                : null;

                            $name = trim(implode(' - ', array_filter([
                                $row->course?->course_name,
                                $row->group?->group_name,
                                $row->day?->day_name,
                                $periodLabel,
                            ])));

                            return [
                                'id' => $row->timetable_id,
                                'name' => $name !== '' ? $name : ('جدول #' . $row->timetable_id),
                                'deleted_at' => $row->deleted_at?->toISOString(),
                                'meta' => [
                                    'course_name' => $row->course?->course_name,
                                    'course_code' => $row->course?->course_code,
                                    'lecturer_name' => $row->lecturer?->user?->full_name,
                                    'group_name' => $row->group?->group_name,
                                    'classroom_name' => $row->classroom?->classroom_name,
                                    'day_name' => $row->day?->day_name,
                                    'period_label' => $periodLabel,
                                    'start_date' => $row->start_date,
                                    'end_date' => $row->end_date,
                                    'academic_year' => $row->academic_year,
                                    'level_name' => $row->level?->level_name,
                                    'program_name' => $row->program?->program_name,
                                    'semester_name' => $row->semester?->semester_name,
                                ],
                                'data' => $row->getAttributes(),
                            ];
                        })->values(),
                    ],
                    [
                        'type' => 'lecture_sessions',
                        'label' => 'جلسات المحاضرات',
                        'records' => $sessionRows->map(function (LectureSession $session) {
                            $courseName = $session->timetable?->course?->course_name;
                            $sessionDate = $session->session_date
                                ? $session->session_date->format('Y-m-d')
                                : null;
                            $timeLabel = $session->start_time
                                ? trim(substr((string) $session->start_time, 0, 5) . ' - ' . substr((string) $session->end_time, 0, 5))
                                : null;

                            $name = trim(implode(' - ', array_filter([
                                $courseName,
                                $sessionDate,
                                $timeLabel,
                            ])));

                            return [
                                'id' => $session->session_id,
                                'name' => $name !== '' ? $name : ('جلسة #' . $session->session_id),
                                'deleted_at' => $session->deleted_at?->toISOString(),
                                'meta' => [
                                    'course_name' => $courseName,
                                    'course_code' => $session->timetable?->course?->course_code,
                                    'lecturer_name' => $session->timetable?->lecturer?->user?->full_name,
                                    'group_name' => $session->timetable?->group?->group_name,
                                    'classroom_name' => $session->actualClassroom?->classroom_name
                                        ?? $session->timetable?->classroom?->classroom_name,
                                    'day_name' => $session->timetable?->day?->day_name,
                                    'session_date' => $sessionDate,
                                    'time_label' => $timeLabel,
                                    'status' => $session->status,
                                    'session_code' => $session->session_code,
                                    'timetable_id' => $session->timetable_id,
                                    'timetable_deleted' => (bool) $session->timetable?->trashed(),
                                ],
                                'data' => $session->getAttributes(),
                            ];
                        })->values(),
                    ],
                ],
            ]);
        }

        $type = $validated['type'];
        $id = (int) $validated['id'];
        $action = $validated['action'];

        if ($type === 'timetable') {
            $timetable = Timetable::onlyTrashed()
                ->where('college_id', $collegeId)
                ->whereKey($id)
                ->firstOrFail();

            DB::transaction(function () use ($timetable, $action) {
                $sessionQuery = LectureSession::withTrashed()
                    ->where('timetable_id', $timetable->timetable_id);

                if ($action === 'restore') {
                    // استعادة الجدول مع كل جلساته المحذوفة
                    $sessionQuery->restore();
                    $timetable->restore();
                } else {
                    // حذف نهائي للجدول مع كل جلساته
                    $sessionQuery->forceDelete();
                    $timetable->forceDelete();
                }
            });
        } else {
            $session = LectureSession::onlyTrashed()
                ->whereHas('timetable', fn ($q) => $q->withTrashed()->where('college_id', $collegeId))
                ->whereKey($id)
                ->firstOrFail();

            DB::transaction(function () use ($session, $action) {
                if ($action === 'restore') {
                    $timetable = $session->timetable()->withTrashed()->first();

                    // إذا كان الجدول الأب محذوفاً، نستعيده هو وكل جلساته معاً
                    if ($timetable && $timetable->trashed()) {
                        LectureSession::withTrashed()
                            ->where('timetable_id', $timetable->timetable_id)
                            ->restore();
                        $timetable->restore();
                    } else {
                        $session->restore();
                    }
                } else {
                    $session->forceDelete();
                }
            });
        }

        return response()->json([
            'message' => $action === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }

    /**
     * دالة مساعدة للتحقق من التعارضات.
     * @param int $day_id
     * @param int $period_id
     * @param array $data - البيانات القادمة من الطلب.
     * @param int|null $ignoreId - معرف السجل الذي يجب تجاهله (يستخدم في التحديث).
     * @return array
     */
    private function checkForConflicts(int $day_id, int $period_id, array $data, int $ignoreId = null): array
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
        $relations = ['course', 'lecturer', 'studentGroup', 'classroom'];
    
        // --- أ) التحقق من تعارض القاعة (Classroom Conflict) ---
        if (isset($data['classroom_id'])) {
            $clashes = Timetable::with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('classroom_id', $data['classroom_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $conflicts[] = [
                    'type' => 'classroom',
                    'entity' => $clash->classroom->classroom_name ?? 'غير معروف',
                    'message' => "القاعة محجوزة لمادة ({$clash->course->course_name}) من تاريخ {$clash->start_date} إلى {$clash->end_date}",
                    'conflict_with_id' => $clash->timetable_id,
                    'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                ];
            }
        }
    
        // --- ب) التحقق من تعارض المحاضر (Lecturer Conflict) ---
        if (isset($data['lecturer_id'])) {
            $clashes = Timetable::with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('lecturer_id', $data['lecturer_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $lecturerName = $clash->lecturer->user->full_name ?? 'المحاضر';
                $conflicts[] = [
                    'type' => 'lecturer',
                    'entity' => $lecturerName,
                    'message' => "المحاضر ({$lecturerName}) لديه محاضرة أخرى ({$clash->course->course_name}) في القاعة ({$clash->classroom->classroom_name})",
                    'conflict_with_id' => $clash->timetable_id,
                    'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                ];
            }
        }
    
        // --- ج) التحقق من تعارض المجموعة الطلابية (Student Group Conflict) ---
        if (isset($data['group_id'])) {
            $clashes = Timetable::with($relations)
                ->where('day_id', $day_id)
                ->where('period_id', $period_id)
                ->where('group_id', $data['group_id'])
                ->where($dateScope)
                ->when($ignoreId, function ($q) use ($ignoreId) {
                    $q->where('timetable_id', '!=', $ignoreId);
                })
                ->get();
    
            foreach ($clashes as $clash) {
                $groupName = $clash->studentGroup->group_name ?? 'المجموعة';
                $conflicts[] = [
                    'type' => 'group',
                    'entity' => $groupName,
                    'message' => "المجموعة ({$groupName}) لديها محاضرة أخرى ({$clash->course->course_name}) في نفس الوقت.",
                    'conflict_with_id' => $clash->timetable_id,
                    'dates' => ['start' => $clash->start_date, 'end' => $clash->end_date]
                ];
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
}