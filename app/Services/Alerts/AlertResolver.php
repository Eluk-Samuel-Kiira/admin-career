<?php

namespace App\Services\Alerts;

use App\Models\SeekerProfile;

class AlertResolver
{
    /**
     * Decide what kind of alert to send this seeker.
     * Returns one of: 'jobs', 'preferences_prompt'
     */
    public function decide(SeekerProfile $seeker, array $prefs): array
    {
        $wantsAll    = !empty($prefs['all_job_alerts']);
        $hasCats     = !empty($prefs['alert_by_category'] ?? []);
        $wantsSkills = !empty($prefs['alert_by_skill_match'])
                    || !empty($prefs['alert_by_cv_skill']);

        if ($wantsAll || $hasCats || $wantsSkills) {
            return [
                'kind'   => 'jobs',
                'reason' => $wantsAll
                    ? 'All jobs selected'
                    : ($hasCats ? 'Categories set' : 'Skill match set'),
            ];
        }

        return [
            'kind'   => 'preferences_prompt',
            'reason' => 'No preferences or skills set',
        ];
    }
}