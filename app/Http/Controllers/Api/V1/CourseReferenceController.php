<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseReference;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseReferenceController extends Controller
{
    /**
     * عرض جميع المراجع
     * GET /api/v1/courses/{course_id}/references
     */
    public function index($courseId, Request $request): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $query = CourseReference::where('course_id', $courseId);

            // فلترة حسب النوع
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $references = $query->orderBy('type')->orderBy('order')->get()
                ->groupBy('type');

            return response()->json([
                'success' => true,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                ],
                'references' => $references,
                'summary' => [
                    'main_count' => CourseReference::where('course_id', $courseId)->where('type', 'main')->count(),
                    'support_count' => CourseReference::where('course_id', $courseId)->where('type', 'support')->count(),
                    'electronic_count' => CourseReference::where('course_id', $courseId)->where('type', 'electronic')->count(),
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
     * إنشاء مرجع جديد
     * POST /api/v1/courses/{course_id}/references
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:main,support,electronic',
                'category' => 'nullable|in:website,journal,other',
                'author' => 'nullable|string|max:300',
                'year' => 'nullable|integer|min:1900|max:2099',
                'title' => 'required|string|max:500',
                'edition' => 'nullable|string|max:100',
                'publisher' => 'nullable|string|max:300',
                'country' => 'nullable|string|max:100',
                'url' => 'nullable|url',
                'order' => 'integer|min:0',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);

            // التحقق من النوع
            if ($validated['type'] === 'electronic') {
                if (!isset($validated['category'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'فئة المصدر الإلكتروني مطلوبة',
                    ], 422);
                }
                if ($validated['category'] !== 'website' && !isset($validated['url'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الرابط مطلوب للمصادر الإلكترونية',
                    ], 422);
                }
            }

            $reference = CourseReference::create([
                'course_id' => $courseId,
                'type' => $validated['type'],
                'category' => $validated['category'] ?? null,
                'author' => $validated['author'] ?? null,
                'year' => $validated['year'] ?? null,
                'title' => $validated['title'],
                'edition' => $validated['edition'] ?? null,
                'publisher' => $validated['publisher'] ?? null,
                'country' => $validated['country'] ?? null,
                'url' => $validated['url'] ?? null,
                'order' => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المرجع بنجاح',
                'data' => $reference,
                'citation' => $reference->getHarvardCitation(),
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
     * عرض مرجع محدد
     * GET /api/v1/courses/{course_id}/references/{reference_id}
     */
    public function show($courseId, $referenceId): JsonResponse
    {
        try {
            $reference = CourseReference::where('course_id', $courseId)
                ->where('reference_id', $referenceId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $reference,
                'citation' => $reference->getHarvardCitation(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'المرجع غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث مرجع
     * PUT /api/v1/courses/{course_id}/references/{reference_id}
     */
    public function update(Request $request, $courseId, $referenceId): JsonResponse
    {
        try {
            $reference = CourseReference::where('course_id', $courseId)
                ->where('reference_id', $referenceId)
                ->firstOrFail();

            $validated = $request->validate([
                'author' => 'nullable|string|max:300',
                'year' => 'nullable|integer|min:1900|max:2099',
                'title' => 'string|max:500',
                'edition' => 'nullable|string|max:100',
                'publisher' => 'nullable|string|max:300',
                'country' => 'nullable|string|max:100',
                'url' => 'nullable|url',
                'order' => 'integer|min:0',
            ], $this->getArabicMessages());

            $reference->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المرجع بنجاح',
                'data' => $reference,
                'citation' => $reference->getHarvardCitation(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف مرجع
     * DELETE /api/v1/courses/{course_id}/references/{reference_id}
     */
    public function destroy($courseId, $referenceId): JsonResponse
    {
        try {
            $reference = CourseReference::where('course_id', $courseId)
                ->where('reference_id', $referenceId)
                ->firstOrFail();

            $reference->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المرجع بنجاح',
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
            'type.required' => 'نوع المرجع مطلوب',
            'title.required' => 'عنوان المرجع مطلوب',
            'url.url' => 'الرابط يجب أن يكون صحيحاً',
        ];
    }
}