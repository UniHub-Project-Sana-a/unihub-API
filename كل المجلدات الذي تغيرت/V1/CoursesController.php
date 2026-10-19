<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Course\StoreCourseRequest;
use App\Http\Requests\V1\Course\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoursesController extends Controller
{
    /**
     * عرض قائمة المقررات مع فلترة متقدمة
     */
    public function index(Request $request)
    {
        $query = Course::with([
            'college',
            'department',
            'program',
            'level',
            'semester',
            'block',
            'prerequisites',
            'corequisites'
        ]);

        // ✅ فلترة حسب الكلية
        if ($request->has('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        // ✅ فلترة حسب القسم
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // ✅ فلترة حسب البرنامج
        if ($request->has('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // ✅ فلترة حسب المستوى
        if ($request->has('level_id')) {
            $query->where('level_id', $request->level_id);
        }

        // ✅ فلترة حسب الفصل الدراسي
        if ($request->has('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        // ✅ فلترة حسب البلوك
        if ($request->has('block_id')) {
            $query->where('block_id', $request->block_id);
        }

        // ✅ فلترة حسب التصنيف
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // ✅ المقررات النشطة فقط
        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        // ✅ المقررات الاختيارية فقط
        if ($request->has('is_elective')) {
            $query->where('is_elective', $request->boolean('is_elective'));
        }

        $courses = $query->get();

        return response()->json($courses);
    }

    /**
     * إضافة مقرر جديد
     */
    public function store(StoreCourseRequest $request)
    {
        $data = $request->validated();

        // ✅ إنشاء المقرر
        $course = Course::create($data);

        // ✅ ربط المتطلبات السابقة
        if (!empty($data['prerequisites'])) {
            foreach ($data['prerequisites'] as $prereqId) {
                CoursePrerequisite::create([
                    'course_id' => $course->course_id,
                    'prerequisite_course_id' => $prereqId,
                    'type' => 'prerequisite'
                ]);
            }
        }

        // ✅ ربط المتطلبات المصاحبة
        if (!empty($data['corequisites'])) {
            foreach ($data['corequisites'] as $coreqId) {
                CoursePrerequisite::create([
                    'course_id' => $course->course_id,
                    'prerequisite_course_id' => $coreqId,
                    'type' => 'corequisite'
                ]);
            }
        }

        return response()->json(
            $course->load(['prerequisites', 'corequisites']),
            201
        );
    }

    /**
     * عرض مقرر محدد
     */
    public function show(Course $course): JsonResponse
    {
        try {
            $course->load('college', 'department', 'program', 'level', 'semester', 'block');

            // ✅ جلب المتطلبات بطريقة صحيحة
            $prerequisites = DB::table('course_prerequisites as cp')
                ->join('courses as c', 'cp.prerequisite_course_id', '=', 'c.course_id')
                ->where('cp.course_id', $course->course_id)
                ->where('cp.type', '!=', 'corequisite') // ✅ احصل على كل شيء ما عدا corequisite
                ->select('c.course_id as id', 'c.course_code', 'c.course_name')
                ->distinct()
                ->get();
            
            $corequisites = DB::table('course_prerequisites as cp')
                ->join('courses as c', 'cp.prerequisite_course_id', '=', 'c.course_id')
                ->where('cp.course_id', $course->course_id)
                ->where('cp.type', 'corequisite')
                ->select('c.course_id as id', 'c.course_code', 'c.course_name')
                ->distinct()
                ->get();
    
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $course->course_id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'credit_hours' => $course->credit_hours,
                    'category' => $course->category,
                    'weight' => $course->weight,
                    'teaching_language' => $course->teaching_language,
                    'notes' => $course->notes,
                    'is_active' => $course->is_active,
                    'is_approved' => $course->is_approved,
                    'approval_date' => $course->approval_date?->format('Y-m-d'),
                    'approved_by' => $course->approved_by,
                    'specification_status' => $course->specification_status ?? 'draft',
                    
                    // ✅ course_parts من JSON column
                    'course_parts' => $course->course_parts ?? [],

                    'college' => $course->college ? [
                        'id' => $course->college->college_id,
                        'name' => $course->college->college_name
                    ] : null,
                    
                    'department' => $course->department ? [
                        'id' => $course->department->department_id,
                        'name' => $course->department->department_name
                    ] : null,
                    
                    'program' => $course->program ? [
                        'id' => $course->program->program_id,
                        'name' => $course->program->program_name,
                        'academic_system' => $course->program->academic_system ?? null
                    ] : null,
                    
                    'level' => $course->level ? [
                        'id' => $course->level->level_id,
                        'number' => $course->level->level_number
                    ] : null,
                    
                    'semester' => $course->semester ? [
                        'id' => $course->semester->semester_id,
                        'name' => $course->semester->semester_name
                    ] : null,
                    
                    'block' => $course->block ? [
                        'id' => $course->block->block_id,
                        'name' => $course->block->block_name
                    ] : null,
                    
                    // ✅ المتطلبات
                    'prerequisites' => $prerequisites->toArray(),
                    'corequisites' => $corequisites->toArray(),
                
                    'learning_outcomes' => [],
                    'description' => null,
                ]
            ]);
    
        } catch (\Exception $e) {
            Log::error('Course show error: ' . $e->getMessage() . ' - Line: ' . $e->getLine());
            
            return response()->json([
                'success' => false,
                'message' => 'فشل جلب بيانات المقرر'
            ], 500);
        }
    }

    /**
     * تنسيق بيانات المقرر للعرض في التوصيف
     */
    private function formatCourseForSpecification($course): array
    {
        return [
            // ==================== البيانات الأساسية ====================
            'id' => $course->course_id,
            'course_code' => $course->course_code,
            'course_name' => $course->course_name,
            'credit_hours' => $course->credit_hours,
            'category' => $course->category,
            'weight' => $course->weight,
            'teaching_language' => $course->teaching_language,
            'notes' => $course->notes,
            'is_active' => $course->is_active,
            
            // ==================== بيانات الاعتماد ====================
            'is_approved' => $course->is_approved,
            'approval_date' => $course->approval_date?->format('Y-m-d'),
            'approved_by' => $course->approved_by,

            // ==================== المؤسسات التعليمية ====================
            'college' => $course->college ? [
                'id' => $course->college->college_id,
                'name' => $course->college->college_name
            ] : null,

            'department' => $course->department ? [
                'id' => $course->department->department_id,
                'name' => $course->department->department_name
            ] : null,

            'program' => $course->program ? [
                'id' => $course->program->program_id,
                'name' => $course->program->program_name,
                'academic_system' => $course->program->academic_system
            ] : null,

            'level' => $course->level ? [
                'id' => $course->level->level_id,
                'number' => $course->level->level_number
            ] : null,

            'semester' => $course->semester ? [
                'id' => $course->semester->semester_id,
                'name' => $course->semester->semester_name
            ] : null,

            'block' => $course->block ? [
                'id' => $course->block->block_id,
                'name' => $course->block->block_name
            ] : null,

            // ==================== أجزاء المقرر ====================
            'course_parts' => $course->courseParts->map(function($part) {
                return [
                    'id' => $part->part_id,
                    'name' => $part->name,
                    'theoretical_hours' => (int)$part->theoretical_hours,
                    'practical_hours' => (int)$part->practical_hours,
                    'exercise_hours' => (int)$part->exercise_hours,
                    'seminar_hours' => (int)$part->seminar_hours,
                    'clinical_hours' => (int)$part->clinical_hours,
                    'total_hours' => (int)($part->theoretical_hours + $part->practical_hours + $part->exercise_hours + $part->seminar_hours + $part->clinical_hours)
                ];
            })->toArray(),

            // ==================== المتطلبات ====================
            'prerequisites' => $course->prerequisites ? $course->prerequisites->map(function($prereq) {
                return [
                    'id' => $prereq->prerequisite_course_id,
                    'code' => $prereq->course_code,
                    'name' => $prereq->course_name
                ];
            })->toArray() : [],

            'corequisites' => $course->corequisites ? $course->corequisites->map(function($coreq) {
                return [
                    'id' => $coreq->corequisite_course_id,
                    'code' => $coreq->course_code,
                    'name' => $coreq->course_name
                ];
            })->toArray() : [],

            // ==================== الوصف والأهداف ====================
            'description' => $course->description ? [
                'description' => $course->description->description,
                'goals' => $course->description->goals ?? [],
                'word_count' => $course->description->word_count
            ] : null,

            // ==================== مخرجات التعلم ====================
            'learning_outcomes' => $course->learningOutcomes->map(function($outcome) {
                return [
                    'id' => $outcome->clo_id,
                    'code' => $outcome->code,
                    'domain' => $outcome->domain,
                    'description' => $outcome->description,
                    'weight' => (float)$outcome->weight
                ];
            })->toArray()
        ];
    }

    /**
     * تحديث مقرر موجود
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $data = $request->validated();

        // ✅ تحديث البيانات الأساسية
        $course->update($data);

        // ✅ تحديث المتطلبات السابقة
        if (isset($data['prerequisites'])) {
            CoursePrerequisite::where('course_id', $course->course_id)
                              ->where('type', 'prerequisite')
                              ->delete();
            
            foreach ($data['prerequisites'] as $prereqId) {
                CoursePrerequisite::create([
                    'course_id' => $course->course_id,
                    'prerequisite_course_id' => $prereqId,
                    'type' => 'prerequisite'
                ]);
            }
        }

        // ✅ تحديث المتطلبات المصاحبة
        if (isset($data['corequisites'])) {
            CoursePrerequisite::where('course_id', $course->course_id)
                              ->where('type', 'corequisite')
                              ->delete();

            // إضافة المتطلبات الجديدة
            foreach ($data['corequisites'] as $coreqId) {
                CoursePrerequisite::create([
                    'course_id' => $course->course_id,
                    'prerequisite_course_id' => $coreqId,
                    'type' => 'corequisite'
                ]);
            }
        }

        return response()->json(
            $course->fresh()->load(['prerequisites', 'corequisites'])
        );
    }

    /**
     * حذف مقرر
     */
    public function destroy(Course $course)
    {
        // ✅ التحقق من وجود جداول مرتبطة
        if ($course->timetables()->count() > 0) {
            return response()->json([
                'message' => 'لا يمكن حذف المقرر لأنه مرتبط بجدول دراسي'
            ], 422);
        }

        // ✅ حذف العلاقات
        $course->prerequisites()->detach();
        $course->corequisites()->detach();

        // ✅ حذف ناعم
        $course->delete();

        return response()->json([
            'message' => 'تم حذف المقرر بنجاح'
        ]);
    }
}