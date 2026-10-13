<?php

namespace App\Jobs;

use App\Models\AutoApplyRun;
use App\Models\SeekerProfile;
use App\Services\AutoApply\JobFinder;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAutoApplyForSeekerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(public int $seekerProfileId) {}

    public function handle(JobFinder $finder, SubscriptionGate $gate): void
    {
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);
        if (!$seeker || !$seeker->user || !$seeker->user->is_active) return;

        // Idempotency: one run per day per user
        $run = AutoApplyRun::firstOrCreate(
            [
                'user_id'           => $seeker->user_id,
                'seeker_profile_id' => $seeker->id,
                'run_date'          => today(),
            ],
            [
                'status' => 'queued',
            ]
        );

        if ($run->status !== 'queued') return;

        $run->update(['status' => 'processing', 'started_at' => now()]);

        try {
            // Quota
            $quota = $gate->autoApplyQuota($seeker->user);
            if ($quota === 0) {
                $run->update([
                    'status'      => 'quota_exhausted',
                    'quota_total' => 0,
                    'completed_at'=> now(),
                ]);
                return;
            }

            // Quota used this month (excluding today)
            $used = \App\Models\Job\JobSeekerJob::where('seeker_profile_id', $seeker->id)
                ->where('is_applied', true)
                ->where('applied_at', '>=', now()->startOfMonth())
                ->count();

            $remaining = $quota === null ? config('auto_apply.max_jobs_per_run') : max(0, $quota - $used);
            $remaining = min($remaining, config('auto_apply.max_jobs_per_run'));

            if ($remaining <= 0) {
                $run->update([
                    'status'          => 'quota_exhausted',
                    'quota_total'     => $quota ?? 0,
                    'quota_used_before' => $used,
                    'quota_remaining' => 0,
                    'completed_at'    => now(),
                ]);
                return;
            }

            // CV check
            if (!$this->seekerHasCv($seeker)) {
                $run->update([
                    'status'        => 'no_cv',
                    'quota_total'   => $quota ?? 0,
                    'quota_used_before' => $used,
                    'quota_remaining' => $remaining,
                    'completed_at'  => now(),
                ]);
                return;
            }

            $jobs = $finder->findForSeeker($seeker, $remaining);

            $run->update([
                'quota_total'       => $quota ?? 0,
                'quota_used_before' => $used,
                'quota_remaining'   => $remaining,
                'jobs_scanned'      => $jobs->count(),
                'dispatched'        => $jobs->count(),
            ]);

            if ($jobs->isEmpty()) {
                $run->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
                return;
            }

            foreach ($jobs as $job) {
                AutoApplyToJobJob::dispatch($run->id, $seeker->id, $job->id)
                    ->onQueue(config('auto_apply.queue', 'auto-apply'));
            }

            // Completion will be flipped by the last child job via a counter check
            // in AutoApplyToJobJob::finalize.

        } catch (\Throwable $e) {
            Log::error('Auto-apply run failed', [
                'run_id' => $run->id,
                'error'  => $e->getMessage(),
            ]);

            $run->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        }
    }

    protected function seekerHasCv(SeekerProfile $seeker): bool
    {
        if (!empty($seeker->cv_file_path)) return true;
        $files = $seeker->cv_files;
        return is_array($files) && count($files) > 0;
    }
}