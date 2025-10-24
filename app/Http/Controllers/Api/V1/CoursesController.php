<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Course\StoreCourseRequest;
use App\Http\Requests\V1\Course\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;

class CoursesController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Course::query()->when($q, fn($qq) => $qq->where('course_name', 'like', "%{$q}%")->orWhere('course_code', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreCourseRequest $request) {
        $course = Course::create($request->validated());
        return response()->json($course, 201);
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