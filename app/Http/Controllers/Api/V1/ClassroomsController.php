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
        $query = Classroom::query();
    
        // الفلترة حسب الكلية (عبر علاقة المبنى)
        if ($request->filled('college_id')) {
            $query->whereHas('building', function ($q) use ($request) {
                $q->where('college_id', (int)$request->college_id);
            });
        }
    
        // الفلترة حسب المبنى مباشرة (إذا تم تمريره)
        if ($request->filled('building_id')) {
            $query->where('building_id', (int)$request->building_id);
        }
        
        // إذا كان الطلب يريد كل النتائج (للقوائم المنسدلة)
        if ($request->query('all') === 'true') {
            return response()->json($query->orderBy('classroom_name')->get());
        }
    
        // الوضع الافتراضي مع pagination
        return response()->json($query->orderBy('classroom_name')->paginate(15));
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