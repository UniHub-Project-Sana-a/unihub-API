<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProgramOptionAudit;
use Illuminate\Http\Request;

class ProgramOptionAuditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['program_id' => 'required|integer|exists:programs,program_id']);

        return response()->json([
            'success' => true,
            'data' => ProgramOptionAudit::where('program_id', $request->program_id)
                ->latest('changed_at')->latest('id')->get(),
        ]);
    }
}
