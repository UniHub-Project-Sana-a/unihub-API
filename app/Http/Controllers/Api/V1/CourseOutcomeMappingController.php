<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLearningOutcome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseOutcomeMappingController extends Controller
{
    public function index(int $courseId): JsonResponse
    {
        Course::findOrFail($courseId);
        $outcomes = CourseLearningOutcome::with(['teachingStrategies:id,name', 'assessmentMethods:id,name'])
            ->where('course_id', $courseId)->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $outcomes->map(fn ($outcome) => [
                'clo_id' => $outcome->code,
                'teaching_strategies' => $outcome->teachingStrategies->pluck('id')->values(),
                'assessment_methods' => $outcome->assessmentMethods->pluck('id')->values(),
            ]),
        ]);
    }

    public function store(Request $request, int $courseId): JsonResponse
    {
        return $this->save($request, $courseId, null);
    }

    public function update(Request $request, int $courseId, string $cloCode): JsonResponse
    {
        return $this->save($request, $courseId, $cloCode);
    }

    private function save(Request $request, int $courseId, ?string $cloCode): JsonResponse
    {
        $validated = $request->validate([
            'clo_id' => 'required|string',
            'teaching_strategies' => 'array',
            'teaching_strategies.*' => 'integer|exists:teaching_strategies,id',
            'assessment_methods' => 'array',
            'assessment_methods.*' => 'integer|exists:assessment_methods,id',
        ]);

        $outcome = CourseLearningOutcome::where('course_id', $courseId)
            ->where('code', $cloCode ?? $validated['clo_id'])->firstOrFail();
        $outcome->teachingStrategies()->sync($validated['teaching_strategies'] ?? []);
        $outcome->assessmentMethods()->sync($validated['assessment_methods'] ?? []);

        return response()->json(['success' => true, 'data' => $validated]);
    }
}
