<?php

namespace App\Services\AutoApply;

use App\Models\SeekerProfile;
use App\Services\Jobs\CvTailorService;
use Illuminate\Support\Facades\Log;

class CvTailor
{
    public function __construct(
        protected CvTailorService $tailorService,
    ) {}

    public function tailor(SeekerProfile $seeker, array $jobContext): array
    {
        // 1. Get the CV file text
        $cvText = $this->resolveCvText($seeker);

        // 2. Prepare the DB profile data
        $dbProfile = [
            'full_name' => $seeker->full_name,
            'email' => $seeker->email,
            'phone' => $seeker->phone,
            'city' => $seeker->city,
            'country' => $seeker->country,
            'professional_title' => $seeker->professional_title,
            'professional_summary' => $seeker->professional_summary,
            'years_of_experience' => $seeker->years_of_experience,
            'skills' => $seeker->skills_array,
            'languages' => $seeker->languages_array,
            'education' => $seeker->education_array,
            'work_experience' => $seeker->work_experience_array,
            'certifications' => $seeker->certifications_array,
            'projects' => $seeker->projects_array,
        ];

        // 3. If both are empty, throw an error
        if (trim($cvText) === '' && empty($seeker->work_experience_array) && empty($seeker->education_array)) {
            throw new \Exception('No CV text available for tailoring.');
        }

        $result = $this->tailorService->tailor(
            cvText:         $cvText,
            jobTitle:       $jobContext['job_title']    ?? 'the advertised post',
            companyName:    $jobContext['company_name'] ?? 'the organisation',
            jobDescription: $jobContext['job_description'] ?? '',
            dbProfile:      $dbProfile
        );

        return [
            'html' => $result['html'],
            'path' => null, // no PDF stored
        ];
    }

    public function resolveCvText(SeekerProfile $seeker): string
    {
        return $this->tailorService->resolveCvText($seeker);
    }
}