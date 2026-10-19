<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManageIntegrationClient extends Command
{
    /**
     * إنشاء/تدوير عميل تكامل خارجي وتوليد توكنه.
     *
     * مثال:
     *   php artisan integration:client "university-oracle" --ips=10.0.0.0/24
     *   php artisan integration:client "university-oracle" --rotate
     */
    protected $signature = 'integration:client
        {name : اسم العميل (فريد)}
        {--ips=* : عناوين IP مسموحة (يدعم CIDR)، يمكن تكرارها}
        {--rate-limit= : حدّ الطلبات لكل دقيقة لهذا العميل (0 = بلا حدّ، تُرك فارغاً = الافتراضي)}
        {--rotate : تدوير التوكن لعميل موجود}
        {--disable : تعطيل العميل}
        {--enable : تفعيل العميل}';

    protected $description = 'إدارة عملاء التكامل الخارجي (integration_clients)';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $client = IntegrationClient::where('name', $name)->first();

        // أوامر التفعيل/التعطيل
        if ($this->option('disable') || $this->option('enable')) {
            if (!$client) {
                $this->error("العميل '{$name}' غير موجود.");
                return self::FAILURE;
            }
            $client->update(['is_active' => (bool) $this->option('enable')]);
            $this->info("تم " . ($this->option('enable') ? 'تفعيل' : 'تعطيل') . " العميل '{$name}'.");
            return self::SUCCESS;
        }

        if ($client && !$this->option('rotate')) {
            // تحديث حدّ المعدّل فقط لعميل موجود
            if ($this->option('rate-limit') !== null) {
                $limit = (int) $this->option('rate-limit');
                $client->update(['rate_limit_per_minute' => $limit]);
                $this->info("تم تحديث حدّ المعدّل للعميل '{$name}' إلى " . ($limit <= 0 ? 'بلا حدّ' : "{$limit} طلب/دقيقة") . '.');
                return self::SUCCESS;
            }

            $this->error("العميل '{$name}' موجود مسبقاً. استخدم --rotate لتدوير التوكن، أو --disable/--enable، أو --rate-limit لضبط الحدّ.");
            return self::FAILURE;
        }

        $token = Str::random(64);
        $ips = array_values(array_filter((array) $this->option('ips')));

        $data = [
            'token_hash' => IntegrationClient::hashToken($token),
            'is_active' => true,
        ];

        // عند الإنشاء أو عند تمرير قوائم جديدة نحدّثها (التدوير وحده يحافظ على القوائم القديمة)
        if (!$client || count($ips) > 0) {
            $data['allowed_ips'] = $ips;
        }

        // حدّ المعدّل (عند تمريره)
        if ($this->option('rate-limit') !== null) {
            $data['rate_limit_per_minute'] = (int) $this->option('rate-limit');
        }

        $client = IntegrationClient::updateOrCreate(['name' => $name], $data);

        $this->newLine();
        $this->info(($this->option('rotate') ? 'تم تدوير توكن' : 'تم إنشاء') . " العميل '{$name}'.");
        $this->line('IPs المسموحة : ' . (count($client->allowed_ips ?? []) ? implode(', ', $client->allowed_ips) : '(فارغة — سيُرفض العميل حتى تضيف IP)'));
        $this->line('حدّ المعدّل   : ' . ($client->rate_limit_per_minute !== null
            ? ($client->rate_limit_per_minute <= 0 ? 'بلا حدّ' : $client->rate_limit_per_minute . ' طلب/دقيقة')
            : 'افتراضي (' . config('integration.default_rate_limit', 600) . ' طلب/دقيقة)'));
        $this->newLine();
        $this->warn('التوكن التالي يُعرض مرّة واحدة فقط — احفظه بأمان:');
        $this->line('<fg=green>' . $token . '</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
