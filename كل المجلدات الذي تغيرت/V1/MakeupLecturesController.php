<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\V1\MakeupLecture\StoreMakeupLectureRequest;
use App\Http\Requests\V1\MakeupLecture\ReviewMakeupLectureRequest;
use App\Models\MakeupLecturesRequest; // تأكد من اسم الموديل (مفرد أم جمع)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MakeupLecturesController extends Controller
{

    public function indexByCollege(Request $request, $collegeId)
    {
        // استقبال الفلتر من الواجهة
        $statusGroup = $request->query('status_group', 'pending');
        
        // تحديد أرقام الحالات المطلوبة بناءً على الصفحة
        $statusIds = match($statusGroup) {
            'pending'   => [0, 1, 2], // للصفحة الإدارية: قيد التوقيع
            'ready'     => [3],       // لصفحة الجداول (TimetableModule): جاهز للجدولة
            'scheduled' => [4],       // للصفحة الإدارية: الأرشيف المجدول
            'rejected'  => [5],       // للصفحة الإدارية: المرفوض
            default     => [0, 1, 2]
        };

        $user = $request->user()->load('userType'); 
        
        $query = MakeupLecturesRequest::query()
            ->whereHas('course', function($q) use ($collegeId) {
                $q->where('college_id', $collegeId);
            })
            ->whereIn('status', $statusIds);

        // منطق رئيس القسم (يرى طلبات قسمه فقط)
        if ($user->userType?->user_type_code === 'dept_head') {
            $lecturerProfile = $user->lecturer; 
            if ($lecturerProfile) {
                $deptId = $lecturerProfile->department_id;
                $query->whereHas('course', function($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
        }

        $requests = $query->with([
                'lecturer.user:user_id,full_name',
                'course:course_id,course_name',
                'group:group_id,group_name',
                'classroom:classroom_id,classroom_name'
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($req) {
                return [
                    'request_id' => $req->request_id,
                    'lecturer_name' => $req->lecturer->user->full_name ?? 'غير معروف',
                    'course_name' => $req->course->course_name ?? '—',
                    'group_name' => $req->group->group_name ?? '—',
                    'original_date' => $req->original_date,
                    'requested_date' => $req->requested_date,
                    'start_time' => $req->start_time,
                    'end_time' => $req->end_time,
                    'classroom_name' => $req->classroom->classroom_name ?? null,
                    'reason_type' => $req->reason_type,
                    'description' => $req->description,
                    'status' => $req->status,
                ];
            });

        return response()->json(['data' => $requests]);
    }

    /**
     * تقديم طلب محاضرة تعويضية (للمحاضر)
     */
    public function store(StoreMakeupLectureRequest $request) 
    {
        // البيانات تم التحقق منها في الـ Request
        $makeup = MakeupLecturesRequest::create([
            ...$request->validated(),
            'status' => 0, // Pending
            'notification_status' => 0
        ]);

        return response()->json(['message' => 'تم إرسال الطلب', 'data' => $makeup], 201);
    }

    /**
     * تحديث الحالة (موافقة إدارية أو تحديد كمجدولة)
     * + إرسال إشعار للطلاب عند الجدولة (الحالة 4)
     */
    public function approve(ReviewMakeupLectureRequest $request, $id) 
    {
        // نحتاج جلب العلاقات (course, lecturer, classroom) لبناء نص الرسالة
        $makeup = MakeupLecturesRequest::with(['course', 'lecturer', 'classroom'])->findOrFail($id);
        
        // --- إضافة منطق الإشعار عند الجدولة (Status = 4) ---
        if ($request->status == 4) {
            try {
                // 1. تجهيز بيانات الرسالة
                $courseName = $makeup->course->course_name ?? 'مادة غير محددة';
                $date = $makeup->requested_date;
                // تنسيق الوقت لإزالة الثواني
                $start = \Carbon\Carbon::parse($makeup->start_time)->format('H:i');
                $end = \Carbon\Carbon::parse($makeup->end_time)->format('H:i');
                $room = $makeup->classroom ? $makeup->classroom->classroom_name : 'قاعة غير محددة';

                // 2. صياغة العنوان والمحتوى
                $subject = "محاضرة تعويضية: {$courseName}";
                
                $messageBody = "عزيزي الطالب، نود إعلامك بأنه قد تم جدولة محاضرة تعويضية لمادة ({$courseName}).\n" .
                               "📅 التاريخ: {$date}\n" .
                               "⏰ الوقت: من {$start} إلى {$end}\n" .
                               "📍 المكان: {$room}\n" .
                               "يرجى الالتزام بالحضور.";

                // 3. إدخال الإشعار في الجدول
                DB::table('lecturer_group_notifications')->insert([
                    'lecturer_user_id' => $makeup->lecturer->user_id, // الانتباه: نستخدم user_id الخاص بالمحاضر
                    'group_id' => $makeup->group_id,
                    'subject' => $subject,
                    'message_body' => $messageBody,
                    'send_at' => now(), // وقت الإرسال الحالي
                    'is_sent' => 0,     // 0: في الانتظار (ليقوم الكرون جوب بإرساله)، أو 1 إذا كان النظام فورياً
                    'is_seen' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                // نسجل الخطأ ولكن لا نوقف العملية، لكي تتم الجدولة حتى لو فشل الإشعار
                Log::error("فشل إنشاء إشعار المحاضرة التعويضية للطلب #{$id}: " . $e->getMessage());
            }
        }

        // --- تحديث حالة الطلب ---
        $makeup->update([
            'status' => $request->status, 
        ]);

        $msg = ($request->status == 4) 
                ? ' تم إرسال إشعار للمجموعة الطلابية.' 
                : 'تم تحديث حالة الطلب بنجاح';

        return response()->json(['message' => $msg]);
    }
}