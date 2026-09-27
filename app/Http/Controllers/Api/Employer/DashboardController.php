<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregated stats for the employer dashboard.
     * Single endpoint so the Web app makes one call.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->company_id) {
            return response()->json([
                'success'         => true,
                'needsOnboarding' => true,
                'stats'           => null,
                'recentActivity'  => [],
            ]);
        }

        $companyId = $profile->company_id;

        // ── Stats
        $activeJobs = JobPost::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $totalJobs = JobPost::where('company_id', $companyId)->count();

        $applicationsThisMonth = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->where('applied_at', '>=', now()->startOfMonth())
            ->count();

        $applicationsToday = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->whereDate('applied_at', today())
            ->count();

        $newApplications = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->where('ats_status', 'new')
            ->count();

        $shortlistedCount = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->whereIn('ats_status', ['shortlisted', 'interview'])
            ->count();

        $jobLimit = $profile->active_job_limit;

        // ── Recent activity
        $recentApplicants = JobSeekerJob::with(['seekerProfile', 'jobPost'])
            ->whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->orderByDesc('applied_at')
            ->limit(6)
            ->get()
            ->map(fn($a) => [
                'type'     => 'new_application',
                'title'    => 'New applicant',
                'subtitle' => ($a->seekerProfile?->full_name ?? 'Someone') . ' applied for ' . ($a->jobPost?->job_title ?? 'a job'),
                'at'       => $a->applied_at?->toISOString(),
                'icon'     => 'ki-user-tick',
                'color'    => 'primary',
            ]);

        $recentJobs = JobPost::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn($j) => [
                'type'     => 'job_created',
                'title'    => 'Job posted',
                'subtitle' => $j->job_title,
                'at'       => $j->created_at?->toISOString(),
                'icon'     => 'ki-briefcase',
                'color'    => 'success',
            ]);

        $recentActivity = collect($recentApplicants)
            ->merge($recentJobs)
            ->sortByDesc('at')
            ->take(8)
            ->values()
            ->toArray();

        $completion = $this->calculateProfileCompletion($profile);

        return response()->json([
            'success'         => true,
            'needsOnboarding' => false,
            'stats' => [
                'active_jobs'             => $activeJobs,
                'total_jobs'              => $totalJobs,
                'applications_this_month' => $applicationsThisMonth,
                'applications_today'      => $applicationsToday,
                'new_applications'        => $newApplications,
                'shortlisted'             => $shortlistedCount,
                'profile_completion'      => $completion,
                'job_limit'               => $jobLimit,
                'job_slots_remaining'     => $jobLimit === null ? null : max(0, $jobLimit - $activeJobs),
                'compliance_status'       => $profile->compliance_status,
                'is_verified'             => (bool) $profile->is_verified,
                'tier'                    => $profile->tier,
                'tier_label'              => $profile->tier_label,
            ],
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Seeker dashboard — same shape, different data.
     */
    public function seeker(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = \App\Models\SeekerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success'         => true,
                'needsOnboarding' => true,
                'stats'           => null,
                'recentActivity'  => [],
            ]);
        }

        $appliedCount   = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_applied', true)->count();
        $savedCount     = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_saved', true)->count();
        $interviewCount = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_called_for_interview', true)->count();
        $hiredCount     = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_got_job', true)->count();

        $recentApplications = JobSeekerJob::with('jobPost.company')
            ->where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->orderByDesc('applied_at')
            ->limit(6)
            ->get()
            ->map(fn($a) => [
                'type'     => 'application',
                'title'    => 'Applied for ' . ($a->jobPost?->job_title ?? 'a job'),
                'subtitle' => $a->jobPost?->company?->name ?? 'Company',
                'at'       => $a->applied_at?->toISOString(),
                'icon'     => 'ki-briefcase',
                'color'    => 'primary',
            ])
            ->toArray();

        return response()->json([
            'success'         => true,
            'needsOnboarding' => false,
            'stats' => [
                'applied_count'      => $appliedCount,
                'saved_count'        => $savedCount,
                'interview_count'    => $interviewCount,
                'hired_count'        => $hiredCount,
                'profile_completion' => $this->calculateSeekerCompletion($profile),
                'cv_count'           => is_array($profile->cv_files) ? count($profile->cv_files) : 0,
            ],
            'recentActivity' => $recentApplications,
        ]);
    }

    // =================================================================
    // HELPERS
    // =================================================================
    private function calculateProfileCompletion(EmployerProfile $p): int
    {
        $fields = [
            'company_name', 'industry', 'company_size', 'company_description',
            'contact_name', 'contact_email', 'contact_phone',
            'city', 'country_code',
            'tin_number',
            'cert_incorporation_path',
        ];

        $filled = 0;
        foreach ($fields as $f) {
            if (!empty($p->$f)) $filled++;
        }

        return (int) round(($filled / count($fields)) * 100);
    }

    private function calculateSeekerCompletion($p): int
    {
        $fields = [
            'first_name', 'last_name', 'phone', 'city', 'country',
            'professional_title', 'professional_summary', 'years_of_experience',
            'skills', 'education', 'work_experience',
        ];

        $filled = 0;
        foreach ($fields as $f) {
            if (!empty($p->$f)) $filled++;
        }

        return (int) round(($filled / count($fields)) * 100);
    }
}