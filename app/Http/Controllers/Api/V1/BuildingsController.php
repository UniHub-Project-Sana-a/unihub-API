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
        ->when($q, fn($qq) => $qq->where('building_name', 'like', "%{$q}%"))
        ->when($collegeId, fn($qq) => $qq->where('college_id', $collegeId));
        return response()->json($query->get());
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
        $building->delete();
        return response()->json(['message' => 'Building deleted']);
    }
}