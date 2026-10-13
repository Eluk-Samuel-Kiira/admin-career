<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRelay;
use App\Models\EmployerProfile;
use App\Models\Hiring\HiringRun;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use App\Models\SeekerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;

class DashboardController extends Controller
{
    // =================================================================
    // EMPLOYER DASHBOARD
    // =================================================================
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
                'subscription'    => null,
            ]);
        }

        $companyId = $profile->company_id;

        // ── Jobs
        $activeJobs = JobPost::where('company_id', $companyId)->where('is_active', true)->count();
        $totalJobs  = JobPost::where('company_id', $companyId)->count();

        // ── Applications
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

        $shortlisted = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->whereIn('ats_status', ['shortlisted', 'interview'])
            ->count();

        // ── AI features (new)
        $aiShortlisted = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->where('ai_recommendation', 'strong_yes')
            ->count();

        $aiScreened = JobSeekerJob::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->whereNotNull('ai_screened_at')
            ->count();

        $screeningRuns = HiringRun::where('company_id', $companyId)->count();

        $cvsScreened = HiringRun::where('company_id', $companyId)->sum('processed');

        // ── Auto-apply relays for this employer's jobs
        $autoApplyRelays = AutoApplyRelay::whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['sent', 'partial'])
            ->count();

        $jobLimit = $profile->active_job_limit;

        // ── Recent activity (unified, sorted)
        $activity = collect();

        // New applicants
        JobSeekerJob::with(['seekerProfile:id,first_name,last_name', 'jobPost:id,job_title'])
            ->whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->where('is_applied', true)
            ->orderByDesc('applied_at')
            ->limit(5)
            ->get()
            ->each(function ($a) use ($activity) {
                $activity->push([
                    'type'     => 'new_application',
                    'title'    => 'New applicant',
                    'subtitle' => ($a->seekerProfile?->full_name ?? 'Someone') . ' applied for ' . ($a->jobPost?->job_title ?? 'a job'),
                    'at'       => $a->applied_at?->toISOString(),
                    'icon'     => 'ki-user-tick',
                    'color'    => 'primary',
                ]);
            });

        // AI screening completed
        JobSeekerJob::with(['seekerProfile:id,first_name,last_name', 'jobPost:id,job_title'])
            ->whereHas('jobPost', fn($q) => $q->where('company_id', $companyId))
            ->whereNotNull('ai_screened_at')
            ->orderByDesc('ai_screened_at')
            ->limit(4)
            ->get()
            ->each(function ($a) use ($activity) {
                $score = $a->ai_score;
                $activity->push([
                    'type'     => 'ai_screening',
                    'title'    => 'AI screened an applicant',
                    'subtitle' => ($a->seekerProfile?->full_name ?? 'Candidate') . ' scored ' . ($score ?? '—') . '/100 for ' . ($a->jobPost?->job_title ?? 'a job'),
                    'at'       => $a->ai_screened_at?->toISOString(),
                    'icon'     => 'ki-abstract-26',
                    'color'    => $score >= 80 ? 'success' : ($score >= 60 ? 'primary' : 'warning'),
                ]);
            });

        // Hiring runs completed
        HiringRun::where('company_id', $companyId)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(3)
            ->get()
            ->each(function ($r) use ($activity) {
                $activity->push([
                    'type'     => 'hiring_run',
                    'title'    => 'CV shortlisting run completed',
                    'subtitle' => $r->title . ' — ' . $r->processed . ' of ' . $r->total . ' CVs screened',
                    'at'       => $r->completed_at?->toISOString(),
                    'icon'     => 'ki-rocket',
                    'color'    => 'info',
                ]);
            });

        // Job posts
        JobPost::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->each(function ($j) use ($activity) {
                $activity->push([
                    'type'     => 'job_created',
                    'title'    => 'Job posted',
                    'subtitle' => $j->job_title,
                    'at'       => $j->created_at?->toISOString(),
                    'icon'     => 'ki-briefcase',
                    'color'    => 'success',
                ]);
            });

        $recentActivity = $activity
            ->filter(fn($a) => !empty($a['at']))
            ->sortByDesc('at')
            ->take(10)
            ->values()
            ->toArray();

        return response()->json([
            'success'         => true,
            'needsOnboarding' => false,
            'stats' => [
                // Jobs
                'active_jobs'             => $activeJobs,
                'total_jobs'              => $totalJobs,
                'job_limit'               => $jobLimit,
                'job_slots_remaining'     => $jobLimit === null ? null : max(0, $jobLimit - $activeJobs),

                // Applications
                'applications_this_month' => $applicationsThisMonth,
                'applications_today'      => $applicationsToday,
                'new_applications'        => $newApplications,
                'shortlisted'             => $shortlisted,

                // AI features (new)
                'ai_shortlisted'          => $aiShortlisted,
                'ai_screened'             => $aiScreened,
                'screening_runs'          => $screeningRuns,
                'cvs_screened'            => $cvsScreened,
                'auto_apply_relays'       => $autoApplyRelays,

                // Profile
                'profile_completion'      => $this->calculateProfileCompletion($profile),
                'compliance_status'       => $profile->compliance_status,
                'is_verified'             => (bool) $profile->is_verified,
                'tier'                    => $profile->tier,
                'tier_label'              => $profile->tier_label,
            ],
            'recentActivity' => $recentActivity,
            'subscription' => $this->subscriptionBlock($user),
        ]);
    }

    // =================================================================
    // SEEKER DASHBOARD
    // =================================================================
    public function seeker(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = SeekerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success'         => true,
                'needsOnboarding' => true,
                'stats'           => null,
                'recentActivity'  => [],
                'subscription'    => null,
            ]);
        }

        // ── Base counts
        $appliedCount   = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_applied', true)->count();
        $savedCount     = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_saved', true)->count();
        $interviewCount = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_called_for_interview', true)->count();
        $hiredCount     = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_got_job', true)->count();
        $rejectedCount  = JobSeekerJob::where('seeker_profile_id', $profile->id)->where('is_rejected', true)->count();

        // ── AI feedback on applications (new)
        $aiScreenedCount = JobSeekerJob::where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->whereNotNull('ai_screened_at')
            ->count();

        $avgAiScore = JobSeekerJob::where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->whereNotNull('ai_score')
            ->avg('ai_score');

        $strongYesCount = JobSeekerJob::where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->where('ai_recommendation', 'strong_yes')
            ->count();

        // ── Auto-apply (new)
        $autoAppliedCount = JobSeekerJob::where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->where('notes', 'like', 'Auto-applied%')
            ->count();

        // Relays the seeker still needs to action — sent but not yet applied by them
        $relayPendingCount = AutoApplyRelay::where('user_id', $user->id)
            ->whereIn('status', ['sent', 'partial'])
            ->whereDoesntHave('jobPost', function ($q) use ($profile) {
                $q->whereHas('seekerJobs', function ($sq) use ($profile) {
                    $sq->where('seeker_profile_id', $profile->id)->where('is_applied', true);
                });
            })
            ->count();

        // ── Recent activity
        $activity = collect();

        // Applications
        JobSeekerJob::with('jobPost.company:id,name')
            ->where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->orderByDesc('applied_at')
            ->limit(6)
            ->get()
            ->each(function ($a) use ($activity) {
                $isAuto = str_starts_with($a->notes ?? '', 'Auto-applied');
                $activity->push([
                    'type'     => $isAuto ? 'auto_apply' : 'application',
                    'title'    => $isAuto
                        ? 'Auto-applied for ' . ($a->jobPost?->job_title ?? 'a job')
                        : 'Applied for ' . ($a->jobPost?->job_title ?? 'a job'),
                    'subtitle' => $a->jobPost?->company?->name ?? 'Company',
                    'at'       => $a->applied_at?->toISOString(),
                    'icon'     => $isAuto ? 'ki-rocket' : 'ki-briefcase',
                    'color'    => $isAuto ? 'info' : 'primary',
                ]);
            });

        // AI feedback on applications
        JobSeekerJob::with('jobPost:id,job_title')
            ->where('seeker_profile_id', $profile->id)
            ->where('is_applied', true)
            ->whereNotNull('ai_screened_at')
            ->orderByDesc('ai_screened_at')
            ->limit(4)
            ->get()
            ->each(function ($a) use ($activity) {
                $score = $a->ai_score;
                $activity->push([
                    'type'     => 'ai_feedback',
                    'title'    => 'AI screened your application',
                    'subtitle' => 'Match score ' . ($score ?? '—') . '/100 for ' . ($a->jobPost?->job_title ?? 'a job'),
                    'at'       => $a->ai_screened_at?->toISOString(),
                    'icon'     => 'ki-abstract-26',
                    'color'    => $score >= 80 ? 'success' : ($score >= 60 ? 'primary' : 'warning'),
                ]);
            });

        // Interview invitations
        JobSeekerJob::with('jobPost.company:id,name')
            ->where('seeker_profile_id', $profile->id)
            ->where('is_called_for_interview', true)
            ->orderByDesc('interview_date')
            ->limit(3)
            ->get()
            ->each(function ($a) use ($activity) {
                $activity->push([
                    'type'     => 'interview',
                    'title'    => 'Interview invitation',
                    'subtitle' => ($a->jobPost?->company?->name ?? 'Company') . ' wants to interview you for ' . ($a->jobPost?->job_title ?? 'a job'),
                    'at'       => $a->interview_date?->toISOString(),
                    'icon'     => 'ki-calendar-tick',
                    'color'    => 'warning',
                ]);
            });

        // Auto-apply relays — actionable
        AutoApplyRelay::with('jobPost:id,job_title,slug')
            ->where('user_id', $user->id)
            ->whereIn('status', ['sent', 'partial'])
            ->orderByDesc('sent_at')
            ->limit(4)
            ->get()
            ->each(function ($r) use ($activity) {
                $activity->push([
                    'type'     => 'relay',
                    'title'    => 'Apply today — the agent could not submit this',
                    'subtitle' => $r->jobPost?->job_title ?? 'Job',
                    'at'       => $r->sent_at?->toISOString(),
                    'icon'     => 'ki-flash',
                    'color'    => 'warning',
                ]);
            });

        $recentActivity = $activity
            ->filter(fn($a) => !empty($a['at']))
            ->sortByDesc('at')
            ->take(10)
            ->values()
            ->toArray();

        return response()->json([
            'success'         => true,
            'needsOnboarding' => false,
            'stats' => [
                // Base
                'applied_count'      => $appliedCount,
                'saved_count'        => $savedCount,
                'interview_count'    => $interviewCount,
                'hired_count'        => $hiredCount,
                'rejected_count'     => $rejectedCount,

                // AI feedback (new)
                'ai_screened_count'  => $aiScreenedCount,
                'avg_ai_score'       => $avgAiScore ? (int) round($avgAiScore) : null,
                'strong_yes_count'   => $strongYesCount,

                // Auto-apply (new)
                'auto_applied_count' => $autoAppliedCount,
                'relay_pending_count'=> $relayPendingCount,

                // Profile
                'profile_completion' => $this->calculateSeekerCompletion($profile),
                'cv_count'           => is_array($profile->cv_files) ? count($profile->cv_files) : 0,
                'is_profile_complete'=> $profile->is_profile_complete,
            ],
            'recentActivity' => $recentActivity,
            'subscription'    => $this->subscriptionBlock($user),
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

    /**
     * Build the subscription block for the dashboard.
     * Returns null when the user has no subscription yet.
     */
    private function subscriptionBlock($user): ?array
    {
        $sub = Subscription::with('service:id,key,name')
            ->where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->orderByDesc('id')
            ->first();

        if (!$sub) {
            return null;
        }

        // Recent payments on this subscription
        $recentPayments = SubscriptionPayment::where('subscription_id', $sub->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'uuid'             => $p->uuid,
                'amount_formatted' => strtoupper($p->currency) . ' ' . number_format($p->amount_cents / 100, $p->currency === 'UGX' ? 0 : 2),
                'status'           => $p->status,
                'status_label'     => ucfirst($p->status),
                'failure_reason'   => $p->failure_reason,
                'paid_at'          => $p->paid_at?->toISOString(),
                'created_at'       => $p->created_at?->toISOString(),
            ]);

        // Days remaining in the current period
        $daysRemaining = $sub->current_period_end
            ? max(0, (int) now()->diffInDays($sub->current_period_end, false))
            : null;

        return [
            'uuid'                 => $sub->uuid,
            'service_key'          => $sub->service_key,
            'service_name'         => $sub->service?->name ?? $sub->service_key,
            'status'               => $sub->status,
            'status_label'         => $this->subscriptionStatusLabel($sub->status, $sub->cancel_at_period_end),
            'is_active'            => $sub->isActive(),
            'currency'             => $sub->currency,
            'amount_formatted'     => strtoupper($sub->currency) . ' ' . number_format($sub->amount_cents / 100, $sub->currency === 'UGX' ? 0 : 2),
            'interval'             => $sub->interval,
            'starts_at'            => $sub->starts_at?->toISOString(),
            'trial_ends_at'        => $sub->trial_ends_at?->toISOString(),
            'current_period_end'   => $sub->current_period_end?->toISOString(),
            'cancel_at_period_end' => (bool) $sub->cancel_at_period_end,
            'days_remaining'       => $daysRemaining,
            'recent_payments'      => $recentPayments,
        ];
    }

    private function subscriptionStatusLabel(string $status, bool $cancelAtPeriodEnd = false): string
    {
        if ($status === 'active' && $cancelAtPeriodEnd) {
            return 'Cancels at period end';
        }

        return match ($status) {
            'trialing'  => 'Free trial',
            'active'    => 'Active',
            'past_due'  => 'Payment failed',
            'cancelled' => 'Cancelled',
            'expired'   => 'Expired',
            default     => ucfirst($status),
        };
    }


}