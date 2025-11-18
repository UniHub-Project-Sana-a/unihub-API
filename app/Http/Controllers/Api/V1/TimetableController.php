<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     * يدعم الفلترة والتحميل المسبق للعلاقات.
     * e.g., /api/v1/timetable?college_id=1&department_id=2&with=course,lecturer.user
     */
    public function index(Request $request)
    {
        $query = Timetable::query();

        // تطبيق الفلاتر بناءً على الطلب
        if ($request->has('college_id')) {
            $query->where('college_id', $request->college_id);
        }
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->has('level_id')) {
            $query->where('level_id', $request->level_id);
        }

        // ✅ --- التعديل هنا: إضافة فلتر المحاضر --- ✅
        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }
        // ✅ --- نهاية التعديل --- ✅

        // التحميل المسبق للعلاقات (Eager Loading)
        if ($request->has('with')) {
            try {
                $relations = explode(',', $request->with);
                $query->with($relations);
            } catch (\Exception $e) {
                // تجاهل في حال وجود خطأ في أسماء العلاقات
            }
        }

        // الآن سيتم جلب السجلات المفلترة فقط
        $timetableEntries = $query->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $timetableEntries
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. التحقق الأساسي من صحة البيانات
        $validator = Validator::make($request->all(), [
            'course_id'     => 'required|integer|exists:courses,course_id',
            'lecturer_id'   => 'required|integer|exists:lecturers,lecturer_id',
            'group_id'      => 'required|integer|exists:student_groups,group_id',
            'level_id'      => 'required|integer|exists:levels,level_id', // تأكد من أن هذا الحقل يُرسل بشكل صحيح
            'classroom_id'  => 'required|integer|exists:classrooms,classroom_id',
            'day_id'        => 'required|integer|exists:days,day_id',
            'period_id'     => 'required|integer|exists:periods,period_id',
            'lecture_type'  => 'required|integer|in:0,1,2',
            'status'        => 'sometimes|integer|in:0,1',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'academic_year' => 'required|string|max:20',
            'college_id'    => 'required|integer|exists:colleges,college_id',
            'department_id' => 'required|integer|exists:departments,department_id',
            'gender_type'   => 'sometimes|integer|in:0,1,2',
            'lecture_hours' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'خطأ في التحقق', 'errors' => $validator->errors()], 422);
        }
        
        // 2. التحقق من التعارضات (Conflicts)
        $conflicts = $this->checkForConflicts($request->day_id, $request->period_id, $request->all());
        if (!empty($conflicts)) {
            return response()->json([
                'status' => false,
                'message' => 'تم اكتشاف تعارض.',
                'conflicts' => $conflicts
            ], 409); // 409 Conflict
        }
        
        // 3. إنشاء السجل
        try {
            // استخدم البيانات التي تم التحقق منها
            $timetableEntry = Timetable::create($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء بند الجدول بنجاح.',
                'data' => $timetableEntry
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // ✅ التعديل هنا: إرجاع رسالة الخطأ الفعلية من الخادم
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ غير متوقع أثناء الحفظ في قاعدة البيانات.',
                'error' => $e->getMessage() // <-- هذا السطر مهم جدًا للتشخيص
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Timetable $timetable)
    {
        // تحميل العلاقات لعرض كافة التفاصيل
        $timetable->load('course', 'lecturer.user', 'group', 'classroom', 'day', 'period', 'college', 'department', 'level');
        
        return response()->json([
            'status' => true,
            'data' => $timetable
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timetable $timetable)
    {
        // 1. التحقق من صحة البيانات (استخدام 'sometimes' للسماح بالتحديث الجزئي)
        $validator = Validator::make($request->all(), [
            'course_id'     => 'sometimes|required|integer|exists:courses,course_id',
            'lecturer_id'   => 'sometimes|required|integer|exists:lecturers,lecturer_id',
            'group_id'      => 'sometimes|required|integer|exists:student_groups,group_id',
            'level_id'      => 'sometimes|required|integer|exists:levels,level_id',
            'classroom_id'  => 'sometimes|required|integer|exists:classrooms,classroom_id',
            'day_id'        => 'sometimes|required|integer|exists:days,day_id',
            'period_id'     => 'sometimes|required|integer|exists:periods,period_id',
            'lecture_type'  => 'sometimes|required|integer|in:0,1,2',
            'status'        => 'sometimes|required|integer|in:0,1',
            'start_date'    => 'sometimes|required|date',
            'end_date'      => 'sometimes|required|date|after_or_equal:start_date',
            'academic_year' => 'sometimes|required|string|max:20',
            'college_id'    => 'sometimes|required|integer|exists:colleges,college_id',
            'department_id' => 'sometimes|required|integer|exists:departments,department_id',
            'gender_type'   => 'sometimes|required|integer|in:0,1.2',
            'lecture_hours' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'خطأ في التحقق', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        
        // 2. التحقق من التعارضات، مع استثناء السجل الحالي
        $day_id = $validatedData['day_id'] ?? $timetable->day_id;
        $period_id = $validatedData['period_id'] ?? $timetable->period_id;
        
        $conflicts = $this->checkForConflicts($day_id, $period_id, $validatedData, $timetable->timetable_id);
        if (!empty($conflicts)) {
            return response()->json([
                'status' => false,
                'message' => 'تم اكتشاف تعارض عند التحديث.',
                'conflicts' => $conflicts
            ], 409);
        }

        // 3. تحديث السجل
        $timetable->update($validatedData);

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بند الجدول بنجاح.',
            'data' => $timetable->fresh() // إرجاع النسخة المحدثة
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timetable $timetable)
    {
        try {
            $timetable->delete();
            return response()->json(['status' => true, 'message' => 'تم حذف بند الجدول بنجاح.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'فشل حذف البند.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * دالة مساعدة للتحقق من التعارضات.
     * @param int $day_id
     * @param int $period_id
     * @param array $data - البيانات القادمة من الطلب.
     * @param int|null $ignoreId - معرف السجل الذي يجب تجاهله (يستخدم في التحديث).
     * @return array
     */
    private function checkForConflicts(int $day_id, int $period_id, array $data, int $ignoreId = null): array
    {
        $conflicts = [];

        // التحقق من تعارض القاعة
        if (isset($data['classroom_id'])) {
            $query = Timetable::where('day_id', $day_id)
                              ->where('period_id', $period_id)
                              ->where('classroom_id', $data['classroom_id']);
            if ($ignoreId) {
                $query->where('timetable_id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                $conflicts[] = ['type' => 'classroom', 'message' => 'القاعة الدراسية محجوزة بالفعل في هذا الوقت.'];
            }
        }

        // التحقق من تعارض المحاضر
        if (isset($data['lecturer_id'])) {
            $query = Timetable::where('day_id', $day_id)
                              ->where('period_id', $period_id)
                              ->where('lecturer_id', $data['lecturer_id']);
            if ($ignoreId) {
                $query->where('timetable_id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                $conflicts[] = ['type' => 'lecturer', 'message' => 'المحاضر لديه محاضرة أخرى في نفس الوقت.'];
            }
        }

        // التحقق من تعارض المجموعة الطلابية
        if (isset($data['group_id'])) {
            $query = Timetable::where('day_id', $day_id)
                              ->where('period_id', $period_id)
                              ->where('group_id', $data['group_id']);
            if ($ignoreId) {
                $query->where('timetable_id', '!=', $ignoreId);
            }
            if ($query->exists()) {
                $conflicts[] = ['type' => 'group', 'message' => 'المجموعة الطلابية لديها محاضرة أخرى في نفس الوقت.'];
            }
        }

        return $conflicts;
    }
}