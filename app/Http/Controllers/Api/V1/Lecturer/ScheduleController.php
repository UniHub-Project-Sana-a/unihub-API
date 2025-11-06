<?php

namespace App\Http\Controllers\Api\V1\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TimetableEntry;

class ScheduleController extends Controller
{
    // تم تغيير اسم الدالة وإزالة فلتر اليوم
    public function getSchedule(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $lecturer = $user->lecturer;
        if (!$lecturer) {
            return response()->json(['message' => 'Lecturer profile not found.'], 404);
        }

        // جلب جميع بنود الجدول الدراسي للمحاضر
        $entries = TimetableEntry::query()
            ->with(['course', 'group', 'period', 'day']) // <-- أضفنا `day` لجلب اسم اليوم
            ->where('lecturer_id', $lecturer->lecturer_id)
            // ->whereHas('schedule', fn($q) => $q->where('status', 'published')) // يمكنك تفعيل هذا لفلترة الجداول المنشورة فقط
            ->get()
            ->sortBy([
                ['day.day_id', 'asc'], // ترتيب حسب الأيام
                ['period.start_time', 'asc'], // ثم حسب الوقت
            ]);

        return response()->json(['data' => $entries->values()]); // ->values() لإعادة ترتيب الفهارس بعد الفرز
    }
}