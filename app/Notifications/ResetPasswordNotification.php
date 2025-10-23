<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = config('app.frontend_url', 'http://localhost:5173');
        $url = $frontend . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور')
            ->greeting('مرحباً،')
            ->line('لقد استلمنا طلباً لإعادة تعيين كلمة المرور لحسابك.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('إذا لم تطلب ذلك، تجاهل هذه الرسالة.')
            ->salutation('فريق النظام');
    }
}