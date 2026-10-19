<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Level\StoreLevelRequest;
use App\Http\Requests\V1\Level\UpdateLevelRequest;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelsController extends Controller {
    public function index(Request $r) {
      $q = Level::query()->select(['level_id','program_id','level_number','level_name']);
      if ($r->filled('program_id')) $q->where('program_id', (int)$r->program_id);
      return response()->json($q->get());
    }
    public function store(StoreLevelRequest $request) {
      $data = $request->validated();
      $level = Level::create([
        'program_id'   => $data['program_id'],
        'level_number' => $data['level_number'],
        'level_name'   => $data['level_name'] ?? 'المستوى '.$data['level_number'],
      ]);
      return response()->json($level->fresh(), 201);
    }
    public function show(Level $level) {
    // لو تحب ترجع معلومات البرنامج والقسم أيضاً:
    return response()->json($level->load('program.department'));
}
public function update(UpdateLevelRequest $request, Level $level) {
    $level->update($request->validated());
    return response()->json($level->load('program.department'));
}
    public function destroy(Level $level) {
        $level->delete();
        return response()->json(['message' => 'Level deleted']);
    }
}