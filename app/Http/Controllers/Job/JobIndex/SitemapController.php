<?php

namespace App\Http\Controllers\Job\JobIndex;

use App\Http\Controllers\Controller;
use App\Services\Indexing\SitemapService;
use App\Services\Indexing\GoogleIndexingService;
use App\Models\Job\JobPost;
use Illuminate\Http\Request;
use App\Models\Job\Country;
use App\Helpers\CountryHelper;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    protected SitemapService $sitemapService;
    protected GoogleIndexingService $googleIndexingService;

    public function __construct(SitemapService $sitemapService, GoogleIndexingService $googleIndexingService)
    {
        $this->sitemapService = $sitemapService;
        $this->googleIndexingService = $googleIndexingService;
    }

    /**
     * Display sitemap dashboard
     */
    public function dashboard()
    {
        return view('job.job-index.ping-index');
    }

    /**
     * Get sitemap statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        $country = $request->input('country', 'all');
        
        $query = JobPost::where('is_active', true)->where('deadline', '>=', now());
        
        if ($country !== 'all') {
            $query->where('country_code', $country);
        }
        
        $stats = [
            'total_jobs' => (clone $query)->count(),
            'pinged_jobs' => (clone $query)->whereNotNull('last_pinged_at')->count(),  // Has been pinged
            'unpinged_jobs' => (clone $query)->whereNull('last_pinged_at')->count(),  // Not pinged yet
            'indexed_jobs' => (clone $query)->where('is_indexed', true)->count(),
            'unindexed_jobs' => (clone $query)->where('is_indexed', false)->count(),
            'featured_jobs' => (clone $query)->where('is_featured', true)->count(),
            'urgent_jobs' => (clone $query)->where('is_urgent', true)->count(),
            'recently_pinged' => (clone $query)->where('last_pinged_at', '>=', now()->subDays(7))->count(),
            'new_jobs_last_7_days' => (clone $query)->where('created_at', '>=', now()->subDays(7))->count(),
            'countries' => [],
        ];

        // Get per-country statistics
        $activeCountries = CountryHelper::getActiveCountries();

        foreach ($activeCountries as $country) {
            $code = $country['code'];
            $countryQuery = JobPost::where('is_active', true)
                ->where('deadline', '>=', now())
                ->where('country_code', $code);
            
            $stats['countries'][$code] = [
                'total' => (clone $countryQuery)->count(),
                'pinged' => (clone $countryQuery)->whereNotNull('last_pinged_at')->count(),
                'unpinged' => (clone $countryQuery)->whereNull('last_pinged_at')->count(),
                'indexed' => (clone $countryQuery)->where('is_indexed', true)->count(),
                'unindexed' => (clone $countryQuery)->where('is_indexed', false)->count(),
                'new_this_week' => (clone $countryQuery)->where('created_at', '>=', now()->subDays(7))->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Get jobs for ping/index management
     */
    public function getJobs(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $country = $request->input('country', 'all');
        $status = $request->input('status', 'all'); // all, pinged, unpinged, indexed, unindexed
        $search = $request->input('search', '');

        $query = JobPost::where('is_active', true)
            ->where('deadline', '>=', now())
            ->with(['company', 'jobCategory']);

        if ($country !== 'all') {
            $query->where('country_code', $country);
        }

        // Use last_pinged_at for ping status (consistent with statistics)
        if ($status === 'pinged') {
            $query->whereNotNull('last_pinged_at');
        } elseif ($status === 'unpinged') {
            $query->whereNull('last_pinged_at');
        } elseif ($status === 'indexed') {
            $query->where('is_indexed', true);
        } elseif ($status === 'unindexed') {
            $query->where('is_indexed', false);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhereHas('company', function($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $jobs = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Add a flag to indicate if job is pinged based on last_pinged_at
        $jobs->getCollection()->transform(function ($job) {
            $job->is_pinged = !is_null($job->last_pinged_at);
            return $job;
        });

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
                'from' => $jobs->firstItem(),
                'to' => $jobs->lastItem(),
            ]
        ]);
    }

    /**
     * Ping selected jobs to search engines
     */
    public function pingJobs(Request $request)
    {
        $request->validate([
            'job_ids' => 'sometimes|array',
            'job_ids.*' => 'integer|exists:job_posts,id',
            'country' => 'nullable|string|size:2',
        ]);

        $jobIds = $request->input('job_ids', []);
        $country = $request->input('country');

        try {
            $now = now();
            $fiftyYearsFromNow = $now->copy()->addYears(50);
            $updated = 0;
            $message = '';

            // ✅ STEP 1: Update selected jobs
            if (!empty($jobIds)) {
                $jobs = JobPost::whereIn('id', $jobIds)
                    ->whereNull('last_pinged_at')  // Only update unpinged jobs
                    ->get();
                
                foreach ($jobs as $job) {
                    $job->is_pinged = true;
                    $job->last_pinged_at = $now;
                    $job->published_at = $now;
                    $job->published_until = $fiftyYearsFromNow;
                    $job->save();
                    $updated++;
                }
                $message = "Successfully pinged {$updated} job(s)";
            } else {
                $message = "No specific jobs selected";
            }

            // ✅ STEP 2 & 3: If country is specified, regenerate sitemap and ping search engines
            $pingResults = [];
            if ($country) {
                $pingResults = $this->sitemapService->pingSearchEngines($country);
                
                // Check if ping was skipped or failed
                if (isset($pingResults['status']) && $pingResults['status'] === 'skipped') {
                    return response()->json([
                        'success' => true,
                        'message' => $message . ". Search engine ping skipped in " . config('app.env') . " environment. Sitemap regenerated.",
                        'updated' => $updated,
                        'ping_status' => 'skipped',
                        'environment' => config('app.env'),
                        'ping_results' => $pingResults,
                    ]);
                }

                if (isset($pingResults['status']) && $pingResults['status'] === 'error') {
                    return response()->json([
                        'success' => true,
                        'message' => $message . ". " . $pingResults['message'],
                        'updated' => $updated,
                        'ping_status' => 'error',
                        'ping_results' => $pingResults,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message . ($country ? " for country {$country}" : ""),
                'updated' => $updated,
                'ping_results' => $pingResults,
                'ping_status' => isset($pingResults['status']) ? $pingResults['status'] : 'success',
            ]);
        } catch (\Exception $e) {
            \Log::error('Ping jobs failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to ping jobs: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    /**
     * Submit selected jobs to Google Search Console Indexing API.
     * Only flips is_indexed/last_indexed_at for jobs Google actually confirmed.
     */
    public function markIndexed(Request $request)
    {
        $request->validate([
            'job_ids' => 'required|array',
            'job_ids.*' => 'integer|exists:job_posts,id',
        ]);

        try {
            $result = $this->googleIndexingService->submitBatch($request->input('job_ids'));

            return response()->json([
                'success' => $result['submitted'] > 0 || ($result['failed'] === 0 && ($result['skipped'] ?? 0) === 0),
                'message' => $this->buildIndexingMessage($result),
                'updated' => $result['submitted'],
                'failed'  => $result['failed'] ?? 0,
                'quota'   => [
                    'used'      => $result['quota_used'] ?? null,
                    'remaining' => $result['quota_remaining'] ?? null,
                ],
                'results' => $result['results'] ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Google indexing submission failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit jobs to Google: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildIndexingMessage(array $result): string
    {
        if (isset($result['message']) && ($result['submitted'] ?? 0) === 0 && empty($result['results'])) {
            return $result['message']; // quota exhausted / not configured
        }

        $msg = "Submitted {$result['submitted']} job(s) to Google";
        if (!empty($result['failed'])) {
            $msg .= ", {$result['failed']} failed";
        }
        if (!empty($result['skipped'])) {
            $msg .= ", {$result['skipped']} skipped (quota)";
        }
        $msg .= ". Quota: {$result['quota_used']}/200 used today.";

        return $msg;
    }

    /**
     * Generate sitemaps (admin only)
     */
    public function generate(Request $request)
    {
        $country = $request->query('country');
        $ping = $request->query('ping', false);

        if ($country) {
            $result = $this->sitemapService->generateCountrySitemap($country);
        } else {
            $result = $this->sitemapService->generateAll();
        }

        if ($ping && !$country) {
            // Ping all active countries dynamically
            $activeCountries = Country::where('is_active', true)
                ->whereNotNull('code')
                ->orderBy('sort_order')
                ->pluck('code')
                ->toArray();
                
            foreach ($activeCountries as $code) {
                $this->sitemapService->pingSearchEngines($code);
            }
        } elseif ($ping && $country) {
            $this->sitemapService->pingSearchEngines($country);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get sitemap statistics
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => $this->sitemapService->getStats(),
        ]);
    }

    /**
     * Serve sitemap index for a specific country
     */
    public function index(Request $request, ?string $country = null)
    {
        if (!$country) {
            return response()->json(['error' => 'Country parameter required'], 400);
        }

        $path = public_path("sitemaps/{$country}/sitemap_index.xml");
        if (!file_exists($path)) {
            abort(404);
        }

        return Response::file($path, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Serve specific sitemap file for a country
     */
    public function show(Request $request, string $country, string $filename)
    {
        if (!str_ends_with($filename, '.xml')) {
            abort(404);
        }

        $path = public_path("sitemaps/{$country}/{$filename}");
        if (!file_exists($path)) {
            abort(404);
        }

        return Response::file($path, [
            'Content-Type' => 'application/xml',
        ]);
    }
}