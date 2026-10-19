<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\AutoCloseSessions;
use App\Console\Commands\PruneIntegrationData;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(AutoCloseSessions::class)->everyFifteenMinutes();

// تقليم سجلات التكامل ومفاتيح Idempotency المنتهية يومياً
Schedule::command(PruneIntegrationData::class)->daily();