<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramLearningOutcome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramLearningOutcomeController extends Controller
{
        /**
     * ✅ خريطة الرموز حسب المجال
     */
    private function getDomainPrefix($domain): string
    {
        $prefixes = [
            'Knowledge' => 'A',
            'Intellectual' => 'B',
            'Professional' => 'C',
            'General' => 'D',
        ];
        
        return $prefixes[$domain] ?? 'A';
    }

    /**
     * ✅ التحقق من صحة الرمز
     */
    private function validateCodeFormat($code, $domain): bool
    {
        $prefix = $this->getDomainPrefix($domain);
        return preg_match("/^{$prefix}\d+$/", $code);
    }
    
    /**
     * ✅ GET /api/v1/program-learning-outcomes/{programId}
     */
    public function index($programId): JsonResponse
    {
        try {
            $program = Program::findOrFail($programId);
    
            $outcomes = ProgramLearningOutcome::where('program_id', $programId)
                ->orderBy('domain')
                ->orderBy('order')
                ->get()
                ->map(function ($outcome) {
                    return [
                        'plo_id' => $outcome->plo_id,
                        'code' => $outcome->code,
                        'domain' => $outcome->domain,
                        'description' => $outcome->description,
                        'weight' => (float) $outcome->weight, // ✅ تحويل صريح إلى float
                        'order' => $outcome->order,
                        'is_active' => (bool) $outcome->is_active,
                    ];
                });
    
            return response()->json([
                'success' => true,
                'message' => 'تم جلب مخرجات التعلم بنجاح',
                'data' => $outcomes,
                'program' => [
                    'program_id' => $program->program_id,
                    'program_name' => $program->program_name,
                ],
                'total_count' => $outcomes->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب البيانات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ POST /api/v1/program-learning-outcomes
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'program_id' => 'required|exists:programs,program_id',
                'code' => 'required|string|max:10',
                'domain' => 'required|in:Knowledge,Intellectual,Professional,General',
                'description' => 'required|string',
                'weight' => 'required|numeric|min:0|max:100',
                'is_active' => 'boolean',
                'order' => 'required|integer|min:1',
            ], $this->getArabicMessages());

            // ✅ التحقق من صحة نمط الرمز
            if (!$this->validateCodeFormat($validated['code'], $validated['domain'])) {
                $prefix = $this->getDomainPrefix($validated['domain']);
                return response()->json([
                    'success' => false,
                    'message' => "الرمز يجب أن يبدأ بـ {$prefix} ثم رقم (مثال: {$prefix}1)",
                ], 422);
            }

            // ✅ التحقق من عدم تكرار الرمز في نفس البرنامج
            $codeExists = ProgramLearningOutcome::where('program_id', $validated['program_id'])
                ->where('code', strtoupper($validated['code']))
                ->exists();

            if ($codeExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرمز موجود مسبقاً في هذا البرنامج',
                ], 422);
            }

            // ✅ التحقق من عدم تكرار الترتيب في نفس البرنامج
            $orderExists = ProgramLearningOutcome::where('program_id', $validated['program_id'])
                ->where('order', $validated['order'])
                ->exists();

            if ($orderExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الترتيب مستخدم بالفعل. يرجى اختيار رقم آخر',
                ], 422);
            }

            // ✅ التحقق من مجموع الأوزان
            $currentTotalWeight = ProgramLearningOutcome::where('program_id', $validated['program_id'])
                ->sum('weight');

            if (($currentTotalWeight + $validated['weight']) > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'مجموع الأوزان سيتجاوز 100%. المجموع الحالي: ' . $currentTotalWeight . '%',
                ], 422);
            }

            $outcome = ProgramLearningOutcome::create([
                'program_id' => $validated['program_id'],
                'code' => strtoupper($validated['code']),
                'domain' => $validated['domain'],
                'description' => $validated['description'],
                'weight' => $validated['weight'],
                'is_active' => $validated['is_active'] ?? true,
                'order' => $validated['order'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء مخرج التعلم بنجاح',
                'data' => $outcome,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في الإنشاء: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ GET /api/v1/program-learning-outcomes/{programId}/{ploId}
     */
    public function show($programId, $ploId): JsonResponse
    {
        try {
            $outcome = ProgramLearningOutcome::where('program_id', $programId)
                ->where('plo_id', $ploId)
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
     * ✅ PUT /api/v1/program-learning-outcomes/{ploId}
     */
    public function update(Request $request, $ploId): JsonResponse
    {
        try {
            $outcome = ProgramLearningOutcome::findOrFail($ploId);

            $validated = $request->validate([
                'code' => 'string|max:10',
                'domain' => 'in:Knowledge,Intellectual,Professional,General',
                'description' => 'string',
                'weight' => 'numeric|min:0|max:100',
                'is_active' => 'boolean',
                'order' => 'integer|min:1',
            ], $this->getArabicMessages());

            // ✅ التحقق من صحة نمط الرمز
            if (isset($validated['code']) && isset($validated['domain'])) {
                if (!$this->validateCodeFormat($validated['code'], $validated['domain'])) {
                    $prefix = $this->getDomainPrefix($validated['domain']);
                    return response()->json([
                        'success' => false,
                        'message' => "الرمز يجب أن يبدأ بـ {$prefix} ثم رقم (مثال: {$prefix}1)",
                    ], 422);
                }
            } elseif (isset($validated['code'])) {
                if (!$this->validateCodeFormat($validated['code'], $outcome->domain)) {
                    $prefix = $this->getDomainPrefix($outcome->domain);
                    return response()->json([
                        'success' => false,
                        'message' => "الرمز يجب أن يبدأ بـ {$prefix} ثم رقم (مثال: {$prefix}1)",
                    ], 422);
                }
            }

            // ✅ التحقق من عدم تكرار الرمز
            if (isset($validated['code'])) {
                $codeExists = ProgramLearningOutcome::where('program_id', $outcome->program_id)
                    ->where('code', strtoupper($validated['code']))
                    ->where('plo_id', '!=', $ploId)
                    ->exists();

                if ($codeExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الرمز موجود مسبقاً في هذا البرنامج',
                    ], 422);
                }

                $validated['code'] = strtoupper($validated['code']);
            }

            // ✅ التحقق من عدم تكرار الترتيب
            if (isset($validated['order'])) {
                $orderExists = ProgramLearningOutcome::where('program_id', $outcome->program_id)
                    ->where('order', $validated['order'])
                    ->where('plo_id', '!=', $ploId)
                    ->exists();

                if ($orderExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'رقم الترتيب مستخدم بالفعل. يرجى اختيار رقم آخر',
                    ], 422);
                }
            }

            // ✅ التحقق من مجموع الأوزان
            if (isset($validated['weight'])) {
                $currentTotalWeight = ProgramLearningOutcome::where('program_id', $outcome->program_id)
                    ->where('plo_id', '!=', $ploId)
                    ->sum('weight');

                if (($currentTotalWeight + $validated['weight']) > 100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'مجموع الأوزان سيتجاوز 100%. المجموع الحالي (بدون هذا المخرج): ' . $currentTotalWeight . '%',
                    ], 422);
                }
            }

            $outcome->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث مخرج التعلم بنجاح',
                'data' => $outcome->fresh(),
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
     * ✅ DELETE /api/v1/program-learning-outcomes/{ploId}
     */
    public function destroy($ploId): JsonResponse
    {
        try {
            $outcome = ProgramLearningOutcome::findOrFail($ploId);
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
     * رسائل التحقق بالعربية
     */
    private function getArabicMessages(): array
    {
        return [
            'program_id.required' => 'معرف البرنامج مطلوب',
            'program_id.exists' => 'البرنامج غير موجود',
            'code.required' => 'الرمز مطلوب',
            'code.unique' => 'الرمز موجود بالفعل في هذا البرنامج',
            'domain.required' => 'المجال مطلوب',
            'domain.in' => 'المجال غير صحيح',
            'description.required' => 'الوصف مطلوب',
            'weight.required' => 'الوزن مطلوب',
            'weight.numeric' => 'الوزن يجب أن يكون رقماً',
            'weight.min' => 'الوزن يجب أن يكون 0 على الأقل',
            'weight.max' => 'الوزن يجب ألا يتجاوز 100',
            'order.required' => 'الترتيب مطلوب',
            'order.integer' => 'الترتيب يجب أن يكون رقماً صحيحاً',
            'order.min' => 'الترتيب يجب أن يكون 1 على الأقل',
        ];
    }
}