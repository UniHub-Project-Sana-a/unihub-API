<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentsController extends Controller
{
    // GET /api/v1/students?college_id=&department_id=&program_id=&level_id=&gender=&status=&q=&per_page=&all=true
    public function index(Request $request)
    {
        $perPage    = (int) $request->query('per_page', 15);
        $pathFilters = [];
        foreach (['college_id', 'department_id', 'program_id', 'level_id', 'semester_id', 'block_id'] as $field) {
            $values = $request->query($field);
            if ($values !== null) {
                $values = is_array($values) ? $values : [$values];
                $pathFilters[$field] = array_values(array_filter(
                    array_map('intval', $values),
                    fn ($value) => $value > 0
                ));
            }
        }
        $gender     = $request->query('gender'); // 1/2
        $status     = $request->query('status'); // 1/0
        $q          = $request->query('q');      // بحث في اسم الطالب/البريد/الهاتف/الرقم الأكاديمي

        $query = Student::query()
            ->with(['user:user_id,full_name,email,phone,academic_number,gender'])
            ->with(['college:college_id,college_name', 'department:department_id,department_name', 'program:program_id,program_name', 'level:level_id,level_number', 'semester:semester_id,term_number', 'block:id,block_name,block_number'])
            ->with(['groups' => function ($groupQuery) {
                $groupQuery
                    ->whereNull('student_groups.deleted_at')
                    ->with(['college:college_id,college_name', 'department:department_id,department_name', 'program:program_id,program_name', 'level:level_id,level_number', 'semester:semester_id,term_number', 'block:id,block_name,block_number']);
            }])
            ->when($pathFilters, function ($qq) use ($pathFilters) {
                $applyFilters = function ($pathQuery) use ($pathFilters) {
                    foreach ($pathFilters as $field => $values) {
                        if ($values) {
                            $pathQuery->whereIn($field, $values);
                        }
                    }
                };

                $qq->where(function ($studentQuery) use ($applyFilters) {
                    $studentQuery->where(function ($pathQuery) use ($applyFilters) {
                        $applyFilters($pathQuery);
                    })
                        ->orWhereHas('groups', function ($groupQuery) use ($applyFilters) {
                            $groupQuery->whereNull('student_groups.deleted_at')
                                ->where(function ($pathQuery) use ($applyFilters) {
                                    $applyFilters($pathQuery);
                                });
                        });
                });
            })
            ->when(isset($status), fn($qq) => $qq->where('status', (bool)$status))
            ->when($gender, function ($qq) use ($gender) {
                $qq->whereHas('user', fn($uq) => $uq->where('gender', (int)$gender));
            })
            ->when($q, function ($qq) use ($q) {
                $qq->whereHas('user', function ($uq) use ($q) {
                    $uq->where('full_name', 'like', "%{$q}%")
                       ->orWhere('academic_number', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%")
                       ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('student_id', 'desc');

        if ($request->boolean('all') || $perPage === 0) {
            return response()->json($query->get());
        }

        $students = $query->paginate($perPage);
        return response()->json($students);
    }

    // DELETE /api/v1/students/{student}
    // حذف ناعم للطالب (Soft Delete) — يدخل سلة المحذوفات ويمكن استعادته منها
    public function destroy($student)
    {
        $model = Student::find((int) $student);

        if (! $model) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $model->delete(); // SoftDeletes → يضبط deleted_at

        return response()->json(['message' => 'Deleted']);
    }
}