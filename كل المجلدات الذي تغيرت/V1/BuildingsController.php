<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Building\StoreBuildingRequest;
use App\Http\Requests\V1\Building\UpdateBuildingRequest;
use App\Models\Building;
use Illuminate\Http\Request;

class BuildingsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $collegeId = $request->query('college_id');
        $query = Building::query()
        ->with('college')
        ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
            $w->where('building_name', 'like', "%{$q}%")
              ->orWhere('building_code', 'like', "%{$q}%");
        }))
        ->when($collegeId, fn($qq) => $qq->where(function ($w) use ($collegeId) {
            // نعرض مباني الكلية المحددة بالإضافة إلى المباني العامة المشتركة (بدون كلية)
            $w->where('college_id', $collegeId)->orWhereNull('college_id');
        }));
        return response()->json($query->orderBy('building_name')->get());
    }
    public function store(StoreBuildingRequest $request) {
        $building = Building::create($request->validated());
        return response()->json($building->load('college'), 201);
    }
    public function show(Building $building) {
        return response()->json($building->load('college'));
    }
    public function update(UpdateBuildingRequest $request, Building $building) {
        $building->update($request->validated());
        return response()->json($building->load('college'));
    }
    public function destroy(Building $building) {
        if ($building->classrooms()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف هذا المبنى لأنه يحتوي على قاعات مرتبطة به.',
            ], 422);
        }
        $building->delete();
        return response()->json(['message' => 'Building deleted']);
    }
}