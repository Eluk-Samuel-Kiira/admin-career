<?php

namespace App\Jobs;

use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use App\Models\AutoApplyExternalDraft;
use App\Models\SeekerProfile;
use App\Services\AutoApply\JobFinder;
use App\Services\AutoApply\CvTailor;
use App\Services\AutoApply\DispatchRouter;
use App\Services\Jobs\AiService;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DraftAutoApplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $seekerProfileId) {}

    public function handle(
        JobFinder $finder, 
        SubscriptionGate $gate,
        DispatchRouter $router,
        CvTailor $tailor,
        AiService $ai
    ): void {
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);
        if (!$seeker || !$seeker->user) return;

        // 1. Subscription Gate (Must have auto_apply quota)
        $quota = $gate->autoApplyQuota($seeker->user);
        if ($quota === 0) return; // Skip if unpaid

        // 2. Setup the Run (Guard against double-triggering)
        $run = AutoApplyRun::firstOrCreate(
            [
                'user_id'            => $seeker->user_id,
                'seeker_profile_id'  => $seeker->id,
                'run_date'           => today(),
            ],
            [
                'status'      => 'drafting',
                'quota_total' => $quota,
            ]
        );

        // If this run was already completed today, skip re-triggering
        if ($run->status === 'drafted') {
            Log::info("AutoApply: Skipping seeker {$seeker->id} — already drafted today.");
            return;
        }

        // 3. Find Jobs (Max 5 per run to save AI costs)
        $jobs = $finder->findForSeeker($seeker, 5);

        foreach ($jobs as $job) {
            // 4. Prevent Duplicates
            if (AutoApplyRelay::where('user_id', $seeker->user_id)->where('job_post_id', $job->id)->exists()) continue;
            if (AutoApplyExternalDraft::where('user_id', $seeker->user_id)->where('job_post_id', $job->id)->exists()) continue;

            // 5. Route
            $route = $router->route($job);

            // 5a. Handle SKIP gracefully — do NOT fail
            if ($route['type'] === DispatchRouter::TYPE_SKIP) {
                Log::info("AutoApply: Skipped job {$job->id} ({$route['reason']})");
                $run->increment('skipped_count');
                continue;
            }

            // 6. Generate AI Content
            Log::info("AutoApply: Drafting for job {$job->id} via {$route['type']}");
            
            $jobContext = [
                'job_title'       => $job->job_title,
                'company_name'    => optional($job->company)->name ?? 'Company',
                'job_description' => strip_tags($job->job_description . ' ' . $job->responsibilities),
            ];

            try {
                $cv = $tailor->tailor($seeker, $jobContext);   // returns ['html' => ..., 'path' => null]
                $coverHtml = $ai->generateLetter($tailor->resolveCvText($seeker), $jobContext, 'application');

                if ($route['type'] === DispatchRouter::TYPE_EXTERNAL) {
                    AutoApplyExternalDraft::create([
                        'auto_apply_run_id' => $run->id,
                        'user_id'           => $seeker->user_id,
                        'job_post_id'       => $job->id,
                        'tailored_cv_html'  => $cv['html'],
                        'cover_letter_html' => $coverHtml,
                        'external_url'      => $route['url'],
                        'status'            => 'drafted',
                    ]);
                    $run->increment('relayed_count');
                } else {
                    AutoApplyRelay::create([
                        'auto_apply_run_id'  => $run->id,
                        'user_id'            => $seeker->user_id,
                        'job_post_id'        => $job->id,
                        'reason'             => $route['reason'],
                        'channels_attempted' => [$route['type']],
                        'channels_sent'      => [],
                        'tailored_cv_html'   => $cv['html'],
                        'cover_letter_html'  => $coverHtml,
                        'status'             => 'queued',
                    ]);
                    $run->increment('relayed_count');
                }
            } catch (\Throwable $e) {
                Log::error("AutoApply Draft failed for job {$job->id}: " . $e->getMessage());
                $run->increment('failed_count');
                $run->update([
                    'error_message' => mb_substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        // 8. Complete the run
        $run->update([
            'status'       => 'drafted',
            'completed_at' => now(),
        ]);

        Log::info("AutoApply: Finished drafting for seeker {$seeker->id}", [
            'relayed' => $run->fresh()->relayed_count,
            'skipped' => $run->fresh()->skipped_count,
            'failed'  => $run->fresh()->failed_count,
        ]);
    }
}