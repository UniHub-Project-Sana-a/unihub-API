<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class UserTypePermissionController extends Controller
{
    public function index(Request $request, int $userTypeId)
    {
        $request->validate([
            'college_id' => ['nullable','integer','exists:colleges,college_id'],
        ]);

        $query = DB::table('user_type_permissions')
            ->where('user_type_id', $userTypeId);

        if ($request->filled('college_id')) {
            $query->where('college_id', (int) $request->college_id);
        }

        $permissionIds = $query->pluck('permission_id');

        $perms = Permission::whereIn('permission_id', $permissionIds)
            ->orderBy('permission_name')
            ->get();

        return response()->json($perms);
    }

    public function bulkAssign($userTypeId, Request $request)
    {
        $validated = $request->validate([
            'permission_ids'   => ['required','array','min:1'],
            'permission_ids.*' => ['integer','exists:permissions,permission_id'],
            'college_ids'      => ['required','array','min:1'],
            'college_ids.*'    => ['integer','exists:colleges,college_id'],
            'mode'             => ['nullable','in:attach,sync'],
        ]);

        $permissionIds = $validated['permission_ids'];
        $collegeIds    = $validated['college_ids'];
        $mode          = $validated['mode'] ?? 'attach';

        DB::beginTransaction();
        try {
            if ($mode === 'sync') {
                DB::table('user_type_permissions')
                    ->where('user_type_id', $userTypeId)
                    ->whereIn('college_id', $collegeIds)
                    ->delete();
            }

            $now = now();
            $rows = [];
            foreach ($permissionIds as $pid) {
                foreach ($collegeIds as $cid) {
                    $rows[] = [
                        'user_type_id'  => (int)$userTypeId,
                        'permission_id' => (int)$pid,
                        'college_id'    => (int)$cid,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }

            DB::table('user_type_permissions')->upsert(
                $rows,
                ['user_type_id','permission_id','college_id'],
                ['updated_at']
            );

            DB::commit();
            return response()->json(['message' => 'تم تحديث صلاحيات نوع المستخدم.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء حفظ الصلاحيات.'], 500);
        }
    }
}