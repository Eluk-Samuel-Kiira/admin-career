<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Skills\SkillMatcher;
use App\Services\Skills\SkillNormaliser;

class MyJobsController extends Controller
{
    public function __construct(
        protected SkillMatcher $matcher,
    ) {}


    /**
     * GET /api/my-jobs
     *
     * Returns jobs grouped by the user's alert preferences:
     *  - by_category:    jobs in the categories the user selected
     *  - by_skill_match: jobs whose title/description/skills overlap the user's skills
     *
     * When neither preference is enabled, returns
     * { configured: false, message: "..." } so the frontend can show
     * the "Set preferences" call-to-action.
     */
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $seeker = $user?->seekerProfile;

        if (!$seeker) {
            return response()->json([
                'success'    => false,
                'configured' => false,
                'message'    => 'Seeker profile not found.',
            ], 404);
        }

        $prefs       = $seeker->job_preferences ?? [];
        $countryCode = $seeker->preferred_country_code
                    ?? $user->country_code
                    ?? config('app.country_code', 'UG');

        $byCategoryOn = !empty($prefs['alert_by_cv_skill']);
        $bySkillOn    = !empty($prefs['alert_by_skill_match']);
        $threshold    = (int) ($prefs['skill_match_threshold'] ?? 60);
        $categories   = $byCategoryOn ? ($prefs['alert_by_category'] ?? []) : [];

        // ── Does the seeker actually have skills? ───────────────
        $seekerSkills     = $seeker->skills ?? [];
        $hasSkills        = is_array($seekerSkills)
                            ? count(array_filter($seekerSkills)) > 0
                            : trim((string) $seekerSkills) !== '';
        $hasCvFile        = !empty($seeker->cv_files) || !empty($seeker->cv_file_path);

        $perPage = 10;
        $catPage   = max(1, (int) $request->get('cat_page', 1));
        $skillPage = max(1, (int) $request->get('skill_page', 1));

        // ── Is anything configured at all? ──────────────────────
        $categoryReady = $byCategoryOn && !empty($categories);
        $skillReady    = $bySkillOn && $hasSkills;


        if (!$categoryReady && !$skillReady) {
            // Decide what fallback CTA to show
            $needs = [];
            if (!$hasSkills && !$hasCvFile) {
                $needs[] = 'skills_or_cv';
            } elseif (!$hasSkills) {
                $needs[] = 'skills';
            }
            if (!$byCategoryOn && !$bySkillOn) {
                $needs[] = 'preferences';
            }

            return response()->json([
                'success'    => true,
                'configured' => false,
                'needs'      => $needs,
                'filters'    => [
                    'by_category'    => false,
                    'by_skill_match' => $bySkillOn,   // ← was already on, just no skills
                    'has_skills'     => $hasSkills,
                    'has_cv_file'    => $hasCvFile,
                ],
                'message'    => 'Set your alert preferences to see jobs matched to you.',
            ]);
        }

        $result = [
            'success'    => true,
            'configured' => true,
            'filters'    => [
                'by_category'         => $categoryReady,
                'category_ids'        => $categories,
                'by_skill_match'      => $bySkillOn,
                'has_skills'          => $hasSkills,
                'has_cv_file'         => $hasCvFile,
                'skill_threshold'     => $threshold,
                'country_code'        => $countryCode,
                'skill_matches_count' => 0,   // ← set below after scoring
            ],
            'by_category'    => $this->emptyPage($catPage,   $perPage),
            'by_skill_match' => $this->emptyPage($skillPage, $perPage),
        ];

