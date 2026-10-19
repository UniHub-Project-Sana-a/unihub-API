<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Department\StoreDepartmentRequest;
use App\Http\Requests\V1\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Block;
use App\Models\BlockRelation;
use App\Models\Course;
use App\Models\CourseLearningOutcome;
use App\Models\Level;
use App\Models\Program;
use App\Models\ProgramLearningOutcome;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentsController extends Controller {
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:departments,programs,levels,program_learning_outcomes,blocks,semesters,block_relations,courses,course_learning_outcomes',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];
        $departmentIds = fn () => Department::withTrashed()
            ->where('college_id', $collegeId)
            ->select('department_id');
        $programIds = fn () => Program::withTrashed()
            ->whereIn('department_id', $departmentIds())
            ->select('program_id');
        $levelIds = fn () => Level::withTrashed()
            ->whereIn('program_id', $programIds())
            ->select('level_id');
        $blockIds = fn () => Block::withTrashed()
            ->where(function ($query) use ($programIds, $levelIds) {
                $query->whereIn('program_id', $programIds())
                    ->orWhereIn('level_id', $levelIds());
            })
            ->select('id');
        $courseScope = fn () => Course::withTrashed()
            ->where('college_id', $collegeId)
            ->where(function ($query) use ($departmentIds, $programIds) {
                $query->whereIn('department_id', $departmentIds())
                    ->orWhereIn('program_id', $programIds());
            });

        $queries = [
            'departments' => fn () => Department::onlyTrashed()->where('college_id', $collegeId),
            'programs' => fn () => Program::onlyTrashed()->whereIn('department_id', $departmentIds()),
            'levels' => fn () => Level::onlyTrashed()->whereIn('program_id', $programIds()),
            'program_learning_outcomes' => fn () => ProgramLearningOutcome::onlyTrashed()->whereIn('program_id', $programIds()),
            'blocks' => fn () => Block::onlyTrashed()->where(function ($query) use ($programIds, $levelIds) {
                $query->whereIn('program_id', $programIds())
                    ->orWhereIn('level_id', $levelIds());
            }),
            'semesters' => fn () => Semester::onlyTrashed()->whereIn('level_id', $levelIds()),
            'block_relations' => fn () => BlockRelation::onlyTrashed()->where(function ($query) use ($blockIds) {
                $query->whereIn('block_id', $blockIds())
                    ->orWhereIn('related_block_id', $blockIds());
            }),
            'courses' => fn () => $courseScope()->onlyTrashed(),
            'course_learning_outcomes' => fn () => CourseLearningOutcome::onlyTrashed()
                ->whereIn('course_id', $courseScope()->select('course_id')),
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'departments' => 'الأقسام',
                'programs' => 'البرامج',
                'levels' => 'المستويات',
                'program_learning_outcomes' => 'مخرجات تعلم البرامج',
                'blocks' => 'البلوكات',
                'semesters' => 'الفصول الدراسية',
                'block_relations' => 'علاقات البلوكات',
                'courses' => 'المقررات',
                'course_learning_outcomes' => 'مخرجات تعلم المقررات',
            ];

            $groups = [];
            foreach ($queries as $type => $queryFactory) {
                $groups[] = [
                    'type' => $type,
                    'label' => $labels[$type],
                    'records' => $queryFactory()->get()->map(function ($record) {
                        $data = $record->getAttributes();
                        $data['deleted_at'] = $record->deleted_at?->toISOString();

                        return [
                            'id' => $record->getKey(),
                            'name' => $record->department_name
                                ?? $record->program_name
                                ?? $record->level_name
                                ?? $record->block_name
                                ?? $record->semester_name
                                ?? $record->course_name
                                ?? $record->description
                                ?? $record->relation_type
                                ?? $record->code
                                ?? ('#' . $record->getKey()),
                            'deleted_at' => $record->deleted_at?->toISOString(),
                            'data' => $data,
                        ];
                    })->values(),
                ];
            }

            return response()->json(['groups' => $groups]);
        }

        $type = $validated['type'];
        $query = $queries[$type]()->whereKey((int) $validated['id']);
        $record = $query->firstOrFail();

        DB::transaction(function () use ($record, $validated) {
            if ($validated['action'] === 'restore') {
                $record->restore();
            } else {
                $record->forceDelete();
            }
        });

        return response()->json([
            'message' => $validated['action'] === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }

    public function index(\Illuminate\Http\Request $r)
{
    $q = \App\Models\Department::query()
        ->select(['department_id','department_name','department_code','college_id']);

    if ($r->filled('college_id')) {
        $q->where('college_id', (int) $r->college_id);
    }

    return response()->json($q->get());
}
    public function store(StoreDepartmentRequest $request)
{
    $data = $request->validated();
    $dept = \App\Models\Department::create([
        'department_name' => $data['department_name'],
        'department_code' => $data['department_code'] ?? null,
        'college_id'      => $data['college_id'],
    ]);
    return response()->json($dept->fresh(), 201);
}
    public function show(Department $department) {
        return response()->json($department->load('college'));
    }
    public function update(UpdateDepartmentRequest $request, Department $department) {
        $department->update($request->validated());
        return response()->json($department->load('college'));
    }
    public function destroy(Department $department) {
        $department->delete();
        return response()->json(['message' => 'Department deleted']);
    }
}