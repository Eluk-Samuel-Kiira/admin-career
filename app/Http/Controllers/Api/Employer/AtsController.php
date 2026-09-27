<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AtsController extends Controller
{
    // =================================================================
    // INDEX
    // =================================================================
    public function index(Request $request, string $slug): JsonResponse
    {
        $job = $this->resolveJob($request, $slug);

        $status = $request->get('status', '');
        $search = $request->get('search', '');
        $rating = $request->get('rating', '');

        $query = JobSeekerJob::with(['seekerProfile', 'reviewer'])
            ->where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->orderByDesc('applied_at');

        if ($status) $query->where('ats_status', $status);
        if ($rating) $query->where('employer_rating', $rating);

        if ($search) {
            $query->whereHas('seekerProfile', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('professional_title', 'like', "%{$search}%")
                  ->orWhere('skills', 'like', "%{$search}%");
            });
        }

        $applicants = $query->paginate(20);

        // Status counts
        $counts = JobSeekerJob::where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->select('ats_status', DB::raw('COUNT(*) as count'))
            ->groupBy('ats_status')
            ->pluck('count', 'ats_status')
            ->toArray();

        $total = array_sum($counts);

        return response()->json([
            'success' => true,
            'job' => [
                'id'        => $job->id,
                'slug'      => $job->slug,
                'job_title' => $job->job_title,
                'company'   => $job->company->name ?? null,
            ],
            'data'   => collect($applicants->items())->map(fn($a) => $this->formatApplicant($a)),
            'counts' => array_merge([
                'all' => $total,
                'new' => 0, 'screening' => 0, 'shortlisted' => 0,
                'interview' => 0, 'offer' => 0, 'hired' => 0,
                'rejected' => 0, 'withdrawn' => 0,
            ], $counts),
            'meta' => [
                'total'        => $applicants->total(),
                'current_page' => $applicants->currentPage(),
                'last_page'    => $applicants->lastPage(),
            ],
        ]);
    }

    // =================================================================
    // SHOW
    // =================================================================
    public function show(Request $request, string $slug, int $id): JsonResponse
    {
        $job = $this->resolveJob($request, $slug);

        $applicant = JobSeekerJob::with(['seekerProfile', 'reviewer'])
            ->where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success'   => true,
            'applicant' => $this->formatApplicant($applicant, true),
        ]);
    }

    // =================================================================
    // UPDATE
    // =================================================================
    public function update(Request $request, string $slug, int $id): JsonResponse
    {
        $job = $this->resolveJob($request, $slug);

        $applicant = JobSeekerJob::where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'ats_status'      => ['nullable', Rule::in([
                'new','screening','shortlisted','interview',
                'offer','hired','rejected','withdrawn',
            ])],
            'employer_rating' => 'nullable|integer|min:1|max:5',
            'employer_notes'  => 'nullable|string|max:5000',
        ]);

        // Sync legacy booleans
        if (!empty($validated['ats_status'])) {
            $s = $validated['ats_status'];
            $validated['is_called_for_interview'] = in_array($s, ['interview','offer','hired'], true);
            $validated['is_got_job']              = $s === 'hired';
            $validated['is_rejected']             = $s === 'rejected';
            $validated['is_viewed']               = true;
        }

        $validated['reviewed_by'] = auth()->id();
        $validated['reviewed_at'] = now();

        $applicant->update($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Applicant updated.',
            'applicant' => $this->formatApplicant($applicant->fresh(['seekerProfile', 'reviewer']), true),
        ]);
    }

    // =================================================================
    // BULK
    // =================================================================
    public function bulk(Request $request, string $slug): JsonResponse
    {
        $job = $this->resolveJob($request, $slug);

        $validated = $request->validate([
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer',
            'ats_status' => ['required', Rule::in([
                'new','screening','shortlisted','interview',
                'offer','hired','rejected','withdrawn',
            ])],
        ]);

        $status = $validated['ats_status'];

        $count = JobSeekerJob::where('job_post_id', $job->id)
            ->whereIn('id', $validated['ids'])
            ->update([
                'ats_status'              => $status,
                'is_called_for_interview' => in_array($status, ['interview','offer','hired'], true),
                'is_got_job'              => $status === 'hired',
                'is_rejected'             => $status === 'rejected',
                'is_viewed'               => true,
                'reviewed_by'             => auth()->id(),
                'reviewed_at'             => now(),
                'updated_at'              => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} applicant(s) updated.",
            'count'   => $count,
        ]);
    }

    // =================================================================
    // DOWNLOAD CV — REUSES existing cv_files resolution logic
    // =================================================================
    public function downloadCv(Request $request, string $slug, int $id)
    {
        $job = $this->resolveJob($request, $slug);

        $applicant = JobSeekerJob::with('seekerProfile')
            ->where('job_post_id', $job->id)
            ->where('id', $id)
            ->firstOrFail();

        $seeker = $applicant->seekerProfile;
        if (!$seeker) abort(404, 'Seeker not found.');

        // Same resolution order as the existing admin CvController:
        // 1. The CV the seeker used to apply
        // 2. Their primary CV path
        // 3. First entry in cv_files array
        $path = $applicant->cv_used_path
              ?: $seeker->cv_file_path
              ?: ($this->getCvFilesArray($seeker)[0]['path'] ?? null);

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'CV file not found.');
        }

        return response()->file(Storage::disk('public')->path($path));
    }

    // =================================================================
    // EXPORT CSV
    // =================================================================
    public function export(Request $request, string $slug)
    {
        $job = $this->resolveJob($request, $slug);

        $applicants = JobSeekerJob::with('seekerProfile')
            ->where('job_post_id', $job->id)
            ->where('is_applied', true)
            ->orderByDesc('applied_at')
            ->get();

        $filename = 'applicants-' . $job->slug . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($applicants) {
            $fh = fopen('php://output', 'w');

            fputcsv($fh, [
                'Name', 'Email', 'Phone', 'Title', 'Skills',
                'ATS Status', 'Rating', 'Applied At',
            ]);

            foreach ($applicants as $a) {
                $s = $a->seekerProfile;
                fputcsv($fh, [
                    trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                    $s->email ?? '',
                    $s->phone ?? '',
                    $s->professional_title ?? '',
                    is_array($s->skills) ? implode(', ', $s->skills) : ($s->skills ?? ''),
                    $a->ats_status,
                    $a->employer_rating ?? '',
                    $a->applied_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($fh);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // =================================================================
    // HELPERS
    // =================================================================
    private function resolveJob(Request $request, string $slug): JobPost
    {
        $profile = EmployerProfile::where('user_id', $request->user()->id)->first();

        if (!$profile || !$profile->company_id) {
            abort(403, 'Employer profile not set up.');
        }

        $job = JobPost::where('slug', $slug)
            ->where('company_id', $profile->company_id)
            ->firstOrFail();

        if (!$job->has_ats) {
            abort(403, 'This job is not on the Enterprise plan.');
        }

        return $job;
    }


    private function formatApplicant(JobSeekerJob $a, bool $full = false): array
    {
        $s = $a->seekerProfile;

        $data = [
            'id'        => $a->id,
            'seeker_id' => $s->id ?? null,

            'name'  => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) ?: '—',
            'email' => $s->email ?? '—',
            'phone' => $s->phone ?? null,

            'professional_title'   => $s->professional_title ?? null,
            'country'              => $s->country ?? null,
            'city'                 => $s->city ?? null,
            'years_of_experience'  => $s->years_of_experience ?? null,
            'skills'               => $s->skills ?? null,

            'ats_status'      => $a->ats_status,
            'status_badge'    => $a->ats_status_badge,
            'employer_rating' => $a->employer_rating,

            'has_cv'          => (bool) ($a->cv_used_path || $s->cv_file_path || !empty($this->getCvFilesArray($s))),

            // ── AI Screening ─────────────────────────────────────
            'ai_score'             => $a->ai_score,
            'ai_recommendation'    => $a->ai_recommendation,
            'ai_summary'           => $a->ai_summary,
            'ai_strengths'         => $a->ai_strengths,
            'ai_gaps'              => $a->ai_gaps,
            'ai_red_flags'         => $a->ai_red_flags,
            'ai_screened_at'       => $a->ai_screened_at?->toISOString(),
            'ai_stale'             => $a->ai_screening_stale,
            'ai_recommendation_badge' => $a->ai_recommendation_badge,
            'ai_score_color'       => $a->ai_score_color,

            'applied_at'  => $a->applied_at?->toISOString(),
            'reviewed_at' => $a->reviewed_at?->toISOString(),
        ];

        if ($full) {
            $data['employer_notes']    = $a->employer_notes;
            $data['cover_letter']      = $a->cover_letter;
            $data['application_letter']= $a->application_letter;
            $data['interview_date']    = $a->interview_date?->toISOString();
            $data['interview_notes']   = $a->interview_notes;

            $data['professional_summary'] = $s->professional_summary ?? null;
            $data['linkedin_url']         = $s->linkedin_url ?? null;
            $data['github_url']           = $s->github_url ?? null;
            $data['portfolio_url']        = $s->portfolio_url ?? null;
            $data['languages']            = $s->languages ?? null;
            $data['education']            = $s->education ?? null;
            $data['work_experience']      = $s->work_experience ?? null;
            $data['certifications']       = $s->certifications ?? null;

            // REUSE the existing cv_files array with URLs — same shape the admin blade already uses
            $cvFiles = $this->getCvFilesArray($s);
            $data['cv_files'] = array_map(function ($file) {
                if (isset($file['path'])) {
                    $file['url'] = Storage::disk('public')->url($file['path']);
                }
                return $file;
            }, $cvFiles);

            $data['cv_used_path'] = $a->cv_used_path;
            $data['cv_file_path'] = $s->cv_file_path;
        }

        return $data;
    }


    /**
     * Queue AI screening for a batch of applicants.
     *
     * POST /api/employer/ats/{slug}/screen
     *
     * Body:
     *   - applicant_ids: optional array (empty = all unscreened)
     *   - force:         optional bool  (re-screen even if already screened)
     */
    public function screen(Request $request, string $slug): JsonResponse
    {
        $job = $this->resolveJobForEmployer($request, $slug);

        $validated = $request->validate([
            'applicant_ids'   => 'nullable|array',
            'applicant_ids.*' => 'integer',
            'force'           => 'nullable|boolean',
        ]);

        $force = (bool) ($validated['force'] ?? false);

        // ── Compute the job fingerprint (stale-detection)
        $jobFingerprint = hash('sha256', implode('|', [
            $job->job_title ?? '',
            strip_tags($job->job_description ?? ''),
            strip_tags($job->responsibilities ?? ''),
            strip_tags($job->qualifications ?? ''),
            $job->skills ?? '',
        ]));

        // ── Build the base applicant query
        $query = JobSeekerJob::with('seekerProfile')
            ->where('job_post_id', $job->id)
            ->where('is_applied', true);

        if (!empty($validated['applicant_ids'])) {
            $query->whereIn('id', $validated['applicant_ids']);
        }

        // Only re-screen if stale or not yet screened
        if (!$force) {
            $query->where(function ($q) use ($jobFingerprint) {
                $q->whereNull('ai_screened_at')
                ->orWhere('ai_job_fingerprint', '!=', $jobFingerprint);
            });
        }

        $applicants = $query->get();

        if ($applicants->isEmpty()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Nothing to screen — all applicants are up to date.',
                'queued'   => 0,
                'skipped'  => 0,
                'batch_id' => null,
            ]);
        }

        // ── Pre-filter: only queue applicants with a usable CV source
        $screened = collect();
        $skipped  = [];

        foreach ($applicants as $applicant) {
            $source = $this->detectCvSource($applicant);

            if ($source) {
                $screened->push($applicant);
            } else {
                $skipped[] = $applicant->id;
            }
        }

        $applicantIds = $screened->pluck('id')->toArray();

        // If nothing survives the filter, be explicit about why
        if (empty($applicantIds)) {
            return response()->json([
                'success' => true,
                'message' => count($skipped) . ' applicant(s) have no CV data available — nothing queued.',
                'queued'  => 0,
                'skipped' => count($skipped),
                'batch_id'=> null,
            ]);
        }

        // ── Create the batch
        $batch = \App\Models\Job\JobScreeningBatch::create([
            'job_post_id'     => $job->id,
            'user_id'         => $request->user()->id,
            'total'           => count($applicantIds),
            'processed'       => 0,
            'failed'          => 0,
            'status'          => 'queued',
            'applicant_ids'   => $applicantIds,
            'job_fingerprint' => $jobFingerprint,
        ]);

        // ── Dispatch one job per applicant
        foreach ($applicantIds as $applicantId) {
            \App\Jobs\ScreenApplicantJob::dispatch($applicantId, $batch->id)
                ->onQueue('ai-screening');
        }

        $message = count($applicantIds) . ' applicant(s) queued for AI screening.';
        if (!empty($skipped)) {
            $message .= ' ' . count($skipped) . ' skipped (no CV data).';
        }

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'batch_id' => $batch->uuid,
            'queued'   => count($applicantIds),
            'skipped'  => count($skipped),
            'total'    => count($applicantIds) + count($skipped),
        ]);
    }

    /**
     * Detect whether an applicant has at least one usable CV source.
     * Returns a short label ('file' | 'profile') or null if nothing exists.
     *
     * Priority:
     *   1. Any file path that actually exists on disk (cv_used_path, cv_file_path, cv_files[])
     *   2. Structured profile data (professional_title, skills, experience, etc.)
     */
    private function detectCvSource(JobSeekerJob $applicant): ?string
    {
        $seeker = $applicant->seekerProfile;
        if (!$seeker) {
            return null;
        }

        // Build candidate paths from every source
        $paths = [];

        if (!empty($applicant->cv_used_path)) {
            $paths[] = $applicant->cv_used_path;
        }

        if (!empty($seeker->cv_file_path)) {
            $paths[] = $seeker->cv_file_path;
        }

        foreach ($this->getCvFilesArray($seeker) as $entry) {
            if (!empty($entry['path'])) {
                $paths[] = $entry['path'];
            }
        }

        $paths = array_values(array_unique($paths));

        // Any file actually on disk?
        foreach ($paths as $path) {
            if (\Storage::disk('public')->exists($path)) {
                return 'file';
            }
        }

        // Fall back to structured profile?
        if (!empty($seeker->professional_title)
            || !empty($seeker->professional_summary)
            || !empty($seeker->skills)
            || !empty($seeker->work_experience)
            || !empty($seeker->education)
            || !empty($seeker->years_of_experience)) {
            return 'profile';
        }

        return null;
    }

    /**
     * Safely get cv_files as an array regardless of underlying storage type.
     */
    private function getCvFilesArray($seeker): array
    {
        $files = $seeker->cv_files ?? null;

        if (is_null($files)) return [];

        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($files)) return $files;

        return [];
    }

    /**
     * Get the current status of a screening batch.
     *
     * GET /api/employer/ats/batches/{uuid}
     */
    public function batchStatus(Request $request, string $uuid): JsonResponse
    {
        $batch = \App\Models\Job\JobScreeningBatch::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'batch'   => [
                'uuid'             => $batch->uuid,
                'status'           => $batch->status,
                'total'            => $batch->total,
                'processed'        => $batch->processed,
                'failed'           => $batch->failed,
                'progress_percent' => $batch->progress_percent,
                'is_complete'      => $batch->is_complete,
                'started_at'       => $batch->started_at?->toISOString(),
                'completed_at'     => $batch->completed_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Resolve the job by slug for the currently authenticated employer.
     * Verifies:
     *   - the employer has a company
     *   - the job belongs to that company
     *   - the job has ATS enabled (Enterprise package)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function resolveJobForEmployer(Request $request, string $slug): JobPost
    {
        $user = $request->user();

        $profile = \App\Models\EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->company_id) {
            abort(403, 'Your employer profile is not set up correctly. Please complete your company profile first.');
        }

        $job = JobPost::where('slug', $slug)
            ->where('company_id', $profile->company_id)
            ->firstOrFail();

        if (!$job->has_ats) {
            abort(403, 'This job is not on the Enterprise plan — applicant management is not available.');
        }

        return $job;
    }

}