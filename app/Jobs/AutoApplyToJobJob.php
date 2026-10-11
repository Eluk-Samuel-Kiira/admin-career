<?php

namespace App\Jobs;

use App\Models\AutoApplyRun;
use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use App\Services\AutoApply\Applier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoApplyToJobJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries;

    public function __construct(
        public int $runId,
        public int $seekerProfileId,
        public int $jobPostId,
    ) {
        $this->timeout = (int) config('auto_apply.job_timeout', 180);
        $this->tries   = (int) config('auto_apply.job_tries', 3);
    }

    public function handle(Applier $applier): void
    {
        $run    = AutoApplyRun::find($this->runId);
        $seeker = SeekerProfile::with('user')->find($this->seekerProfileId);
        $job    = JobPost::with(['company'])->find($this->jobPostId);

        if (!$run || !$seeker || !$job) {
            Log::warning('AutoApplyToJobJob: missing models', [
                'run_id'    => $this->runId,
                'seeker_id' => $this->seekerProfileId,
                'job_id'    => $this->jobPostId,
            ]);
            return;
        }

        try {
            $outcome = $applier->apply($run, $seeker, $job);
            Log::info('AutoApply: outcome', [
                'run_id'  => $run->id,
                'job_id'  => $job->id,
                'outcome' => $outcome,
            ]);
        } finally {
            $this->finalizeRun($run);
        }
    }

    protected function finalizeRun(AutoApplyRun $run): void
    {
        $run->refresh();
        $finished = $run->applied_count + $run->relayed_count + $run->skipped_count + $run->failed_count;

        if ($finished >= $run->dispatched && !$run->isFinished()) {
            $run->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $run = AutoApplyRun::find($this->runId);
        if ($run) {
            AutoApplyRun::where('id', $run->id)->increment('failed_count');
            $this->finalizeRun($run->fresh());
        }
    }
}