<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Level\StoreLevelRequest;
use App\Http\Requests\V1\Level\UpdateLevelRequest;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Level::query()->with('department')->when($q, fn($qq) => $qq->where('level_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreLevelRequest $request) {
        $level = Level::create($request->validated());
        return response()->json($level->load('department'), 201);
    }
    public function show(Level $level) {
        return response()->json($level->load('department'));
    }
    public function update(UpdateLevelRequest $request, Level $level) {
        $level->update($request->validated());
        return response()->json($level->load('department'));
    }
    public function destroy(Level $level) {
        $level->delete();
        return response()->json(['message' => 'Level deleted']);
    }
}