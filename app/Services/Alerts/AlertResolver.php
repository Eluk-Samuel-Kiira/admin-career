<?php

namespace App\Services\Alerts;

use App\Models\SeekerProfile;

class AlertResolver
{
    /**
     * Decide what kind of alert to send this seeker.
     * Returns one of: 'jobs', 'preferences_prompt', 'profile_prompt', 'cv_prompt', 'skip'
     */
    public function decide(SeekerProfile $seeker, array $prefs): array
    {
        // 1. No CV uploaded
        if (!$this->hasCv($seeker)) {
            return [
                'kind'    => 'cv_prompt',
                'reason'  => 'No CV uploaded',
            ];
        }

        // 2. Profile incomplete (missing key employer-facing fields)
        if (!$this->isProfileComplete($seeker)) {
            return [
                'kind'    => 'profile_prompt',
                'reason'  => 'Profile incomplete',
            ];
        }

        // 3. all_job_alerts is on -> no preferences set
        if (!empty($prefs['all_job_alerts'])) {
            return [
                'kind'    => 'preferences_prompt',
                'reason'  => 'Preferences not set',
            ];
        }

        // 4. Preferences set — but no category chosen
        $cats = $prefs['alert_by_category'] ?? [];
        if (empty($cats)) {
            return [
                'kind'    => 'preferences_prompt',
                'reason'  => 'No categories selected',
            ];
        }

        // 5. All good
        return [
            'kind'   => 'jobs',
            'reason' => 'Preferences set',
        ];
    }

    protected function hasCv(SeekerProfile $seeker): bool
    {
        if ($seeker->cv_file_path) return true;
        $files = $seeker->cv_files;
        return is_array($files) && count($files) > 0;
    }

    protected function isProfileComplete(SeekerProfile $seeker): bool
    {
        return $seeker->job_category_id
            && $seeker->industry_id
            && $seeker->job_location_id
            && $seeker->experience_level_id
            && $seeker->education_level_id
            && !empty($seeker->professional_title)
            && !empty($seeker->skills);
    }
}