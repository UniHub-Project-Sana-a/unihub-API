<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Classroom\StoreClassroomRequest;
use App\Http\Requests\V1\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomsController extends Controller {
// App\Http\Controllers\Api\V1\ClassroomsController.php
    public function index(Request $request)
    {
        $q = Classroom::query();
    
        if ($request->filled('building_id')) {
            $q->where('building_id', $request->building_id);
        }
    
        return $q->orderBy('classroom_name')->get();
        // أو ClassroomResource::collection($q->paginate()) لو تستخدم pagination
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