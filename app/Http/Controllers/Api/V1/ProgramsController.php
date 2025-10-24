<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Program\StoreProgramRequest;
use App\Http\Requests\V1\Program\UpdateProgramRequest;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Program::query()->when($q, fn($qq) => $qq->where('program_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreProgramRequest $request) {
        $program = Program::create($request->validated());
        return response()->json($program, 201);
    }
    public function show(Program $program) {
        return response()->json($program);
    }
    public function update(UpdateProgramRequest $request, Program $program) {
        $program->update($request->validated());
        return response()->json($program);
    }
    public function destroy(Program $program) {
        $program->delete();
        return response()->json(['message' => 'Program deleted']);
    }
}