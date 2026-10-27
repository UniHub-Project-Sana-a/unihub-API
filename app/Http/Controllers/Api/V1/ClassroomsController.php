<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Classroom\StoreClassroomRequest;
use App\Http\Requests\V1\Classroom\UpdateClassroomRequest;
use App\Models\Building;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LectureSession;

class ClassroomsController extends Controller {
// App\Http\Controllers\Api\V1\ClassroomsController.php
    public function index(Request $request)
    {
        $query = Classroom::query()->with(['building', 'college']);
    
        // اعرض قاعات الكلية، القاعات داخل مبانيها، والقاعات العامة داخل مبانٍ عامة مشتركة.
        if ($request->filled('college_id')) {
            $collegeId = (int)$request->college_id;
            $query->where(function ($q) use ($collegeId) {
                $q->where('classrooms.college_id', $collegeId)
                  ->orWhereHas('building', function ($b) use ($collegeId) {
                      $b->where('college_id', $collegeId);
                  })
                  ->orWhere(function ($shared) {
                      $shared->whereNull('classrooms.college_id')
                          ->whereHas('building', function ($building) {
                              $building->whereNull('college_id');
                          });
                  });
            });
        }
    
        // الفلترة حسب المبنى مباشرة (إذا تم تمريره)
        if ($request->filled('building_id')) {
            $query->where('building_id', (int)$request->building_id);
        }
        
        // إذا كان الطلب يريد كل النتائج (للقوائم المنسدلة)
        if ($request->query('all') === 'true') {
            return response()->json($query->orderBy('classroom_name')->get());
        }
    
        // الوضع الافتراضي مع pagination
        return response()->json($query->orderBy('classroom_name')->paginate(15));
    }
    public function store(StoreClassroomRequest $request) {
        $classroom = Classroom::create($request->validated());
        return response()->json($classroom->load(['building', 'college']), 201);
    }
    public function show(Classroom $classroom) {
        return response()->json($classroom->load(['building', 'college']));
    }
    public function update(UpdateClassroomRequest $request, Classroom $classroom) {
        $classroom->update($request->validated());
        return response()->json($classroom->refresh()->load(['building', 'college']));
    }
    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return response()->json(['message' => 'Classroom deleted']);
    }

    /**
     * سلة المحذوفات (Soft Delete) للمباني والقاعات.
     * نفس نمط DepartmentsController::trash: list / restore / force_delete
     * POST /v1/classrooms/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:buildings,classrooms',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];

        // القاعات التابعة للكلية: مباشرة أو عبر المبنى (حتى لو كان المبنى محذوفاً ناعماً)
        $classroomScope = fn () => Classroom::withTrashed()
            ->where(function ($query) use ($collegeId) {
                $query->where('college_id', $collegeId)
                    ->orWhereHas('building', function ($building) use ($collegeId) {
                        $building->withTrashed()->where('college_id', $collegeId);
                    });
            });

        $queries = [
            'buildings' => fn () => Building::onlyTrashed()->where('college_id', $collegeId),
            'classrooms' => fn () => $classroomScope()->onlyTrashed(),
        ];

        $nameColumns = [
            'buildings' => 'building_name',
            'classrooms' => 'classroom_name',
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'buildings' => 'المباني',
                'classrooms' => 'القاعات',
            ];

            $groups = [];
            foreach ($queries as $type => $queryFactory) {
                $nameColumn = $nameColumns[$type];

                $groups[] = [
                    'type' => $type,
                    'label' => $labels[$type],
                    'records' => $queryFactory()->get()->map(function ($record) use ($nameColumn) {
                        $data = $record->getAttributes();
                        $data['deleted_at'] = $record->deleted_at?->toISOString();

                        return [
                            'id' => $record->getKey(),
                            'name' => $record->getAttribute($nameColumn)
                                ?: ('#' . $record->getKey()),
                            'deleted_at' => $record->deleted_at?->toISOString(),
                            'data' => $data,
                        ];
                    })->values(),
                ];
            }

            return response()->json(['groups' => $groups]);
        }

        $type = $validated['type'];
        $record = $queries[$type]()->whereKey((int) $validated['id'])->firstOrFail();

        DB::transaction(function () use ($record, $validated) {
            if ($validated['action'] === 'restore') {
                $record->restore();
            } else {
                $record->forceDelete();
            }
        });

        return response()->json([
            'message' => $validated['action'] === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }

    public function checkAvailability(Request $request)
    {
        try {
            $collegeId = $request->query('college_id');
            $date = $request->query('date');
            $startTime = $request->query('start_time');
            $endTime = $request->query('end_time');
    
            // 1. جلب القاعات (التصحيح هنا: البحث عبر علاقة المبنى)
            $classrooms = Classroom::query()
                // ندخل إلى جدول buildings المرتبط
                ->whereHas('building', function ($q) use ($collegeId) {
                    // ونبحث هناك عن college_id
                    $q->where('college_id', $collegeId);
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