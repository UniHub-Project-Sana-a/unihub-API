<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function sessions(Request $request)
    {
        $tokens = DB::table('oauth_access_tokens as t')
            ->join('users as u','u.user_id','=','t.user_id')
            ->select('t.id','t.user_id','u.full_name','u.email','t.name as device','t.revoked','t.created_at','t.expires_at')
            ->orderBy('t.created_at','desc')
            ->limit(200)
            ->get();

        return response()->json($tokens);
    }

    public function revokeSession(Request $request)
    {
        $request->validate([
            'token_id' => ['nullable','string'],
            'user_id'  => ['nullable','integer','exists:users,user_id']
        ]);

        if ($request->filled('token_id')) {
            DB::table('oauth_access_tokens')->where('id',$request->token_id)->update(['revoked'=>1]);
            return response()->json(['message'=>'Token revoked']);
        }

        if ($request->filled('user_id')) {
            DB::table('oauth_access_tokens')->where('user_id',$request->user_id)->update(['revoked'=>1]);
            return response()->json(['message'=>'All user tokens revoked']);
        }

        return response()->json(['message'=>'Provide token_id or user_id'], 422);
    }

    public function auditLogs(Request $request)
    {
        $q = DB::table('user_activities as a')
            ->join('users as u','u.user_id','=','a.user_id')
            ->select('a.activity_id','a.created_at','u.full_name','u.email','a.action_type','a.module_name','a.action_description')
            ->when($request->filled('user_id'), fn($qq)=>$qq->where('a.user_id',(int)$request->user_id))
            ->when($request->filled('from'), fn($qq)=>$qq->where('a.created_at','>=',$request->from))
            ->when($request->filled('to'), fn($qq)=>$qq->where('a.created_at','<=',$request->to))
            ->orderBy('a.activity_id','desc')
            ->limit(500);

        return response()->json($q->get());
    }
}