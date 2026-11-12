<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use App\Models\Subscription\Subscription;
use App\Jobs\DraftAutoApplyJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ═══════════════════════════════════════════════════════════════════
//  QUEUE WORKERS
//
//  Strategy:
//  ────────────────────────────────────────────────────────────────
//  · Alerts get their own dedicated queues — they are time-sensitive
//    and must never be starved by slow AI jobs.
//  · WhatsApp / Telegram are rate-limited by the provider, so they
//    run at their own pace with a slower --sleep.
//  · Letters (CV / cover letter, Gemini API) run separately so a
//    backlog of 100 AI jobs never blocks alert delivery.
//  · Every worker exits after 50s so the next minute's cron cycle
//    always starts a fresh process — no long-lived zombie workers.
//
//  Queue → worker mapping:
//    alerts-email     ┐
//    default          ┴── worker A (fast: ~200/min)
//    alerts-whatsapp  ─── worker B (slow: ~30/min, sleeps 3s)
//    alerts-telegram  ─── worker C (slow: ~30/min, sleeps 3s)
//    letters, auto-apply, ai-screening, emails
//                     ─── worker D (background: AI + support work)
// ═══════════════════════════════════════════════════════════════════

// ── Worker A — fast path: email alerts + generic default queue
Schedule::command(
    'queue:work ' .
    '--queue=alerts-email,default ' .
    '--stop-when-empty ' .
    '--max-time=50 ' .
    '--max-jobs=200 ' .
    '--timeout=90 ' .
    '--tries=1 ' .
    '--sleep=1'
)
    ->everyMinute()
    ->runInBackground()
    ->name('queue-worker-alerts-email');

// ── Worker B — WhatsApp alerts (rate-limited by Meta)
Schedule::command(
    'queue:work ' .
    '--queue=alerts-whatsapp ' .
    '--stop-when-empty ' .
    '--max-time=50 ' .
    '--max-jobs=30 ' .
    '--timeout=60 ' .
    '--tries=1 ' .
    '--sleep=3'
)
    ->everyMinute()
    ->runInBackground()
    ->name('queue-worker-alerts-whatsapp');

// ── Worker C — Telegram alerts (rate-limited by Telegram)
Schedule::command(
    'queue:work ' .
    '--queue=alerts-telegram ' .
    '--stop-when-empty ' .
    '--max-time=50 ' .
    '--max-jobs=30 ' .
    '--timeout=60 ' .
    '--tries=1 ' .
    '--sleep=3'
)
    ->everyMinute()
    ->runInBackground()
    ->name('queue-worker-alerts-telegram');

// ── Worker D — CV / cover letter generation + AI screening + misc
//   Gemini calls can take 30–120s each. Generous --timeout, low
//   --max-jobs so a slow batch doesn't eat the whole minute.
Schedule::command(
    'queue:work ' .
    '--queue=letters,auto-apply,ai-screening,emails ' .
    '--stop-when-empty ' .
    '--max-time=50 ' .
    '--max-jobs=10 ' .
    '--timeout=180 ' .
    '--tries=3 ' .
    '--sleep=1'
)
    ->everyMinute()
    ->runInBackground()
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
//  AUTO APPLY — AI DRAFTING (Every 3 Days)
//
//  Every 3 days at 03:00 AM, we find active subscribers and dispatch
//  the DraftAutoApplyJob. This job:
//    - Finds matching jobs (last 3 days)
//    - Uses AI to tailor the CV and cover letter
//    - Saves them as drafts for the admin to review and send
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
    ->name('auto-apply-drafts')      // <--- MUST COME BEFORE withoutOverlapping
    ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  AUTO APPLY — LEGACY RUNNER (Keep as fallback)
// ═══════════════════════════════════════════════════════════════════
// Disabled by default because it sends immediately without admin review.
// Uncomment if you ever want fully automatic sending.
//
// Schedule::command('auto-apply:run')
//     ->dailyAt('03:00')
//     ->withoutOverlapping();

// ═══════════════════════════════════════════════════════════════════
//  DATA CLEANUP
// ═══════════════════════════════════════════════════════════════════
Schedule::command('queue:prune-failed --hours=168')
    ->dailyAt('04:00')
    ->withoutOverlapping();

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
