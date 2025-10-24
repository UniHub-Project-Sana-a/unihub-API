<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Semester\StoreSemesterRequest;
use App\Http\Requests\V1\Semester\UpdateSemesterRequest;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemestersController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Semester::query()->with('level')->when($q, fn($qq) => $qq->where('semester_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreSemesterRequest $request) {
        $semester = Semester::create($request->validated());
        return response()->json($semester->load('level'), 201);
    }
    public function show(Semester $semester) {
        return response()->json($semester->load('level'));
    }
    public function update(UpdateSemesterRequest $request, Semester $semester) {
        $semester->update($request->validated());
        return response()->json($semester->load('level'));
    }
    public function destroy(Semester $semester) {
        $semester->delete();
        return response()->json(['message' => 'Semester deleted']);
    }
}