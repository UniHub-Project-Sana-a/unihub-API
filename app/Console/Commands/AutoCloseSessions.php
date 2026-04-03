<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LectureSession;
use Carbon\Carbon;

class AutoCloseSessions extends Command
{
    protected $signature = 'sessions:auto-close';
    protected $description = 'إغلاق الجلسات المعلقة بعد انتهاء وقتها بـ 30 دقيقة';

    public function handle()
    {
        // نحدد وقت "التسامح" (Buffer) - مثلاً 30 دقيقة بعد النهاية
        $bufferMinutes = 3;
        
        // الوقت الحالي
        $now = Carbon::now();

        // 1. البحث عن الجلسات الجارية (status = 2) التي انتهى وقتها
        // الشرط: (وقت النهاية المجدول + 30 دقيقة) < الوقت الحالي
        // ملاحظة: end_time مخزن كـ TIME فقط، نحتاج لدمجه مع session_date
        
        $sessions = LectureSession::where('status', 2)->get();

        foreach ($sessions as $session) {
            // تركيب التاريخ والوقت
            $endTimeString = $session->session_date->format('Y-m-d') . ' ' . $session->end_time;
            $scheduledEndTime = Carbon::parse($endTimeString);

            // إضافة الـ Buffer
            $thresholdTime = $scheduledEndTime->copy()->addMinutes($bufferMinutes);

            if ($now->greaterThan($thresholdTime)) {
                // 2. إغلاق الجلسة
                $session->update([
                    'status' => 1,
                    // نعتبر وقت الخروج هو نفس وقت النهاية المجدول (لعدم ظلم المحاضر)
                    // أو نجعله now() إذا أردنا توثيق وقت الإغلاق الفعلي
                    'actual_end_time' => $scheduledEndTime, 
                    'early_exit_reason' => 'إغلاق تلقائي (نسيان تسجيل الخروج)',
                    'is_ended_remotely' => true // نعتبره عن بعد لأنه لم يكن في القاعة
                ]);

                // 3. إبطال الـ QR
                \App\Models\QrCode::where('session_id', $session->session_id)
                    ->update(['is_active' => false]);

                $this->info("Closed Session ID: {$session->session_id}");
            }
        }

        $this->info('Auto close check completed.');
    }
}