<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\LectureSession;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LectureSessionController extends Controller
{
    public function index(Request $request)
    {
        // 1. ابدأ ببناء الاستعلام
        $query = LectureSession::query();
    
        // 2. إضافة التحميل المسبق للعلاقات
        $query->with([
            'timetable.day', 
            'timetable.course:course_id,course_name,course_code',
            'timetable.lecturer.user:user_id,full_name',
            
            // ✅ التعديل هنا: جلب اسم المجموعة + عدد الطلاب فيها
            'timetable.group' => function ($q) {
                $q->select('group_id', 'group_name')
                  ->withCount('students'); // تأكد أن العلاقة students موجودة في موديل StudentGroup
            },
    
            // جلب القاعة والمبنى المرتبط بها
            'timetable.classroom.building:building_id,building_name', 
    
            // جلب القسم القاعة الفعلية (مهم جداً للتعديلات السابقة)
            'timetable.department:department_id,department_name',
            
            // ✅ إضافة هامة: جلب بيانات القاعة الفعلية للجلسة (وليس فقط المجدولة)
            'actualClassroom.building' 
        ]);
        
        // 3. إضافة فلتر نطاق التاريخ للعرض الأسبوعي
        if ($request->has('start_date') && $request->has('end_date')) {
            $request->validate([
                'start_date' => 'date|date_format:Y-m-d',
                'end_date'   => 'date|date_format:Y-m-d|after_or_equal:start_date',
            ]);
            $query->whereBetween('session_date', [$request->start_date, $request->end_date]);
        }
    
        // --- (الفلاتر الحالية لديك) ---
        if ($request->filled('lecturer_id')) {
            $query->whereHas('timetable', function ($q) use ($request) {
                $q->where('lecturer_id', (int)$request->lecturer_id);
            });
        }
        if ($request->filled('session_date')) {
            $query->where('session_date', $request->session_date);
        }
        if ($request->filled('status')) {
            $query->where('status', (int)$request->status);
        }
        if ($request->has('college_id')) {
             $query->whereHas('timetable', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }
    
        // 4. جلب البيانات وترتيبها
        $sessions = $query->orderBy('session_date')->orderBy('start_time')->get();
    
        // 5. إرجاع الاستجابة
        return response()->json([
            'status' => true,
            'data' => $sessions
        ]);
    }

     /**
     * جلب المحاضرات التي لم يتم إنشاء جلسة لها اليوم.
     */
        /**
     * [نسخة تشخيصية] جلب المحاضرات التي لم يتم إنشاء جلسة لها اليوم.
     */
        /**
     * جلب المحاضرات القابلة للجدولة مع التواريخ المتاحة لها فقط.
     */
    public function getSchedulableLectures(Request $request)
    {
        try {
            // جلب جميع المحاضرات النشطة
            // يمكنك إضافة فلاتر هنا، مثلاً حسب الكلية
            $timetables = Timetable::where('status', 1)
                                   ->with('course', 'group', 'day')
                                   ->get();

            // جلب كل الجلسات الموجودة حالياً وتجميعها حسب timetable_id
            $existingSessions = LectureSession::select('timetable_id', 'session_date')
                                                ->get()
                                                ->groupBy('timetable_id')
                                                ->map(function ($sessions) {
                                                    // تحويل التواريخ إلى صيغة Y-m-d لسهولة المقارنة
                                                    return $sessions->pluck('session_date')
                                                                    ->map(fn($date) => $date instanceof \Carbon\Carbon ? $date->toDateString() : (string) $date)
                                                                    ->all();
                                                });
            
            $schedulableLectures = [];

            foreach ($timetables as $timetable) {
                // 1. توليد كل التواريخ الممكنة نظرياً للمحاضرة
                $allPossibleDates = [];
                try {
                    $startDate = new \DateTime($timetable->start_date);
                    $endDate = new \DateTime($timetable->end_date);
                    $interval = new \DateInterval('P1D');
                    $dateRange = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day'));
    
                    // هذا مجرد مثال، يجب تعديله ليطابق الترقيم في جدول `days` لديك
                    $dayMap = [1 => 6, 2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5]; // سبت->6, أحد->0
                    $targetDayOfWeek = $dayMap[$timetable->day_id] ?? -1;
    
                    foreach ($dateRange as $date) {
                        if ($date->format('w') == $targetDayOfWeek) {
                            $allPossibleDates[] = $date->format('Y-m-d');
                        }
                    }
                } catch (\Exception $e) {
                    // تجاهل المحاضرات ذات التواريخ غير الصالحة
                    continue;
                }

                // 2. جلب التواريخ التي تم إنشاؤها بالفعل لهذه المحاضرة
                $existingDatesForThisTimetable = $existingSessions->get($timetable->timetable_id, []);

                // 3. حساب التواريخ المتبقية (المتاحة للجدولة)
                $availableDates = array_values(array_diff($allPossibleDates, $existingDatesForThisTimetable));

                // 4. أضف المحاضرة إلى القائمة فقط إذا كانت هناك تواريخ متاحة لها
                if (!empty($availableDates)) {
                    // أضف قائمة التواريخ المتاحة إلى كائن المحاضرة
                    $timetable->available_dates = $availableDates;
                    $schedulableLectures[] = $timetable;
                }
            }

            return response()->json(['data' => $schedulableLectures]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب المحاضرات القابلة للجدولة.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء جلسة محاضرة جديدة.
     */
    public function store(Request $request)
    {
        /** @var \Illuminate\Http\Request $request */ // ⬅️ أضف هذا السطر
        $validated = $request->validate([
            'timetable_id' => 'required|integer|exists:timetable,timetable_id',
            'session_date' => 'required|date',
        ]);

        $timetable = Timetable::with('period', 'group.members')->find($validated['timetable_id']);

        // التحقق من أن تاريخ الجلسة يقع ضمن نطاق المحاضرة
        if ($validated['session_date'] < $timetable->start_date || $validated['session_date'] > $timetable->end_date) {
            return response()->json(['message' => 'تاريخ الجلسة خارج النطاق الزمني المحدد للمحاضرة.'], 422);
        }

        // التحقق من أن يوم الجلسة يطابق يوم المحاضرة
        $sessionDayOfWeek = date('w', strtotime($validated['session_date'])); // 0 for Sunday, 6 for Saturday
        // نفترض أن day_id في جدول days يتبع نفس الترقيم أو يتم مطابقته
        // $timetableDayId = $timetable->day_id;
        // if ($sessionDayOfWeek != $timetableDayId) { ... }

        $session = LectureSession::create([
            'timetable_id' => $timetable->timetable_id,
            'session_date' => $validated['session_date'],
            'start_time' => $timetable->period->start_time,
            'end_time' => $timetable->period->end_time,
            'actual_classroom_id' => $timetable->classroom_id,
            'actual_attendance_count' => $timetable->group->members->count(), // عدد الطلاب في المجموعة
            'session_code' => uniqid('SESS_'),
            'status' => 0, // مجدولة
            'attendance_overage_alert' => false,
            'system_attendance_count' => 0,
        ]);

        return response()->json(['data' => $session, 'message' => 'تم إنشاء الجلسة بنجاح.'], 201);
    }

    /**
    * إنشاء جلسات متعددة لمحاضرة واحدة.
    */
    public function storeBulk(Request $request)
    {
        /** @var \Illuminate\Http\Request $request */ // ⬅️ أضف هذا السطر
        $validated = $request->validate([
            'timetable_id' => 'required|integer|exists:timetable,timetable_id',
        ]);
    
        $timetable = Timetable::with('period')->findOrFail($validated['timetable_id']);
        
        // 1. توليد كل التواريخ الممكنة للمحاضرة بناءً على يوم الأسبوع
        $availableDates = [];
        $startDate = new \DateTime($timetable->start_date);
        $endDate = new \DateTime($timetable->end_date);
        $interval = new \DateInterval('P1D'); // فترة يوم واحد
        $dateRange = new \DatePeriod($startDate, $interval, $endDate->modify('+1 day')); // +1 day لتشمل يوم النهاية
    
        // تحويل day_id من قاعدة البيانات إلى رقم يوم الأسبوع في PHP (0=الأحد, 6=السبت)
        // هذا مجرد مثال، يجب تعديله ليطابق الترقيم في جدول `days` لديك
        $dayMap = [1 => 6, 2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5];
        $targetDayOfWeek = $dayMap[$timetable->day_id] ?? -1;
    
        foreach ($dateRange as $date) {
            if ($date->format('w') == $targetDayOfWeek) {
                $availableDates[] = $date->format('Y-m-d');
            }
        }
        
        if (empty($availableDates)) {
            return response()->json(['message' => 'لا توجد تواريخ متاحة للجدولة في هذا النطاق.'], 404);
        }
        
        // 2. جلب الجلسات الموجودة بالفعل لهذه المحاضرة لتجنب التكرار
        $existingSessionDates = LectureSession::where('timetable_id', $timetable->timetable_id)
                                                ->pluck('session_date')
                                                ->map(fn($date) => $date instanceof \Carbon\Carbon ? $date->toDateString() : (string) $date)
                                                ->all();
    
        // 3. تحديد التواريخ الجديدة فقط التي سيتم إنشاؤها
        $datesToCreate = array_diff($availableDates, $existingSessionDates);
        
        $createdCount = 0;
        $sessionsToInsert = [];
        $now = now();
    
        // 4. إعداد البيانات للإنشاء المجمع
        foreach ($datesToCreate as $date) {
            $sessionsToInsert[] = [
                'timetable_id' => $timetable->timetable_id,
                'session_date' => $date,
                'start_time' => $timetable->period->start_time,
                'end_time' => $timetable->period->end_time,
                'actual_classroom_id' => $timetable->classroom_id,
                'session_code' => uniqid('SESS_'),
                'status' => 0,
                'attendance_overage_alert' => false,
                'system_attendance_count' => 0,
                'actual_attendance_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $createdCount++;
        }
    
        // 5. إدخال جميع الجلسات الجديدة في قاعدة البيانات بطلب واحد (أكثر كفاءة)
        if (!empty($sessionsToInsert)) {
            LectureSession::insert($sessionsToInsert);
        }
    
        return response()->json([
            'message' => 'اكتملت عملية الإنشاء المجمع.',
            'created_count' => $createdCount,
            'skipped_count' => count($availableDates) - $createdCount,
        ], 201);
    }

     /**
     * عرض تفاصيل جلسة محددة
     */
    public function show($id)
    {
        // جلب الجلسة مع بيانات الجدول المرتبطة (المادة، المحاضر، القاعة، المجموعة)
        $session = \App\Models\LectureSession::with([
            'timetable.course',
            'timetable.lecturer.user',
            'timetable.group',
            'timetable.classroom'
        ])->findOrFail($id);

        return response()->json($session);
    }

    /**
     * تعديل بيانات الجلسة
     */
    public function update(\Illuminate\Http\Request $request, $id)
    {
        $session = \App\Models\LectureSession::findOrFail($id);

        $validated = $request->validate([
            'session_date' => 'sometimes|date',
            'status' => 'sometimes|integer',
            'actual_classroom_id' => 'nullable|exists:classrooms,classroom_id',
            // 'actual_lecturer_id'  => 'nullable|exists:lecturers,lecturer_id', // ✅ الحقل الجديد
            'start_time' => 'nullable', // يقبل H:i
            'end_time'   => 'nullable',
        ]);

        $session->update($validated);

        return response()->json([
            'message' => 'Lecture session updated successfully',
            'data' => $session
        ]);
    }

    /**
     * حذف الجلسة
     */
    public function destroy($id)
    {
        $session = \App\Models\LectureSession::findOrFail($id);
        $session->delete();

        return response()->json([
            'message' => 'Lecture session deleted successfully'
        ]);
    }
} 