        $baseQuery = JobPost::query()
            ->with([
                'company:id,name,logo_path',
                'jobCategory:id,name,slug',
                'jobLocation:id,district,city',
                'jobType:id,name,slug',
            ])
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('deadline')
                ->orWhere('deadline', '>=', now()->toDateString());
            });

        // ═══════════════════════════════════════════════════════════
        // Group 1: By Category
        // ═══════════════════════════════════════════════════════════
        if ($categoryReady) {
            $catPaginator = $baseQuery->clone()
                ->whereIn('job_category_id', $categories)
                ->orderByRaw('(legacy_id IS NULL) DESC')
                ->orderByDesc('published_at')
                ->paginate($perPage, ['*'], 'cat_page', $catPage);

            $result['by_category'] = $this->pageToArray($catPaginator, $seeker);
        }

        // ═══════════════════════════════════════════════════════════
        // Group 2: By Skill Match
        // ═══════════════════════════════════════════════════════════
        if ($skillReady) {
            $seekerTokens = (new SkillNormaliser())->canonicalise($seekerSkills);

            if (!empty($seekerTokens)) {
                $query = $baseQuery->clone();

                $query->where(function ($q) use ($seekerTokens) {
                    foreach ($seekerTokens as $token) {
                        $like = '%' . $token . '%';
                        $q->orWhere('skills',          'LIKE', $like)
                        ->orWhere('job_title',       'LIKE', $like)
                        ->orWhere('job_description', 'LIKE', $like);
                    }
                });

                // Fetch enough candidates to filter by score THEN paginate.
                // 200 is a safe upper bound: even if 60% pass the threshold,
                // that's 120 scored jobs, plenty for 12 pages of 10.
                $candidates = $query
                    ->orderByRaw('(legacy_id IS NULL) DESC')
                    ->orderByDesc('published_at')
                    ->limit(200)
                    ->get();

                $scored = $candidates
                    ->map(fn ($job) => [
                        'job'   => $job,
                        'score' => $this->matcher->score($seeker, $job),
                    ])
                    ->filter(fn ($row) => $row['score'] >= $threshold)
                    ->sortByDesc('score')
                    ->values();

                $result['filters']['skill_matches_count'] = $scored->count();

                // Manual pagination on the scored collection
                $total      = $scored->count();
                $lastPage   = max(1, (int) ceil($total / $perPage));
                $skillPage  = min($skillPage, $lastPage);
                $offset     = ($skillPage - 1) * $perPage;
                $pageItems  = $scored->slice($offset, $perPage)->values();

                $result['by_skill_match'] = [
                    'data' => $pageItems->map(function ($row) use ($seeker) {
                        $formatted = $this->formatJob($row['job'], $seeker);
                        $formatted['match_score'] = $row['score'];
                        return $formatted;
                    })->all(),
                    'pagination' => [
                        'current_page' => $skillPage,
                        'last_page'    => $lastPage,
                        'per_page'     => $perPage,
                        'total'        => $total,
                        'has_more'     => $skillPage < $lastPage,
                    ],
                ];
            }
        }

        // ── Dedupe: remove category jobs from skill page ─────────
        if ($categoryReady && !empty($result['by_skill_match']['data'])) {
            // Use the CURRENT cat page — items across pages are still
            // distinct because they're different pages of the same list.
            // This only prevents duplicates within the same response.
            $catIds = collect($result['by_category']['data'] ?? [])
                ->pluck('id')
                ->all();

            if (!empty($catIds)) {
                $result['by_skill_match']['data'] = array_values(array_filter(
                    $result['by_skill_match']['data'],
                    fn ($j) => !in_array($j['id'], $catIds, true)
                ));
            }
        }

        return response()->json($result);
    }

    private function emptyPage(int $page, int $perPage): array
    {
        return [
            'data' => [],
            'pagination' => [
                'current_page' => $page,
                'last_page'    => 1,
                'per_page'     => $perPage,
                'total'        => 0,
                'has_more'     => false,
            ],
        ];
    }

    private function pageToArray($paginator, $seeker): array
    {
        return [
            'data' => collect($paginator->items())
                ->map(fn ($j) => $this->formatJob($j, $seeker))
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function normaliseSkills($skills): array
    {
        if (is_string($skills)) {
            $skills = array_filter(array_map('trim', explode(',', $skills)));
        }
        if (!is_array($skills)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($s) => is_string($s) ? trim(mb_strtolower($s)) : '',
            $skills
        ), fn ($s) => $s !== '' && mb_strlen($s) >= 2));
    }

    private function skillMatchScore(JobPost $job, array $skills): int
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $job->job_title,
            $job->job_description,
            $job->skills,
            $job->qualifications,
        ])));

        $hits = 0;
        foreach ($skills as $skill) {
            if (mb_strpos($haystack, $skill) !== false) {
                $hits++;
            }
        }

        return count($skills) > 0
            ? (int) round(($hits / count($skills)) * 100)
            : 0;
    }

    private function formatJob(JobPost $job, $seeker): array
    {
        $seekerJob = JobSeekerJob::where('seeker_profile_id', $seeker->id)
            ->where('job_post_id', $job->id)
            ->first();

        return [
            'id'         => $job->id,
            'job_title'  => $job->job_title,
            'slug'       => $job->slug,
            'company'    => [
                'name' => $job->company?->name,
                'logo' => $job->company?->logo_url,
            ],
            'category'    => $job->jobCategory ? [
                'name' => $job->jobCategory->name,
                'slug' => $job->jobCategory->slug,
            ] : null,
            'location'   => $job->jobLocation ? [
                'district' => $job->jobLocation->district,
                'city'     => $job->jobLocation->city,
            ] : null,
            'job_type'    => $job->jobType?->name,
            'formatted_salary' => $this->formatSalary($job),
            'published_at'     => $job->published_at?->toIso8601String(),
            'is_saved'         => (bool) ($seekerJob?->is_saved ?? false),
            'is_applied'       => (bool) ($seekerJob?->is_applied ?? false),
            'easy_apply'       => $this->hasEasyApply($job),
        ];
    }

    private function formatSalary(JobPost $job): string
    {
        if ($job->salary_amount > 0) {
            $period = $job->payment_period ?? 'monthly';
            $suffix = match ($period) {
                'daily'   => '/day',
                'weekly'  => '/week',
                'yearly'  => '/year',
                default   => '/month',
            };
            return ($job->currency ?? 'UGX') . ' ' . number_format((float) $job->salary_amount) . $suffix;
        }
        if ($job->salaryRange && $job->salaryRange->min_salary) {
            return ($job->salaryRange->currency ?? 'UGX')
                . ' ' . number_format($job->salaryRange->min_salary)
                . ' – ' . number_format($job->salaryRange->max_salary);
        }
        return 'Negotiable';
    }

    private function hasEasyApply(JobPost $job): bool
    {
        return (bool) $job->has_ats
            && strtolower((string) $job->package_key) === 'job_post_enterprise';
    }
}