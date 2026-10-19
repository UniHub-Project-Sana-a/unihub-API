<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseAssignmentController extends Controller
{
    /**
     * عرض جميع التكليفات والأنشطة
     * GET /api/v1/courses/{course_id}/assignments
     */
    public function index($courseId, Request $request): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $query = CourseAssignment::where('course_id', $courseId);

            // فلترة حسب الجزء
            if ($request->has('part')) {
                $query->where('part', $request->part);
            }

            // فلترة حسب نوع التكليف
            if ($request->has('assignment_type')) {
                $query->where('assignment_type', $request->assignment_type);
            }

            // فلترة التكاليف الإجبارية
            if ($request->has('mandatory_only') && $request->boolean('mandatory_only')) {
                $query->where('is_mandatory', true);
            }

            $assignments = $query->orderBy('part')->orderBy('week')->get();

            // حساب الإجماليات
            $summary = [];
            foreach (['نظري', 'عملي', 'تمارين', 'سريري'] as $part) {
                $partAssignments = $assignments->where('part', $part);
                if ($partAssignments->isNotEmpty()) {
                    $summary[$part] = [
                        'count' => $partAssignments->count(),
                        'total_grade' => $partAssignments->sum('grade'),
                        'mandatory_count' => $partAssignments->where('is_mandatory', true)->count(),
                    ];
                }
            }

            $totalGrade = $assignments->sum('grade');

            return response()->json([
                'success' => true,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                ],
                'assignments' => $assignments,
                'summary' => $summary,
                'total_grade' => $totalGrade,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء تكليف جديد
     * POST /api/v1/courses/{course_id}/assignments
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'part' => 'required|in:نظري,عملي,تمارين,سريري',
                'title' => 'required|string|max:300',
                'description' => 'nullable|string',
                'week' => 'required|integer|min:1|max:16',
                'grade' => 'required|numeric|min:0.5',
                'clo_ids' => 'nullable|array',
                'clo_ids.*' => 'string',
                'assignment_type' => 'required|in:homework,project,presentation,quiz,other',
                'is_mandatory' => 'boolean',
                'notes' => 'nullable|string',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);

            // التحقق من صحة الأسبوع
            if ($validated['week'] < 1 || $validated['week'] > 16) {
                return response()->json([
                    'success' => false,
                    'message' => 'الأسبوع يجب أن يكون بين 1 و 16',
                ], 422);
            }

            $assignment = CourseAssignment::create([
                'course_id' => $courseId,
                'part' => $validated['part'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'week' => $validated['week'],
                'grade' => $validated['grade'],
                'clo_ids' => $validated['clo_ids'] ?? [],
                'assignment_type' => $validated['assignment_type'],
                'is_mandatory' => $validated['is_mandatory'] ?? true,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التكليف بنجاح',
                'data' => $assignment,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض تكليف محدد
     * GET /api/v1/courses/{course_id}/assignments/{assignment_id}
     */
    public function show($courseId, $assignmentId): JsonResponse
    {
        try {
            $assignment = CourseAssignment::where('course_id', $courseId)
                ->where('assignment_id', $assignmentId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $assignment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'التكليف غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث تكليف
     * PUT /api/v1/courses/{course_id}/assignments/{assignment_id}
     */
    public function update(Request $request, $courseId, $assignmentId): JsonResponse
    {
        try {
            $assignment = CourseAssignment::where('course_id', $courseId)
                ->where('assignment_id', $assignmentId)
                ->firstOrFail();

            $validated = $request->validate([
                'title' => 'string|max:300',
                'description' => 'nullable|string',
                'week' => 'integer|min:1|max:16',
                'grade' => 'numeric|min:0.5',
                'clo_ids' => 'nullable|array',
                'assignment_type' => 'in:homework,project,presentation,quiz,other',
                'is_mandatory' => 'boolean',
                'notes' => 'nullable|string',
            ], $this->getArabicMessages());

            $assignment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث التكليف بنجاح',
                'data' => $assignment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف تكليف
     * DELETE /api/v1/courses/{course_id}/assignments/{assignment_id}
     */
    public function destroy($courseId, $assignmentId): JsonResponse
    {
        try {
            $assignment = CourseAssignment::where('course_id', $courseId)
                ->where('assignment_id', $assignmentId)
                ->firstOrFail();

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التكليف بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getArabicMessages()
    {
        return [
            'part.required' => 'الجزء مطلوب',
            'title.required' => 'عنوان التكليف مطلوب',
            'week.required' => 'الأسبوع مطلوب',
            'grade.required' => 'الدرجة مطلوبة',
            'grade.min' => 'الدرجة يجب أن تكون 0.5 على الأقل',
            'assignment_type.required' => 'نوع التكليف مطلوب',
        ];
    }
}