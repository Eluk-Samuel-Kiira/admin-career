<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\Company;
use App\Models\Job\Industry;
use App\Models\Job\JobLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    /**
     * Get companies with filters and pagination - FRONTEND API
     */
    public function index(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $perPage = $request->input('per_page', 20);

            $query = Company::with([
                'industry',
                'location',
                'creator'
            ])
            ->where('country_code', $countryCode)
            ->where('is_active', true);

            // Keyword search
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhereHas('industry', function($iq) use ($search) {
                          $iq->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filter by industry (supports multiple)
            if ($request->has('industry_id')) {
                $industryIds = (array) $request->industry_id;
                $query->whereIn('industry_id', $industryIds);
            }

            // Filter by location (supports multiple)
            if ($request->has('location_id')) {
                $locationIds = (array) $request->location_id;
                $query->whereIn('location_id', $locationIds);
            }

            // Filter by verification status
            if ($request->has('is_verified') && $request->is_verified) {
                $query->where('is_verified', true);
            }

            // Filter by featured status
            if ($request->has('is_featured') && $request->is_featured) {
                $query->where('is_featured', true);
            }

            // Filter by gold status
            if ($request->has('is_gold') && $request->is_gold) {
                $query->where('is_gold', true);
            }

            // Sorting
            $sort = $request->get('sort', 'name');
            switch ($sort) {
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'jobs_count':
                    $query->withCount('jobs')
                          ->orderBy('jobs_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default: // 'name' ascending
                    $query->orderBy('name', 'asc');
            }

            $companies = $query->paginate($perPage);

            $formattedCompanies = $companies->getCollection()->map(function($company) {
                return $this->formatCompanyData($company);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedCompanies,
                'pagination' => [
                    'current_page' => $companies->currentPage(),
                    'last_page' => $companies->lastPage(),
                    'per_page' => $companies->perPage(),
                    'total' => $companies->total(),
                    'prev_page_url' => $companies->previousPageUrl(),
                    'next_page_url' => $companies->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching companies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch companies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single company by slug or ID with its jobs
     */
    public function show(Request $request, $identifier)
    {
        try {
            $company = Company::with([
                'industry',
                'location',
                'creator'
            ])
            ->where(function($q) use ($identifier) {
                $q->where('slug', $identifier)
                ->orWhere('id', $identifier);
            })
            ->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }

            // Build jobs query with filters
            $jobsQuery = $company->jobs()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $jobsQuery->where(function($q) use ($search) {
                    $q->where('job_title', 'LIKE', "%{$search}%")
                    ->orWhere('job_description', 'LIKE', "%{$search}%")
                    ->orWhere('skills', 'LIKE', "%{$search}%");
                });
            }

            // Apply category filter
            if ($request->has('category_id') && !empty($request->category_id)) {
                $jobsQuery->where('job_category_id', $request->category_id);
            }

            // Apply job type filter
            if ($request->has('job_type_id') && !empty($request->job_type_id)) {
                $jobsQuery->where('job_type_id', $request->job_type_id);
            }

            // Apply location filter
            if ($request->has('location_id') && !empty($request->location_id)) {
                $jobsQuery->where('job_location_id', $request->location_id);
            }

            // Apply salary filters
            if ($request->has('min_salary') && !empty($request->min_salary)) {
                $jobsQuery->where('salary_amount', '>=', $request->min_salary);
            }
            if ($request->has('max_salary') && !empty($request->max_salary)) {
                $jobsQuery->where('salary_amount', '<=', $request->max_salary);
            }

            // Apply sorting
            $sort = $request->get('sort', 'newest');
            switch ($sort) {
                case 'oldest':
                    $jobsQuery->orderBy('published_at', 'asc');
                    break;
                case 'salary_high':
                    $jobsQuery->orderBy('salary_amount', 'desc');
                    break;
                case 'salary_low':
                    $jobsQuery->orderBy('salary_amount', 'asc');
                    break;
                default:
                    $jobsQuery->orderBy('published_at', 'desc');
            }

            // Paginate jobs
            $perPage = $request->input('per_page', 20);
            $jobs = $jobsQuery->paginate($perPage);

            // Log the jobs count
            // \Log::info('Company show with filtered jobs', [
            //     'company_id' => $company->id,
            //     'company_name' => $company->name,
            //     'jobs_count' => $jobs->total(),
            //     'filters' => $request->all()
            // ]);

            return response()->json([
                'success' => true,
                'data' => $this->formatCompanyDataWithJobs($company, $jobs)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching company: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company'
            ], 500);
        }
    }

    /**
     * Format company data with paginated jobs
     */
    private function formatCompanyDataWithJobs($company, $jobs)
    {
        $data = $this->formatCompanyData($company, true);
        
        $data['jobs'] = $jobs->map(function($job) {
            return [
                'id' => $job->id,
                'job_title' => $job->job_title ?? '',
                'slug' => $job->slug ?? '',
                'formatted_salary' => $this->formatSalary($job),
                'location' => $job->jobLocation ? [
                    'name' => $job->jobLocation->district ?? $job->jobLocation->city ?? '',
                ] : null,
                'job_type' => $job->jobType ? [
                    'name' => $job->jobType->name ?? '',
                ] : null,
                'job_category' => $job->jobCategory ? [
                    'name' => $job->jobCategory->name ?? '',
                ] : null,
                'published_at' => $job->published_at ? $job->published_at->format('Y-m-d H:i:s') : null,
                'is_urgent' => (bool) $job->is_urgent,
                'is_featured' => (bool) $job->is_featured,
                'employment_type' => $job->employment_type ?? 'full-time',
                'duty_station' => $job->duty_station ?? '',
                'has_real_deadline' => !is_null($job->legacy_id) && $job->deadline ? true : false,
                'deadline' => $job->deadline ? $job->deadline->format('Y-m-d') : null,
                'company' => [
                    'name' => $job->company->name ?? '',
                    'logo' => $job->company->logo_url ?? null,
                ],
                'job_location' => $job->jobLocation ? [
                    'name' => $job->jobLocation->district ?? $job->jobLocation->city ?? '',
                ] : null,
            ];
        })->toArray();
        
        $data['pagination'] = [
            'current_page' => $jobs->currentPage(),
            'last_page' => $jobs->lastPage(),
            'per_page' => $jobs->perPage(),
            'total' => $jobs->total(),
            'prev_page_url' => $jobs->previousPageUrl(),
            'next_page_url' => $jobs->nextPageUrl(),
        ];
        
        return $data;
    }

    /**
     * Get featured companies
     */
    public function featured(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $limit = $request->input('limit', 6);

            $companies = Company::with([
                'industry',
                'location'
            ])
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function($company) {
                return $this->formatCompanyData($company);
            });

            return response()->json([
                'success' => true,
                'data' => $companies
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching featured companies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Format company data for API response
     */
    private function formatCompanyData($company, $detailed = false, $jobs = null)
    {
        try {
            $isLegacy = !is_null($company->legacy_id);

            $baseData = [
                'id' => $company->id,
                'name' => $company->name ?? '',
                'slug' => $company->slug ?? '',
                'logo_url' => $company->logo_url,
                'description' => $company->description ?? '',
                'website' => $company->website ?? '',
                'contact_name' => $company->contact_name ?? '',
                'contact_email' => $company->contact_email ?? '',
                'contact_phone' => $company->contact_phone ?? '',
                'address1' => $company->address1 ?? '',
                'company_size' => $company->company_size ?? '',
                'company_size_label' => $company->company_size_label,
                'is_active' => (bool) ($company->is_active ?? true),
                'is_verified' => (bool) ($company->is_verified ?? false),
                'is_gold' => (bool) ($company->is_gold ?? false),
                'is_featured' => (bool) ($company->is_featured ?? false),
                'legacy_id' => $company->legacy_id,
                'is_legacy' => $isLegacy,
                'created_at' => $company->created_at ? $company->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $company->updated_at ? $company->updated_at->format('Y-m-d H:i:s') : null,
                'hits' => (int) ($company->hits ?? 0),
            ];

            // Count active jobs
            $jobsCount = $company->jobs()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->count();

            $baseData['jobs_count'] = $jobsCount;

            $baseData['industry'] = $company->industry ? [
                'id' => $company->industry->id,
                'name' => $company->industry->name ?? '',
                'slug' => $company->industry->slug ?? '',
            ] : null;

            $baseData['location'] = $company->location ? [
                'id' => $company->location->id,
                'name' => $company->location->district ?? $company->location->city ?? '',
                'district' => $company->location->district ?? '',
                'city' => $company->location->city ?? '',
                'country' => $company->location->country ?? '',
            ] : null;

            $baseData['status_badge'] = $company->status_badge;
            $baseData['verified_badge'] = $company->verified_badge;
            $baseData['gold_badge'] = $company->gold_badge;
            $baseData['featured_badge'] = $company->featured_badge;

            if ($detailed) {
                $baseData['creator'] = $company->creator ? [
                    'id' => $company->creator->id,
                    'name' => $company->creator->name ?? '',
                    'email' => $company->creator->email ?? '',
                ] : null;

                // Use paginated jobs if provided, otherwise get all jobs
                if ($jobs) {
                    $baseData['jobs'] = $jobs->map(function($job) {
                        return $this->formatJobData($job);
                    })->toArray();
                    
                    $baseData['pagination'] = [
                        'current_page' => $jobs->currentPage(),
                        'last_page' => $jobs->lastPage(),
                        'per_page' => $jobs->perPage(),
                        'total' => $jobs->total(),
                        'prev_page_url' => $jobs->previousPageUrl(),
                        'next_page_url' => $jobs->nextPageUrl(),
                    ];
                } else {
                    $baseData['jobs'] = $company->jobs->map(function($job) {
                        return $this->formatJobData($job);
                    })->toArray();
                    
                    $baseData['pagination'] = [
                        'current_page' => 1,
                        'last_page' => 1,
                        'total' => count($baseData['jobs']),
                        'per_page' => count($baseData['jobs']),
                        'prev_page_url' => null,
                        'next_page_url' => null,
                    ];
                }

                $baseData['meta_title'] = $company->meta_title ?? '';
                $baseData['meta_description'] = $company->meta_description ?? '';
            }

            return $baseData;

        } catch (\Exception $e) {
            Log::error('Error formatting company data: ' . $e->getMessage());
            return [
                'id' => $company->id ?? null,
                'name' => $company->name ?? 'Unknown Company',
                'slug' => $company->slug ?? '',
            ];
        }
    }

    private function formatJobData($job)
    {
        return [
            'id' => $job->id,
            'job_title' => $job->job_title ?? '',
            'slug' => $job->slug ?? '',
            'formatted_salary' => $this->formatSalary($job),
            'location' => $job->jobLocation ? [
                'name' => $job->jobLocation->district ?? $job->jobLocation->city ?? '',
            ] : null,
            'job_type' => $job->jobType ? [
                'name' => $job->jobType->name ?? '',
            ] : null,
            'job_category' => $job->jobCategory ? [
                'name' => $job->jobCategory->name ?? '',
            ] : null,
            'published_at' => $job->published_at ? $job->published_at->format('Y-m-d H:i:s') : null,
            'is_urgent' => (bool) $job->is_urgent,
            'is_featured' => (bool) $job->is_featured,
            'employment_type' => $job->employment_type ?? 'full-time',
            'duty_station' => $job->duty_station ?? '',
            'has_real_deadline' => !is_null($job->legacy_id) && $job->deadline ? true : false,
            'deadline' => $job->deadline ? $job->deadline->format('Y-m-d') : null,
            'company' => [
                'name' => $job->company->name ?? '',
                'logo' => $job->company->logo_url ?? null,
            ],
            'job_location' => $job->jobLocation ? [
                'name' => $job->jobLocation->district ?? $job->jobLocation->city ?? '',
            ] : null,
        ];
    }

    /**
     * Format salary for display
     */
    private function formatSalary($job)
    {
        if ($job->salary_amount && $job->salary_amount > 0) {
            $period = $job->payment_period ?? 'monthly';
            $periodText = $period === 'daily' ? '/day' :
                         ($period === 'weekly' ? '/week' :
                         ($period === 'monthly' ? '/month' : '/year'));
            $currency = $job->currency ?? 'AUD';
            return $currency . ' ' . number_format((float)$job->salary_amount) . $periodText;
        }
        return 'Negotiable';
    }

    /**
     * Get industries for dropdown
     */
    public function industries(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $industries = Industry::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(function($industry) {
                    return [
                        'id' => $industry->id,
                        'name' => $industry->name,
                        'slug' => $industry->slug,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $industries
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching industries: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }
}