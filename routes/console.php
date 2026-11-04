<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════════════
//  QUEUE WORKER — the most important command on the whole site
//
//  Runs every minute via schedule:run. Drains every queue that
//  your job classes use, for up to 50 seconds, then exits so the
//  next cron cycle starts a fresh process.
//
//  When you add a new queue name in a future job class, just add
//  it to the --queue= list below. Order = priority (left is first).
// ═══════════════════════════════════════════════════════════════════
Schedule::command(
    'queue:work ' .
    '--queue=letters,default,auto-apply,ai-screening,emails ' .
    '--stop-when-empty ' .
    '--max-time=50 ' .
    '--max-jobs=30 ' .
    '--timeout=180 ' .
    '--tries=3 ' .
    '--sleep=1'
)
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// ═══════════════════════════════════════════════════════════════════
//  WATCHDOG — re-dispatch letters/CVs stuck at "paid"
// ═══════════════════════════════════════════════════════════════════
Schedule::command('queue:redispatch-stuck --minutes=15')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  HOUSEKEEPING
// ═══════════════════════════════════════════════════════════════════
Schedule::command('screening:cleanup-stuck')->hourly();

Schedule::command('auto-apply:run')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')->dailyAt('04:00');

Schedule::command('letters:cleanup-temp-cvs --hours=24 --max-age-days=3')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  LOCK CLEANUP — clear stuck queue-overlap + Gemini cooldown keys
// ═══════════════════════════════════════════════════════════════════
Schedule::call(function () {
    $driver = config('cache.default');

    if ($driver === 'database') {
        $table = config('cache.stores.database.table', 'cache');
        $deleted = DB::table($table)
            ->where(function ($q) {
                $q->where('key', 'like', '%laravel-queue-overlap%')
                  ->orWhere('key', 'like', 'gemini_letter_cooldown:%');
            })
            ->delete();
        Log::info('Daily queue-lock cleanup', ['deleted' => $deleted, 'driver' => $driver]);

    } elseif ($driver === 'file') {
        $path = storage_path('framework/cache/data');
        $count = 0;
        if (is_dir($path)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                $real = $file->getRealPath();
                if (str_contains($real, 'overlap')
                    || str_contains($real, 'gemini_letter_cooldown')) {
                    @unlink($real);
                    $count++;
                }
            }
        }
        Log::info('Daily queue-lock cleanup', ['deleted' => $count, 'driver' => $driver]);
    }
})->dailyAt('05:00')->name('queue-locks-cleanup');