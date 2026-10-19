<?php

namespace App\Console\Commands;

use App\Models\IntegrationIdempotency;
use App\Models\IntegrationLog;
use Illuminate\Console\Command;

/**
 * تقليم بيانات التكامل: حذف السجلات الأقدم من مدة الاحتفاظ،
 * ومفاتيح Idempotency المنتهية.
 *
 * مثال:
 *   php artisan integration:prune
 *   php artisan integration:prune --days=30
 *   php artisan integration:prune --dry-run
 */
class PruneIntegrationData extends Command
{
    protected $signature = 'integration:prune
        {--days= : أيام الاحتفاظ بالسجلات (افتراضياً من الإعدادات)}
        {--dry-run : عرض ما سيُحذف دون تنفيذ الحذف}';

    protected $description = 'تقليم سجلات التكامل القديمة ومفاتيح Idempotency المنتهية';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('integration.log_retention_days', 90));
        $days = max(1, $days);
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $logsQuery = IntegrationLog::where('created_at', '<', $cutoff);
        $idemQuery = IntegrationIdempotency::where('expires_at', '<', now());

        $logsCount = (clone $logsQuery)->count();
        $idemCount = (clone $idemQuery)->count();

        if (!$dry) {
            $logsQuery->delete();
            $idemQuery->delete();
        }

        $prefix = $dry ? '[محاكاة] ' : '';
        $this->info($prefix . "سجلات التكامل: {$logsCount} (أقدم من {$cutoff->toDateString()})");
        $this->info($prefix . "مفاتيح Idempotency منتهية: {$idemCount}");

        return self::SUCCESS;
    }
}
