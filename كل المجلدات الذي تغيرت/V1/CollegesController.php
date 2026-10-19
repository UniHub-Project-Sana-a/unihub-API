<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\College\StoreCollegeRequest;
use App\Http\Requests\V1\College\UpdateCollegeRequest;
use App\Models\College;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Program;
use App\Models\Classroom;
use App\Models\Lecturer;
use App\Models\LecturerAttendance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CollegesController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = College::query()->when($q, fn($qq) => $qq->where('college_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreCollegeRequest $request)
    {
        // 1. إنشاء السجل أولاً للحصول على الـ ID
        $college = College::create($request->safe()->except(['college_logo']));

        // 2. التحقق من وجود ملف مرفق
        if ($request->hasFile('college_logo')) {
            $file = $request->file('college_logo');
            
            // تسمية الملف: ID.extension (مثال: 15.png)
            $filename = $college->college_id . '.' . $file->getClientOriginalExtension();
            
            // حفظ الملف في المسار: storage/app/public/colleges
            $path = $file->storeAs('colleges', $filename, 'public');
            
            // تحديث الحقل في قاعدة البيانات بالمسار الجديد
            $college->update(['college_logo' => $path]);
        }

        return response()->json($college, 201);
    }
    public function show(College $college) {
        return response()->json($college);
    }
    public function update(UpdateCollegeRequest $request, College $college)
    {
        // 1. تحديث البيانات النصية
        $college->update($request->safe()->except(['college_logo']));

        // 2. معالجة الصورة في حال تم رفع صورة جديدة
        if ($request->hasFile('college_logo')) {
            $file = $request->file('college_logo');

            // حذف الصورة القديمة إذا كانت موجودة (اختياري، لكنه أنظف)
            if ($college->college_logo && Storage::disk('public')->exists($college->college_logo)) {
                Storage::disk('public')->delete($college->college_logo);
            }

            // تسمية الملف بنفس طريقة الـ ID
            $filename = $college->college_id . '.' . $file->getClientOriginalExtension();
            
            // الحفظ (سيقوم بالكتابة فوق الملف القديم إذا كان بنفس الاسم)
            $path = $file->storeAs('colleges', $filename, 'public');

            // حفظ المسار في قاعدة البيانات
            $college->college_logo = $path;
            $college->save();
        }

        return response()->json($college);
    }
    public function destroy(College $college) 
    {
        // حذف ناعم: نُبقي ملف الشعار على التخزين حتى يمكن استعادة الكلية من سلة المحذوفات.
        // حذف الملف فعلياً يتم فقط عند "الحذف النهائي" من داخل السلة.
        $college->delete();
        
        return response()->json(['message' => 'College deleted']);
    }

    /**
     * سلة المحذوفات (Soft Delete) للكليات.
     * POST /api/v1/colleges/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'nullable|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:colleges',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = $validated['college_id'] ?? null;

        $queries = [
            'colleges' => fn () => College::onlyTrashed()
                ->when($collegeId, fn ($q) => $q->where('college_id', (int) $collegeId)),
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'colleges' => 'الكليات',
            ];

            $groups = [];
            foreach ($queries as $type => $queryFactory) {
                $groups[] = [
                    'type' => $type,
                    'label' => $labels[$type],
                    'records' => $queryFactory()->get()->map(function ($record) {
                        $data = $record->getAttributes();
                        $data['deleted_at'] = $record->deleted_at?->toISOString();

                        return [
                            'id' => $record->getKey(),
                            'name' => $record->college_name ?? ('#' . $record->getKey()),
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
                // الحذف النهائي: نحذف الآن ملف الشعار من التخزين ثم نحذف السجل نهائياً.
                $logoPath = $record->getRawOriginal('college_logo');
                if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                    Storage::disk('public')->delete($logoPath);
                }

                $record->forceDelete();
            }
        });

        return response()->json([
            'message' => $validated['action'] === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }

    public function dashboard($id)
    {
        // 1. الإحصائيات الأساسية
        $departmentsCount = Department::where('college_id', $id)->count();
        
        // القاعات: نصل إليها عبر المباني التابعة للكلية
        $classroomsCount = Classroom::whereHas('building', function($q) use ($id) {
            $q->where('college_id', $id);
        })->count();

        // البرامج: نصل إليها عبر الأقسام
        $programsCount = Program::whereHas('department', function($q) use ($id) {
            $q->where('college_id', $id);
        })->count();

        $staffCount = Lecturer::where('college_id', $id)->count();

        // 2. المصروفات الشهرية (للشهر الحالي)
        $currentMonthExpenses = LecturerAttendance::where('college_id', $id)
            ->whereMonth('attendance_date', Carbon::now()->month)
            ->whereYear('attendance_date', Carbon::now()->year)
            ->sum('lecture_rate_at_attendance');

        // 3. الرسم البياني (آخر 6 أشهر)
        $monthlyStats = LecturerAttendance::where('college_id', $id)
            ->select(
                DB::raw('DATE_FORMAT(attendance_date, "%Y-%m") as month_key'),
                DB::raw('SUM(lecture_rate_at_attendance) as total_amount')
            )
            ->where('attendance_date', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month_key')
            ->orderBy('month_key', 'asc')
            ->get();

        // 4. أعلى أعضاء هيئة التدريس صرفاً
        $topSpenders = LecturerAttendance::where('college_id', $id)
            ->with(['lecturer.user:user_id,full_name', 'lecturer.department:department_id,department_name'])
            ->selectRaw('lecturer_id, SUM(lecture_hours) as total_hours, SUM(lecture_rate_at_attendance) as total_amount')
            ->groupBy('lecturer_id')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->lecturer->user->full_name ?? 'غير معروف',
                    'department' => $item->lecturer->department->department_name ?? '-',
                    'hours' => $item->total_hours,
                    'amount' => $item->total_amount
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'counts' => [
                    'departments' => $departmentsCount,
                    'classrooms' => $classroomsCount,
                    'programs' => $programsCount,
                    'staff' => $staffCount,
                ],
                'financials' => [
                    'current_month' => $currentMonthExpenses,
                    'last_six_months' => $monthlyStats,
                    'top_spenders' => $topSpenders
                ]
            ]
        ]);
    }
}