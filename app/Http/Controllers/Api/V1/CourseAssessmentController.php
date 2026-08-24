<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseAssessment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseAssessmentController extends Controller
{
    /**
     * عرض جميع طرق التقييم
     * GET /api/v1/courses/{course_id}/assessments
     */
    public function index($courseId, Request $request): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $query = CourseAssessment::where('course_id', $courseId);

            // فلترة حسب نوع التقييم
            if ($request->has('assessment_type')) {
                $query->where('assessment_type', $request->assessment_type);
            }

            // فلترة حسب السنة الدراسية
            if ($request->has('academic_year')) {
                $query->where('academic_year', $request->academic_year);
            }

            $assessments = $query->orderBy('order')->get();

            // حساب الإجماليات
            $totalGrade = $assessments->sum('grade');
            $totalPercentage = $assessments->sum('percentage');

            // التحقق من التوازن
            $isBalanced = $totalPercentage == 100;

            return response()->json([
                'success' => true,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                ],
                'assessments' => $assessments,
                'summary' => [
                    'total_count' => $assessments->count(),
                    'total_grade' => round($totalGrade, 2),
                    'total_percentage' => round($totalPercentage, 2),
                    'is_balanced' => $isBalanced,
                    'status' => $isBalanced ? 'متوازن' : 'غير متوازن',
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء طريقة تقييم جديدة
     * POST /api/v1/courses/{course_id}/assessments
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:300',
                'week' => 'nullable|integer|min:0|max:16',
                'grade' => 'required|numeric|min:0.5',
                'percentage' => 'required|numeric|min:0|max:100',
                'clo_ids' => 'nullable|array',
                'clo_ids.*' => 'string',
                'assessment_type' => 'required|in:activities,quizzes,midterm_exam,final_exam,project,presentation,practical_exam,other',
                'order' => 'integer|min:0',
                'notes' => 'nullable|string',
                'academic_year' => 'nullable|string',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);

            // حساب مجموع النسب المئوية الحالية
            $currentTotal = CourseAssessment::where('course_id', $courseId)
                ->sum('percentage');

            $newTotal = $currentTotal + $validated['percentage'];

            if ($newTotal > 100) {
                return response()->json([
                    'success' => false,
                    'message' => "مجموع النسب المئوية سيتجاوز 100% (الحالي: {$currentTotal}%)",
                ], 422);
            }

            $assessment = CourseAssessment::create([
                'course_id' => $courseId,
                'college_id' => $course->college_id,
                'semester_id' => $course->semester_id,
                'name' => $validated['name'],
                'week' => $validated['week'] ?? null,
                'max_score' => $validated['grade'],
                'grade' => $validated['grade'],
                'percentage' => $validated['percentage'],
                'clo_ids' => $validated['clo_ids'] ?? [],
                'assessment_type' => $validated['assessment_type'],
                'order' => $validated['order'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'academic_year' => $validated['academic_year'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طريقة التقييم بنجاح',
                'data' => $assessment,
                'total_percentage' => $newTotal,
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
     * عرض طريقة تقييم محددة
     * GET /api/v1/courses/{course_id}/assessments/{assessment_id}
     */
    public function show($courseId, $assessmentId): JsonResponse
    {
        try {
            $assessment = CourseAssessment::where('course_id', $courseId)
                ->where('assessment_id', $assessmentId)
                ->with(['studentGrades'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $assessment,
                'grades_count' => $assessment->studentGrades->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'طريقة التقييم غير موجودة',
            ], 404);
        }
    }

    /**
     * تحديث طريقة تقييم
     * PUT /api/v1/courses/{course_id}/assessments/{assessment_id}
     */
    public function update(Request $request, $courseId, $assessmentId): JsonResponse
    {
        try {
            $assessment = CourseAssessment::where('course_id', $courseId)
                ->where('assessment_id', $assessmentId)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => 'string|max:300',
                'week' => 'nullable|integer|min:0|max:16',
                'grade' => 'numeric|min:0.5',
                'percentage' => 'numeric|min:0|max:100',
                'clo_ids' => 'nullable|array',
                'assessment_type' => 'in:activities,quizzes,midterm_exam,final_exam,project,presentation,practical_exam,other',
                'notes' => 'nullable|string',
            ], $this->getArabicMessages());

            // إذا تم تحديث النسبة المئوية، تحقق من المجموع
            if (isset($validated['percentage']) && $validated['percentage'] != $assessment->percentage) {
                $otherTotal = CourseAssessment::where('course_id', $courseId)
                    ->where('assessment_id', '!=', $assessmentId)
                    ->sum('percentage');

                $newTotal = $otherTotal + $validated['percentage'];

                if ($newTotal > 100) {
                    return response()->json([
                        'success' => false,
                        'message' => "مجموع النسب المئوية سيتجاوز 100% (المجموع الجديد: {$newTotal}%)",
                    ], 422);
                }
            }

            if (isset($validated['grade'])) {
                $validated['max_score'] = $validated['grade'];
            }

            $assessment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث طريقة التقييم بنجاح',
                'data' => $assessment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف طريقة تقييم
     * DELETE /api/v1/courses/{course_id}/assessments/{assessment_id}
     */
    public function destroy($courseId, $assessmentId): JsonResponse
    {
        try {
            $assessment = CourseAssessment::where('course_id', $courseId)
                ->where('assessment_id', $assessmentId)
                ->firstOrFail();

            // التحقق من وجود درجات
            if ($assessment->studentGrades()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف طريقة تقييم بها درجات للطلاب',
                ], 422);
            }

            $assessment->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف طريقة التقييم بنجاح',
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
            'name.required' => 'اسم طريقة التقييم مطلوب',
            'grade.required' => 'الدرجة مطلوبة',
            'percentage.required' => 'النسبة المئوية مطلوبة',
            'assessment_type.required' => 'نوع التقييم مطلوب',
        ];
    }
}