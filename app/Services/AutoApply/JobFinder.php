<?php

namespace App\Services\AutoApply;

use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\AutoApplyRelay;
use App\Models\SeekerProfile;
use Illuminate\Support\Collection;

class JobFinder
{
    /**
     * Return up to $limit fresh jobs matching the seeker's filters.
     * Newest first. Never returns a job the seeker already applied to.
     */
    public function findForSeeker(SeekerProfile $seeker, int $limit): Collection
    {
        $prefs = $seeker->job_preferences_array;

        $query = JobPost::query()
            ->with(['company:id,name,email,telephone', 'jobLocation:id,district,city'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('deadline')->orWhere('deadline', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('published_until')->orWhere('published_until', '>=', now());
            });

        // Country scope
        $countryCode = $this->resolveCountryCode($seeker);
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        // Primary filter: alert_by_category, else profile category/industry
        $categoryIds = $prefs['alert_by_category'] ?? [];
        if (!empty($categoryIds)) {
            $query->whereIn('job_category_id', $categoryIds);
        } else {
            $query->where(function ($q) use ($seeker) {
                if ($seeker->job_category_id) $q->orWhere('job_category_id', $seeker->job_category_id);
                if ($seeker->industry_id)     $q->orWhere('industry_id', $seeker->industry_id);
                if ($seeker->job_type_id)     $q->orWhere('job_type_id', $seeker->job_type_id);
            });
        }

        // Optional: location preference
        if ($seeker->job_location_id) {
            $query->where(function ($q) use ($seeker) {
                $q->where('job_location_id', $seeker->job_location_id)
                  ->orWhereNull('job_location_id');
            });
        }

        // Exclude jobs the seeker already applied to
        $appliedIds = JobSeekerJob::where('seeker_profile_id', $seeker->id)
            ->where('is_applied', true)
            ->pluck('job_post_id')
            ->all();

        if (!empty($appliedIds)) {
            $query->whereNotIn('id', $appliedIds);
        }

        // Exclude jobs already relayed to this seeker
        $relayedIds = AutoApplyRelay::where('user_id', $seeker->user_id)
            ->whereIn('status', ['sent', 'partial'])
            ->pluck('job_post_id')
            ->all();

        if (!empty($relayedIds)) {
            $query->whereNotIn('id', $relayedIds);
        }

        return $query->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    protected function resolveCountryCode(SeekerProfile $seeker): ?string
    {
        $candidates = [
            $seeker->preferred_country_code ?? null,
            $seeker->country                ?? null,
            $seeker->user->country_code     ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (empty($candidate)) continue;

            $candidate = trim((string) $candidate);

            if (strlen($candidate) === 2) {
                return strtoupper($candidate);
            }

            $code = \App\Models\Job\Country::whereRaw('LOWER(name) = ?', [mb_strtolower($candidate)])
                ->value('code');
            if ($code) return strtoupper($code);
        }

        return null;
    }
}