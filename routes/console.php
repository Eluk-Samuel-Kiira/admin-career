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
//  next cron cycle starts a fresh process. This is the ONLY way
//  to run a queue on shared cPanel hosting without triggering the
//  60-second process kill.
//
//  When you add a new queue name in a future job class, just add
//  it to the --queue= list below. Order = priority (left is first).
// ═══════════════════════════════════════════════════════════════════
Schedule::command(
    'queue:work ' .
    '--queue=letters,default,auto-apply,ai-screening,emails ' .
    '--stop-when-empty ' .      // exit when queue is empty
    '--max-time=50 ' .          // hard wall clock (cPanel kills at 60s)
    '--max-jobs=30 ' .          // don't hog a single cron cycle
    '--timeout=180 ' .          // per-job timeout (letter gen is ~90s)
    '--tries=3 ' .              // 3 attempts before failed_jobs
    '--sleep=1'                 // 1s between polls when empty
)
    ->everyMinute()
    ->withoutOverlapping(5)     // release the lock after 5 min if a run hangs
    ->runInBackground();        // don't block the scheduler

// ═══════════════════════════════════════════════════════════════════
//  WATCHDOG — re-dispatch letters/CVs that got stuck at "paid"
//
//  A cron-driven worker can be killed mid-flight (memory, cPanel
//  process limit, SMTP outage, etc.). The job disappears from `jobs`
//  but the letter stays at `paid` forever. This runs every 30 min,
//  finds anything paid >15 min old with no result, and re-dispatches.
// ═══════════════════════════════════════════════════════════════════
Schedule::command('queue:redispatch-stuck --minutes=15')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  HOUSEKEEPING — your existing schedules
// ═══════════════════════════════════════════════════════════════════
Schedule::command('screening:cleanup-stuck')->hourly();

Schedule::command('auto-apply:run')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')->dailyAt('04:00');

Schedule::command('subscriptions:renew')->hourly();
Schedule::command('subscriptions:expire')->daily();

Schedule::command('letters:cleanup-temp-cvs --hours=24 --max-age-days=3')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  LOCK CLEANUP — prevent stuck WithoutOverlapping + job cooldowns
//
//  Every `withoutOverlapping()` call and every `WithoutOverlapping`
//  middleware on a job acquires a cache lock. If a worker dies while
//  holding one, the lock can stay for hours, blocking every future
//  run. This wipes ONLY the queue-overlap keys + Gemini key cooldowns
//  (from GeminiLetterRotator) once a day. Nothing else is touched —
//  your sessions, config cache, and view cache stay intact.
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