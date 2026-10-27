<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\IntegrationClient;
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\DB;

echo "==== العملاء ====\n";
foreach (IntegrationClient::all() as $c) {
    echo "id={$c->id} | name={$c->name} | active=" . var_export($c->is_active, true)
       . " | hash=" . substr($c->token_hash, 0, 12) . "..."
       . " | ips=" . implode(',', $c->allowed_ips ?? [])
       . " | last_used=" . ($c->last_used_at ?: 'never')
       . " | created=" . $c->created_at . "\n";
}

echo "\n==== آخر 12 طلب مسجّل (اليوم بالأخص) ====\n";
$rows = IntegrationLog::orderByDesc('id')->limit(12)->get();
foreach ($rows as $l) {
    echo "id={$l->id} | {$l->created_at} | client_id=" . ($l->client_id ?: 'NULL')
       . " | {$l->method} {$l->path} | ip={$l->ip} | status={$l->http_status}"
       . " | summary=" . mb_substr(json_encode($l->summary, JSON_UNESCAPED_UNICODE), 0, 120) . "\n";
}

echo "\n==== هل طلبات فاشلة (401) سجّلت client_id؟ ====\n";
$fails = IntegrationLog::where('http_status', 401)->orderByDesc('id')->limit(5)->get();
foreach ($fails as $f) {
    echo "time={$f->created_at} | client_id=" . ($f->client_id ?: 'NULL (التوكن لم يطابق أي عميل)')
       . " | path={$f->path} | ip={$f->ip}\n";
}
