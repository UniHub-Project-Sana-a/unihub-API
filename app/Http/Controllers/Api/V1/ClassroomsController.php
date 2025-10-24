<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Classroom\StoreClassroomRequest;
use App\Http\Requests\V1\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Classroom::query()->with('building')->when($q, fn($qq) => $qq->where('classroom_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreClassroomRequest $request) {
        $classroom = Classroom::create($request->validated());
        return response()->json($classroom->load('building'), 201);
    }
    public function show(Classroom $classroom) {
        return response()->json($classroom->load('building'));
    }
    public function update(UpdateClassroomRequest $request, Classroom $classroom) {
        $classroom->update($request->validated());
        return response()->json($classroom->load('building'));
    }
    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return response()->json(['message' => 'Classroom deleted']);
    }
}