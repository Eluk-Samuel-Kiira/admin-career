<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every hour: clean up jobs that got stuck mid-screen
Schedule::command('screening:cleanup-stuck')->hourly();

// Every day at 03:00: auto-apply fanout
Schedule::command('auto-apply:run')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Every day at 04:00: prune failed jobs older than 7 days
Schedule::command('queue:prune-failed --hours=168')->dailyAt('04:00');