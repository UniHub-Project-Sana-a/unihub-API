<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Classroom\StoreClassroomRequest;
use App\Http\Requests\V1\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Models\LectureSession;

class ClassroomsController extends Controller {
// App\Http\Controllers\Api\V1\ClassroomsController.php
    public function index(Request $request)
    {
        $query = Classroom::query();
    
        // الفلترة حسب الكلية (المباشرة للقاعة أو عبر علاقة المبنى كدعم تراجعي)
        if ($request->filled('college_id')) {
            $cId = (int)$request->college_id;
            $query->where(function ($q) use ($cId) {
                $q->where('college_id', $cId)
                  ->orWhereHas('building', function ($bq) use ($cId) {
                      $bq->where('college_id', $cId);
                  });
            });
        }
    
        // الفلترة حسب المبنى مباشرة (إذا تم تمريره)
        if ($request->filled('building_id')) {
            $query->where('building_id', (int)$request->building_id);
        }
        
        $query->with(['college', 'building']);

        // إذا كان الطلب يريد كل النتائج (للقوائم المنسدلة)
        if ($request->query('all') === 'true') {
            return response()->json($query->orderBy('classroom_name')->get());
        }
    
        // الوضع الافتراضي مع pagination
        return response()->json($query->orderBy('classroom_name')->paginate(15));
    }
    public function store(StoreClassroomRequest $request) {
        $classroom = Classroom::create($request->validated());
        return response()->json($classroom->load('building'), 201);
    }
    public function show(Classroom $classroom) {
        return response()->json($classroom->load('building'));
    }
    public function update(UpdateClassroomRequest $request, Classroom $classroom) {
        $classroom->update($request->validated());
        return response()->json($classroom->load('building'));
    }
    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return response()->json(['message' => 'Classroom deleted']);
    }

    public function checkAvailability(Request $request)
    {
        try {
            $collegeId = $request->query('college_id');
            $date = $request->query('date');
            $startTime = $request->query('start_time');
            $endTime = $request->query('end_time');
    
            // 1. جلب القاعات (البحث المباشر في القاعة أو عبر علاقة المبنى كدعم تراجعي)
            $classrooms = Classroom::query()
                ->where(function ($q) use ($collegeId) {
                    $q->where('college_id', $collegeId)
                      ->orWhereHas('building', function ($bq) use ($collegeId) {
                          $bq->where('college_id', $collegeId);
                      });
                })
                ->select('classroom_id', 'classroom_name as name', 'capacity')
                ->get();
    
            // 2. إذا لم يتم تحديد وقت، نرجع الكل متاح
            if (!$date || !$startTime || !$endTime) {
                $data = $classrooms->map(function($c) {
                    $c->setAttribute('is_busy', false);
                    return $c;
                });
                return response()->json(['data' => $data]);
            }
    
            // 3. البحث عن القاعات المشغولة
            $busyClassroomIds = LectureSession::where('session_date', $date)
                ->where('status', '!=', 2)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->pluck('actual_classroom_id')
                ->toArray();
    
            // 4. دمج المعلومة
            $results = $classrooms->map(function ($room) use ($busyClassroomIds) {
                $isBusy = in_array($room->classroom_id, $busyClassroomIds);
                $room->setAttribute('is_busy', $isBusy);
                return $room;
            });
    
            return response()->json(['data' => $results]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ في السيرفر',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}