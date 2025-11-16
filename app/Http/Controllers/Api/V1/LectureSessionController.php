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
    $query = LectureSession::query();

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

    return response()->json($query->get());
}

     /**
     * جلب المحاضرات التي لم يتم إنشاء جلسة لها اليوم.
     */
        /**
     * [نسخة تشخيصية] جلب المحاضرات التي لم يتم إنشاء جلسة لها اليوم.
     */
    public function getSchedulableLectures(Request $request)
    {
        try {
            $today = now()->toDateString();
            // $scheduledIds = LectureSession::whereDate('session_date', $today)->pluck('timetable_id');
            
            // الخطوة 1: فحص قائمة المحاضرات المجدولة اليوم
            $scheduledIds = LectureSession::whereDate('session_date', $today)->pluck('timetable_id');
            
            // الخطوة 2: بناء الاستعلام الأساسي
            $query = Timetable::whereNotIn('timetable_id', $scheduledIds)
                                ->where('start_date', '<=', $today)
                                ->where('end_date', '>=', $today)
                                ->with('course', 'group', 'day');

            // الخطوة 3 (الأهم): تنفيذ الاستعلام مع العلاقات وفحصه
            $lectures = $query->with('course', 'group')->get();

            // إذا وصل الكود إلى هنا، فكل شيء يعمل.
            return response()->json(['data' => $lectures]);

        } catch (\Exception $e) {
            // إذا حدث أي خطأ، سيتم إيقاف التنفيذ هنا وعرض الخطأ الكامل
            dd('CRITICAL ERROR:', $e);
        }
    }

    /**
     * إنشاء جلسة محاضرة جديدة.
     */
    public function store(Request $request)
    {
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
} 