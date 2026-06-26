<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLearningOutcome;
use App\Models\ProgramLearningOutcome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseLearningOutcomeController extends Controller
{
    /**
     * ✅ GET /api/v1/courses/{course_id}/learning-outcomes
     */
    public function index(int $courseId): JsonResponse
    {
        try {
            $course = Course::with('program')->findOrFail($courseId);
            
            $outcomes = CourseLearningOutcome::with('programLearningOutcome')
                ->where('course_id', $courseId)
                ->ordered()
                ->get();

            // إحصائيات
            $stats = [
                'total_count' => $outcomes->count(),
                'total_weight' => $outcomes->sum('weight'),
                'course_weight' => $course->weight ?? 0,
                'remaining_weight' => ($course->weight ?? 0) - $outcomes->sum('weight'),
                'by_domain' => [
                    'Knowledge' => $outcomes->where('domain', 'Knowledge')->count(),
                    'Intellectual' => $outcomes->where('domain', 'Intellectual')->count(),
                    'Professional' => $outcomes->where('domain', 'Professional')->count(),
                    'General' => $outcomes->where('domain', 'General')->count(),
                ],
                'can_add_more' => $outcomes->count() < 8,
            ];

            return response()->json([
                'success' => true,
                'message' => 'تم جلب مخرجات التعلم بنجاح',
                'data' => $outcomes,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_code' => $course->course_code,
                    'course_name_ar' => $course->course_name_ar,
                    'weight' => $course->weight,
                ],
                'stats' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب البيانات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ POST /api/v1/courses/{course_id}/learning-outcomes
     */
    public function store(Request $request, int $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
    
            if (!CourseLearningOutcome::canAddMore($courseId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة أكثر من 8 مخرجات تعلم للمقرر',
                ], 422);
            }
    
            $validated = $request->validate([
                'domain' => 'required|in:Knowledge,Intellectual,Professional,General',
                'description' => 'required|string|min:10',
                'weight' => 'required|numeric|min:0|max:100',
                'plo_id' => 'required|exists:program_learning_outcomes,plo_id', // ✅ إجباري
                'order' => 'nullable|integer|min:1',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());
    
            // ✅ التحقق من مطابقة المجال
            $plo = ProgramLearningOutcome::findOrFail($validated['plo_id']);
            if ($plo->domain !== $validated['domain']) {
                return response()->json([
                    'success' => false,
                    'message' => 'مخرج البرنامج المحدد لا يطابق مجال مخرج المقرر',
                ], 422);
            }
    
            // ✅ التحقق من مجموع الأوزان
            $currentWeight = CourseLearningOutcome::getTotalWeight($courseId);
            if (($currentWeight + $validated['weight']) > ($course->weight ?? 100)) {
                return response()->json([
                    'success' => false,
                    'message' => sprintf(
                        'مجموع الأوزان سيتجاوز وزن المقرر. الحالي: %.2f%%, المقرر: %.2f%%',
                        $currentWeight,
                        $course->weight ?? 0
                    ),
                ], 422);
            }
    
            $code = CourseLearningOutcome::generateNextCode($courseId, $validated['domain']);
            $order = $validated['order'] ?? (CourseLearningOutcome::where('course_id', $courseId)->max('order') + 1);
    
            $outcome = CourseLearningOutcome::create([
                'course_id' => $courseId,
                'code' => $code,
                'domain' => $validated['domain'],
                'description' => $validated['description'],
                'weight' => $validated['weight'],
                'plo_id' => $validated['plo_id'],
                'plo_weight' => $plo->weight ?? 0, // ✅ حفظ وزن PLO
                'order' => $order,
                'is_active' => $validated['is_active'] ?? true,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء مخرج التعلم بنجاح',
                'data' => $outcome->load('programLearningOutcome'),
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الإنشاء: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ GET /api/v1/courses/{course_id}/learning-outcomes/{clo_id}
     */
    public function show(int $courseId, int $cloId): JsonResponse
    {
        try {
            $outcome = CourseLearningOutcome::with('programLearningOutcome')
                ->where('course_id', $courseId)
                ->where('clo_id', $cloId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $outcome,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'مخرج التعلم غير موجود',
            ], 404);
        }
    }

    /**
     * ✅ PUT /api/v1/courses/{course_id}/learning-outcomes/{clo_id}
     */
    public function update(Request $request, int $courseId, int $cloId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
            $outcome = CourseLearningOutcome::where('course_id', $courseId)
                ->where('clo_id', $cloId)
                ->firstOrFail();

            $validated = $request->validate([
                'domain' => 'in:Knowledge,Intellectual,Professional,General',
                'description' => 'string|min:10',
                'weight' => 'numeric|min:0|max:100',
                'plo_id' => 'nullable|exists:program_learning_outcomes,plo_id',
                'order' => 'integer|min:1',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            // ✅ التحقق من تغيير المجال
            if (isset($validated['domain']) && $validated['domain'] !== $outcome->domain) {
                // التحقق من الحد الأقصى للمجال الجديد
                if (!CourseLearningOutcome::canAddMoreInDomain($courseId, $validated['domain'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن إضافة أكثر من مخرجين في المجال الجديد',
                    ], 422);
                }

                // توليد رمز جديد
                $validated['code'] = CourseLearningOutcome::generateNextCode($courseId, $validated['domain']);
            }

            // ✅ التحقق من مجموع الأوزان
            if (isset($validated['weight'])) {
                $currentWeight = CourseLearningOutcome::getTotalWeight($courseId, $cloId);
                if (($currentWeight + $validated['weight']) > ($course->weight ?? 100)) {
                    return response()->json([
                        'success' => false,
                        'message' => sprintf(
                            'مجموع الأوزان سيتجاوز وزن المقرر. الحالي: %.2f%%, المقرر: %.2f%%',
                            $currentWeight,
                            $course->weight ?? 0
                        ),
                    ], 422);
                }
            }

            // ✅ تحديث وزن PLO
            if (isset($validated['plo_id'])) {
                $plo = ProgramLearningOutcome::find($validated['plo_id']);
                if ($plo) {
                    $domainToCheck = $validated['domain'] ?? $outcome->domain;
                    if ($plo->domain !== $domainToCheck) {
                        return response()->json([
                            'success' => false,
                            'message' => 'مخرج البرنامج المحدد لا يطابق مجال مخرج المقرر',
                        ], 422);
                    }
                    $validated['plo_weight'] = $plo->weight;
                }
            }

            $outcome->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث مخرج التعلم بنجاح',
                'data' => $outcome->fresh()->load('programLearningOutcome'),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ DELETE /api/v1/courses/{course_id}/learning-outcomes/{clo_id}
     */
    public function destroy(int $courseId, int $cloId): JsonResponse
    {
        try {
            $outcome = CourseLearningOutcome::where('course_id', $courseId)
                ->where('clo_id', $cloId)
                ->firstOrFail();

            $outcome->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف مخرج التعلم بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الحذف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ GET /api/v1/courses/{course_id}/learning-outcomes/stats
     */
    public function stats(int $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
            $outcomes = CourseLearningOutcome::where('course_id', $courseId)->get();

            $stats = [
                'total_count' => $outcomes->count(),
                'max_allowed' => 8,
                'can_add_more' => $outcomes->count() < 8,
                'total_weight' => $outcomes->sum('weight'),
                'course_weight' => $course->weight ?? 0,
                'remaining_weight' => ($course->weight ?? 0) - $outcomes->sum('weight'),
                'weight_valid' => $outcomes->sum('weight') <= ($course->weight ?? 100),
                'by_domain' => [
                    'Knowledge' => [
                        'count' => $outcomes->where('domain', 'Knowledge')->count(),
                        'max' => 2,
                        'can_add' => $outcomes->where('domain', 'Knowledge')->count() < 2,
                        'weight' => $outcomes->where('domain', 'Knowledge')->sum('weight'),
                    ],
                    'Intellectual' => [
                        'count' => $outcomes->where('domain', 'Intellectual')->count(),
                        'max' => 2,
                        'can_add' => $outcomes->where('domain', 'Intellectual')->count() < 2,
                        'weight' => $outcomes->where('domain', 'Intellectual')->sum('weight'),
                    ],
                    'Professional' => [
                        'count' => $outcomes->where('domain', 'Professional')->count(),
                        'max' => 2,
                        'can_add' => $outcomes->where('domain', 'Professional')->count() < 2,
                        'weight' => $outcomes->where('domain', 'Professional')->sum('weight'),
                    ],
                    'General' => [
                        'count' => $outcomes->where('domain', 'General')->count(),
                        'max' => 2,
                        'can_add' => $outcomes->where('domain', 'General')->count() < 2,
                        'weight' => $outcomes->where('domain', 'General')->sum('weight'),
                    ],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ GET /api/v1/courses/{course_id}/learning-outcomes/domain/{domain}
     */
    public function byDomain(int $courseId, string $domain): JsonResponse
    {
        try {
            $outcomes = CourseLearningOutcome::with('programLearningOutcome')
                ->where('course_id', $courseId)
                ->where('domain', $domain)
                ->ordered()
                ->get();

            return response()->json([
                'success' => true,
                'domain' => $domain,
                'count' => $outcomes->count(),
                'max_allowed' => 2,
                'can_add_more' => $outcomes->count() < 2,
                'data' => $outcomes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * رسائل التحقق بالعربية
     */
    private function getArabicMessages(): array
    {
        return [
            'domain.required' => 'المجال مطلوب',
            'domain.in' => 'المجال غير صحيح',
            'description.required' => 'الوصف مطلوب',
            'description.min' => 'الوصف يجب أن يكون 10 أحرف على الأقل',
            'weight.required' => 'الوزن مطلوب',
            'weight.numeric' => 'الوزن يجب أن يكون رقماً',
            'plo_id.required' => 'يجب ربط المخرج بمخرج تعلم البرنامج', // ✅ إضافة
            'plo_id.exists' => 'مخرج البرنامج غير موجود',
        ];
    }
}