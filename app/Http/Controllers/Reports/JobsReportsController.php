<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Models\Job\JobCategory;
use App\Models\Job\Industry;
use App\Models\Job\JobLocation;
use App\Models\Job\JobType;
use App\Models\Job\ExperienceLevel;
use App\Models\Job\EducationLevel;
use App\Models\Job\SalaryRange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class JobsReportsController extends Controller
{
    /**
     * Country list for filters
     */
    protected function getCountries()
    {
        return [
            'AU' => 'Australia',
            'UG' => 'Uganda',
            'KE' => 'Kenya',
            'TZ' => 'Tanzania',
            'RW' => 'Rwanda',
            'MW' => 'Malawi',
            'ZM' => 'Zambia',
            'SG' => 'Singapore',
        ];
    }

    /**
     * Display jobs reports dashboard.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get per page from request or default to 10
        $perPage = $request->get('per_page', 10);
        $countryCode = $request->get('country_code');
        
        // Get date range - last 12 months
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Build query with country filter
        $query = JobPost::query();
        
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }
        
        // Summary statistics
        $summary = $this->getSummaryStatistics($startDate, $endDate, $countryCode);
        
        // Monthly trends - ensure data is returned as array
        $monthlyTrends = $this->getMonthlyTrends($startDate, $endDate, $countryCode);
        
        // ✅ Convert monthly trends to a simple array for chart
        $chartData = [
            'labels' => array_values(array_map(function($item) {
                return $item['month_label'];
            }, $monthlyTrends)),
            'counts' => array_values(array_map(function($item) {
                return $item['count'];
            }, $monthlyTrends)),
            'views' => array_values(array_map(function($item) {
                return $item['views'];
            }, $monthlyTrends)),
            'applications' => array_values(array_map(function($item) {
                return $item['applications'];
            }, $monthlyTrends)),
        ];
        
        // Top categories with pagination
        $topCategories = $this->getTopCategoriesPaginated($startDate, $endDate, $perPage, $countryCode);
        
        // Top companies with pagination
        $topCompanies = $this->getTopCompaniesPaginated($startDate, $endDate, $perPage, $countryCode);
        
        // Status breakdown
        $statusBreakdown = $this->getStatusBreakdown($startDate, $endDate, $countryCode);
        
        $countries = $this->getCountries();
        
        return view('reports.jobs.index', compact(
            'summary',
            'monthlyTrends',
            'chartData',
            'topCategories',
            'topCompanies',
            'statusBreakdown',
            'startDate',
            'endDate',
            'perPage',
            'countries',
            'countryCode'
        ));
    }

    /**
     * Get top categories with pagination
     */
    private function getTopCategoriesPaginated($startDate, $endDate, $perPage = 10, $countryCode = null)
    {
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'job_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(job_posts.view_count) as views')
            )
            ->groupBy('job_categories.name')
            ->orderBy('count', 'desc')
            ->paginate($perPage, ['*'], 'category_page');
    }

    /**
     * Get top companies with pagination
     */
    private function getTopCompaniesPaginated($startDate, $endDate, $perPage = 10, $countryCode = null)
    {
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'companies.name as company_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(job_posts.view_count) as views')
            )
            ->groupBy('companies.name')
            ->orderBy('count', 'desc')
            ->paginate($perPage, ['*'], 'company_page');
    }

    /**
     * Job Summary Report
     */
    public function summary(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $locationId = $request->get('location_id');
        $jobTypeId = $request->get('job_type_id');
        $experienceLevelId = $request->get('experience_level_id');
        $educationLevelId = $request->get('education_level_id');
        $jobSource = $request->get('job_source');
        $status = $request->get('status');
        $posterId = $request->get('poster_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 10);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // Build query
        $query = JobPost::with(['company', 'jobCategory', 'jobLocation', 'jobType', 'experienceLevel', 'educationLevel', 'salaryRange', 'poster']);
        
        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('job_posts.created_at', [$startDateTime, $endDateTime]);
        }
        
        if ($categoryId) {
            $query->where('job_category_id', $categoryId);
        }
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        if ($locationId) {
            $query->where('job_location_id', $locationId);
        }
        
        if ($jobTypeId) {
            $query->where('job_type_id', $jobTypeId);
        }
        
        if ($experienceLevelId) {
            $query->where('experience_level_id', $experienceLevelId);
        }
        
        if ($educationLevelId) {
            $query->where('education_level_id', $educationLevelId);
        }
        
        if ($jobSource) {
            $query->where('job_source', $jobSource);
        }
        
        if ($status) {
            if ($status === 'active') {
                $query->where('is_active', true)->where('deadline', '>=', now());
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'expired') {
                $query->where('deadline', '<', now());
            } elseif ($status === 'featured') {
                $query->where('is_featured', true);
            } elseif ($status === 'urgent') {
                $query->where('is_urgent', true);
            } elseif ($status === 'pinged') {
                $query->where('is_pinged', true);
            } elseif ($status === 'unpinged') {
                $query->where('is_pinged', false);
            } elseif ($status === 'indexed') {
                $query->where('is_indexed', true);
            } elseif ($status === 'unindexed') {
                $query->where('is_indexed', false);
            }
        }
        
        if ($posterId) {
            $query->where('poster_id', $posterId);
        }
        
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }
        
        // Get all jobs for summary (without pagination) - for daily trend data and summary stats
        $allJobs = clone $query;
        $allJobs = $allJobs->get();
        
        // ================================================================
        // DAILY TREND DATA FOR LINE CHART
        // ================================================================
        $dailyTrendData = $allJobs->groupBy(function($job) {
            return $job->created_at->format('Y-m-d');
        })->map(function($items, $date) {
            return [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('M d, Y'),
                'count' => $items->count(),
                'views' => $items->sum('view_count'),
                'applications' => $items->sum('application_count'),
                'clicks' => $items->sum('click_count'),
            ];
        })->values()->sortBy('date');
        
        // Prepare data for chart
        $chartLabels = $dailyTrendData->pluck('date_formatted')->toArray();
        $chartCounts = $dailyTrendData->pluck('count')->toArray();
        $chartViews = $dailyTrendData->pluck('views')->toArray();
        $chartApplications = $dailyTrendData->pluck('applications')->toArray();
        
        // Summary statistics
        $summary = [
            'total_jobs' => $allJobs->count(),
            'total_views' => $allJobs->sum('view_count'),
            'total_applications' => $allJobs->sum('application_count'),
            'total_clicks' => $allJobs->sum('click_count'),
            'average_views' => $allJobs->count() > 0 ? $allJobs->avg('view_count') : 0,
            'average_applications' => $allJobs->count() > 0 ? $allJobs->avg('application_count') : 0,
            'max_views' => $allJobs->max('view_count') ?? 0,
            'max_applications' => $allJobs->max('application_count') ?? 0,
            'active_jobs' => $allJobs->where('is_active', true)->where('deadline', '>=', now())->count(),
            'featured_jobs' => $allJobs->where('is_featured', true)->count(),
            'urgent_jobs' => $allJobs->where('is_urgent', true)->count(),
            'pinged_jobs' => $allJobs->where('is_pinged', true)->count(),
            'indexed_jobs' => $allJobs->where('is_indexed', true)->count(),
            'expired_jobs' => $allJobs->where('deadline', '<', now())->count(),
            'peak_daily_posts' => $dailyTrendData->max('count') ?? 0,
            'avg_daily_posts' => $dailyTrendData->count() > 0 ? $dailyTrendData->avg('count') : 0,
        ];
        
        // ================================================================
        // DAILY BREAKDOWN WITH PAGINATION
        // ================================================================
        $dailyBreakdown = $this->paginateCollection($dailyTrendData, $perPage, 'daily_page');
        
        // ================================================================
        // CATEGORY BREAKDOWN WITH PAGINATION
        // ================================================================
        $categoryData = [];
        foreach ($allJobs as $job) {
            $catId = $job->job_category_id;
            $categoryName = $job->jobCategory?->name ?? 'Uncategorized';
            
            if (!isset($categoryData[$catId])) {
                $categoryData[$catId] = [
                    'category_id' => $catId,
                    'category_name' => $categoryName,
                    'count' => 0,
                    'views' => 0,
                    'applications' => 0,
                ];
            }
            
            $categoryData[$catId]['count']++;
            $categoryData[$catId]['views'] += $job->view_count;
            $categoryData[$catId]['applications'] += $job->application_count;
        }
        
        $categoryBreakdown = collect($categoryData)->sortByDesc('count')->values();
        $categoryBreakdown = $this->paginateCollection($categoryBreakdown, $perPage, 'category_page');
        
        // ================================================================
        // COMPANY BREAKDOWN WITH PAGINATION
        // ================================================================
        $companyData = [];
        foreach ($allJobs as $job) {
            $compId = $job->company_id;
            $companyName = $job->company?->name ?? 'N/A';
            
            if (!isset($companyData[$compId])) {
                $companyData[$compId] = [
                    'company_id' => $compId,
                    'company_name' => $companyName,
                    'count' => 0,
                    'views' => 0,
                    'applications' => 0,
                ];
            }
            
            $companyData[$compId]['count']++;
            $companyData[$compId]['views'] += $job->view_count;
            $companyData[$compId]['applications'] += $job->application_count;
        }
        
        $companyBreakdown = collect($companyData)->sortByDesc('count')->values();
        $companyBreakdown = $this->paginateCollection($companyBreakdown, $perPage, 'company_page');
        
        // ================================================================
        // SOURCE BREAKDOWN WITH PAGINATION
        // ================================================================
        $sourceData = [];
        foreach ($allJobs as $job) {
            $source = $job->job_source ?? 'Not specified';
            
            if (!isset($sourceData[$source])) {
                $sourceData[$source] = [
                    'source' => $source,
                    'count' => 0,
                    'views' => 0,
                    'applications' => 0,
                ];
            }
            
            $sourceData[$source]['count']++;
            $sourceData[$source]['views'] += $job->view_count;
            $sourceData[$source]['applications'] += $job->application_count;
        }
        
        $sourceBreakdown = collect($sourceData)->sortByDesc('count')->values();
        $sourceBreakdown = $this->paginateCollection($sourceBreakdown, $perPage, 'source_page');
        
        // ================================================================
        // LOCATION BREAKDOWN WITH PAGINATION
        // ================================================================
        $locationData = [];
        foreach ($allJobs as $job) {
            $locId = $job->job_location_id;
            $locationName = $job->jobLocation?->district ?? 'N/A';
            
            if (!isset($locationData[$locId])) {
                $locationData[$locId] = [
                    'location_id' => $locId,
                    'location_name' => $locationName,
                    'count' => 0,
                    'views' => 0,
                    'applications' => 0,
                ];
            }
            
            $locationData[$locId]['count']++;
            $locationData[$locId]['views'] += $job->view_count;
            $locationData[$locId]['applications'] += $job->application_count;
        }
        
        $locationBreakdown = collect($locationData)->sortByDesc('count')->values();
        $locationBreakdown = $this->paginateCollection($locationBreakdown, $perPage, 'location_page');
        
        // ================================================================
        // STATUS BREAKDOWN (no pagination needed as it's always small)
        // ================================================================
        $statusData = [
            'active' => ['status' => 'active', 'label' => 'Active', 'count' => 0, 'views' => 0],
            'inactive' => ['status' => 'inactive', 'label' => 'Inactive', 'count' => 0, 'views' => 0],
            'expired' => ['status' => 'expired', 'label' => 'Expired', 'count' => 0, 'views' => 0],
            'featured' => ['status' => 'featured', 'label' => 'Featured', 'count' => 0, 'views' => 0],
            'urgent' => ['status' => 'urgent', 'label' => 'Urgent', 'count' => 0, 'views' => 0],
            'pinged' => ['status' => 'pinged', 'label' => 'Pinged', 'count' => 0, 'views' => 0],
            'indexed' => ['status' => 'indexed', 'label' => 'Indexed', 'count' => 0, 'views' => 0],
        ];
        
        foreach ($allJobs as $job) {
            if ($job->is_active && $job->deadline >= now()) {
                $statusData['active']['count']++;
                $statusData['active']['views'] += $job->view_count;
            } elseif (!$job->is_active) {
                $statusData['inactive']['count']++;
                $statusData['inactive']['views'] += $job->view_count;
            } elseif ($job->deadline < now()) {
                $statusData['expired']['count']++;
                $statusData['expired']['views'] += $job->view_count;
            }
            
            if ($job->is_featured) {
                $statusData['featured']['count']++;
                $statusData['featured']['views'] += $job->view_count;
            }
            
            if ($job->is_urgent) {
                $statusData['urgent']['count']++;
                $statusData['urgent']['views'] += $job->view_count;
            }
            
            if ($job->is_pinged) {
                $statusData['pinged']['count']++;
                $statusData['pinged']['views'] += $job->view_count;
            }
            
            if ($job->is_indexed) {
                $statusData['indexed']['count']++;
                $statusData['indexed']['views'] += $job->view_count;
            }
        }
        
        $statusBreakdown = collect($statusData)->values();
        
        // Get filter options
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $locations = JobLocation::where('is_active', true)->orderBy('district')->get();
        $jobTypes = JobType::where('is_active', true)->orderBy('name')->get();
        $experienceLevels = ExperienceLevel::where('is_active', true)->orderBy('name')->get();
        $educationLevels = EducationLevel::where('is_active', true)->orderBy('name')->get();
        $posters = User::orderBy('name')->get();
        $jobSources = ['competitor_website', 'whatsapp', 'newspaper', 'employer_website', 'linkedin', 'facebook', 'other'];
        $statuses = ['active', 'inactive', 'expired', 'featured', 'urgent', 'pinged', 'indexed', 'unpinged', 'unindexed'];
        $countries = $this->getCountries();
        
        return view('reports.jobs.summary', compact(
            'summary',
            'dailyBreakdown',
            'categoryBreakdown',
            'companyBreakdown',
            'sourceBreakdown',
            'locationBreakdown',
            'statusBreakdown',
            'categories',
            'companies',
            'locations',
            'jobTypes',
            'experienceLevels',
            'educationLevels',
            'posters',
            'jobSources',
            'statuses',
            'countries',
            'startDate',
            'endDate',
            'categoryId',
            'companyId',
            'locationId',
            'jobTypeId',
            'experienceLevelId',
            'educationLevelId',
            'jobSource',
            'status',
            'posterId',
            'countryCode',
            'perPage',
            'chartLabels',
            'chartCounts',
            'chartViews',
            'chartApplications'
        ));
    }

    /**
     * Paginate a collection manually
     */
    private function paginateCollection($collection, $perPage, $pageName = 'page')
    {
        $page = request()->get($pageName, 1);
        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => $pageName, 'query' => request()->query()]
        );
    }
    
    /**
     * Jobs by Category Report
     */
    public function byCategory(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Fix: Add end of day to end date
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // Get category breakdown with pagination
        $categoryBreakdownQuery = JobPost::whereBetween('job_posts.created_at', [
                Carbon::parse($startDate)->startOfDay(), 
                $endDateTime
            ])
            ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
            ->select(
                'job_categories.id',
                'job_categories.name as category_name',
                'job_categories.slug as category_slug',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(job_posts.view_count) as total_views'),
                DB::raw('SUM(job_posts.application_count) as total_applications'),
                DB::raw('AVG(job_posts.view_count) as avg_views'),
                DB::raw('AVG(job_posts.application_count) as avg_applications'),
                DB::raw('MAX(job_posts.view_count) as max_views'),
                DB::raw('MAX(job_posts.application_count) as max_applications')
            )
            ->groupBy('job_categories.id', 'job_categories.name', 'job_categories.slug')
            ->orderBy('job_count', 'desc');
        
        if ($categoryId) {
            $categoryBreakdownQuery->having('job_categories.id', $categoryId);
        }
        
        if ($countryCode) {
            $categoryBreakdownQuery->where('job_posts.country_code', $countryCode);
        }
        
        $categoryBreakdown = $categoryBreakdownQuery->paginate($perPage);
        
        // Monthly trend by category
        $monthlyTrend = JobPost::whereBetween('job_posts.created_at', [
                Carbon::parse($startDate)->startOfDay(),
                $endDateTime
            ])
            ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                DB::raw('YEAR(job_posts.created_at) as year'),
                DB::raw('MONTH(job_posts.created_at) as month'),
                'job_categories.name as category_name',
                DB::raw('COUNT(*) as monthly_count')
            )
            ->groupBy('year', 'month', 'job_categories.name')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('category_name');
        
        // Categories list for filter
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-category', compact(
            'categoryBreakdown',
            'monthlyTrend',
            'categories',
            'countries',
            'startDate',
            'endDate',
            'categoryId',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Jobs by Company Report
     */
    public function byCompany(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $companyId = $request->get('company_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select(
                'companies.id',
                'companies.name as company_name',
                'companies.slug as company_slug',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(job_posts.view_count) as total_views'),
                DB::raw('SUM(job_posts.application_count) as total_applications'),
                DB::raw('AVG(job_posts.view_count) as avg_views'),
                DB::raw('AVG(job_posts.application_count) as avg_applications'),
                DB::raw('MAX(job_posts.view_count) as max_views'),
                DB::raw('MAX(job_posts.application_count) as max_applications')
            )
            ->groupBy('companies.id', 'companies.name', 'companies.slug')
            ->orderBy('job_count', 'desc');
        
        if ($companyId) {
            $query->having('companies.id', $companyId);
        }
        
        if ($countryCode) {
            $query->where('job_posts.country_code', $countryCode);
        }
        
        $companyBreakdown = $query->paginate($perPage);
        
        // Companies for filter
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-company', compact(
            'companyBreakdown',
            'companies',
            'countries',
            'startDate',
            'endDate',
            'companyId',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Jobs by Location Report
     */
    public function byLocation(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $locationId = $request->get('location_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->join('job_locations', 'job_posts.job_location_id', '=', 'job_locations.id')
            ->select(
                'job_locations.id',
                'job_locations.district as location_name',
                'job_locations.city',
                'job_locations.country',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(job_posts.view_count) as total_views'),
                DB::raw('SUM(job_posts.application_count) as total_applications')
            )
            ->groupBy('job_locations.id', 'job_locations.district', 'job_locations.city', 'job_locations.country')
            ->orderBy('job_count', 'desc');
        
        if ($locationId) {
            $query->having('job_locations.id', $locationId);
        }
        
        if ($countryCode) {
            $query->where('job_posts.country_code', $countryCode);
        }
        
        $locationBreakdown = $query->paginate($perPage);
        
        $locations = JobLocation::where('is_active', true)->orderBy('district')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-location', compact(
            'locationBreakdown',
            'locations',
            'countries',
            'startDate',
            'endDate',
            'locationId',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Jobs by Source Report
     */
    public function bySource(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $jobSource = $request->get('job_source');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->whereNotNull('job_source')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->select(
                'job_source',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(view_count) as total_views'),
                DB::raw('SUM(application_count) as total_applications'),
                DB::raw('AVG(view_count) as avg_views'),
                DB::raw('AVG(application_count) as avg_applications')
            )
            ->groupBy('job_source')
            ->orderBy('job_count', 'desc');
        
        if ($jobSource) {
            $query->where('job_source', $jobSource);
        }
        
        $sourceBreakdown = $query->paginate($perPage);
        
        $jobSources = ['competitor_website', 'whatsapp', 'newspaper', 'employer_website', 'linkedin', 'facebook', 'other'];
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-source', compact(
            'sourceBreakdown',
            'jobSources',
            'countries',
            'startDate',
            'endDate',
            'jobSource',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Job Performance Report
     */
    public function performance(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::with(['company', 'jobCategory'])
            ->whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            });
        
        if ($categoryId) {
            $query->where('job_category_id', $categoryId);
        }
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $jobs = $query->orderBy('view_count', 'desc')->paginate($perPage);
        
        // Performance summary
        $allJobs = clone $query;
        $allJobs = $allJobs->get();
        
        $summary = [
            'total_jobs' => $allJobs->count(),
            'total_views' => $allJobs->sum('view_count'),
            'total_applications' => $allJobs->sum('application_count'),
            'total_clicks' => $allJobs->sum('click_count'),
            'avg_views' => $allJobs->count() > 0 ? $allJobs->avg('view_count') : 0,
            'avg_applications' => $allJobs->count() > 0 ? $allJobs->avg('application_count') : 0,
            'avg_ctr' => $allJobs->count() > 0 ? $allJobs->avg('click_through_rate') : 0,
            'avg_seo_score' => $allJobs->count() > 0 ? $allJobs->avg('seo_score') : 0,
            'avg_content_score' => $allJobs->count() > 0 ? $allJobs->avg('content_quality_score') : 0,
            'views_per_job' => $allJobs->count() > 0 ? $allJobs->sum('view_count') / $allJobs->count() : 0,
            'applications_per_job' => $allJobs->count() > 0 ? $allJobs->sum('application_count') / $allJobs->count() : 0,
        ];
        
        // Top performing jobs
        $topJobs = $allJobs->sortByDesc('view_count')->take(10);
        
        // Performance by category
        $performanceByCategory = $allJobs->groupBy('job_category_id')->map(function($items) {
            $category = $items->first()->jobCategory;
            return [
                'category_name' => $category?->name ?? 'Uncategorized',
                'count' => $items->count(),
                'total_views' => $items->sum('view_count'),
                'total_applications' => $items->sum('application_count'),
                'avg_views' => $items->avg('view_count'),
                'avg_applications' => $items->avg('application_count'),
            ];
        })->sortByDesc('total_views')->values();
        
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.performance', compact(
            'jobs',
            'summary',
            'topJobs',
            'performanceByCategory',
            'categories',
            'companies',
            'countries',
            'startDate',
            'endDate',
            'categoryId',
            'companyId',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * SEO Performance Report
     */
    public function seo(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $minSeoScore = $request->get('min_seo_score');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // ✅ Build query WITHOUT eager loading for summary
        $baseQuery = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            });
        
        // Apply filters to base query
        if ($categoryId) {
            $baseQuery->where('job_category_id', $categoryId);
        }
        
        if ($minSeoScore) {
            $baseQuery->where('seo_score', '>=', $minSeoScore);
        }
        
        // ✅ Get paginated jobs with relationships (for the table)
        $jobs = JobPost::with(['company', 'jobCategory'])
            ->whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where('job_category_id', $categoryId);
            })
            ->when($minSeoScore, function($q) use ($minSeoScore) {
                $q->where('seo_score', '>=', $minSeoScore);
            })
            ->orderBy('seo_score', 'desc')
            ->paginate($perPage);
        
        // ✅ Get all jobs for summary WITHOUT eager loading
        $allJobs = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where('job_category_id', $categoryId);
            })
            ->when($minSeoScore, function($q) use ($minSeoScore) {
                $q->where('seo_score', '>=', $minSeoScore);
            })
            ->get();
        
        // SEO Summary
        $summary = [
            'total_jobs' => $allJobs->count(),
            'avg_seo_score' => $allJobs->count() > 0 ? $allJobs->avg('seo_score') : 0,
            'avg_content_score' => $allJobs->count() > 0 ? $allJobs->avg('content_quality_score') : 0,
            'avg_ctr' => $allJobs->count() > 0 ? $allJobs->avg('click_through_rate') : 0,
            'total_search_impressions' => $allJobs->sum('search_impressions'),
            'total_search_clicks' => $allJobs->sum('search_clicks'),
            'avg_google_rank' => $allJobs->count() > 0 ? $allJobs->avg('google_rank') : 0,
            'jobs_with_meta_title' => $allJobs->whereNotNull('meta_title')->count(),
            'jobs_with_meta_description' => $allJobs->whereNotNull('meta_description')->count(),
            'jobs_with_keywords' => $allJobs->whereNotNull('keywords')->count(),
            'jobs_with_focus_keyphrase' => $allJobs->whereNotNull('focus_keyphrase')->count(),
        ];
        
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.seo', compact(
            'jobs',
            'summary',
            'categories',
            'countries',
            'startDate',
            'endDate',
            'categoryId',
            'minSeoScore',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Jobs by Timeline Report
     * Shows jobs posted over time with flexible date ranges
     */
    public function byTimeline(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $period = $request->get('period', 'weekly'); // weekly, monthly, quarterly, daily
        $countryCode = $request->get('country_code');
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $posterId = $request->get('poster_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        
        // Default date ranges
        if (!$startDate) {
            $startDate = Carbon::now()->subMonths(6)->startOfDay()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d');
        }
        
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // Build base query
        $baseQuery = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->when($categoryId, function($q) use ($categoryId) {
                $q->where('job_category_id', $categoryId);
            })
            ->when($companyId, function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when($posterId, function($q) use ($posterId) {
                $q->where('poster_id', $posterId);
            });
        
        // Get timeline data based on period
        $labels = [];
        $countData = [];
        
        switch ($period) {
            case 'daily':
                // Last 30 days
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $dateStr = $date->format('Y-m-d');
                    $query = clone $baseQuery;
                    $query->whereDate('job_posts.created_at', $dateStr);
                    
                    $labels[] = $date->format('M d');
                    $countData[] = $query->count();
                }
                break;
                
            case 'weekly':
                // Last 6 months (26 weeks)
                for ($i = 25; $i >= 0; $i--) {
                    $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                    $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
                    
                    $query = clone $baseQuery;
                    $query->whereBetween('job_posts.created_at', [$weekStart, $weekEnd]);
                    
                    $labels[] = 'W' . $weekStart->format('W') . ' ' . $weekStart->format('M d');
                    $countData[] = $query->count();
                }
                break;
                
            case 'monthly':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                    
                    $query = clone $baseQuery;
                    $query->whereBetween('job_posts.created_at', [$monthStart, $monthEnd]);
                    
                    $labels[] = $monthStart->format('M Y');
                    $countData[] = $query->count();
                }
                break;
                
            case 'quarterly':
                // Last 8 quarters (2 years)
                for ($i = 7; $i >= 0; $i--) {
                    $quarterStart = Carbon::now()->subQuarters($i)->startOfQuarter();
                    $quarterEnd = Carbon::now()->subQuarters($i)->endOfQuarter();
                    
                    $query = clone $baseQuery;
                    $query->whereBetween('job_posts.created_at', [$quarterStart, $quarterEnd]);
                    
                    $labels[] = 'Q' . $quarterStart->quarter . ' ' . $quarterStart->format('Y');
                    $countData[] = $query->count();
                }
                break;
                
            case 'yearly':
                // Last 5 years
                for ($i = 4; $i >= 0; $i--) {
                    $yearStart = Carbon::now()->subYears($i)->startOfYear();
                    $yearEnd = Carbon::now()->subYears($i)->endOfYear();
                    
                    $query = clone $baseQuery;
                    $query->whereBetween('job_posts.created_at', [$yearStart, $yearEnd]);
                    
                    $labels[] = $yearStart->format('Y');
                    $countData[] = $query->count();
                }
                break;
        }
        
        // Summary statistics
        $allJobs = clone $baseQuery;
        $allJobs = $allJobs->get();
        
        $summary = [
            'total_jobs' => $allJobs->count(),
            'total_views' => $allJobs->sum('view_count'),
            'total_applications' => $allJobs->sum('application_count'),
            'total_clicks' => $allJobs->sum('click_count'),
            'avg_views_per_job' => $allJobs->count() > 0 ? $allJobs->sum('view_count') / $allJobs->count() : 0,
            'avg_applications_per_job' => $allJobs->count() > 0 ? $allJobs->sum('application_count') / $allJobs->count() : 0,
            'max_views' => $allJobs->max('view_count') ?? 0,
            'max_applications' => $allJobs->max('application_count') ?? 0,
            'active_jobs' => $allJobs->where('is_active', true)->where('deadline', '>=', now())->count(),
            'featured_jobs' => $allJobs->where('is_featured', true)->count(),
            'urgent_jobs' => $allJobs->where('is_urgent', true)->count(),
            'pinged_jobs' => $allJobs->where('is_pinged', true)->count(),
            'indexed_jobs' => $allJobs->where('is_indexed', true)->count(),
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'peak_count' => !empty($countData) ? max($countData) : 0,
            'avg_count' => !empty($countData) ? array_sum($countData) / count($countData) : 0,
        ];
        
        // Calculate growth/trend
        $trend = [];
        if (count($countData) > 1) {
            foreach ($countData as $index => $value) {
                if ($index > 0) {
                    $previous = $countData[$index - 1];
                    if ($previous > 0) {
                        $growth = (($value - $previous) / $previous) * 100;
                    } else {
                        $growth = $value > 0 ? 100 : 0;
                    }
                    $trend[] = round($growth, 1);
                } else {
                    $trend[] = 0;
                }
            }
        }
        
        // Get filter options
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $posters = User::whereHas('jobPosts')->orderBy('name')->get();
        $countries = $this->getCountries();
        $periods = [
            'daily' => 'Daily (30 days)',
            'weekly' => 'Weekly (6 months)',
            'monthly' => 'Monthly (12 months)',
            'quarterly' => 'Quarterly (8 quarters)',
            'yearly' => 'Yearly (5 years)',
        ];
        
        return view('reports.jobs.timeline', compact(
            'labels',
            'countData',
            'trend',
            'summary',
            'categories',
            'companies',
            'posters',
            'countries',
            'periods',
            'period',
            'categoryId',
            'companyId',
            'posterId',
            'countryCode',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Job Trends Report
     */
    public function trends(Request $request)
    {
        $user = auth()->user();
        
        $period = $request->get('period', 'monthly');
        $year = $request->get('year', date('Y'));
        $categoryId = $request->get('category_id');
        $companyId = $request->get('company_id');
        $countryCode = $request->get('country_code');
        
        $trendData = [];
        
        if ($period === 'monthly') {
            // Monthly trend for the year
            for ($month = 1; $month <= 12; $month++) {
                $query = JobPost::whereYear('job_posts.created_at', $year)
                    ->whereMonth('job_posts.created_at', $month)
                    ->when($countryCode, function($q) use ($countryCode) {
                        $q->where('country_code', $countryCode);
                    });
                
                if ($categoryId) {
                    $query->where('job_category_id', $categoryId);
                }
                
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                
                $count = $query->count();
                $views = $query->sum('view_count');
                $applications = $query->sum('application_count');
                
                $trendData[$month] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('M'),
                    'count' => $count,
                    'views' => $views,
                    'applications' => $applications,
                ];
            }
        } elseif ($period === 'quarterly') {
            // Quarterly trend
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                
                $query = JobPost::whereYear('job_posts.created_at', $year)
                    ->whereMonth('job_posts.created_at', '>=', $startMonth)
                    ->whereMonth('job_posts.created_at', '<=', $endMonth)
                    ->when($countryCode, function($q) use ($countryCode) {
                        $q->where('country_code', $countryCode);
                    });
                
                if ($categoryId) {
                    $query->where('job_category_id', $categoryId);
                }
                
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                
                $count = $query->count();
                $views = $query->sum('view_count');
                $applications = $query->sum('application_count');
                
                $trendData[$quarter] = [
                    'quarter' => $quarter,
                    'quarter_label' => "Q{$quarter}",
                    'count' => $count,
                    'views' => $views,
                    'applications' => $applications,
                ];
            }
        } else {
            // Yearly trend (last 5 years)
            $years = range(date('Y') - 4, date('Y'));
            
            foreach ($years as $yr) {
                $query = JobPost::whereYear('job_posts.created_at', $yr)
                    ->when($countryCode, function($q) use ($countryCode) {
                        $q->where('country_code', $countryCode);
                    });
                
                if ($categoryId) {
                    $query->where('job_category_id', $categoryId);
                }
                
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                
                $count = $query->count();
                $views = $query->sum('view_count');
                $applications = $query->sum('application_count');
                
                $trendData[$yr] = [
                    'year' => $yr,
                    'count' => $count,
                    'views' => $views,
                    'applications' => $applications,
                ];
            }
        }
        
        $categories = JobCategory::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $years = range(date('Y') - 5, date('Y'));
        $countries = $this->getCountries();
        
        return view('reports.jobs.trends', compact(
            'trendData',
            'period',
            'year',
            'categoryId',
            'companyId',
            'countryCode',
            'categories',
            'companies',
            'years',
            'countries'
        ));
    }

    /**
     * Jobs by Poster Report
     */
    public function byPoster(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $posterId = $request->get('poster_id');
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->join('users', 'job_posts.poster_id', '=', 'users.id')
            ->leftJoin('employees', 'users.id', '=', 'employees.user_id')
            ->select(
                'users.id',
                'users.name as poster_name',
                'users.email as poster_email',
                'employees.job_title as poster_job_title',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(job_posts.view_count) as total_views'),
                DB::raw('SUM(job_posts.application_count) as total_applications'),
                DB::raw('AVG(job_posts.view_count) as avg_views'),
                DB::raw('AVG(job_posts.application_count) as avg_applications'),
                DB::raw('MAX(job_posts.view_count) as max_views'),
                DB::raw('MAX(job_posts.application_count) as max_applications')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'employees.job_title')
            ->orderBy('job_count', 'desc');
        
        if ($posterId) {
            $query->having('users.id', $posterId);
        }
        
        if ($countryCode) {
            $query->where('job_posts.country_code', $countryCode);
        }
        
        $posterBreakdown = $query->paginate($perPage);
        
        // Posters for filter
        $posters = User::whereHas('jobPosts')->orderBy('name')->get();
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-poster', compact(
            'posterBreakdown',
            'posters',
            'countries',
            'startDate',
            'endDate',
            'posterId',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Get poster activity by time of day - Multi-poster line graph
     */
    public function getPosterActivity(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $posterId = $request->get('poster_id');
        $countryCode = $request->get('country_code');
        
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        // Get all posters with their jobs
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->join('users', 'job_posts.poster_id', '=', 'users.id')
            ->select(
                'job_posts.id',
                'job_posts.created_at',
                'users.id as poster_id',
                'users.name as poster_name',
                DB::raw('HOUR(job_posts.created_at) as hour')
            )
            ->orderBy('job_posts.created_at', 'asc');
        
        if ($posterId) {
            $query->where('poster_id', $posterId);
        }
        
        if ($countryCode) {
            $query->where('job_posts.country_code', $countryCode);
        }
        
        $jobs = $query->get();
        
        // Get all unique posters
        $posters = $jobs->groupBy('poster_id')->map(function($items, $key) {
            return [
                'id' => $key,
                'name' => $items->first()->poster_name,
            ];
        })->values();
        
        // Get date range for labels (hourly)
        $labels = [];
        $currentHour = $startDateTime->copy()->startOfHour();
        $endHour = $endDateTime->copy()->startOfHour();
        
        while ($currentHour <= $endHour) {
            $labels[] = $currentHour->format('M d H:00');
            $currentHour->addHour();
        }
        
        // Build dataset for each poster
        $posterDatasets = [];
        $hourlyDistribution = array_fill(0, 24, 0);
        
        foreach ($posters as $poster) {
            $posterJobs = $jobs->where('poster_id', $poster['id']);
            $data = [];
            
            // Fill data for each hour
            $currentHour = $startDateTime->copy()->startOfHour();
            while ($currentHour <= $endHour) {
                $hourCount = $posterJobs->filter(function($job) use ($currentHour) {
                    return $job->created_at->format('Y-m-d H:00') == $currentHour->format('Y-m-d H:00');
                })->count();
                
                $data[] = $hourCount;
                $currentHour->addHour();
            }
            
            $posterDatasets[] = [
                'name' => $poster['name'],
                'data' => $data,
            ];
        }
        
        // Calculate hourly distribution (aggregated)
        foreach ($jobs as $job) {
            $hour = (int)$job->hour;
            $hourlyDistribution[$hour]++;
        }
        
        $hourlyDistributionData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourlyDistributionData[] = [
                'hour' => $hour,
                'label' => date('g A', mktime($hour, 0, 0)),
                'count' => $hourlyDistribution[$hour],
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'poster_datasets' => $posterDatasets,
                'poster_count' => $posters->count(),
                'total_posts' => $jobs->count(),
                'hourly_distribution' => $hourlyDistributionData,
            ]
        ]);
    }

    /**
     * Jobs by Country Report
     */
    public function byCountry(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        $perPage = $request->get('per_page', 20);
        
        // ✅ Convert dates to proper datetime range
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();
        
        $query = JobPost::whereBetween('job_posts.created_at', [$startDateTime, $endDateTime])
            ->select(
                'country_code',
                DB::raw('COUNT(*) as job_count'),
                DB::raw('SUM(view_count) as total_views'),
                DB::raw('SUM(application_count) as total_applications'),
                DB::raw('AVG(view_count) as avg_views'),
                DB::raw('AVG(application_count) as avg_applications'),
                DB::raw('MAX(view_count) as max_views'),
                DB::raw('MAX(application_count) as max_applications')
            )
            ->groupBy('country_code')
            ->orderBy('job_count', 'desc');
        
        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }
        
        $countryBreakdown = $query->paginate($perPage);
        
        // Country list for filter
        $countries = $this->getCountries();
        
        return view('reports.jobs.by-country', compact(
            'countryBreakdown',
            'countries',
            'startDate',
            'endDate',
            'countryCode',
            'perPage'
        ));
    }

    /**
     * Export Report
     */
    public function export(Request $request, $type)
    {
        $user = auth()->user();
        $format = $request->get('format', 'csv');
        
        $data = [];
        $filename = "job-report-{$type}-" . date('Y-m-d');
        
        switch ($type) {
            case 'summary':
                $data = $this->getSummaryData($request);
                break;
            case 'category':
                $data = $this->getCategoryData($request);
                break;
            case 'company':
                $data = $this->getCompanyData($request);
                break;
            case 'location':
                $data = $this->getLocationData($request);
                break;
            case 'source':
                $data = $this->getSourceData($request);
                break;
            case 'performance':
                $data = $this->getPerformanceData($request);
                break;
            default:
                return redirect()->back()->with('error', 'Invalid report type');
        }
        
        if ($format === 'csv') {
            return $this->exportCSV($data, $filename);
        }
        
        return redirect()->back()->with('error', 'Unsupported export format');
    }

    /**
     * Export data as CSV
     */
    private function exportCSV($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];
        
        return response()->stream(function() use ($data) {
            $handle = fopen('php://output', 'w');
            
            if (!empty($data)) {
                fputcsv($handle, array_keys((array)$data[0]));
            }
            
            foreach ($data as $row) {
                fputcsv($handle, (array)$row);
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Get summary data for export
     */
    private function getSummaryData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        $query = JobPost::with(['company', 'jobCategory'])
            ->whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            });
        
        return $query->get()->map(function($job) {
            return [
                'Title' => $job->job_title,
                'Company' => $job->company?->name ?? 'N/A',
                'Category' => $job->jobCategory?->name ?? 'N/A',
                'Location' => $job->jobLocation?->district ?? 'N/A',
                'Views' => $job->view_count,
                'Applications' => $job->application_count,
                'Clicks' => $job->click_count,
                'Status' => $job->is_active ? 'Active' : 'Inactive',
                'Featured' => $job->is_featured ? 'Yes' : 'No',
                'Urgent' => $job->is_urgent ? 'Yes' : 'No',
                'Source' => $job->job_source ?? 'N/A',
                'Country' => $job->country_code ?? 'N/A',
                'Created' => $job->created_at->format('Y-m-d'),
                'Deadline' => $job->deadline->format('Y-m-d'),
            ];
        })->toArray();
    }

    /**
     * Get category data for export
     */
    private function getCategoryData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'job_categories.name as Category',
                DB::raw('COUNT(*) as Jobs'),
                DB::raw('SUM(job_posts.view_count) as Views'),
                DB::raw('SUM(job_posts.application_count) as Applications'),
                DB::raw('AVG(job_posts.view_count) as Avg_Views'),
                DB::raw('AVG(job_posts.application_count) as Avg_Applications')
            )
            ->groupBy('job_categories.name')
            ->orderBy('Jobs', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get company data for export
     */
    private function getCompanyData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'companies.name as Company',
                DB::raw('COUNT(*) as Jobs'),
                DB::raw('SUM(job_posts.view_count) as Views'),
                DB::raw('SUM(job_posts.application_count) as Applications')
            )
            ->groupBy('companies.name')
            ->orderBy('Jobs', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get location data for export
     */
    private function getLocationData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('job_locations', 'job_posts.job_location_id', '=', 'job_locations.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'job_locations.district as Location',
                DB::raw('COUNT(*) as Jobs'),
                DB::raw('SUM(job_posts.view_count) as Views'),
                DB::raw('SUM(job_posts.application_count) as Applications')
            )
            ->groupBy('job_locations.district')
            ->orderBy('Jobs', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get source data for export
     */
    private function getSourceData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->whereNotNull('job_source')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->select(
                'job_source as Source',
                DB::raw('COUNT(*) as Jobs'),
                DB::raw('SUM(view_count) as Views'),
                DB::raw('SUM(application_count) as Applications')
            )
            ->groupBy('job_source')
            ->orderBy('Jobs', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get performance data for export
     */
    private function getPerformanceData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $countryCode = $request->get('country_code');
        
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            })
            ->select(
                'job_title as Title',
                'view_count as Views',
                'application_count as Applications',
                'click_count as Clicks',
                'seo_score as SEO_Score',
                'content_quality_score as Content_Score',
                'click_through_rate as CTR'
            )
            ->orderBy('view_count', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStatistics($startDate, $endDate, $countryCode = null)
    {
        $query = JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            });
        
        $total = $query->count();
        $views = $query->sum('view_count');
        $applications = $query->sum('application_count');
        $clicks = $query->sum('click_count');
        
        return [
            'total' => $total,
            'views' => $views,
            'applications' => $applications,
            'clicks' => $clicks,
            'avg_views' => $total > 0 ? $views / $total : 0,
            'avg_applications' => $total > 0 ? $applications / $total : 0,
        ];
    }

    /**
     * Get monthly trends
     */
    private function getMonthlyTrends($startDate, $endDate, $countryCode = null)
    {
        $trends = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $month = $currentDate->format('Y-m');
            $monthLabel = $currentDate->format('M Y');
            
            $query = JobPost::whereYear('job_posts.created_at', $currentDate->year)
                ->whereMonth('job_posts.created_at', $currentDate->month)
                ->when($countryCode, function($q) use ($countryCode) {
                    $q->where('country_code', $countryCode);
                });
            
            $trends[$month] = [
                'month' => $month,
                'month_label' => $monthLabel,
                'count' => $query->count(),
                'views' => $query->sum('view_count'),
                'applications' => $query->sum('application_count'),
            ];
            
            $currentDate->addMonth();
        }
        
        return $trends;
    }

    /**
     * Get top categories
     */
    private function getTopCategories($startDate, $endDate, $limit = 10, $countryCode = null)
    {
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('job_categories', 'job_posts.job_category_id', '=', 'job_categories.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'job_categories.name as category_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(job_posts.view_count) as views')
            )
            ->groupBy('job_categories.name')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies
     */
    private function getTopCompanies($startDate, $endDate, $limit = 10, $countryCode = null)
    {
        return JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('job_posts.country_code', $countryCode);
            })
            ->select(
                'companies.name as company_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(job_posts.view_count) as views')
            )
            ->groupBy('companies.name')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get status breakdown
     */
    private function getStatusBreakdown($startDate, $endDate, $countryCode = null)
    {
        $query = JobPost::whereBetween('job_posts.created_at', [$startDate, $endDate])
            ->when($countryCode, function($q) use ($countryCode) {
                $q->where('country_code', $countryCode);
            });
        $allJobs = $query->get();
        
        $result = [
            'active' => ['label' => 'Active', 'count' => 0, 'views' => 0],
            'inactive' => ['label' => 'Inactive', 'count' => 0, 'views' => 0],
            'expired' => ['label' => 'Expired', 'count' => 0, 'views' => 0],
            'featured' => ['label' => 'Featured', 'count' => 0, 'views' => 0],
            'urgent' => ['label' => 'Urgent', 'count' => 0, 'views' => 0],
        ];
        
        foreach ($allJobs as $job) {
            if ($job->is_active && $job->deadline >= now()) {
                $result['active']['count']++;
                $result['active']['views'] += $job->view_count;
            } elseif (!$job->is_active) {
                $result['inactive']['count']++;
                $result['inactive']['views'] += $job->view_count;
            } elseif ($job->deadline < now()) {
                $result['expired']['count']++;
                $result['expired']['views'] += $job->view_count;
            }
            
            if ($job->is_featured) {
                $result['featured']['count']++;
                $result['featured']['views'] += $job->view_count;
            }
            
            if ($job->is_urgent) {
                $result['urgent']['count']++;
                $result['urgent']['views'] += $job->view_count;
            }
        }
        
        return $result;
    }
}