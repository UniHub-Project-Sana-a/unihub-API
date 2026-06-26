<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TeachingStrategy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeachingStrategyController extends Controller
{
    /**
     * عرض جميع استراتيجيات التدريس
     * GET /api/v1/teaching-strategies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TeachingStrategy::query();

            // فلترة حسب الفئة
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // فلترة النشطة فقط
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $strategies = $query->orderBy('order')->get()->groupBy('category');

            return response()->json([
                'success' => true,
                'strategies' => $strategies,
                'total_count' => TeachingStrategy::count(),
                'active_count' => TeachingStrategy::where('is_active', true)->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء استراتيجية تدريس جديدة
     * POST /api/v1/teaching-strategies
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|unique:teaching_strategies,name',
                'description' => 'nullable|string',
                'category' => 'required|in:lecture,practical,discussion,collaboration,project_based,problem_solving,simulation,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $strategy = TeachingStrategy::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'order' => $validated['order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الاستراتيجية بنجاح',
                'data' => $strategy,
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
     * عرض استراتيجية محددة
     * GET /api/v1/teaching-strategies/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $strategy = TeachingStrategy::with('courseLearningOutcomes')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $strategy,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الاستراتيجية غير موجودة',
            ], 404);
        }
    }

    /**
     * تحديث استراتيجية
     * PUT /api/v1/teaching-strategies/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $strategy = TeachingStrategy::findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:200|unique:teaching_strategies,name,' . $id,
                'description' => 'nullable|string',
                'category' => 'in:lecture,practical,discussion,collaboration,project_based,problem_solving,simulation,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $strategy->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الاستراتيجية بنجاح',
                'data' => $strategy,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف استراتيجية
     * DELETE /api/v1/teaching-strategies/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $strategy = TeachingStrategy::findOrFail($id);

            // التحقق من عدم ارتباطها بمخرجات التعلم
            if ($strategy->courseLearningOutcomes()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف استراتيجية مرتبطة بمخرجات التعلم',
                ], 422);
            }

            $strategy->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الاستراتيجية بنجاح',
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
            'name.required' => 'اسم الاستراتيجية مطلوب',
            'name.unique' => 'هذه الاستراتيجية موجودة بالفعل',
            'category.required' => 'الفئة مطلوبة',
        ];
    }
}