<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\V1\UpdateUserRequest;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function index(Request $request)
    {
        $q = $request->query('q');
        $perPage = (int)($request->query('per_page', 15));
        $collegeId = $request->query('college_id');
        $userTypeCode = $request->query('user_type_code');
    
        $usersQuery = \App\Models\User::query()
            ->with('userType:user_type_id,user_type_name,user_type_code') // اختياري
            ->when($q, function ($query_search) use ($q) {
                $query_search->where(function ($sub_query) use ($q) {
                    $sub_query->where('full_name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%")
                        ->orWhere('phone', 'like', "%$q%")
                        ->orWhere('academic_number', 'like', "%$q%");
                });
            })
            ->when($collegeId, function ($query_college) use ($collegeId) {
                $query_college->where('college_id', (int)$collegeId);
            })
            ->when($userTypeCode, function ($query_type) use ($userTypeCode) {
                $query_type->whereHas('userType', function ($sub_query_type) use ($userTypeCode) {
                    $sub_query_type->where('user_type_code', $userTypeCode);
                });
            })
            ->orderBy('user_id', 'desc');
    
        // إذا لم يكن هناك pagination مطلوب، أرجع الكل
        if ($perPage === 0 || $request->query('all') === 'true') {
            return \App\Http\Resources\V1\UserResource::collection($usersQuery->get());
        }
        
        // إذا تطلب pagination
        $users = $usersQuery->paginate($perPage);
    
        return \App\Http\Resources\V1\UserResource::collection($users);
    }
    
    public function show(int $user)
    {
        $u = \App\Models\User::findOrFail($user);
        return new \App\Http\Resources\V1\UserResource($u);
    }
    
    public function update(UpdateUserRequest $request, int $user)
    {
        $u = \App\Models\User::findOrFail($user);
        $data = $request->validated();
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $u->update($data);
        return new \App\Http\Resources\V1\UserResource($u);
    }
    
    public function destroy(int $user)
    {
        $u = \App\Models\User::findOrFail($user);
        $u->delete(); // Soft delete
        // إلغاء كل التوكنات
        if (method_exists($u, 'tokens')) {
            $u->tokens()->delete();
        }
        return response()->json(['message'=>'User deleted']);
    }
    
    public function restore(int $user)
    {
        $u = \App\Models\User::withTrashed()->findOrFail($user);
        $u->restore();
        return new \App\Http\Resources\V1\UserResource($u);
    }
}