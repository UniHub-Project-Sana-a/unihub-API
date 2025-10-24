<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Notification\StoreNotificationRequest;
use App\Models\LecturerGroupNotification;
use Illuminate\Http\Request;

class NotificationsController extends Controller {
    public function store(StoreNotificationRequest $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $notification = LecturerGroupNotification::create([
            'lecturer_user_id' => $user->id,
            ...$request->validated()
        ]);
        // TODO: إرسال الإشعار (Push, Email, etc.)
        return response()->json($notification, 201);
    }
}