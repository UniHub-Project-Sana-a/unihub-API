<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Block;
use App\Models\Program;
use Illuminate\Support\Facades\Log;

class BlockController extends Controller
{
    /**
     * جلب البلوكات
     */
    public function index(Request $request)
    {
        try {
            $query = Block::query();

            // ✅ فلترة حسب البرنامج
            if ($request->has('program_id')) {
                $query->where('program_id', $request->program_id);
            }

            // ✅ فلترة حسب المستوى (اختياري)
            if ($request->has('level_id')) {
                $query->where('level_id', $request->level_id);
            }

            $blocks = $query->with(['prerequisites', 'concurrents'])->get();

            return response()->json($blocks);

        } catch (\Exception $e) {
            Log::error('Error fetching blocks: ' . $e->getMessage());
            return response()->json(['error' => 'فشل تحميل البلوكات'], 500);
        }
    }

    /**
     * إضافة بلوك جديد
     */
    public function store(Request $request)
    {
        try {
            // ✅ التحقق من البرنامج لتحديد نوع النظام
            $program = Program::where('program_id', $request->program_id)->first();
            
            if (!$program) {
                return response()->json(['message' => 'البرنامج غير موجود'], 404);
            }

            // ✅ تحديد Validation Rules حسب نوع النظام
            $rules = [
                'block_name' => 'required|string|max:255',
                'block_number' => 'required|integer',
                'program_id' => 'required|exists:programs,program_id',
                'weight' => 'nullable|numeric|min:0|max:100',
                'weeks' => 'required|integer|min:1',
                'type' => 'required|in:compulsory,elective',
                'description' => 'nullable|string',
                'prerequisites' => 'nullable|array',
                'concurrents' => 'nullable|array',
            ];

            // ✅ في نظام الساعات المعتمدة: الساعات مطلوبة، المستوى غير مطلوب
            if ($program->academic_system === 'credit' && $program->block_based) {
                $rules['credit_hours'] = 'required|integer|min:0';
                $rules['level_id'] = 'nullable';
            } 
            // ✅ في نظام الفصول + بلوكات: المستوى مطلوب، الساعات اختيارية
            else if ($program->academic_system === 'semester' && $program->block_based) {
                $rules['level_id'] = 'required|exists:levels,level_id';
                $rules['credit_hours'] = 'nullable|integer';
            }

            $validated = $request->validate($rules);

            // ✅ التحقق من تكرار رقم البلوك
            $existsQuery = Block::where('program_id', $request->program_id)
                                ->where('block_number', $request->block_number);

            if ($request->has('level_id') && $request->level_id) {
                $existsQuery->where('level_id', $request->level_id);
            } else {
                $existsQuery->whereNull('level_id');
            }

            if ($existsQuery->exists()) {
                return response()->json([
                    'message' => 'خطأ: رقم البلوك هذا موجود مسبقاً'
                ], 422);
            }

            // ✅ إنشاء البلوك
            $blockData = $validated;
            
            // في نظام الساعات + بلوكات: level_id يكون null
            if ($program->academic_system === 'credit' && $program->block_based) {
                $blockData['level_id'] = null;
            }

            $block = Block::create($blockData);

            // إضافة المتطلبات السابقة
            if ($request->has('prerequisites') && is_array($request->prerequisites)) {
                foreach ($request->prerequisites as $preId) {
                    $block->prerequisites()->attach($preId, ['relation_type' => 'prerequisite']);
                }
            }

            // إضافة البلوكات المجاورة
            if ($request->has('concurrents') && is_array($request->concurrents)) {
                foreach ($request->concurrents as $conId) {
                    $block->concurrents()->attach($conId, ['relation_type' => 'concurrent']);
                }
            }

            return response()->json([
                'message' => 'تم حفظ البلوك بنجاح',
                'data' => $block->load(['prerequisites', 'concurrents'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating block: ' . $e->getMessage());
            return response()->json([
                'message' => 'فشل حفظ البلوك',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بلوك موجود
     */
    public function update(Request $request, $id)
    {
        try {
            $block = Block::findOrFail($id);
            $program = Program::where('program_id', $block->program_id)->first();

            // ✅ Validation Rules
            $rules = [
                'block_name' => 'sometimes|required|string|max:255',
                'block_number' => 'sometimes|required|integer',
                'weight' => 'nullable|numeric|min:0|max:100',
                'weeks' => 'sometimes|required|integer|min:1',
                'type' => 'sometimes|required|in:compulsory,elective',
                'description' => 'nullable|string',
                'prerequisites' => 'nullable|array',
                'concurrents' => 'nullable|array',
            ];

            if ($program && $program->academic_system === 'credit') {
                $rules['credit_hours'] = 'sometimes|required|integer|min:0';
            }

            $validated = $request->validate($rules);

            // ✅ التحقق من تكرار رقم البلوك
            if ($request->has('block_number')) {
                $existsQuery = Block::where('program_id', $block->program_id)
                                    ->where('block_number', $request->block_number)
                                    ->where('id', '!=', $block->id);

                if ($block->level_id) {
                    $existsQuery->where('level_id', $block->level_id);
                } else {
                    $existsQuery->whereNull('level_id');
                }

                if ($existsQuery->exists()) {
                    return response()->json([
                        'message' => 'رقم البلوك مستخدم بالفعل'
                    ], 422);
                }
            }

            // ✅ تحديث البيانات
            $updateData = $validated;
            
            // التأكد من credit_hours
            if (!isset($updateData['credit_hours']) || empty($updateData['credit_hours'])) {
                $updateData['credit_hours'] = 0;
            }

            $block->update($updateData);

            // تحديث المتطلبات السابقة
            if ($request->has('prerequisites')) {
                $syncData = [];
                foreach ($request->prerequisites as $preId) {
                    $syncData[$preId] = ['relation_type' => 'prerequisite'];
                }
                $block->prerequisites()->sync($syncData);
            }

            // تحديث البلوكات المجاورة
            if ($request->has('concurrents')) {
                $syncData = [];
                foreach ($request->concurrents as $conId) {
                    $syncData[$conId] = ['relation_type' => 'concurrent'];
                }
                $block->concurrents()->sync($syncData);
            }

            return response()->json([
                'message' => 'تم تحديث البلوك بنجاح',
                'data' => $block->load(['prerequisites', 'concurrents'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'البلوك غير موجود'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating block: ' . $e->getMessage());
            return response()->json([
                'message' => 'فشل تحديث البلوك',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف بلوك
     */
    public function destroy($id)
    {
        try {
            $block = Block::findOrFail($id);
            
            // التحقق من المقررات المرتبطة
            $coursesCount = $block->courses()->count();
            if ($coursesCount > 0) {
                return response()->json([
                    'message' => "لا يمكن حذف البلوك لأنه يحتوي على {$coursesCount} مقرر"
                ], 422);
            }

            // حذف العلاقات أولاً
            $block->prerequisites()->detach();
            $block->concurrents()->detach();

            $block->delete();

            return response()->json(['message' => 'تم حذف البلوك بنجاح']);

        } catch (\Exception $e) {
            Log::error('Error deleting block: ' . $e->getMessage());
            return response()->json(['message' => 'فشل حذف البلوك'], 500);
        }
    }
}