<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $deviceName = $request->input('device_name', 'web');
        $tokenResult = $user->createToken($deviceName);
        $accessToken = $tokenResult->accessToken;

        return response()->json([
            'token_type'   => 'Bearer',
            'access_token' => $accessToken,
            'user'         => new UserResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request)
{
    /** @var Token $token */
    $token = $request->user()->token();
    $token->revoke();

    return response()->json(['message' => 'Logged out']);
}
}