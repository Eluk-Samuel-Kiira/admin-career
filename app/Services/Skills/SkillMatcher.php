<?php

namespace App\Services\Skills;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;

class SkillMatcher
{
    public function __construct(protected SkillNormaliser $normaliser) {}

    /**
     * Score = (unique_seeker_tokens_found_anywhere / total_seeker_tokens) * 100
     *
     * "Anywhere" means: job.skills OR job.title OR job.description.
     * A hit in ANY field counts once — we dedupe.
     */
    public function score(SeekerProfile $seeker, JobPost $job): int
    {
        $seekerTokens = $this->normaliser->canonicalise($seeker->skills ?? []);
        if (empty($seekerTokens)) {
            return 0;
        }

        // All tokens the job advertises, from every field
        $jobTokens = [];
        foreach ([
            $job->skills ?? '',
            $job->job_title ?? '',
            $job->job_description ?? '',
            $job->qualifications ?? '',
            $job->responsibilities ?? '',
        ] as $field) {
            foreach ($this->normaliser->canonicalise($this->stripHtml($field)) as $t) {
                $jobTokens[$t] = true;
            }
        }
        $jobTokens = array_keys($jobTokens);

        // How many seeker tokens are in the job?
        $matched = count(array_intersect($seekerTokens, $jobTokens));

        return (int) round(($matched / count($seekerTokens)) * 100);
    }

    private function stripHtml(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}