<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentExcusesController extends Controller
{

    /**
     * تحديث حالة العذر من قبل المحاضر
     */
    public function updateStatus(Request $request, $id)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'status' => 'required|in:0,1,2', // 0: معلق, 1: مقبول, 2: مرفوض
            'comment' => 'nullable|string|max:255'
        ]);

        $lecturerId = $request->user()->user_id; // أو $request->user()->id حسب الموديل

        // 2. التأكد من وجود العذر وأنه يخص هذا المحاضر (للحماية)
        $excuse = DB::table('student_excuse_submissions')
            ->where('submission_id', $id)
            ->where('lecturer_user_id', $lecturerId) // أمان: المحاضر يعدل أعذار طلابه فقط
            ->first();

        if (!$excuse) {
            return response()->json(['message' => 'العذر غير موجود أو لا تملك صلاحية تعديله'], 404);
        }

        // 3. التحديث
        DB::table('student_excuse_submissions')
            ->where('submission_id', $id)
            ->update([
                'response_status' => $request->status,
                'lecturer_comment' => $request->comment,
                'is_lecturer_notified' => true, // نعتبر أنه تم الاطلاع عليه
                'updated_at' => now()
            ]);

        // 4. (اختياري) إذا تم القبول، يمكن تحديث حالة الحضور تلقائياً في جدول student_attendance
        // if ($request->status == 1) {
            // منطق تعديل الحضور لـ "بعذر"
            /*
            DB::table('student_attendance')
                ->where('student_id', $excuse->student_id) // تحتاج لجلب student_id من جدول الطلاب
                ->where('attendance_date', $excuse->request_date)
                ->where('timetable_id', ...) // يحتاج منطق ربط معقد قليلاً
                ->update(['status' => 2]); // نفرض أن 2 تعني غياب بعذر
            */
        // }

        return response()->json(['message' => 'تم تحديث حالة العذر بنجاح']);
    }
}