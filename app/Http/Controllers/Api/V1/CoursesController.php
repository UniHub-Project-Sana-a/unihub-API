<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Course\StoreCourseRequest;
use App\Http\Requests\V1\Course\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;

class CoursesController extends Controller {
    public function index(Request $r) {
      // إضافة الحقول المطلوبة للعرض (أضفت program_id, level_id, college_id للمساعدة في الفلترة والتحقق)
      $q = Course::query()->select([
          'course_id', 'course_name', 'course_code', 'course_type', 'is_active', 
          'semester_id', 'credit_hours', 'is_elective', 'department_id', 'notes',
          'college_id', 'program_id', 'level_id' // ✅ إضافات مهمة
      ]);
    
      // ✅ تطبيق الفلاتر القادمة من الواجهة
      if ($r->filled('college_id')) $q->where('college_id', (int)$r->college_id);
      if ($r->filled('department_id')) $q->where('department_id', (int)$r->department_id);
      if ($r->filled('program_id')) $q->where('program_id', (int)$r->program_id);
      if ($r->filled('level_id')) $q->where('level_id', (int)$r->level_id);
      if ($r->filled('semester_id')) $q->where('semester_id', (int)$r->semester_id);
    
      // فلتر إضافي للبحث بالاسم أو الكود (اختياري، مفيد للبحث السريع)
      if ($r->filled('search')) {
          $term = $r->search;
          $q->where(function($query) use ($term) {
              $query->where('course_name', 'LIKE', "%$term%")
                    ->orWhere('course_code', 'LIKE', "%$term%");
          });
      }
    
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