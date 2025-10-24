<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MakeupLecture\StoreMakeupLectureRequest;
use App\Http\Requests\V1\MakeupLecture\ReviewMakeupLectureRequest;
use App\Http\Requests\V1\MakeupLecture\ScheduleMakeupLectureRequest;
use App\Models\MakeupLecturesRequest;
use App\Models\Timetable;
use Illuminate\Http\Request;

class MakeupLecturesController extends Controller {
    public function store(StoreMakeupLectureRequest $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $makeup = MakeupLecturesRequest::create([
            'lecturer_id' => $user->lecturer->lecturer_id,
            ...$request->validated()
        ]);
        return response()->json($makeup, 201);
    }

    public function review(ReviewMakeupLectureRequest $request, MakeupLecturesRequest $makeupLecture) {
        // منطق المراجعة من الشؤون الأكاديمية
        $makeupLecture->update($request->validated());
        return response()->json($makeupLecture);
    }

    public function approve(Request $request, MakeupLecturesRequest $makeupLecture) {
        // منطق الموافقة من رئيس القسم
        $makeupLecture->update(['status' => 'approved_by_head']);
        return response()->json($makeupLecture);
    }

    public function schedule(ScheduleMakeupLectureRequest $request, MakeupLecturesRequest $makeupLecture) {
        // منطق جدولة المحاضرة
        // TODO: التحقق من عدم وجود تعارض
        $newTimetableEntry = Timetable::create([
            'course_id' => $makeupLecture->course_id,
            'lecturer_id' => $makeupLecture->lecturer_id,
            'group_id' => $makeupLecture->group_id,
            'classroom_id' => $request->classroom_id,
            'day_id' => $request->day_id,
            'period_id' => $request->period_id,
            // ... باقي الحقول
        ]);
        $makeupLecture->update(['status' => 'scheduled']);
        return response()->json($newTimetableEntry);
    }
}