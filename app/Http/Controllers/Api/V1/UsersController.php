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
    
        $users = \App\Models\User::query()
            ->when($q, function($qq) use ($q){
                $qq->where('full_name','like',"%$q%")
                   ->orWhere('email','like',"%$q%")
                   ->orWhere('phone','like',"%$q%");
            })
            ->orderBy('user_id','desc')
            ->paginate($perPage);
    
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