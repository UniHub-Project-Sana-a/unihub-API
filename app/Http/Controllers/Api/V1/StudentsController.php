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
        $collegeId  = $request->query('college_id');
        $deptId     = $request->query('department_id');
        $programId  = $request->query('program_id');
        $levelId    = $request->query('level_id');
        $semesterId = $request->query('semester_id');
        $blockId    = $request->query('block_id');
        $gender     = $request->query('gender'); // 1/2
        $status     = $request->query('status'); // 1/0
        $q          = $request->query('q');      // بحث في اسم الطالب/البريد/الهاتف/الرقم الأكاديمي

        $query = Student::query()
            ->with(['user:user_id,full_name,email,phone,academic_number,gender'])
            ->when($collegeId, fn($qq) => $qq->where('college_id', (int)$collegeId))
            ->when($deptId, fn($qq) => $qq->where('department_id', (int)$deptId))
            ->when($programId, fn($qq) => $qq->where('program_id', (int)$programId))
            ->when($levelId, fn($qq) => $qq->where('level_id', (int)$levelId))
            ->when($semesterId, fn($qq) => $qq->where('semester_id', (int)$semesterId))
            ->when($blockId, fn($qq) => $qq->where('block_id', (int)$blockId))
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

        if ($request->query('all') === 'true' || $perPage === 0) {
            return response()->json($query->get());
        }

        $students = $query->paginate($perPage);
        return response()->json($students);
    }
}