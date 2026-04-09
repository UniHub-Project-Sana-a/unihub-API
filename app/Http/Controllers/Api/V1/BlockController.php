<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Block;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index()
    {
        // جلب البلوكات مع المتطلبات والمجاورات
        $blocks = Block::with(['prerequisites:id,block_name', 'concurrents:id,block_name'])->get();
        return response()->json($blocks);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'block_name' => 'required|string',
            'block_number' => 'required|integer',
            'program_id' => 'required|exists:programs,id',
            'level_id' => 'nullable|exists:levels,id',
            'weight' => 'numeric',
            'type' => 'required|in:compulsory,elective',
            // الحقول الجديدة للعلاقات (مصفوفة من المعرفات)
            'prerequisites' => 'nullable|array',
            'concurrents' => 'nullable|array'
        ]);
    
        $block = Block::create($validated);
    
        // إضافة المتطلبات السابقة
        if ($request->has('prerequisites')) {
            foreach ($request->prerequisites as $preId) {
                $block->prerequisites()->attach($preId, ['relation_type' => 'prerequisite']);
            }
        }
    
        // إضافة البلوكات المجاورة
        if ($request->has('concurrents')) {
            foreach ($request->concurrents as $conId) {
                $block->concurrents()->attach($conId, ['relation_type' => 'concurrent']);
            }
        }
    
        return response()->json(['message' => 'تم الحفظ مع العلاقات', 'data' => $block->load(['prerequisites', 'concurrents'])]);
    }
    
    public function update(Request $request, Block $block) {
        $block->update($request->all());
    
        // تحديث العلاقات (نحذف القديم ونضيف الجديد لتسهيل العملية)
        if ($request->has('prerequisites')) {
            $block->prerequisites()->detach(); // حذف العلاقات القديمة من نوع متطلب
            foreach ($request->prerequisites as $preId) {
                $block->prerequisites()->attach($preId, ['relation_type' => 'prerequisite']);
            }
        }
    
        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    public function destroy(Block $block) {
        $block->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}
