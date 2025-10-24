<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\College\StoreCollegeRequest;
use App\Http\Requests\V1\College\UpdateCollegeRequest;
use App\Models\College;
use Illuminate\Http\Request;

class CollegesController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = College::query()->when($q, fn($qq) => $qq->where('college_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreCollegeRequest $request) {
        $college = College::create($request->validated());
        return response()->json($college, 201);
    }
    public function show(College $college) {
        return response()->json($college);
    }
    public function update(UpdateCollegeRequest $request, College $college) {
        $college->update($request->validated());
        return response()->json($college);
    }
    public function destroy(College $college) {
        $college->delete();
        return response()->json(['message' => 'College deleted']);
    }
}