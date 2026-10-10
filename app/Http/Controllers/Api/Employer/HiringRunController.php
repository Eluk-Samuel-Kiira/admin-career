<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Jobs\ScreenUploadedCvJob;
use App\Models\Hiring\HiringRun;
use App\Models\Hiring\HiringRunCandidate;
use App\Models\Job\JobPost;
use App\Services\Jobs\CvTextExtractionService;
use App\Services\Subscription\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HiringRunController extends Controller
{
    public function __construct(
        protected SubscriptionGate $gate,
        protected CvTextExtractionService $extractor,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // List past runs
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $runs = HiringRun::where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $runs->through(fn($r) => $this->formatRun($r)),
        ]);
    }

    /**
     * GET /api/employer/hiring/my-job-posts
     *
     * Returns the authenticated employer's own active job posts, used to
     * prefill the job description picker on the create run form.
     */
    public function myJobPosts(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = \App\Models\EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile || !$profile->company_id) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $jobs = JobPost::where('company_id', $profile->company_id)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get(['id', 'job_title', 'slug', 'country_code', 'created_at']);

        return response()->json([
            'success' => true,
            'data'    => $jobs->map(fn($j) => [
                'id'           => $j->id,
                'job_title'    => $j->job_title,
                'slug'         => $j->slug,
                'country_code' => $j->country_code,
                'label'        => $j->job_title . ($j->country_code ? " ({$j->country_code})" : ''),
            ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Create a new run — the main entry point
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        // \Log::info($request->all());
        $user = $request->user();

        // 1. Subscription check
        if (!$this->gate->hasFeature($user, 'cv_screening')) {
            return response()->json([
                'success' => false,
                'code'    => 'subscription_required',
                'message' => 'AI CV screening is available on paid plans. Upgrade to unlock.',
            ], 402);
        }

        // 2. Validate payload
        $validated = $request->validate([
            'title'                => 'required|string|max:150',
            'job_description'      => 'nullable|string|max:50000',
            'job_description_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'job_post_id'          => 'nullable|integer|exists:job_posts,id',
            'cvs'                  => 'required_without:cvs_zip|array|max:500',
            'cvs.*'                => 'file|mimes:pdf,doc,docx,txt|max:5120',
            'cvs_zip'              => 'nullable|file|mimes:zip|max:102400',
        ]);

        // 3. Resolve the job description text
        $jobDescription = $this->resolveJobDescription($request, $validated);
        if (trim($jobDescription) === '') {
            return response()->json([
                'success' => false,
                'message' => 'A job description is required.',
            ], 422);
        }

        // 4. Collect the CV files (direct uploads and/or from a zip)
        $cvFiles = $this->collectCvFiles($request);
        if (empty($cvFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload at least one CV.',
            ], 422);
        }

        // 5. Enforce monthly quota
        // $quota = $this->gate->cvScreeningQuota($user);
        // if ($quota !== null) {
        //     $usedThisMonth = HiringRunCandidate::whereHas('run', function ($q) use ($user) {
        //         $q->where('user_id', $user->id)
        //           ->where('created_at', '>=', now()->startOfMonth());
        //     })->count();

        //     if ($usedThisMonth + count($cvFiles) > $quota) {
        //         return response()->json([
        //             'success' => false,
        //             'code'    => 'quota_exceeded',
        //             'message' => "This run would exceed your monthly quota of {$quota} CVs. "
        //                        . "You have {$usedThisMonth} used and " . count($cvFiles) . " new.",
        //         ], 402);
        //     }
        // }

        // 6. Create the run row
        $run = HiringRun::create([
            'uuid'                  => (string) Str::uuid(),
            'user_id'               => $user->id,
            'company_id'            => $user->employerProfile?->company_id,
            'title'                 => $validated['title'],
            'job_post_id'           => $validated['job_post_id'] ?? null,
            'job_description'       => $jobDescription,
            'job_description_source'=> $this->resolveJdSource($request),
            'total'                 => count($cvFiles),
            'status'                => 'queued',
            'job_fingerprint'       => hash('sha256', $jobDescription),
        ]);

        // 7. Store the CVs and create candidate rows
        $candidateIds = [];
        $baseDir = 'hiring/' . $run->uuid;

        foreach ($cvFiles as $file) {
            $storedPath = $file->store($baseDir, 'public');

            $candidate = HiringRunCandidate::create([
                'hiring_run_id' => $run->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $storedPath,
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
                'status'        => 'pending',
            ]);

            $candidateIds[] = $candidate->id;
        }

        // 8. Mark the run as processing and dispatch one job per CV
        $run->update(['status' => 'processing', 'started_at' => now()]);

        foreach ($candidateIds as $id) {
            ScreenUploadedCvJob::dispatch($id)->onQueue('default');
        }

        return response()->json([
            'success' => true,
            'message' => count($candidateIds) . ' CV(s) queued for screening.',
            'run'     => $this->formatRun($run->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Progress polling — same shape as the ATS batch status
    // ─────────────────────────────────────────────────────────────
    public function status(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $run = HiringRun::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'run' => [
                'uuid'              => $run->uuid,
                'title'             => $run->title,
                'status'            => $run->status,
                'total'             => $run->total,
                'processed'         => $run->processed,
                'failed'            => $run->failed,
                'progress_percent'  => $run->total > 0
                    ? (int) round((($run->processed + $run->failed) / $run->total) * 100)
                    : 0,
                'is_complete'       => $run->status === 'completed',
                'started_at'        => $run->started_at?->toISOString(),
                'completed_at'      => $run->completed_at?->toISOString(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Results — the shortlist
    // ─────────────────────────────────────────────────────────────
    public function results(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $run  = HiringRun::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $query = HiringRunCandidate::where('hiring_run_id', $run->id);

        if ($s = $request->get('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('candidate_name', 'like', "%{$s}%")
                  ->orWhere('candidate_email', 'like', "%{$s}%")
                  ->orWhere('current_title', 'like', "%{$s}%")
                  ->orWhere('original_name', 'like', "%{$s}%");
            });
        }

        if ($rec = $request->get('recommendation')) {
            $query->where('recommendation', $rec);
        }

        if ($min = $request->get('min_score')) {
            $query->where('score', '>=', (int) $min);
        }

        $sort = $request->get('sort', 'score');
        $query->orderByDesc($sort === 'recent' ? 'screened_at' : 'score');

        $candidates = $query->paginate(50);

        return response()->json([
            'success' => true,
            'run'     => $this->formatRun($run),
            'data'    => $candidates->through(fn($c) => $this->formatCandidate($c)),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Export the shortlist as CSV
    // ─────────────────────────────────────────────────────────────
    public function export(Request $request, string $uuid)
    {
        $user = $request->user();
        $run  = HiringRun::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        $candidates = HiringRunCandidate::where('hiring_run_id', $run->id)
            ->orderByDesc('score')
            ->get();

        $filename = 'shortlist-' . Str::slug($run->title) . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($candidates) {
            $fh = fopen('php://output', 'w');

            fputcsv($fh, [
                'Rank', 'Name', 'Email', 'Phone', 'Current Title',
                'Years Exp', 'Education', 'Score', 'Recommendation',
                'Matched Skills', 'Missing Skills', 'Summary', 'File',
            ]);

            foreach ($candidates as $i => $c) {
                fputcsv($fh, [
                    $i + 1,
                    $c->candidate_name ?: $c->original_name,
                    $c->candidate_email,
                    $c->candidate_phone,
                    $c->current_title,
                    $c->years_of_experience,
                    $c->highest_education,
                    $c->score,
                    $c->recommendation,
                    implode(', ', $c->matched_skills ?? []),
                    implode(', ', $c->missing_skills ?? []),
                    $c->summary,
                    $c->original_name,
                ]);
            }

            fclose($fh);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ─────────────────────────────────────────────────────────────
    // Delete a run and its CV files
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();
        $run  = HiringRun::where('uuid', $uuid)->where('user_id', $user->id)->firstOrFail();

        foreach ($run->candidates as $c) {
            if ($c->stored_path && Storage::disk('public')->exists($c->stored_path)) {
                Storage::disk('public')->delete($c->stored_path);
            }
        }

        $run->delete();

        return response()->json(['success' => true, 'message' => 'Run deleted.']);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────
    protected function resolveJobDescription(Request $request, array $validated): string
    {
        if (!empty($validated['job_post_id'])) {
            $job = JobPost::find($validated['job_post_id']);
            if ($job) {
                return implode("\n\n", array_filter([
                    strip_tags($job->job_description ?? ''),
                    strip_tags($job->responsibilities ?? ''),
                    strip_tags($job->qualifications ?? ''),
                    $job->skills ? 'Skills: ' . $job->skills : null,
                ]));
            }
        }

        if ($request->hasFile('job_description_file')) {
            $file = $request->file('job_description_file');
            try {
                return $this->extractor->extract($file->getRealPath(), $file->getMimeType());
            } catch (\Throwable $e) {
                return '';
            }
        }

        return (string) ($validated['job_description'] ?? '');
    }

    protected function resolveJdSource(Request $request): string
    {
        if ($request->filled('job_post_id'))      return 'post';
        if ($request->hasFile('job_description_file')) return 'upload';
        return 'paste';
    }

    protected function collectCvFiles(Request $request): array
    {
        $files = [];

        if ($request->hasFile('cvs')) {
            foreach ($request->file('cvs') as $f) {
                if ($f->isValid()) $files[] = $f;
            }
        }

        if ($request->hasFile('cvs_zip')) {
            $zip = $request->file('cvs_zip');
            $tmp = $zip->getRealPath();
            $zipArchive = new \ZipArchive();

            if ($zipArchive->open($tmp) === true) {
                $extractDir = storage_path('app/tmp/zip_' . Str::uuid());
                @mkdir($extractDir, 0755, true);
                $zipArchive->extractTo($extractDir);
                $zipArchive->close();

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && in_array(strtolower($file->getExtension()), ['pdf', 'doc', 'docx', 'txt'])) {
                        $files[] = new \Illuminate\Http\UploadedFile(
                            $file->getRealPath(),
                            $file->getFilename(),
                            mime_content_type($file->getRealPath()) ?: 'application/octet-stream',
                            null,
                            true
                        );
                    }
                }
            }
        }

        return $files;
    }

    protected function formatRun(HiringRun $r): array
    {
        return [
            'uuid'             => $r->uuid,
            'title'            => $r->title,
            'status'           => $r->status,
            'total'            => $r->total,
            'processed'        => $r->processed,
            'failed'           => $r->failed,
            'progress_percent' => $r->total > 0
                ? (int) round((($r->processed + $r->failed) / $r->total) * 100)
                : 0,
            'created_at'       => $r->created_at?->toISOString(),
            'completed_at'     => $r->completed_at?->toISOString(),
        ];
    }

    protected function formatCandidate(HiringRunCandidate $c, bool $detailed = false): array
    {
        $data = [
            'id'                 => $c->id,
            'name'               => $c->candidate_name ?: pathinfo($c->original_name, PATHINFO_FILENAME),
            'email'              => $c->candidate_email,
            'phone'              => $c->candidate_phone,
            'current_title'      => $c->current_title,
            'years_of_experience'=> $c->years_of_experience,
            'highest_education'  => $c->highest_education,
            'score'              => $c->score,
            'recommendation'     => $c->recommendation,
            'recommendation_badge' => $c->recommendation_badge,
            'score_color'        => $c->score_color,
            'status'             => $c->status,
            'file_name'          => $c->original_name,
            'file_url'           => $c->stored_path
                ? Storage::disk('public')->url($c->stored_path)
                : null,
            'screened_at'        => $c->screened_at?->toISOString(),
        ];

        if ($detailed) {
            $data['summary']        = $c->summary;
            $data['strengths']      = $c->strengths ?? [];
            $data['gaps']           = $c->gaps ?? [];
            $data['red_flags']      = $c->red_flags ?? [];
            $data['matched_skills'] = $c->matched_skills ?? [];
            $data['missing_skills'] = $c->missing_skills ?? [];
        }

        return $data;
    }
}