<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class JobActionController extends Controller
{
    /**
     * Save or unsave a job
     */
    public function toggleSave(Request $request, $jobId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to save jobs.',
                'requires_login' => true
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Seeker profile not found'
            ], 404);
        }

        $jobPost = JobPost::find($jobId);
        if (!$jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        $jobSeekerJob = JobSeekerJob::firstOrNew([
            'seeker_profile_id' => $seekerProfile->id,
            'job_post_id' => $jobId,
        ]);

        $jobSeekerJob->is_saved = !$jobSeekerJob->is_saved;
        $jobSeekerJob->saved_at = $jobSeekerJob->is_saved ? now() : null;
        $jobSeekerJob->save();

        return response()->json([
            'success' => true,
            'message' => $jobSeekerJob->is_saved ? 'Job saved successfully!' : 'Job unsaved.',
            'is_saved' => $jobSeekerJob->is_saved,
        ]);
    }

    /**
     * Track application (called when apply modal is opened)
     */
    public function trackApplication(Request $request, $jobId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to apply',
                'requires_login' => true
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Seeker profile not found'
            ], 404);
        }

        // ✅ Enforce profile completeness BEFORE tracking anything
        // if (!$seekerProfile->is_profile_complete) {
        //     return response()->json([
        //         'success' => false,
        //         'code'    => 'profile_incomplete',
        //         'message' => 'Please complete your profile (category, industry, location, experience, education, and skills) before applying.',
        //         'redirect' => '/profile',
        //     ], 422);
        // }

        $jobPost = JobPost::find($jobId);
        if (!$jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        $jobSeekerJob = JobSeekerJob::firstOrNew([
            'seeker_profile_id' => $seekerProfile->id,
            'job_post_id'       => $jobId,
        ]);

        if ($jobSeekerJob->is_applied) {
            return response()->json([
                'success' => true,
                'message' => 'Already applied',
                'is_applied' => true,
            ]);
        }

        $jobSeekerJob->is_applied = true;
        $jobSeekerJob->applied_at = now();
        $jobSeekerJob->save();

        $jobPost->increment('application_count');

        return response()->json([
            'success' => true,
            'message' => 'Application tracked successfully',
            'is_applied' => true,
        ]);
    }

    /**
     * Get job status for a specific job (saved, applied, etc.)
     */
    public function getJobStatus(Request $request, $jobId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login',
                'requires_login' => true
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success' => true,
                'is_saved' => false,
                'is_applied' => false,
                'is_called_for_interview' => false,
                'is_got_job' => false,
                'is_rejected' => false,
            ]);
        }

        $jobSeekerJob = JobSeekerJob::where([
            'seeker_profile_id' => $seekerProfile->id,
            'job_post_id' => $jobId,
        ])->first();

        return response()->json([
            'success' => true,
            'is_saved' => $jobSeekerJob?->is_saved ?? false,
            'is_applied' => $jobSeekerJob?->is_applied ?? false,
            'is_called_for_interview' => $jobSeekerJob?->is_called_for_interview ?? false,
            'is_got_job' => $jobSeekerJob?->is_got_job ?? false,
            'is_rejected' => $jobSeekerJob?->is_rejected ?? false,
        ]);
    }

    public function getSavedJobs(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success'         => false,
                'message'         => 'Please login',
                'requires_login'  => true,
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success'    => true,
                'data'       => [],
                'total'      => 0,
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => 10,
                    'total'        => 0,
                ],
            ]);
        }

        $perPage = max(1, min(50, (int) $request->get('per_page', 10)));

        $paginator = JobSeekerJob::with([
                'jobPost.company',
                'jobPost.jobLocation',
                'jobPost.jobType',
            ])
            ->where('seeker_profile_id', $seekerProfile->id)
            ->where('is_saved', true)
            // Only rows whose parent job is still active, live and not soft-deleted
            ->whereHas('jobPost', function ($q) {
                $q->where('is_active', true)
                ->where(function ($date) {
                    $date->whereNull('deadline')
                        ->orWhere('deadline', '>=', now()->toDateString());
                });
            })
            ->orderByDesc('saved_at')
            ->paginate($perPage, ['*'], 'page', (int) $request->get('page', 1));

        $data = collect($paginator->items())
            ->filter(fn ($item) => $item->jobPost !== null)
            ->map(function ($item) {
                $job = $item->jobPost;

                $job->is_saved   = true;
                $job->is_applied = (bool) $item->is_applied;

                // The accessor already exposes logo_url on the Company model.
                // setAttribute() makes it explicit that we want it in the payload.
                if ($job->company) {
                    $job->company->setAttribute('logo_url', $job->company->logo_url);
                }

                return $job;
            })
            ->values();

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'total'      => $paginator->total(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function getAppliedJobs(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success'        => false,
                'message'        => 'Please login',
                'requires_login' => true,
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success'    => true,
                'data'       => [],
                'total'      => 0,
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => 10,
                    'total'        => 0,
                ],
            ]);
        }

        $perPage  = max(1, min(50, (int) $request->get('per_page', 10)));
        $page     = (int) $request->get('page', 1);
        $status   = $request->get('status'); // optional filter: applied|interviewing|hired|rejected

        $query = JobSeekerJob::with([
                'jobPost.company',
                'jobPost.jobLocation',
                'jobPost.jobType',
            ])
            ->where('seeker_profile_id', $seekerProfile->id)
            ->where('is_applied', true)
            // Only filter soft-deleted / hard-deleted jobs.
            // Expired jobs (deadline in the past) MUST still appear —
            // the seeker wants their full application trail.
            ->whereHas('jobPost');

        // Optional status filter
        if ($status && in_array($status, ['applied', 'interviewing', 'hired', 'rejected'], true)) {
            match ($status) {
                'hired'        => $query->where('is_got_job', true),
                'interviewing' => $query->where('is_called_for_interview', true)
                                        ->where('is_got_job', false),
                'rejected'     => $query->where('is_rejected', true)
                                        ->where('is_got_job', false),
                'applied'      => $query->where('is_called_for_interview', false)
                                        ->where('is_got_job', false)
                                        ->where('is_rejected', false),
                default        => null,
            };
        }

        $paginator = $query
            ->orderByDesc('applied_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->filter(fn ($item) => $item->jobPost !== null)
            ->map(function ($item) {
                $job = $item->jobPost;

                $job->is_saved   = (bool) $item->is_saved;
                $job->is_applied = true;

                if ($job->company) {
                    $job->company->setAttribute('logo_url', $job->company->logo_url);
                }

                // Expose whether the job is expired so the frontend can badge it
                $isExpired = $job->deadline && $job->deadline->isPast();
                $isActive  = (bool) $job->is_active;

                return [
                    'job'          => $job,
                    'applied_at'   => $item->applied_at,
                    'status'       => $this->getApplicationStatus($item),
                    'is_expired'   => $isExpired,
                    'is_inactive'  => !$isActive,
                ];
            })
            ->values();

        return response()->json([
            'success'    => true,
            'data'       => $data,
            'total'      => $paginator->total(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get application status
     */
    private function getApplicationStatus($jobSeekerJob): string
    {
        if ($jobSeekerJob->is_got_job) return 'hired';
        if ($jobSeekerJob->is_called_for_interview) return 'interviewing';
        if ($jobSeekerJob->is_rejected) return 'rejected';
        return 'applied';
    }

    /**
     * Update application status (for job seeker).
     * The job seeker can update their own application status at any time,
     * including after the job has expired or been deactivated.
     */
    public function updateApplicationStatus(Request $request, $jobId): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success'        => false,
                'message'        => 'Please login',
                'requires_login' => true,
            ], 401);
        }

        $seekerProfile = $user->seekerProfile;
        if (!$seekerProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Seeker profile not found',
            ], 404);
        }

        $request->validate([
            'status'          => 'required|in:applied,interviewing,hired,rejected',
            'interview_date'  => 'nullable|date',
        ]);

        // Find the application — works whether the job is live, expired, or inactive
        $jobSeekerJob = JobSeekerJob::where([
            'seeker_profile_id' => $seekerProfile->id,
            'job_post_id'       => $jobId,
        ])->first();

        if (!$jobSeekerJob) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        // Reset all flags first so state is always clean on every transition.
        // This prevents bugs like "hired + rejected" both being true after
        // consecutive updates.
        $jobSeekerJob->is_called_for_interview = false;
        $jobSeekerJob->is_got_job              = false;
        $jobSeekerJob->is_rejected             = false;
        $jobSeekerJob->interview_date          = null;

        switch ($request->status) {
            case 'interviewing':
                $jobSeekerJob->is_called_for_interview = true;
                $jobSeekerJob->interview_date = $request->interview_date ?? now();
                break;

            case 'hired':
                $jobSeekerJob->is_called_for_interview = true; // implied
                $jobSeekerJob->is_got_job              = true;
                break;

            case 'rejected':
                $jobSeekerJob->is_rejected = true;
                break;

            case 'applied':
                // Already reset above — nothing else to set
                break;
        }

        $jobSeekerJob->save();

        return response()->json([
            'success' => true,
            'message' => 'Application status updated successfully.',
            'status'  => $this->getApplicationStatus($jobSeekerJob),
        ]);
    }

}