<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TeachingStrategy;
use App\Models\ProgramOptionAudit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

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

            if ($request->filled('program_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('program_id', (int) $request->program_id)
                        ->orWhereNull('program_id');
                });
            }

            // فلترة حسب الفئة
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // فلترة النشطة فقط
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $strategies = $query->orderBy('order')->get();

            return response()->json([
                'success' => true,
                'strategies' => $strategies,
                'data' => $strategies,
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
                'program_id' => 'nullable|integer|exists:programs,program_id',
                'name' => ['required', 'string', 'max:200', Rule::unique('teaching_strategies', 'name')->where(fn ($q) => $q->where('program_id', $request->program_id))],
                'description' => 'nullable|string',
                'category' => 'required|in:lecture,practical,discussion,collaboration,project_based,problem_solving,simulation,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $strategy = TeachingStrategy::create([
                'program_id' => $validated['program_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'order' => $validated['order'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            $this->audit($strategy, 'created', null, $strategy->toArray());

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
                'program_id' => 'nullable|integer|exists:programs,program_id',
                'name' => ['string', 'max:200', Rule::unique('teaching_strategies', 'name')->ignore($id)->where(fn ($q) => $q->where('program_id', $request->program_id ?? $strategy->program_id))],
                'description' => 'nullable|string',
                'category' => 'in:lecture,practical,discussion,collaboration,project_based,problem_solving,simulation,other',
                'order' => 'integer|min:0',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            $before = $strategy->toArray();
            $strategy->update($validated);
            $this->audit($strategy, 'updated', $before, $strategy->fresh()->toArray());

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

            $before = $strategy->toArray();
            $this->audit($strategy, 'deleted', $before, null);
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

    private function audit(TeachingStrategy $strategy, string $action, ?array $before, ?array $after): void
    {
        if (!$strategy->program_id) return;
        ProgramOptionAudit::create([
            'program_id' => $strategy->program_id,
            'option_type' => 'teaching_strategy',
            'option_id' => $strategy->id,
            'action' => $action,
            'details' => ['before' => $before, 'after' => $after],
            'changed_by' => request()->user()?->getAuthIdentifier(),
        ]);
    }
}