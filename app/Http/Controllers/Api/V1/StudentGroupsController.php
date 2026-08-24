<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;

class StudentGroupsController extends Controller
{

    private function makeUniquePhone(): string
    {
        // مثال سعودي 059XXXXXXX (7 أرقام عشوائية) مع التحقق من التفرد
        do {
            $candidate = '059' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (DB::table('users')->where('phone', $candidate)->exists());
    
        return $candidate;
    }

private function makePlaceholderEmail(?string $academic): string
{
    $base = $academic
        ? strtolower(preg_replace('/[^a-z0-9]/i', '', $academic))
        : ('std' . random_int(1000, 9999));

    $candidate = $base . '@students.local';
    $i = 0;

    while (DB::table('users')->where('email', $candidate)->exists()) {
        $i++;
        $candidate = $base . $i . '@students.local';
        if ($i > 100) { // ملاذ أخير
            $candidate = 'std' . Str::uuid() . '@students.local';
            break;
        }
    }
    return $candidate;
}


    private function resolveGroupPathFields(Request $request): array
    {
        $payload = [
            'college_id'    => $request->input('college_id'),
            'department_id' => $request->input('department_id'),
            'program_id'    => $request->input('program_id'),
            'level_id'      => $request->input('level_id'),
            'semester_id'   => $request->input('semester_id'),
            'block_id'      => $request->input('block_id'),
            'group_name'    => trim((string) $request->input('group_name', '')),
        ];

        $program = null;
        if (!empty($payload['program_id'])) {
            $program = Program::where('program_id', (int) $payload['program_id'])->first();
        }

        if (!$program && !empty($payload['department_id']) && !empty($payload['college_id'])) {
            $payload['program_id'] = null;
        }

        if ($program) {
            if ($program->academic_system === 'semester') {
                if ($program->block_based) {
                    $payload['semester_id'] = null;
                }
            } else {
                $payload['level_id'] = null;
                $payload['semester_id'] = null;
                if (!$program->block_based) {
                    $payload['block_id'] = null;
                }
            }
        }

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    // GET /api/v1/student-groups?college_id=&department_id=&level_id=&semester_id=&with_counts=1
    public function index(Request $request)
    {
        $q = DB::table('student_groups as g')->whereNull('g.deleted_at');

        if ($request->filled('college_id'))    $q->where('g.college_id',    $request->integer('college_id'));
        if ($request->filled('department_id')) $q->where('g.department_id', $request->integer('department_id'));
        if ($request->filled('program_id'))    $q->where('g.program_id',    $request->integer('program_id'));
        if ($request->filled('level_id'))      $q->where('g.level_id',      $request->integer('level_id'));
        if ($request->filled('semester_id'))   $q->where('g.semester_id',   $request->integer('semester_id'));
        if ($request->filled('block_id'))      $q->where('g.block_id',      $request->integer('block_id'));

        $withCounts = (bool) $request->get('with_counts', false);

        if ($withCounts) {
            $q->leftJoin(
                DB::raw('(SELECT group_id, COUNT(*) as students_count FROM student_group_members GROUP BY group_id) sgm'),
                'sgm.group_id',
                '=',
                'g.group_id'
            )->select('g.*', DB::raw('COALESCE(sgm.students_count,0) as students_count'));
        } else {
            $q->select('g.*');
        }

        $perPage = (int) $request->get('per_page', 100);
        return response()->json($q->orderBy('g.group_name')->paginate($perPage));
    }

    // POST /api/v1/student-groups
    public function store(Request $request)
    {
        $program = null;
        if ($request->filled('program_id')) {
            $program = Program::where('program_id', (int) $request->program_id)->first();
        }

        $rules = [
            'college_id'    => 'required|integer|exists:colleges,college_id',
            'department_id' => 'required|integer|exists:departments,department_id',
            'program_id'    => ['nullable', 'integer', 'exists:programs,program_id'],
            'level_id'      => ['nullable', 'integer', 'exists:levels,level_id'],
            'semester_id'   => ['nullable', 'integer', 'exists:semesters,semester_id'],
            'block_id'      => ['nullable', 'integer', 'exists:blocks,id'],
            'group_name'    => 'required|string|max:100',
            'max_students'  => ['nullable', 'integer', 'min:1', 'max:500'],
        ];

        if (!$program) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
        } elseif ($program->academic_system === 'semester' && $program->block_based) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
        } elseif ($program->academic_system === 'semester') {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
        } elseif ($program->block_based) {
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
        } else {
            $rules['program_id'] = ['required', 'integer', 'exists:programs,program_id'];
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
        }

        $data = $request->validate($rules);
        $maxStudents = (int) ($data['max_students'] ?? 30);

        $path = [
            'college_id'    => (int) $data['college_id'],
            'department_id' => (int) $data['department_id'],
            'program_id'    => $data['program_id'] ?? null,
            'level_id'      => $data['level_id'] ?? null,
            'semester_id'   => $data['semester_id'] ?? null,
            'block_id'      => $data['block_id'] ?? null,
            'group_name'    => trim($data['group_name']),
        ];
    
        $path = array_filter($path, fn ($value) => $value !== null && $value !== '');

        // 1) فحص وجود مجموعة فعّالة بنفس المسار (تجاهل المحذوفة Soft)
        $activeDup = DB::table('student_groups')
            ->whereNull('deleted_at')
            ->where($path)
            ->first();
    
        if ($activeDup) {
            return response()->json(['message' => 'Duplicate group for this path'], 409);
        }
    
        // 2) فحص وجود مجموعة محذوفة Soft بنفس المسار → استرجاعها بدل إنشاء سجل جديد
        $softDup = DB::table('student_groups')
            ->whereNotNull('deleted_at')
            ->where($path)
            ->first();
    
        if ($softDup) {
            DB::table('student_groups')
                ->where('group_id', $softDup->group_id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
    
            $restored = DB::table('student_groups')->where('group_id', $softDup->group_id)->first();
    
            return response()->json([
                'status'  => 'restored',
                'group'   => $restored,
                'message' => 'Group restored from soft delete',
            ], 200);
        }
    
        // 3) الإدراج الفعلي
        try {
            $id = DB::table('student_groups')->insertGetId([
                'college_id'    => $path['college_id'],
                'department_id' => $path['department_id'],
                'program_id'    => $path['program_id'] ?? null,
                'level_id'      => $path['level_id'] ?? null,
                'semester_id'   => $path['semester_id'] ?? null,
                'block_id'      => $path['block_id'] ?? null,
                'group_name'    => $path['group_name'],
                'max_students'  => $maxStudents,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $group = DB::table('student_groups')->where('group_id', $id)->first();

            return response()->json([
                'group_id' => $id,
                'group'    => $group,
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // تعارض المفتاح الفريد فعلاً (unique_group_per_path) → 409
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'unique_group_per_path')) {
                return response()->json(['message' => 'Duplicate group for this path'], 409);
            }
            // أخطاء قاعدة بيانات أخرى → 500 مع تسجيل الحدث
            Log::error('student_groups.store DB error', [
                'code' => $e->getCode(),
                'info' => $e->errorInfo,
                'msg'  => $e->getMessage(),
            ]);
    
            return response()->json([
                'message'  => 'DB error',
                'sqlstate' => $e->getCode(),
            ], 500);
        }
    }

    // GET /api/v1/student-groups/{group}
    public function show($group)
    {
        $row = DB::table('student_groups')
            ->where('group_id', (int)$group)
            ->whereNull('deleted_at')
            ->first();

        return $row ? response()->json($row) : response()->json(['message' => 'Not found'], 404);
    }

    // PUT/PATCH /api/v1/student-groups/{group}
    public function update(Request $request, $group)
    {
        $existing = DB::table('student_groups')
            ->where('group_id', (int)$group)
            ->whereNull('deleted_at')
            ->first();

        if (!$existing) return response()->json(['message' => 'Not found'], 404);

        $rules = [
            'group_name'   => 'sometimes|string|max:100',
            'max_students' => 'sometimes|integer|min:1|max:500',
        ];

        $data = $request->validate($rules);

        if (isset($data['group_name'])) {
            $data['group_name'] = trim($data['group_name']);
            if ($data['group_name'] === '') {
                return response()->json(['message' => 'اسم المجموعة مطلوب'], 422);
            }
        }

        if (isset($data['max_students'])) {
            $data['max_students'] = (int) $data['max_students'];
        }

        $data['updated_at'] = now();

        try {
            $duplicate = DB::table('student_groups')
                ->whereNull('deleted_at')
                ->where('group_id', '!=', (int)$group)
                ->where('college_id', $existing->college_id)
                ->where('department_id', $existing->department_id)
                ->when($existing->program_id !== null, fn($q) => $q->where('program_id', $existing->program_id))
                ->when($existing->program_id === null, fn($q) => $q->whereNull('program_id'))
                ->when($existing->level_id !== null, fn($q) => $q->where('level_id', $existing->level_id))
                ->when($existing->level_id === null, fn($q) => $q->whereNull('level_id'))
                ->when($existing->semester_id !== null, fn($q) => $q->where('semester_id', $existing->semester_id))
                ->when($existing->semester_id === null, fn($q) => $q->whereNull('semester_id'))
                ->when($existing->block_id !== null, fn($q) => $q->where('block_id', $existing->block_id))
                ->when($existing->block_id === null, fn($q) => $q->whereNull('block_id'))
                ->where('group_name', trim($data['group_name'] ?? $existing->group_name))
                ->exists();

            if ($duplicate) {
                return response()->json(['message' => 'يوجد اسم مجموعة مكرر في نفس المسار الدراسي'], 409);
            }

            DB::table('student_groups')->where('group_id', (int)$group)->update($data);
            return response()->json(['message' => 'Updated']);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Duplicate group for this path', 'error' => $e->getMessage()], 409);
        }
    }

    // DELETE /api/v1/student-groups/{group}
    public function destroy($group)
    {
        // Soft delete
        $deleted = DB::table('student_groups')
            ->where('group_id', (int)$group)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return $deleted ? response()->json(['message' => 'Deleted']) : response()->json(['message' => 'Not found'], 404);
    }

    private function validateSameProgramTransfer(array $source, array $target): bool
    {
        if (($source['college_id'] ?? null) !== ($target['college_id'] ?? null)) {
            return false;
        }

        if (($source['department_id'] ?? null) !== ($target['department_id'] ?? null)) {
            return false;
        }

        if (($source['program_id'] ?? null) !== ($target['program_id'] ?? null)) {
            return false;
        }

        return true;
    }

    private function normalizeGroupTransferPayload(array $base, Request $request): array
    {
        $programId = $request->input('program_id', $base['program_id'] ?? null);
        $program = $programId ? Program::where('program_id', (int) $programId)->first() : null;

        $payload = [
            'college_id'    => (int) ($request->input('college_id', $base['college_id'] ?? 0)),
            'department_id' => (int) ($request->input('department_id', $base['department_id'] ?? 0)),
            'program_id'    => $programId !== null ? (int) $programId : null,
            'level_id'      => $request->filled('level_id') ? (int) $request->input('level_id') : ($base['level_id'] ?? null),
            'semester_id'   => $request->filled('semester_id') ? (int) $request->input('semester_id') : ($base['semester_id'] ?? null),
            'block_id'      => $request->filled('block_id') ? (int) $request->input('block_id') : ($base['block_id'] ?? null),
        ];

        if ($program) {
            if ($program->academic_system === 'semester' && $program->block_based) {
                $payload['semester_id'] = $request->filled('semester_id') ? (int) $request->input('semester_id') : ($base['semester_id'] ?? null);
                $payload['level_id'] = $request->filled('level_id') ? (int) $request->input('level_id') : ($base['level_id'] ?? null);
                $payload['block_id'] = $request->filled('block_id') ? (int) $request->input('block_id') : ($base['block_id'] ?? null);
            } elseif ($program->academic_system === 'semester') {
                $payload['level_id'] = $request->filled('level_id') ? (int) $request->input('level_id') : ($base['level_id'] ?? null);
                $payload['semester_id'] = $request->filled('semester_id') ? (int) $request->input('semester_id') : ($base['semester_id'] ?? null);
                $payload['block_id'] = null;
            } elseif ($program->block_based) {
                $payload['level_id'] = null;
                $payload['semester_id'] = null;
                $payload['block_id'] = $request->filled('block_id') ? (int) $request->input('block_id') : ($base['block_id'] ?? null);
            } else {
                $payload['level_id'] = null;
                $payload['semester_id'] = null;
                $payload['block_id'] = null;
            }
        }

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    public function bulkMoveStudents(Request $request)
    {
        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,program_id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,level_id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,semester_id'],
            'block_id' => ['nullable', 'integer', 'exists:blocks,id'],
            'action' => ['nullable', 'in:promote,demote,pass,fail,move'],
            'status' => ['nullable', 'in:passed,failed,active,inactive'],
        ]);

        $studentIds = array_map('intval', $data['student_ids']);
        $students = DB::table('students')->whereIn('student_id', $studentIds)->get();

        if ($students->count() !== count($studentIds)) {
            return response()->json(['message' => 'One or more students were not found.'], 404);
        }

        $firstStudent = $students->first();
        $baseTarget = [
            'college_id' => (int) ($data['college_id'] ?? $firstStudent->college_id),
            'department_id' => (int) ($data['department_id'] ?? $firstStudent->department_id),
            'program_id' => $data['program_id'] ?? $firstStudent->program_id,
            'level_id' => $data['level_id'] ?? $firstStudent->level_id,
            'semester_id' => $data['semester_id'] ?? $firstStudent->semester_id,
            'block_id' => $data['block_id'] ?? $firstStudent->block_id,
        ];

        $program = $baseTarget['program_id'] ? Program::where('program_id', (int) $baseTarget['program_id'])->first() : null;

        if ($program) {
            if ($program->academic_system === 'semester' && $program->block_based) {
                $baseTarget['level_id'] = $data['level_id'] ?? $firstStudent->level_id;
                $baseTarget['block_id'] = $data['block_id'] ?? $firstStudent->block_id;
                $baseTarget['semester_id'] = $data['semester_id'] ?? $firstStudent->semester_id;
            } elseif ($program->academic_system === 'semester') {
                $baseTarget['level_id'] = $data['level_id'] ?? $firstStudent->level_id;
                $baseTarget['semester_id'] = $data['semester_id'] ?? $firstStudent->semester_id;
                $baseTarget['block_id'] = null;
            } elseif ($program->block_based) {
                $baseTarget['level_id'] = null;
                $baseTarget['semester_id'] = null;
                $baseTarget['block_id'] = $data['block_id'] ?? $firstStudent->block_id;
            } else {
                $baseTarget['level_id'] = null;
                $baseTarget['semester_id'] = null;
                $baseTarget['block_id'] = null;
            }
        }

        $action = $data['action'] ?? 'move';

        foreach ($students as $student) {
            $source = [
                'college_id' => (int) $student->college_id,
                'department_id' => (int) $student->department_id,
                'program_id' => $student->program_id !== null ? (int) $student->program_id : null,
                'level_id' => $student->level_id !== null ? (int) $student->level_id : null,
                'semester_id' => $student->semester_id !== null ? (int) $student->semester_id : null,
                'block_id' => $student->block_id !== null ? (int) $student->block_id : null,
            ];

            if (in_array($action, ['promote', 'demote', 'move'], true)) {
                if (!$this->validateSameProgramTransfer($source, $baseTarget)) {
                    return response()->json([
                        'message' => 'لا يمكن نقل الطلاب خارج نفس البرنامج أو نفس الكلية/القسم.',
                        'student_id' => $student->student_id,
                    ], 422);
                }
            }

            $update = [
                'college_id' => $baseTarget['college_id'],
                'department_id' => $baseTarget['department_id'],
                'program_id' => $baseTarget['program_id'],
                'level_id' => $baseTarget['level_id'] ?? null,
                'semester_id' => $baseTarget['semester_id'] ?? null,
                'block_id' => $baseTarget['block_id'] ?? null,
                'updated_at' => now(),
            ];

            if (in_array($action, ['pass', 'passed'], true)) {
                $update['status'] = 1;
            }

            if (in_array($action, ['fail', 'failed'], true)) {
                $update['status'] = 0;
            }

            DB::table('students')->where('student_id', $student->student_id)->update($update);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث مسار الطلاب بنجاح.',
            'updated_count' => $students->count(),
            'action' => $action,
        ]);
    }

    public function moveGroupPath(Request $request, $group)
    {
        $groupRow = DB::table('student_groups')->where('group_id', (int) $group)->whereNull('deleted_at')->first();

        if (!$groupRow) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $baseTarget = [
            'college_id' => (int) ($groupRow->college_id ?? 0),
            'department_id' => (int) ($groupRow->department_id ?? 0),
            'program_id' => $groupRow->program_id !== null ? (int) $groupRow->program_id : null,
            'level_id' => $groupRow->level_id !== null ? (int) $groupRow->level_id : null,
            'semester_id' => $groupRow->semester_id !== null ? (int) $groupRow->semester_id : null,
            'block_id' => $groupRow->block_id !== null ? (int) $groupRow->block_id : null,
        ];

        $target = $this->normalizeGroupTransferPayload($baseTarget, $request);

        if (!$this->validateSameProgramTransfer($baseTarget, $target)) {
            return response()->json(['message' => 'لا يمكن نقل المجموعة خارج نفس البرنامج أو نفس الكلية/القسم.'], 422);
        }

        $program = $target['program_id'] ? Program::where('program_id', (int) $target['program_id'])->first() : null;
        if ($program) {
            if ($program->academic_system === 'semester' && $program->block_based) {
                $required = ['level_id', 'block_id'];
            } elseif ($program->academic_system === 'semester') {
                $required = ['level_id', 'semester_id'];
            } elseif ($program->block_based) {
                $required = ['block_id'];
            } else {
                $required = [];
            }

            foreach ($required as $field) {
                if (empty($target[$field])) {
                    return response()->json(['message' => 'المسار الجديد غير مكتمل لهذا البرنامج.'], 422);
                }
            }
        }

        $duplicate = DB::table('student_groups')
            ->whereNull('deleted_at')
            ->where('group_id', '!=', (int) $group)
            ->where('college_id', $target['college_id'])
            ->where('department_id', $target['department_id'])
            ->where('program_id', $target['program_id'] ?? null)
            ->where('level_id', $target['level_id'] ?? null)
            ->where('semester_id', $target['semester_id'] ?? null)
            ->where('block_id', $target['block_id'] ?? null)
            ->where('group_name', $groupRow->group_name)
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'يوجد اسم مجموعة مكرر في هذا المسار الجديد.'], 409);
        }

        $updateData = [
            'college_id' => $target['college_id'],
            'department_id' => $target['department_id'],
            'program_id' => $target['program_id'] ?? null,
            'level_id' => $target['level_id'] ?? null,
            'semester_id' => $target['semester_id'] ?? null,
            'block_id' => $target['block_id'] ?? null,
            'updated_at' => now(),
        ];

        DB::table('student_groups')->where('group_id', (int) $group)->update($updateData);

        $memberIds = DB::table('student_group_members')->where('group_id', (int) $group)->pluck('student_id');
        if ($memberIds->isNotEmpty()) {
            DB::table('students')->whereIn('student_id', $memberIds)->update([
                'college_id' => $target['college_id'],
                'department_id' => $target['department_id'],
                'program_id' => $target['program_id'] ?? null,
                'level_id' => $target['level_id'] ?? null,
                'semester_id' => $target['semester_id'] ?? null,
                'block_id' => $target['block_id'] ?? null,
                'updated_at' => now(),
            ]);
        }

        $updatedGroup = DB::table('student_groups')->where('group_id', (int) $group)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'تم نقل المجموعة داخل نفس البرنامج بنجاح.',
            'group' => $updatedGroup,
        ]);
    }

    // POST /api/v1/student-groups/upsert-and-attach
    public function upsertAndAttach(Request $request)
    {
        $requestData = $request->all();
        $program = null;
        if (!empty($requestData['program_id'])) {
            $program = Program::where('program_id', (int) $requestData['program_id'])->first();
        }

        $rules = [
            'college_id'    => 'required|integer|exists:colleges,college_id',
            'department_id' => 'required|integer|exists:departments,department_id',
            'program_id'    => ['nullable', 'integer', 'exists:programs,program_id'],
            'level_id'      => ['nullable', 'integer', 'exists:levels,level_id'],
            'semester_id'   => ['nullable', 'integer', 'exists:semesters,semester_id'],
            'block_id'      => ['nullable', 'integer', 'exists:blocks,id'],
            'group_name'    => 'required|string|max:100',
            'max_students'  => ['nullable', 'integer', 'min:1', 'max:500'],
        ];

        if (!$program) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
        } elseif ($program->academic_system === 'semester' && $program->block_based) {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
        } elseif ($program->academic_system === 'semester') {
            $rules['level_id'] = ['required', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['required', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
        } elseif ($program->block_based) {
            $rules['block_id'] = ['required', 'integer', 'exists:blocks,id'];
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
        } else {
            $rules['program_id'] = ['required', 'integer', 'exists:programs,program_id'];
            $rules['level_id'] = ['nullable', 'integer', 'exists:levels,level_id'];
            $rules['semester_id'] = ['nullable', 'integer', 'exists:semesters,semester_id'];
            $rules['block_id'] = ['nullable', 'integer', 'exists:blocks,id'];
        }

        $data = $request->validate($rules);

        $maxStudents = (int) ($data['max_students'] ?? 30);

        $path = [
            'college_id'    => (int) $data['college_id'],
            'department_id' => (int) $data['department_id'],
            'program_id'    => $data['program_id'] ?? null,
            'level_id'      => $data['level_id'] ?? null,
            'semester_id'   => $data['semester_id'] ?? null,
            'block_id'      => $data['block_id'] ?? null,
            'group_name'    => trim($data['group_name']),
        ];

        $path = array_filter($path, fn ($value) => $value !== null && $value !== '');
    
        // 1) هل هناك مجموعة فعّالة (غير محذوفة) بهذا المسار؟
        $existing = DB::table('student_groups')
            ->whereNull('deleted_at')
            ->where($path)
            ->first();
    
        if ($existing) {
            return response()->json([
                'status'  => 'exists',
                'group'   => $existing,
                'message' => 'Group already exists for this path',
            ], 200);
        }
    
        // 2) هل هناك مجموعة محذوفة Soft بنفس المسار؟ إن وُجدت نعيد تفعيلها
        $soft = DB::table('student_groups')
            ->whereNotNull('deleted_at')
            ->where($path)
            ->first();
    
        if ($soft) {
            DB::table('student_groups')
                ->where('group_id', $soft->group_id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
    
            $restored = DB::table('student_groups')->where('group_id', $soft->group_id)->first();
    
            return response()->json([
                'status'  => 'restored',
                'group'   => $restored,
                'message' => 'Group restored from soft delete',
            ], 200);
        }
    
        // 3) إنشاء مجموعة جديدة
        try {
            $id = DB::table('student_groups')->insertGetId([
                'college_id'    => $path['college_id'],
                'department_id' => $path['department_id'],
                'program_id'    => $path['program_id'] ?? null,
                'level_id'      => $path['level_id'] ?? null,
                'semester_id'   => $path['semester_id'] ?? null,
                'block_id'      => $path['block_id'] ?? null,
                'group_name'    => $path['group_name'],
                'max_students'  => $maxStudents,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $group = DB::table('student_groups')->where('group_id', $id)->first();

            return response()->json([
                'status'  => 'created',
                'group'   => $group,
                'message' => 'Group created successfully',
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // في حال حصل تعارض فريد (بسبب سباق طلبات)، ارجع المجموعة الموجودة بدلاً من الخطأ
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'unique_group_per_path')) {
                $dup = DB::table('student_groups')->whereNull('deleted_at')->where($path)->first()
                    ?: DB::table('student_groups')->where($path)->first();
    
                return response()->json([
                    'status'  => 'exists',
                    'group'   => $dup,
                    'message' => 'Group already exists for this path',
                ], 200);
            }
    
            Log::error('student_groups.upsertAndAttach DB error', [
                'code' => $e->getCode(),
                'info' => $e->errorInfo,
                'msg'  => $e->getMessage(),
            ]);
    
            return response()->json([
                'message'  => 'DB error',
                'sqlstate' => $e->getCode(),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('student_groups.upsertAndAttach error', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    // GET /api/v1/student-groups/{group}/students
    public function students($group)
    {
        $rows = DB::table('student_group_members as sgm')
            ->join('students as s', 's.student_id', '=', 'sgm.student_id')
            ->join('users as u', 'u.user_id', '=', 's.user_id')
            ->where('sgm.group_id', (int)$group)
            ->select(
                's.student_id',
                'u.full_name',
                'u.academic_number',
                'u.gender'
            )
            ->orderBy('u.full_name')
            ->get();

        // نعيد user ككائن متداخل كما تتوقع الواجهة
        $out = $rows->map(function ($r) {
            return [
                'student_id' => $r->student_id,
                'user' => [
                    'full_name'       => $r->full_name,
                    'academic_number' => $r->academic_number,
                    'gender'          => (int)$r->gender,
                ],
            ];
        });

        return response()->json($out);
    }

    // DELETE /api/v1/student-groups/{group}/students  body: { student_id: ... }
    public function detachStudent(Request $request, $group)
    {
        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,student_id',
        ]);

        $deleted = DB::table('student_group_members')
            ->where('group_id', (int)$group)
            ->where('student_id', $data['student_id'])
            ->delete();

        return response()->json(['deleted' => (bool)$deleted]);
    }

    private function studentMatchesGroupPath(object $student, object $group): bool
    {
        if ((int) ($student->college_id ?? 0) !== (int) ($group->college_id ?? 0)) {
            return false;
        }

        if ((int) ($student->department_id ?? 0) !== (int) ($group->department_id ?? 0)) {
            return false;
        }

        if (!is_null($group->program_id) && !is_null($student->program_id) && (int) $student->program_id !== (int) $group->program_id) {
            return false;
        }

        if (!is_null($group->program_id) && is_null($student->program_id)) {
            return false;
        }

        if (!is_null($group->level_id) && !is_null($student->level_id) && (int) $student->level_id !== (int) $group->level_id) {
            return false;
        }

        if (!is_null($group->level_id) && is_null($student->level_id)) {
            return false;
        }

        if (!is_null($group->semester_id) && !is_null($student->semester_id) && (int) $student->semester_id !== (int) $group->semester_id) {
            return false;
        }

        if (!is_null($group->semester_id) && is_null($student->semester_id)) {
            return false;
        }

        if (!is_null($group->block_id) && !is_null($student->block_id) && (int) $student->block_id !== (int) $group->block_id) {
            return false;
        }

        if (!is_null($group->block_id) && is_null($student->block_id)) {
            return false;
        }

        return true;
    }

    private function studentAlreadyBelongsToDifferentProgram(int $studentId, int $groupId): bool
    {
        $conflict = DB::table('student_group_members as sgm')
            ->join('student_groups as sg', 'sg.group_id', '=', 'sgm.group_id')
            ->where('sgm.student_id', $studentId)
            ->where('sg.group_id', '!=', $groupId)
            ->select('sg.program_id', 'sg.level_id', 'sg.semester_id', 'sg.block_id', 'sg.college_id', 'sg.department_id')
            ->first();

        if (!$conflict) {
            return false;
        }

        $group = DB::table('student_groups')->where('group_id', $groupId)->first();
        if (!$group) {
            return false;
        }

        return (int) ($conflict->program_id ?? 0) !== (int) ($group->program_id ?? 0)
            || (int) ($conflict->college_id ?? 0) !== (int) ($group->college_id ?? 0)
            || (int) ($conflict->department_id ?? 0) !== (int) ($group->department_id ?? 0)
            || (int) ($conflict->level_id ?? 0) !== (int) ($group->level_id ?? 0)
            || (int) ($conflict->semester_id ?? 0) !== (int) ($group->semester_id ?? 0)
            || (int) ($conflict->block_id ?? 0) !== (int) ($group->block_id ?? 0);
    }

    // POST /api/v1/student-groups/import-csv (form-data: file, group_id)
    public function importCsv(\Illuminate\Http\Request $request)
    {
        // 1) التحقق من المدخلات (csv/txt + xlsx/xls)
        $request->validate([
            'file'     => 'required|file|mimes:csv,txt,xlsx,xls|max:20480',
            'group_id' => 'required|integer|exists:student_groups,group_id',
        ]);
    
        // Helpers محلية
        $normalizeHeader = function (string $h): string {
            $h = trim($h);
            $h = preg_replace('/\s+/u', ' ', $h);
            $h = mb_strtolower($h);
            return match ($h) {
                'student_id', 'id', 'رقم_الطالب', 'رقم الطالب' => 'student_id',
                'academic_number', 'student_no', 'student number', 'رقم_جامعي', 'الرقم الجامعي', 'رقم الجامعي' => 'academic_number',
                'full_name', 'name', 'الاسم', 'اسم' => 'full_name',
                'email', 'البريد', 'البريد الالكتروني', 'البريد الإلكتروني', 'الإيميل', 'الايميل' => 'email',
                'phone', 'رقم', 'الجوال', 'الهاتف', 'رقم الجوال', 'رقم الهاتف' => 'phone',
                'gender', 'الجنس', 'sex', 'type' => 'gender',
                default => $h,
            };
        };
    
        $parseGender = function ($v): int {
            if ($v === null) return 0;
            if (is_bool($v)) return $v ? 1 : 0;
            if (is_numeric($v)) return ((int)$v) > 0 ? 1 : 0;
            $s = mb_strtolower(trim((string)$v));
            $s = strtr($s, ['أ'=>'ا','إ'=>'ا','آ'=>'ا','ى'=>'ي','ة'=>'ه','ـ'=>'']);
            $maleSet   = ['1','m','male','man','boy','ذكر','ذ','ذكور'];
            $femaleSet = ['0','f','female','woman','girl','انثى','انثي','انث','اناث','بنت','فتاه','فتاة','نساء','سيده','امرأه','امراه','ا'];
            if (in_array($s, $maleSet, true)) return 1;
            if (in_array($s, $femaleSet, true)) return 0;
            $c = mb_substr($s, 0, 1);
            if ($c === 'm' || $c === 'ذ') return 1;
            if ($c === 'f' || $c === 'ا' || $c === 'ب') return 0;
            return 0;
        };
    
        $makeAcademicNumberFromEmail = function (string $email): string {
            [$local] = explode('@', $email, 2);
            $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $local));
            if ($base === '') $base = 'STD' . random_int(1000, 9999);
            $candidate = $base; $maxLen = 50; $i = 0;
            while (\Illuminate\Support\Facades\DB::table('users')->where('academic_number', $candidate)->exists()) {
                $i++;
                $suffix = (string) $i;
                $candidate = substr($base, 0, $maxLen - strlen($suffix)) . $suffix;
                if ($i > 20) {
                    $suffix = (string) random_int(1000, 999999);
                    $candidate = substr($base, 0, $maxLen - strlen($suffix)) . $suffix;
                }
                if ($i > 40) {
                    $suffix = date('ymdHis');
                    $candidate = substr($base, 0, $maxLen - strlen($suffix)) . $suffix;
                    break;
                }
            }
            return $candidate;
        };
    
        $groupId = (int) $request->group_id;
    
        // 2) مسار المجموعة + user_type للطلاب
        $group = \Illuminate\Support\Facades\DB::table('student_groups')->where('group_id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Group not found'], 404);
    
        $studentTypeId =
            \Illuminate\Support\Facades\DB::table('user_types')->where('user_type_code', 'STUDENT')->value('user_type_id')
            ?: \Illuminate\Support\Facades\DB::table('user_types')->where('user_type_name', 'طالب')->value('user_type_id');
        if (!$studentTypeId) return response()->json(['message' => 'Student user type not configured (STUDENT/طالب)'], 422);
    
        // 3) قراءة الملف (Excel أو CSV)
        $file = $request->file('file');
        $path = $file->getPathname(); // أدق في بعض البيئات
        $ext  = strtolower($file->getClientOriginalExtension());
    
        $rows = [];
        if (in_array($ext, ['xlsx','xls'])) {
            try {
                if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    return response()->json(['message' => 'PhpSpreadsheet not installed'], 500);
                }
                $type = \PhpOffice\PhpSpreadsheet\IOFactory::identify($path);
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    
                if (!$sheet || count($sheet) < 2) {
                    return response()->json(['message' => 'Empty Excel sheet'], 422);
                }
    
                $header = array_map(fn($h) => $normalizeHeader((string)$h), array_values($sheet[1]));
                $rowCount = count($sheet);
                for ($i = 2; $i <= $rowCount; $i++) {
                    $line = array_values($sheet[$i] ?? []);
                    if (!array_filter($line, fn($v) => $v !== null && trim((string)$v) !== '')) continue;
                    $row = [];
                    foreach ($header as $idx => $key) {
                        $row[$key] = isset($line[$idx]) ? trim((string)$line[$idx]) : null;
                    }
                    $rows[] = $row;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to read Excel', ['err' => $e->getMessage()]);
                return response()->json([
                    'message' => 'Failed to read Excel file',
                    'error'   => $e->getMessage(),
                ], 422);
            }
        } else {
            // CSV/TXT
            $handle = fopen($path, 'r');
            if (!$handle) return response()->json(['message' => 'Cannot open file'], 422);
            $first = fgets($handle);
            if ($first === false) { fclose($handle); return response()->json(['message' => 'Empty file'], 422); }
            $delimiter = str_contains($first, ';') ? ';' : ',';
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $header = array_map(fn($h) => $normalizeHeader($h), str_getcsv($first, $delimiter));
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count(array_filter($data, fn($v) => $v !== null && trim((string)$v) !== '')) === 0) continue;
                $row = [];
                foreach ($header as $i => $key) {
                    $row[$key] = isset($data[$i]) ? trim((string)$data[$i]) : null;
                }
                $rows[] = $row;
            }
            fclose($handle);
        }
    
        // 4) عدادات
        $createdUsers = 0; $restoredUsers = 0; $createdStudents = 0; $restoredStudents = 0; $attached = 0; $skippedMissing = 0; $skippedConflicts = 0; $errors = [];
        $defaultPassword = env('DEFAULT_STUDENT_PASSWORD', '12345678');
    
        // 5) معالجة كل صف (إنشاء/استرجاع User + Student + الربط بالمجموعة)
        foreach ($rows as $row) {
            $academic = $row['academic_number'] ?? null;
            $email    = $row['email'] ?? null;
            $phone    = $row['phone'] ?? null;
            $fullName = $row['full_name'] ?? null;
            $genderV  = $row['gender'] ?? null;

            $phoneRaw = $row['phone'] ?? null;
            $phone = ($phoneRaw !== null && trim((string)$phoneRaw) !== '') ? trim((string)$phoneRaw) : null;
    
            if (!$academic && !$email && !$phone) {
                $skippedMissing++; $errors[] = ['reason' => 'missing_keys', 'row' => $row]; continue;
            }
    
            // ابحث عن المستخدم
            $user = null;
            if ($academic) $user = \Illuminate\Support\Facades\DB::table('users')->where('academic_number', $academic)->first();
            if (!$user && $email) $user = \Illuminate\Support\Facades\DB::table('users')->where('email', $email)->first();
            if (!$user && $phone) $user = \Illuminate\Support\Facades\DB::table('users')->where('phone', $phone)->first();
    
            // أنشئ User عند عدم الوجود
            if (!$user) {
                if (!$fullName || $genderV === null || (!$academic && !$email && !$phone)) {
                    $skippedMissing++; $errors[] = ['reason' => 'missing_user_fields', 'row' => $row]; continue;
                }
                $gender = $parseGender($genderV);
                $resolvedEmail = $email ?: ($academic ? strtolower($academic) . '@local.invalid' : 'student-' . date('YmdHis') . '-' . random_int(1000, 9999) . '@local.invalid');
                $academicNumber = $academic ?: $makeAcademicNumberFromEmail($resolvedEmail);

                try {
                    $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                        'full_name'       => $fullName,
                        'email'           => $resolvedEmail,
                        'phone'           => $phone,
                        'college_id'      => $group->college_id,
                        'password'        => \Illuminate\Support\Facades\Hash::make($defaultPassword),
                        'academic_number' => $academicNumber,
                        'gender'          => $gender,
                        'user_type_id'    => $studentTypeId,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $user = \Illuminate\Support\Facades\DB::table('users')->where('user_id', $userId)->first();
                    $createdUsers++;
                } catch (\Illuminate\Database\QueryException $e) {
                    \Illuminate\Support\Facades\Log::warning('importCsv user insert failed', ['msg' => $e->getMessage(), 'row' => $row]);
                    $skippedConflicts++; continue;
                }
            } else {
                if (is_null($user->phone) && $phone) {
                     \Illuminate\Support\Facades\DB::table('users')->where('user_id', $user->user_id)->update(['phone' => $phone]);
                }
                if (!is_null($user->deleted_at)) {
                    \Illuminate\Support\Facades\DB::table('users')->where('user_id', $user->user_id)->update([
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]);
                    $user = \Illuminate\Support\Facades\DB::table('users')->where('user_id', $user->user_id)->first();
                    $restoredUsers++;
                }
            }
    
            // تأكد من وجود سجل الطالب
            $student = \Illuminate\Support\Facades\DB::table('students')->where('user_id', $user->user_id)->first();
            if (!$student) {
                try {
                    \Illuminate\Support\Facades\DB::table('students')->insert([
                        'user_id'       => $user->user_id,
                        'college_id'    => $group->college_id,
                        'department_id' => $group->department_id,
                        'program_id'    => $group->program_id ?? null,
                        'level_id'      => $group->level_id ?? null,
                        'semester_id'   => $group->semester_id ?? null,
                        'block_id'      => $group->block_id ?? null,
                        'status'        => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                    $student = \Illuminate\Support\Facades\DB::table('students')->where('user_id', $user->user_id)->first();
                    $createdStudents++;
                } catch (\Illuminate\Database\QueryException $e) {
                    \Illuminate\Support\Facades\Log::warning('importCsv student insert failed', ['msg' => $e->getMessage(), 'row' => $row]);
                    $skippedConflicts++; continue;
                }
            } else {
                if (!is_null($student->deleted_at)) {
                    \Illuminate\Support\Facades\DB::table('students')->where('student_id', $student->student_id)->update([
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]);
                    $student = \Illuminate\Support\Facades\DB::table('students')->where('student_id', $student->student_id)->first();
                    $restoredStudents++;
                }
            }
    
            // 6) التحقق النهائي: لا يسمح للطالب بأن يربط بمسار مختلف عن مساره الأكاديمي الحالي
            if (!$this->studentMatchesGroupPath($student, $group) || $this->studentAlreadyBelongsToDifferentProgram((int) $student->student_id, (int) $groupId)) {
                $skippedConflicts++;
                $errors[] = [
                    'reason' => 'student_path_mismatch',
                    'student_id' => $student->student_id,
                    'group_id' => $groupId,
                    'student_path' => [
                        'college_id' => $student->college_id ?? null,
                        'department_id' => $student->department_id ?? null,
                        'program_id' => $student->program_id ?? null,
                        'level_id' => $student->level_id ?? null,
                        'semester_id' => $student->semester_id ?? null,
                        'block_id' => $student->block_id ?? null,
                    ],
                    'group_path' => [
                        'college_id' => $group->college_id ?? null,
                        'department_id' => $group->department_id ?? null,
                        'program_id' => $group->program_id ?? null,
                        'level_id' => $group->level_id ?? null,
                        'semester_id' => $group->semester_id ?? null,
                        'block_id' => $group->block_id ?? null,
                    ],
                ];
                continue;
            }

            // 7) ربط الطالب بالمجموعة
            try {
                \Illuminate\Support\Facades\DB::table('student_group_members')->insertOrIgnore([
                    'student_id' => $student->student_id,
                    'group_id'   => $groupId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $attached++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('importCsv attach failed', ['msg' => $e->getMessage(), 'row' => $row]);
                $skippedConflicts++;
            }
        }
    
        // 7) النتيجة
        if ($skippedConflicts > 0 && $attached === 0 && $createdStudents === 0 && $createdUsers === 0 && $restoredStudents === 0 && $restoredUsers === 0) {
            return response()->json([
                'status'             => 'error',
                'message'            => 'تعذر إضافة أي طالب إلى هذه المجموعة. السبب الشائع هو أن الطالب مسجل في برنامج/مسار مختلف، أو أن البيانات ناقصة.',
                'created_users'      => 0,
                'restored_users'     => 0,
                'created_students'   => 0,
                'restored_students'  => 0,
                'attached_to_group'  => 0,
                'skipped_missing'    => $skippedMissing,
                'skipped_conflicts'  => $skippedConflicts,
                'errors'             => $errors,
            ], 422);
        }

        return response()->json([
            'status'             => 'success',
            'created_users'      => $createdUsers,
            'restored_users'     => $restoredUsers,
            'created_students'   => $createdStudents,
            'restored_students'  => $restoredStudents,
            'attached_to_group'  => $attached,
            'skipped_missing'    => $skippedMissing,
            'skipped_conflicts'  => $skippedConflicts,
            'errors'             => $errors,
        ]);
    }

    // POST /api/v1/student-groups/import-external (Placeholder)
    public function importExternal(Request $request)
    {
        $request->validate([
            'url'      => 'required|url',
            'group_id' => 'required|integer|exists:student_groups,group_id',
        ]);

        return response()->json([
            'message' => 'Import from external API is not enabled yet.',
        ], 501);
    }

    private function normalizeHeader(string $h): string
    {
        // تنظيف مبسط للاسم
        $h = trim($h);
        $h = preg_replace('/\s+/u', ' ', $h);
        $h = mb_strtolower($h);
    
        return match ($h) {
            // معرف الطالب
            'student_id', 'id', 'رقم_الطالب', 'رقم الطالب' => 'student_id',
    
            // الرقم الجامعي
            'academic_number', 'student_no', 'student number', 'رقم_جامعي', 'الرقم الجامعي', 'رقم الجامعي' => 'academic_number',
    
            // الاسم
            'full_name', 'name', 'الاسم', 'اسم' => 'full_name',
    
            // البريد الإلكتروني
            'email', 'البريد', 'البريد الالكتروني', 'البريد الإلكتروني', 'الإيميل', 'الايميل' => 'email',
    
            // الجوال/الهاتف
            'phone', 'رقم', 'الجوال', 'الهاتف', 'رقم الجوال', 'رقم الهاتف' => 'phone',
    
            // الجنس
            'gender', 'الجنس', 'sex', 'type' => 'gender',
    
            default => $h,
        };
    }
}