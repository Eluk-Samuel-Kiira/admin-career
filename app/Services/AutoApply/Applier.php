<?php

namespace App\Services\AutoApply;

use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;
use App\Services\AutoApply\Channels\AtsChannel;
use App\Services\AutoApply\Channels\EmailChannel;
use App\Services\AutoApply\Channels\RelayChannel;
use App\Services\AutoApply\Channels\WhatsAppChannel;
use App\Services\Jobs\AiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Applier
{
    public function __construct(
        protected EligibilityChecker $checker,
        protected CvTailor $tailor,
        protected AiService $ai,
        protected AtsChannel $ats,
        protected EmailChannel $email,
        protected WhatsAppChannel $whatsapp,
        protected RelayChannel $relay,
    ) {}

    /**
     * Run one (seeker, job) application attempt.
     * Idempotent: if a JobSeekerJob with is_applied=true exists, returns immediately.
     */
    public function apply(AutoApplyRun $run, SeekerProfile $seeker, JobPost $job): string
    {
        // Idempotency guard
        if (JobSeekerJob::where('seeker_profile_id', $seeker->id)
            ->where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->exists()) {
            return 'already_applied';
        }

        $decision = $this->checker->decide($job, $seeker);

        if ($decision['channel'] === EligibilityChecker::CHANNEL_SKIP) {
            $this->bump($run, 'skipped_count');
            return 'skipped:' . $decision['reason'];
        }

        // Build the job context
        $jobContext = [
            'job_title'       => $job->job_title,
            'company_name'    => optional($job->company)->name ?? 'Company',
            'job_description' => implode("\n\n", array_filter([
                strip_tags($job->job_description ?? ''),
                strip_tags($job->responsibilities ?? ''),
                strip_tags($job->qualifications ?? ''),
                $job->skills ? "Skills: {$job->skills}" : null,
            ])),
            'skills' => $job->skills,
        ];

        // Tailor CV (with idempotency — if a previous attempt wrote it, reuse)
        $cv = $this->tailor->tailor($seeker, $jobContext);

        // Generate cover letter if required
        $coverHtml = null;
        if ($job->is_cover_letter_required || in_array($decision['channel'], [EligibilityChecker::CHANNEL_EMAIL, EligibilityChecker::CHANNEL_WHATSAPP], true)) {
            try {
                $cvText = $this->readCvText($seeker);
                $coverHtml = $this->ai->generateLetter(
                    $cvText,
                    $jobContext,
                    'application'
                );
            } catch (\Throwable $e) {
                Log::warning('Auto-apply: cover letter generation failed', [
                    'job_id' => $job->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // Dispatch by channel
        try {
            DB::transaction(function () use ($run, $seeker, $job, $decision, $cv, $coverHtml, $jobContext) {
                match ($decision['channel']) {
                    EligibilityChecker::CHANNEL_ATS      => $this->ats->submit($seeker, $job, $cv, $coverHtml),
                    EligibilityChecker::CHANNEL_EMAIL    => $this->email->submit($seeker, $job, $cv, $coverHtml, $jobContext),
                    EligibilityChecker::CHANNEL_WHATSAPP => $this->whatsapp->submit($seeker, $job, $cv, $coverHtml, $jobContext),
                    EligibilityChecker::CHANNEL_RELAY    => $this->relay->submit($run, $seeker, $job, $cv, $coverHtml, $jobContext),
                    default => throw new \Exception('Unknown channel: ' . $decision['channel']),
                };
            });

            if ($decision['channel'] === EligibilityChecker::CHANNEL_RELAY) {
                $this->bump($run, 'relayed_count');
            } else {
                $this->bump($run, 'applied_count');
            }

            return 'applied:' . $decision['channel'];
        } catch (\Throwable $e) {
            Log::error('Auto-apply channel failed', [
                'job_id'  => $job->id,
                'channel' => $decision['channel'],
                'error'   => $e->getMessage(),
            ]);

            $this->bump($run, 'failed_count');
            throw $e;
        }
    }

    protected function readCvText(SeekerProfile $seeker): string
    {
        // Same logic as CvTailor::resolveCvText. Move it to CvTailor and reuse.
        // For brevity, expose a public method on CvTailor and call it here.
        return app(CvTailor::class)->resolveCvText($seeker);
    }

    protected function bump(AutoApplyRun $run, string $column): void
    {
        AutoApplyRun::where('id', $run->id)->increment($column);
    }
}