<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = url(config('app.frontend_url', 'http://localhost:8080') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email));

        $frontendUrl = config('app.frontend_url', 'http://localhost:8080');

        // 2. بناء الرابط الكامل
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
                    ->subject('UniHub - إعادة تعيين كلمة المرور') // ✅ عنوان مخصص
                    ->greeting('مرحبًا!')
                    ->line('لقد طلبت إعادة تعيين كلمة المرور لحسابك في نظام UniHub.')
                    ->action('إعادة تعيين كلمة المرور', $url) // ✅ زر مخصص
                    ->line('إذا لم تطلب ذلك، يمكنك تجاهل هذه الرسالة.')
                    ->line('شكرًا لك.');
    }
}