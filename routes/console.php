<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule untuk release expired orders setiap 30 detik
Schedule::command('orders:release-expired')->everyThirtySeconds();

// Schedule untuk generate daily summary setiap hari jam 00:00 (midnight)
// Akan generate untuk hari kemarin secara otomatis
Schedule::command('reports:generate-daily')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        info('✅ Daily summaries generated successfully');
    })
    ->onFailure(function () {
        error('❌ Daily summaries generation failed');
    });
