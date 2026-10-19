<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Semester\StoreSemesterRequest;
use App\Http\Requests\V1\Semester\UpdateSemesterRequest;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemestersController extends Controller {
    public function index(Request $r) {
      $q = Semester::query()->select(['semester_id','level_id','term_number','semester_name','academic_year']);
      if ($r->filled('level_id')) $q->where('level_id', (int)$r->level_id);
      return response()->json($q->get());
    }
    public function store(StoreSemesterRequest $request) {
      $data = $request->validated();
      $semester = Semester::create([
        'level_id'      => $data['level_id'],
        'term_number'   => $data['term_number'],
        'semester_name' => $data['semester_name'] ?? 'الترم '.$data['term_number'],
        'academic_year' => $data['academic_year'] ?? date('Y'),
      ]);
      return response()->json($semester->fresh(), 201);
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