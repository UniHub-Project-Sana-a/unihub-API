<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Course\StoreCourseRequest;
use App\Http\Requests\V1\Course\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;

class CoursesController extends Controller {
    public function index(Request $r) {
  $q = Course::query()->select(['course_id','course_name','course_code','course_type','is_active','semester_id','credit_hours','is_elective','department_id','notes']);
  if ($r->filled('semester_id')) $q->where('semester_id', (int)$r->semester_id);
  return response()->json($q->get());
}
   public function store(StoreCourseRequest $request) {
  $data = $request->validated();
  $course = Course::create($data);
  return response()->json($course->fresh(), 201);
}
    public function show(Course $course) {
        return response()->json($course);
    }
    public function update(UpdateCourseRequest $request, Course $course) {
        $course->update($request->validated());
        return response()->json($course);
    }
    public function destroy(Course $course) {
        $course->delete();
        return response()->json(['message' => 'Course deleted']);
    }
}