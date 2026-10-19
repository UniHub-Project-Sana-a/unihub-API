<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UserType\StoreUserTypeRequest;
use App\Http\Requests\V1\UserType\UpdateUserTypeRequest;
use App\Models\UserType;
use Illuminate\Http\Request;

class UserTypeController extends Controller
{
    public function store(StoreUserTypeRequest $request)
    {
        $t = UserType::create($request->validated());
        return response()->json($t, 201);
    }

    public function update(UpdateUserTypeRequest $request, int $userType)
    {
        $t = UserType::findOrFail($userType);
        $t->update($request->validated());
        return response()->json($t);
    }

    public function destroy(int $userType)
    {
        $t = UserType::findOrFail($userType);
        $t->delete();
        return response()->json(['message'=>'User type deleted']);
    }
}