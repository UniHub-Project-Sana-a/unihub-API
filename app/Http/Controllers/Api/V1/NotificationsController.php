<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Notification\StoreNotificationRequest;
use App\Models\LecturerGroupNotification;
use Illuminate\Http\Request;

class NotificationsController extends Controller 
{
        // جلب إشعارات مجموعة معينة للمحاضر الحالي
    public function index(Request $request)
    {
        $request->validate(['group_id' => 'required|integer']);
        
        $notifications = LecturerGroupNotification::where('group_id', $request->group_id)
            ->where('lecturer_user_id', $request->user()->user_id)
            ->orderBy('send_at', 'desc')
            ->get();

        // إضافة إحصائيات وهمية للمشاهدة (لأن الجدول الحالي فيه is_seen واحد لكل الإشعار وليس لكل طالب)
        // لتطوير مستقبلي: نحتاج جدول pivot (notification_student) لحساب المشاهدات بدقة.
        // حالياً سنعيد البيانات كما هي.
        
        return response()->json(['data' => $notifications]);
    }

    public function store(StoreNotificationRequest $request) 
    {
        // استخدام المستخدم الحالي (المحاضر)
        $user = $request->user();
        
        // التحقق من أن الطلب يخص مجموعة (لأن جدولنا الحالي للمجموعات فقط)
        if (!$request->filled('group_id')) {
            return response()->json(['message' => 'group_id is required for lecturer notifications'], 422);
        }

        try {
            // إنشاء الإشعار
            $notification = LecturerGroupNotification::create([
                'lecturer_user_id' => $user->user_id, // استخدام user_id حسب الموديل
                'group_id' => $request->group_id,
                'subject' => $request->subject,
                'message_body' => $request->message_body,
                'send_at' => now(),
                'is_sent' => true,
                'is_seen' => false,
            ]);

            // (هنا يمكن إضافة كود إرسال Firebase/Email لاحقاً)

            return response()->json([
                'message' => 'تم إرسال الإشعار بنجاح',
                'data' => $notification
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل حفظ الإشعار',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        // تعديل الإشعار
    public function update(Request $request, $id)
    {
        $notification = LecturerGroupNotification::where('notification_id', $id)
            ->where('lecturer_user_id', $request->user()->user_id)
            ->firstOrFail();

        $request->validate([
            'subject' => 'required|string|max:150',
            'message_body' => 'required|string',
        ]);

        $notification->update([
            'subject' => $request->subject,
            'message_body' => $request->message_body
        ]);

        return response()->json(['message' => 'تم التعديل بنجاح', 'data' => $notification]);
    }

    // حذف الإشعار
    public function destroy(Request $request, $id)
    {
        $notification = LecturerGroupNotification::where('notification_id', $id)
            ->where('lecturer_user_id', $request->user()->user_id)
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'تم الحذف بنجاح']);
    }
}