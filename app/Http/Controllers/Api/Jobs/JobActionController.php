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

        // Increment application count on job post
        $jobPost->increment('application_count');

        Log::info('Job application tracked', [
            'user_id' => $user->id,
            'job_id' => $jobId,
            'seeker_profile_id' => $seekerProfile->id,
        ]);

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

    /**
     * Get all saved jobs for the user
     */
    public function getSavedJobs(Request $request): JsonResponse
    {
        
        \Log::info('Yes');
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
                'data' => [],
                'total' => 0,
            ]);
        }

        $savedJobs = JobSeekerJob::with(['jobPost.company', 'jobPost.jobLocation', 'jobPost.jobType'])
            ->where('seeker_profile_id', $seekerProfile->id)
            ->where('is_saved', true)
            ->orderBy('saved_at', 'desc')
            ->get()
            ->map(function ($item) {
                return $item->jobPost;
            });

        return response()->json([
            'success' => true,
            'data' => $savedJobs,
            'total' => $savedJobs->count(),
        ]);
    }

    /**
     * Get all applied jobs for the user with status
     */
    public function getAppliedJobs(Request $request): JsonResponse
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
                'data' => [],
                'total' => 0,
            ]);
        }

        $appliedJobs = JobSeekerJob::with(['jobPost.company', 'jobPost.jobLocation', 'jobPost.jobType'])
            ->where('seeker_profile_id', $seekerProfile->id)
            ->where('is_applied', true)
            ->orderBy('applied_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'job' => $item->jobPost,
                    'applied_at' => $item->applied_at,
                    'status' => $this->getApplicationStatus($item),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $appliedJobs,
            'total' => $appliedJobs->count(),
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
     * Update application status (for employer/admin)
     */
    public function updateApplicationStatus(Request $request, $jobId): JsonResponse
    {
        $user = $request->user();
        
        // Only employers or admins can update status
        if (!$user || (!$user->isEmployer() && !$user->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'seeker_profile_id' => 'required|exists:seeker_profiles,id',
            'status' => 'required|in:applied,interviewing,hired,rejected',
        ]);

        $jobSeekerJob = JobSeekerJob::where([
            'seeker_profile_id' => $request->seeker_profile_id,
            'job_post_id' => $jobId,
        ])->first();

        if (!$jobSeekerJob) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found'
            ], 404);
        }

        // Update status
        switch ($request->status) {
            case 'interviewing':
                $jobSeekerJob->is_called_for_interview = true;
                $jobSeekerJob->interview_date = $request->interview_date ?? now();
                break;
            case 'hired':
                $jobSeekerJob->is_got_job = true;
                break;
            case 'rejected':
                $jobSeekerJob->is_rejected = true;
                break;
            default:
                // applied - no change to flags
                break;
        }

        $jobSeekerJob->save();

        return response()->json([
            'success' => true,
            'message' => 'Application status updated',
            'status' => $this->getApplicationStatus($jobSeekerJob),
        ]);
    }
}