<?php

namespace App\Http\Controllers\Api\Employer;

use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Job\JobPost;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * GET /api/employer/analytics/filters
     * Returns the list of jobs for the filter dropdown.
     */
    public function filters(Request $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);
        if (!$profile) {
            return response()->json([
                'success' => true,
                'jobs'    => [],
            ]);
        }

        $jobs = JobPost::where('company_id', $profile->company_id)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->get(['id', 'job_title', 'slug'])
            ->map(fn($j) => [
                'id'    => $j->id,
                'title' => $j->job_title,
                'slug'  => $j->slug,
            ]);

        return response()->json([
            'success' => true,
            'jobs'    => $jobs,
        ]);
    }

    /**
     * GET /api/employer/analytics/data
     * Returns all chart + table data for a given range and job filter.
     */
    public function data(Request $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);
        if (!$profile) {
            return response()->json([
                'success' => true,
                'data'    => $this->emptyResponse(),
            ]);
        }

        $range = $request->get('range', '30d');
        $jobId = $request->get('job_id');

        [$from, $to] = $this->resolveRange($range);

        $jobQuery = JobPost::where('company_id', $profile->company_id);
        if ($jobId) {
            $jobQuery->where('id', $jobId);
        }
        $jobIds = $jobQuery->pluck('id')->toArray();

        if (empty($jobIds)) {
            return response()->json([
                'success' => true,
                'data'    => $this->emptyResponse(),
            ]);
        }

        // ── Totals
        $totals = [
            'jobs_posted'      => JobPost::whereIn('id', $jobIds)
                                    ->whereBetween('created_at', [$from, $to])
                                    ->count(),
            'total_views'      => (int) JobPost::whereIn('id', $jobIds)->sum('view_count'),
            'total_applicants' => JobSeekerJob::whereIn('job_post_id', $jobIds)
                                    ->where('is_applied', true)
                                    ->count(),
            'applicants_range' => JobSeekerJob::whereIn('job_post_id', $jobIds)
                                    ->where('is_applied', true)
                                    ->whereBetween('applied_at', [$from, $to])
                                    ->count(),
        ];

        $totals['conversion_rate'] = $totals['total_views'] > 0
            ? round(($totals['total_applicants'] / $totals['total_views']) * 100, 2)
            : 0;

        // ── By ATS status
        $byStatus = JobSeekerJob::whereIn('job_post_id', $jobIds)
            ->where('is_applied', true)
            ->select('ats_status', DB::raw('COUNT(*) as count'))
            ->groupBy('ats_status')
            ->pluck('count', 'ats_status')
            ->toArray();

        // ── Time series
        $series = $this->buildSeries($jobIds, $from, $to, $range);

        // ── Top jobs
        $topJobs = JobSeekerJob::whereIn('job_post_id', $jobIds)
            ->where('is_applied', true)
            ->whereBetween('applied_at', [$from, $to])
            ->select('job_post_id', DB::raw('COUNT(*) as applicants'))
            ->groupBy('job_post_id')
            ->orderByDesc('applicants')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $job = JobPost::find($row->job_post_id);
                return [
                    'id'         => $row->job_post_id,
                    'title'      => $job->job_title ?? '—',
                    'slug'       => $job->slug ?? null,
                    'views'      => (int) ($job->view_count ?? 0),
                    'applicants' => (int) $row->applicants,
                    'conversion' => ($job->view_count ?? 0) > 0
                        ? round(($row->applicants / $job->view_count) * 100, 2)
                        : 0,
                ];
            });

        // ── Top countries
        $topCountries = JobSeekerJob::whereIn('job_post_id', $jobIds)
            ->where('is_applied', true)
            ->whereBetween('applied_at', [$from, $to])
            ->join('seeker_profiles', 'seeker_profiles.id', '=', 'job_seeker_jobs.seeker_profile_id')
            ->whereNotNull('seeker_profiles.country')
            ->select('seeker_profiles.country', DB::raw('COUNT(*) as count'))
            ->groupBy('seeker_profiles.country')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'seeker_profiles.country')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'range'         => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
                'totals'        => $totals,
                'by_status'     => array_merge([
                    'new' => 0, 'screening' => 0, 'shortlisted' => 0,
                    'interview' => 0, 'offer' => 0, 'hired' => 0,
                    'rejected' => 0, 'withdrawn' => 0,
                ], $byStatus),
                'series'        => $series,
                'top_jobs'      => $topJobs,
                'top_countries' => $topCountries,
            ],
        ]);
    }

    // =================================================================
    // HELPERS
    // =================================================================
    private function resolveProfile(Request $request): ?EmployerProfile
    {
        $profile = EmployerProfile::where('user_id', $request->user()->id)->first();

        if (!$profile || !$profile->company_id) {
            return null;
        }

        return $profile;
    }

    private function resolveRange(string $range): array
    {
        $now = now();

        return match ($range) {
            '7d'    => [$now->copy()->subDays(7)->startOfDay(),  $now->copy()->endOfDay()],
            '30d'   => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            '90d'   => [$now->copy()->subDays(90)->startOfDay(), $now->copy()->endOfDay()],
            '12m'   => [$now->copy()->subMonths(12)->startOfDay(), $now->copy()->endOfDay()],
            'all'   => [now()->subYears(10)->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    private function buildSeries(array $jobIds, $from, $to, string $range): array
    {
        $groupBy = match ($range) {
            '7d', '30d'  => 'day',
            '90d'        => 'week',
            '12m', 'all' => 'month',
            default      => 'day',
        };

        $format = match ($groupBy) {
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
        };

        $rows = JobSeekerJob::whereIn('job_post_id', $jobIds)
            ->where('is_applied', true)
            ->whereBetween('applied_at', [$from, $to])
            ->select(
                DB::raw("DATE_FORMAT(applied_at, '{$format}') as period"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $rows->map(fn($r) => ['period' => $r->period, 'count' => (int) $r->count])->toArray();
    }

    private function emptyResponse(): array
    {
        return [
            'range'         => ['from' => now()->subDays(30)->toDateString(), 'to' => now()->toDateString()],
            'totals'        => ['jobs_posted' => 0, 'total_views' => 0, 'total_applicants' => 0, 'applicants_range' => 0, 'conversion_rate' => 0],
            'by_status'     => ['new' => 0, 'screening' => 0, 'shortlisted' => 0, 'interview' => 0, 'offer' => 0, 'hired' => 0, 'rejected' => 0, 'withdrawn' => 0],
            'series'        => [],
            'top_jobs'      => [],
            'top_countries' => [],
        ];
    }
}