<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssessmentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssessmentMethodController extends Controller
{
    /**
     * عرض جميع طرق التقييم المرجعية
     * GET /api/v1/assessment-methods
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AssessmentMethod::query();

            // فلترة حسب الفئة
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // فلترة النشطة فقط
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $methods = $query->orderBy('order')->get()->groupBy('category');

            return response()->json([
                'success' => true,
                'methods' => $methods,
                'total_count' => AssessmentMethod::count(),
                'active_count' => AssessmentMethod::where('is_active', true)->count(),
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
     * POST /api/v1/assessment-methods
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|unique:assessment_methods,name',
                'description' => 'nullable|string',
                'category' => 'required|in:exam,assignment,project,presentation,participation,portfolio,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $method = AssessmentMethod::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'order' => $validated['order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء طريقة التقييم بنجاح',
                'data' => $method,
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
     * GET /api/v1/assessment-methods/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $method = AssessmentMethod::with('courseLearningOutcomes')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $method,
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
     * PUT /api/v1/assessment-methods/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $method = AssessmentMethod::findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:200|unique:assessment_methods,name,' . $id,
                'description' => 'nullable|string',
                'category' => 'in:exam,assignment,project,presentation,participation,portfolio,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $method->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث طريقة التقييم بنجاح',
                'data' => $method,
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
     * DELETE /api/v1/assessment-methods/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $method = AssessmentMethod::findOrFail($id);

            // التحقق من عدم ارتباطها بمخرجات التعلم
            if ($method->courseLearningOutcomes()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف طريقة تقييم مرتبطة بمخرجات التعلم',
                ], 422);
            }

            $method->delete();

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
            'name.required' => 'اسم الطريقة مطلوب',
            'name.unique' => 'هذه الطريقة موجودة بالفعل',
            'category.required' => 'الفئة مطلوبة',
        ];
    }
}