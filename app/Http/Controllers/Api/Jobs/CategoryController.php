<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\JobCategory;
use App\Models\Job\JobPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Get all categories with job counts
     */
    public function index(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $categories = JobCategory::where('country_code', $countryCode)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $formattedCategories = $categories->map(function($category) {
                // Count active jobs in this category
                $jobCount = JobPost::where('job_category_id', $category->id)
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->count();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug, // Make sure slug is included
                    'icon' => $category->icon ?? 'bi-briefcase',
                    'description' => $category->description ?? '',
                    'jobs_count' => $jobCount,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedCategories
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single category with details
     */
    public function show(Request $request, $id)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $category = JobCategory::where('country_code', $countryCode)
                ->where('is_active', true)
                ->where(function($q) use ($id) {
                    $q->where('id', $id)
                      ->orWhere('slug', $id);
                })
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            // Count active jobs in this category
            $jobCount = JobPost::where('job_category_id', $category->id)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon ?? 'bi-briefcase',
                    'description' => $category->description ?? '',
                    'meta_title' => $category->meta_title ?? '',
                    'meta_description' => $category->meta_description ?? '',
                    'jobs_count' => $jobCount,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category'
            ], 500);
        }
    }

    /**
     * Get jobs for a specific category with pagination and filters
     */
    public function jobs(Request $request, $id)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $perPage = $request->input('per_page', 20);
            $categoryId = $id;

            // Verify category exists
            $category = JobCategory::where('country_code', $countryCode)
                ->where('is_active', true)
                ->where(function($q) use ($id) {
                    $q->where('id', $id)
                      ->orWhere('slug', $id);
                })
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $query = JobPost::with([
                'company',
                'jobCategory',
                'jobLocation',
                'jobType',
                'experienceLevel',
                'educationLevel',
                'salaryRange'
            ])
            ->where('country_code', $countryCode)
            ->where('job_category_id', $category->id)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

            // Log the query for debugging
            Log::info('Category jobs query', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'filters' => $request->all()
            ]);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('job_title', 'LIKE', "%{$search}%")
                      ->orWhere('job_description', 'LIKE', "%{$search}%")
                      ->orWhere('skills', 'LIKE', "%{$search}%")
                      ->orWhere('qualifications', 'LIKE', "%{$search}%")
                      ->orWhereHas('company', function($cq) use ($search) {
                          $cq->where('name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('jobLocation', function($lq) use ($search) {
                          $lq->where('district', 'LIKE', "%{$search}%")
                             ->orWhere('city', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filter by job type
            if ($request->has('job_type_id')) {
                $jobTypeIds = (array) $request->job_type_id;
                $query->whereIn('job_type_id', $jobTypeIds);
            }

            // Filter by location
            if ($request->has('location_id')) {
                $locationIds = (array) $request->location_id;
                $query->whereIn('job_location_id', $locationIds);
            }

            // Filter by salary range
            if ($request->has('min_salary')) {
                $query->where('salary_amount', '>=', $request->min_salary);
            }
            if ($request->has('max_salary')) {
                $query->where('salary_amount', '<=', $request->max_salary);
            }

            // Apply sorting
            $sort = $request->get('sort', 'newest');
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('published_at', 'asc');
                    break;
                case 'salary_high':
                    $query->orderBy('salary_amount', 'desc');
                    break;
                case 'salary_low':
                    $query->orderBy('salary_amount', 'asc');
                    break;
                default:
                    $query->orderBy('published_at', 'desc');
            }

            // Fresh jobs first
            $query->orderByRaw('(legacy_id IS NULL) DESC');

            $jobs = $query->paginate($perPage);

            $formattedJobs = $jobs->getCollection()->map(function($job) {
                return $this->formatJobData($job);
            });

            Log::info('Category jobs result', [
                'category_id' => $category->id,
                'total_jobs' => $jobs->total(),
                'returned_count' => $formattedJobs->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $formattedJobs,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'pagination' => [
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'prev_page_url' => $jobs->previousPageUrl(),
                    'next_page_url' => $jobs->nextPageUrl(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching category jobs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format job data for API response
     */
    private function formatJobData($job)
    {
        try {
            $isLegacy = !is_null($job->legacy_id);

            return [
                'id' => $job->id,
                'job_title' => $job->job_title ?? '',
                'slug' => $job->slug ?? '',
                'job_description' => $job->job_description ?? '',
                'responsibilities' => $job->responsibilities ?? '',
                'qualifications' => $job->qualifications ?? '',
                'skills' => $job->skills ?? '',
                'application_procedure' => $job->application_procedure ?? '',
                'email' => $job->email ?? '',
                'telephone' => $job->telephone ?? '',
                'duty_station' => $job->duty_station ?? '',
                'location_type' => $job->location_type ?? 'on-site',
                'work_hours' => $job->work_hours ?? '',
                'employment_type' => $job->employment_type ?? 'full-time',
                'salary_amount' => $job->salary_amount,
                'currency' => $job->currency ?? 'AUD',
                'payment_period' => $job->payment_period ?? 'monthly',
                'formatted_salary' => $this->formatSalary($job),
                'is_featured' => (bool) ($job->is_featured ?? false),
                'is_urgent' => (bool) ($job->is_urgent ?? false),
                'is_verified' => (bool) ($job->is_verified ?? false),
                'is_legacy' => $isLegacy,
                'has_real_deadline' => !$isLegacy && (bool) $job->deadline,
                'deadline' => (!$isLegacy && $job->deadline) ? $job->deadline->format('Y-m-d') : null,
                'published_at' => $job->published_at ? $job->published_at->format('Y-m-d H:i:s') : null,
                'company' => $job->company ? [
                    'id' => $job->company->id,
                    'name' => $job->company->name ?? '',
                    'logo' => $job->company->logo_url ?? null,
                    'website' => $job->company->website ?? null,
                ] : null,
                'job_location' => $job->jobLocation ? [
                    'id' => $job->jobLocation->id,
                    'name' => $job->jobLocation->district ?? $job->jobLocation->city ?? '',
                    'district' => $job->jobLocation->district ?? '',
                    'city' => $job->jobLocation->city ?? '',
                ] : null,
                'job_category' => $job->jobCategory ? [
                    'id' => $job->jobCategory->id,
                    'name' => $job->jobCategory->name ?? '',
                    'slug' => $job->jobCategory->slug ?? '',
                ] : null,
                'job_type' => $job->jobType ? [
                    'id' => $job->jobType->id,
                    'name' => $job->jobType->name ?? '',
                    'slug' => $job->jobType->slug ?? '',
                ] : null,
            ];

        } catch (\Exception $e) {
            Log::error('Error formatting job data: ' . $e->getMessage());
            return [
                'id' => $job->id ?? null,
                'job_title' => $job->job_title ?? 'Unknown Job',
                'slug' => $job->slug ?? '',
                'formatted_salary' => 'Negotiable',
            ];
        }
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

        if ($job->salaryRange && $job->salaryRange->min_salary && $job->salaryRange->max_salary) {
            $currency = $job->salaryRange->currency ?? 'AUD';
            return $currency . ' ' . number_format($job->salaryRange->min_salary) . ' - ' . number_format($job->salaryRange->max_salary);
        }

        return 'Negotiable';
    }
}