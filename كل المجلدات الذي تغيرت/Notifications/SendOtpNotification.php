<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOtpNotification extends Notification
{
    use Queueable;
    protected $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        
        return (new MailMessage)
                    ->subject('UniHub - رمز التحقق الخاص بك (OTP)') // ✅ عنوان مخصص
                    ->greeting('مرحبًا ' . $notifiable->full_name . ',')
                    ->line('رمز التحقق لمرة واحدة (OTP) لتسجيل جهاز جديد في نظام UniHub هو:')
                    ->line($this->otp)
                    ->line('هذا الرمز صالح لمدة 10 دقائق.')
                    ->line('شكرًا لاستخدامك نظام UniHub.');
    }
}