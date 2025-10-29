<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Program\StoreProgramRequest;
use App\Http\Requests\V1\Program\UpdateProgramRequest;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramsController extends Controller {
    public function index(Request $r) {
      $q = Program::query()->select(['program_id','program_name','is_active','department_id']);
      if ($r->filled('department_id')) $q->where('department_id', (int)$r->department_id);
      return response()->json($q->get());
    }

    public function store(StoreProgramRequest $request) {
      $data = $request->validated();
      $program = Program::create([
        'program_name'  => $data['program_name'],
        'department_id' => $data['department_id'],
        'is_active'     => $data['is_active'] ?? true,
      ]);
      return response()->json($program->fresh(), 201);
    }
    public function show(Program $program) {
        return response()->json($program);
    }
    public function update(UpdateProgramRequest $request, Program $program) {
      $data = $request->validated();
      $program->update([
        'program_name' => $data['program_name'] ?? $program->program_name,
        'is_active'    => array_key_exists('is_active',$data) ? $data['is_active'] : $program->is_active,
      ]);
      return response()->json($program->fresh());
    }
    public function destroy(Program $program)
    {
        $program->delete(); // Soft delete
        return response()->json(['message' => 'Deleted']);
    }
}