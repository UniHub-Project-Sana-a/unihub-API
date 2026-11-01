<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\LecturerGroupNotification;
use App\Models\StudentAttendance;
use App\Models\StudentExcuseSubmission;
use App\Models\StudentGroup;
use App\Models\Timetable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LecturerInteractionController extends Controller
{
    /**
     * استرجاع المحاضر المرتبط بالمستخدم الحالي
     */
    protected function getCurrentLecturer(Request $request): ?Lecturer
    {
        $user = $request->user();
        if (!$user) return null;

        return Lecturer::where('user_id', $user->user_id)->first();
    }

    /**
     * قائمة المجموعات والمقررات التي يدرسها هذا المحاضر (لإرسال إعلانات أو مراجعة الأعذار)
     * GET /api/v1/lecturer/my-groups
     */
    public function myGroups(Request $request): JsonResponse
    {
        $lecturer = $this->getCurrentLecturer($request);
        if (!$lecturer) {
            return response()->json(['message' => 'بيانات المحاضر غير موجودة.'], 404);
        }

        $timetables = Timetable::with(['course', 'group'])
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->get();

        $groups = [];
        foreach ($timetables as $tt) {
            if (!$tt->group) continue;
            $gId = $tt->group->group_id;
            if (!isset($groups[$gId])) {
                $groups[$gId] = [
                    'group_id' => $gId,
                    'group_name' => $tt->group->group_name,
                    'courses' => [],
                ];
            }
            if ($tt->course && !in_array($tt->course->course_name, $groups[$gId]['courses'])) {
                $groups[$gId]['courses'][] = $tt->course->course_name;
            }
        }

        return response()->json([
            'status' => true,
            'groups' => array_values($groups),
        ]);
    }

    /**
     * استعراض أعذار الطلاب المقدمة للمحاضر
     * GET /api/v1/lecturer/student-excuses
     */
    public function studentExcuses(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json([], 401);

        $excuses = StudentExcuseSubmission::with(['course', 'studentUser.student.department'])
            ->where('lecturer_user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($ex) => [
                'id' => $ex->submission_id,
                'student_name' => $ex->studentUser->full_name ?? 'طالب',
                'academic_number' => $ex->studentUser->academic_number ?? '',
                'course_name' => $ex->course->course_name ?? '',
                'request_date' => $ex->request_date ? Carbon::parse($ex->request_date)->format('Y-m-d') : '',
                'reason' => $ex->reason,
                'excuse_image' => $ex->excuse_image,
                'response_status' => (int)$ex->response_status,
                'status_text' => match ((int)$ex->response_status) {
                    1 => 'مقبول ✅',
                    2 => 'مرفوض ❌',
                    default => 'قيد الانتظار ⏳'
                },
                'lecturer_comment' => $ex->lecturer_comment,
                'created_at' => $ex->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'status' => true,
            'excuses' => $excuses,
        ]);
    }

    /**
     * قبول أو رفض عذر الطالب
     * POST /api/v1/lecturer/student-excuses/{id}/review
     */
    public function reviewExcuse(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'status' => 'required|in:1,2', // 1: قبول, 2: رفض
            'comment' => 'nullable|string|max:500',
        ]);

        $excuse = StudentExcuseSubmission::where('submission_id', $id)
            ->where('lecturer_user_id', $user->user_id)
            ->first();

        if (!$excuse) {
            return response()->json(['message' => 'العذر غير موجود أو لا تملك صلاحية تعديله.'], 404);
        }

        $excuse->response_status = (int)$validated['status'];
        $excuse->lecturer_comment = $validated['comment'] ?? null;
        $excuse->save();

        // في حال تم القبول، نقوم بتحديث سجل الحضور للغياب بعذر إن وجد
        if ($excuse->response_status === 1) {
            $student = Student::where('user_id', $excuse->student_user_id)->first();
            if ($student) {
                // نحدث حالة الحضور لليوم المحدد إلى 2 (غياب بعذر)
                StudentAttendance::where('student_id', $student->student_id)
                    ->whereDate('attendance_date', $excuse->request_date)
                    ->where('status', 0) // كان غائباً
                    ->update(['status' => 2]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => $excuse->response_status === 1 ? 'تم قبول العذر وتوثيقه كغياب رسمي بعذر.' : 'تم رفض العذر بنجاح.',
            'excuse' => $excuse,
        ]);
    }

    /**
     * إرسال إعلان أو تنبيه للمجموعة (اختبار، اعتذار عن محاضرة، تكليف...)
     * POST /api/v1/lecturer/group-announcements
     */
    public function postAnnouncement(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'group_id' => 'required|exists:student_groups,group_id',
            'subject' => 'required|string|max:150',
            'message_body' => 'required|string|min:5|max:2000',
            'notification_type' => 'nullable|string', // exam, apology, assignment, general
        ]);

        $notification = LecturerGroupNotification::create([
            'lecturer_user_id' => $user->user_id,
            'group_id' => $validated['group_id'],
            'subject' => $validated['subject'],
            'message_body' => $validated['message_body'],
            'send_at' => now(),
            'is_sent' => true,
            'is_seen' => false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم بث الإعلان بنجاح لجميع طلاب المجموعة.',
            'data' => $notification,
        ], 201);
    }

    /**
     * استعراض الإعلانات السابقة التي أرسلها المحاضر
     * GET /api/v1/lecturer/group-announcements
     */
    public function getAnnouncements(Request $request): JsonResponse
    {
        $user = $request->user();

        $list = LecturerGroupNotification::with('group')
            ->where('lecturer_user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->notification_id,
                'group_id' => $item->group_id,
                'group_name' => $item->group->group_name ?? 'المجموعة',
                'subject' => $item->subject,
                'message' => $item->message_body,
                'date' => $item->send_at ? $item->send_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'status' => true,
            'announcements' => $list,
        ]);
    }
}

