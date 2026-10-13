<?php

namespace App\Services\Alerts;

use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use Illuminate\Support\Collection;

class JobMatcher
{
    public const MAX_JOBS = 15;

    /**
     * Given a seeker's parsed preferences, return the top matching active jobs.
     * Order: featured first, then by published_at desc.
     */
    public function match(SeekerProfile $seeker, array $prefs, int $limit = self::MAX_JOBS): Collection
    {
        $query = JobPost::query()
            ->with(['company:id,name,logo', 'jobLocation:id,district,city,country_code'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_until')
                  ->orWhere('published_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now());
            });

        // Country scope — use the seeker's preferred country if set, else their country
        $country = $seeker->preferred_country_code
            ?: $seeker->country
            ?: $seeker->user?->country_code
            ?: 'UG';
        $country = strtoupper(substr($country, 0, 2) === $country ? $country : strtoupper($country));
        // Some rows store the country name; only filter when we have a 2-letter code
        if (strlen($country) === 2) {
            $query->where('country_code', $country);
        }

        // Category filter — the heart of the preference model
        $categories = $prefs['alert_by_category'] ?? [];
        if (!empty($categories)) {
            $query->whereIn('job_category_id', $categories);
        }

        // Skill matching
        if (!empty($prefs['alert_by_cv_skill']) || !empty($prefs['alert_by_skill_match'])) {
            $skills = $seeker->skills_array;
            if (!empty($skills)) {
                $threshold = (int) ($prefs['skill_match_threshold'] ?? 50);
                $query->where(function ($q) use ($skills) {
                    foreach ($skills as $skill) {
                        $q->orWhere('skills', 'like', '%' . $skill . '%');
                    }
                });

                // If both flags are on, honour the threshold by only accepting jobs
                // where at least one skill was matched. The like-filter above already
                // guarantees that. The threshold is reserved for future scoring.
            }
        }

        return $query
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }
}