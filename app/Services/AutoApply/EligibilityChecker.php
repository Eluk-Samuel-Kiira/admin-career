<?php

namespace App\Services\AutoApply;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;

class EligibilityChecker
{
    public const CHANNEL_ATS      = 'ats';
    public const CHANNEL_EMAIL    = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_RELAY    = 'relay';
    public const CHANNEL_SKIP     = 'skip';

    /**
     * Decide the outcome for this (seeker, job) pair.
     *
     * @return array{channel: string, reason: string}
     */
    public function decide(JobPost $job, SeekerProfile $seeker): array
    {
        // Jobs that require academic documents cannot be auto-applied.
        if ($job->is_academic_documents_required) {
            return [
                'channel' => self::CHANNEL_SKIP,
                'reason'  => 'academic_documents_required',
            ];
        }

        // Jobs that require a resume but the seeker has no CV -> skip, not relay.
        if ($job->is_resume_required && !$this->seekerHasCv($seeker)) {
            return [
                'channel' => self::CHANNEL_SKIP,
                'reason'  => 'cv_required_but_missing',
            ];
        }

        // Priority order for direct apply.
        foreach (config('auto_apply.channel_priority', ['ats', 'email', 'whatsapp']) as $channel) {
            if ($this->canUseChannel($job, $seeker, $channel)) {
                return ['channel' => $channel, 'reason' => 'direct'];
            }
        }

        // No direct channel. Relay to the seeker.
        return [
            'channel' => self::CHANNEL_RELAY,
            'reason'  => 'no_direct_channel',
        ];
    }

    protected function canUseChannel(JobPost $job, SeekerProfile $seeker, string $channel): bool
    {
        return match ($channel) {
            self::CHANNEL_ATS => (bool) $job->has_ats
                && strtolower((string) $job->package_key) === 'enterprise',

            self::CHANNEL_EMAIL => !empty($job->email)
                && filter_var($job->email, FILTER_VALIDATE_EMAIL) !== false,

            self::CHANNEL_WHATSAPP => (bool) $job->is_whatsapp_contact
                && !empty($job->telephone),

            default => false,
        };
    }

    protected function seekerHasCv(SeekerProfile $seeker): bool
    {
        if (!empty($seeker->cv_file_path)) return true;
        $files = $seeker->cv_files;
        return is_array($files) && count($files) > 0;
    }
}