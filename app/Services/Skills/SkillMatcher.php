<?php

namespace App\Services\Skills;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;

class SkillMatcher
{
    public function __construct(protected SkillNormaliser $normaliser) {}

    /**
     * Score = (matched / job_target_tokens) * 100
     *
     * Job "target tokens" come ONLY from fields that actually list skills:
     *   - job.skills       (recruiter-curated skill list)
     *   - job.job_title    (role keywords)
     *
     * We deliberately exclude job_description / responsibilities /
     * qualifications — those are prose, and tokenising them produces
     * 90–150 words that are NOT skills, which destroys the score.
     */
    public function score(SeekerProfile $seeker, JobPost $job): int
    {
        $seekerTokens = $this->normaliser->canonicalise($seeker->skills ?? []);
        if (empty($seekerTokens)) {
            return 0;
        }

        // ── Collect ONLY skill-bearing tokens
        $jobTokens = [];

        // 1. skills field (highest priority)
        foreach ($this->normaliser->canonicalise($this->stripHtml((string) $job->skills)) as $t) {
            $jobTokens[$t] = true;
        }

        // 2. job title keywords
        foreach ($this->normaliser->canonicalise((string) $job->job_title) as $t) {
            $jobTokens[$t] = true;
        }

        $jobTokens = array_keys($jobTokens);
        if (empty($jobTokens)) {
            return 0;
        }

        $matched = count(array_intersect($seekerTokens, $jobTokens));

        return (int) round(($matched / count($jobTokens)) * 100);
    }

    private function stripHtml(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}