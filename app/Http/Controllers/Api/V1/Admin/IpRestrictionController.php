<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IpRestriction;

class IpRestrictionController extends Controller
{
    // جلب كل القواعد
    public function index()
    {
        $rules = IpRestriction::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $rules]);
    }

    // إضافة قاعدة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:whitelist,blacklist',
            'address' => 'required|string', // يمكن إضافة تحقق من صحة IP هنا
            'description' => 'required|string|max:255'
        ]);

        $rule = IpRestriction::create([
            'type' => $request->type,
            'ip_address' => $request->address,
            'description' => $request->description,
            'is_active' => true
        ]);

        return response()->json(['message' => 'تم إضافة القاعدة بنجاح', 'data' => $rule]);
    }

    // حذف قاعدة
    public function destroy($id)
    {
        $rule = IpRestriction::find($id);
        if ($rule) {
            $rule->delete();
            return response()->json(['message' => 'تم الحذف بنجاح']);
        }
        return response()->json(['message' => 'غير موجود'], 404);
    }
}