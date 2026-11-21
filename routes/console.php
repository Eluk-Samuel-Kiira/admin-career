<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\Job\JobPost;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════════════
//  QUEUE WORKERS (Windows / XAMPP — cron-based)
//
//  Queue → worker mapping:
//    alerts-email     ─── Worker A  (email alerts, 20/min via limiter)
//    alerts-whatsapp  ─── Worker B  (WhatsApp alerts)
//    alerts-telegram  ─── Worker C  (Telegram alerts)
//    letters, auto-apply, ai-screening, emails
//                     ─── Worker D  (AI + support work)
// ═══════════════════════════════════════════════════════════════════

// ── Worker A — email alerts only
//   --max-time=58 lets it run nearly the full minute
//   --tries=50 allows many RateLimited releases without failing
//   --max-jobs=25 caps at ~25 emails/min (safely under 20/min limiter + burst)
Schedule::command(
    'queue:work ' .
    '--queue=alerts-email,default ' .
    '--stop-when-empty ' .
    '--max-time=58 ' .
    '--max-jobs=25 ' .
    '--timeout=90 ' .
    '--tries=50 ' .          // ← was 1
    '--sleep=1'
)
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(2)   // avoid duplicate workers from slow cron startup
    ->name('queue-worker-alerts-email');

// ── Worker B — WhatsApp alerts
Schedule::command(
    'queue:work ' .
    '--queue=alerts-whatsapp ' .
    '--stop-when-empty ' .
    '--max-time=58 ' .
    '--max-jobs=30 ' .
    '--timeout=60 ' .
    '--tries=10 ' .          // ← WhatsApp API transient failures get retried
    '--sleep=3'
)
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(2)
    ->name('queue-worker-alerts-whatsapp');

// ── Worker C — Telegram alerts
Schedule::command(
    'queue:work ' .
    '--queue=alerts-telegram ' .
    '--stop-when-empty ' .
    '--max-time=58 ' .
    '--max-jobs=30 ' .
    '--timeout=60 ' .
    '--tries=10 ' .
    '--sleep=3'
)
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(2)
    ->name('queue-worker-alerts-telegram');

// ── Worker D — AI / letters / auto-apply
//   --max-time=290 because Gemini jobs can take 2–5 min
//   --max-jobs=3 to avoid running multiple slow jobs per cycle
//   withoutOverlapping(300) prevents a second worker starting mid-run
Schedule::command(
    'queue:work ' .
    '--queue=letters,auto-apply,ai-screening,emails' .
    '--stop-when-empty ' .
    '--max-time=290 ' .      // ← was 50; jobs take up to 5 min
    '--max-jobs=3 ' .        // ← was 10; slow jobs, don't stack
    '--timeout=180 ' .
    '--tries=3 ' .
    '--sleep=1'
)
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping(300)
    ->name('queue-worker-letters');

// ═══════════════════════════════════════════════════════════════════
//  WATCHDOG — re-dispatch letters/CVs stuck at "paid"
// ═══════════════════════════════════════════════════════════════════
Schedule::command('queue:redispatch-stuck --minutes=15')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  HOUSEKEEPING
// ═══════════════════════════════════════════════════════════════════
Schedule::command('screening:cleanup-stuck')
    ->hourly()
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  AUTO APPLY — AI DRAFTING (Every 3 Days at 03:00)
// ═══════════════════════════════════════════════════════════════════
Schedule::call(function () {
    $activeSubs = \App\Models\Subscription\Subscription::whereIn('status', ['active', 'trialing'])
        ->where('current_period_end', '>', now())
        ->pluck('user_id');

    $seekers = \App\Models\SeekerProfile::whereIn('user_id', $activeSubs)
        ->where('is_active', true)
        ->get();

    foreach ($seekers as $seeker) {
        \App\Jobs\DraftAutoApplyJob::dispatch($seeker->id)->onQueue('auto-apply');
    }

    \Illuminate\Support\Facades\Log::info('Auto Apply: Drafting jobs dispatched for ' . $seekers->count() . ' active subscribers.');
})
    ->cron('0 3 */3 * *')
    ->name('auto-apply-drafts')
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  DATA CLEANUP
// ═══════════════════════════════════════════════════════════════════
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('04:00')
    ->withoutOverlapping();

Schedule::command('letters:cleanup-temp-cvs --hours=24 --max-age-days=3 --stale-days=14')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  LOCK CLEANUP
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

// ═══════════════════════════════════════════════════════════════════
//  FEATURED JOB EXPIRY
// ═══════════════════════════════════════════════════════════════════
Schedule::call(function () {
    JobPost::where('is_featured', true)
        ->whereNotNull('featured_until')
        ->where('featured_until', '<', now())
        ->update(['is_featured' => false]);
})->hourly()->name('unfeature-expired-jobs');

// Re-dispatch queued relays that missed their retry window
Schedule::call(function () {
    $stuck = \App\Models\AutoApplyRelay::where('status', 'queued')
        ->whereNotNull('next_retry_at')
        ->where('next_retry_at', '<=', now())
        ->where('send_attempts', '<', 3)
        ->get();

    foreach ($stuck as $relay) {
        \App\Jobs\RetrySendRelayJob::dispatch($relay->id);
    }

    if ($stuck->count() > 0) {
        \Log::info("AutoApply: Re-dispatched {$stuck->count()} overdue relays");
    }
})->everyTenMinutes()->name('auto-apply-retry-watchdog');