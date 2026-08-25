<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job\JobPost;
use App\Models\Job\JobCategory;
use App\Models\Job\JobLocation;
use App\Models\Job\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class JobController extends Controller
{
    /**
     * Get jobs with filters and pagination - FRONTEND API
     */
    public function index(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $perPage = $request->input('per_page', 20);

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
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

            // Keyword search
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

            if ($request->has('category_id')) {
                $query->where('job_category_id', $request->category_id);
            }
            if ($request->has('location_id')) {
                $query->where('job_location_id', $request->location_id);
            }
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            if ($request->has('job_type_id')) {
                $query->where('job_type_id', $request->job_type_id);
            }
            if ($request->has('experience_level_id')) {
                $query->where('experience_level_id', $request->experience_level_id);
            }
            if ($request->has('education_level_id')) {
                $query->where('education_level_id', $request->education_level_id);
            }
            if ($request->has('featured') && $request->featured) {
                $query->where('is_featured', true);
            }
            if ($request->has('urgent') && $request->urgent) {
                $query->where('is_urgent', true);
            }
            if ($request->has('min_salary')) {
                $query->where('salary_amount', '>=', $request->min_salary);
            }
            if ($request->has('max_salary')) {
                $query->where('salary_amount', '<=', $request->max_salary);
            }

            // Fresh (non-legacy) jobs always sort ahead of migrated legacy jobs -
            // legacy records have clean-ish company/category data but empty
            // responsibilities/qualifications/skills/application_procedure (it's
            // all dumped into job_description as one blob) and a meaningless
            // placeholder deadline inherited from the old "stop publishing" field,
            // so they're the weaker listings and belong further down the page.
            $query->orderByRaw('(legacy_id IS NULL) DESC');

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

            $jobs = $query->paginate($perPage);

            $formattedJobs = $jobs->getCollection()->map(function($job) {
                return $this->formatJobData($job);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedJobs,
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
            Log::error('Error fetching jobs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch jobs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single job by slug or ID
     */
    public function show($identifier)
    {
        try {
            $job = JobPost::with([
                'company',
                'jobLocation',
                'jobCategory',
                'industry',
                'jobType',
                'experienceLevel',
                'educationLevel',
                'salaryRange',
                'poster'
            ])
            ->where(function($q) use ($identifier) {
                $q->where('slug', $identifier)
                  ->orWhere('id', $identifier);
            })
            ->first();

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found'
                ], 404);
            }

            $job->increment('view_count');

            return response()->json([
                'success' => true,
                'data' => $this->formatJobData($job, true)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching job: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch job'
            ], 500);
        }
    }

    /**
     * Get featured jobs
     */
    public function featured(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');
            $limit = $request->input('limit', 10);

            $jobs = JobPost::with([
                'company',
                'jobLocation',
                'jobType'
            ])
            ->where('country_code', $countryCode)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function($query) {
                $query->whereNull('featured_until')
                    ->orWhere('featured_until', '>=', now());
            })
            ->orderByRaw('(legacy_id IS NULL) DESC')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($job) {
                return $this->formatJobData($job);
            });

            return response()->json([
                'success' => true,
                'data' => $jobs
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching featured jobs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Get categories for dropdown
     */
    public function categories(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $categories = JobCategory::where('country_code', $countryCode)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'icon'])
                ->map(function($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'icon' => $category->icon ?? 'bi-briefcase',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Get locations for dropdown
     */
    public function locations(Request $request)
    {
        try {
            $countryCode = $request->input('country_code', 'AU');

            $locations = JobLocation::where('country_code', $countryCode)
                ->where('is_active', true)
                ->orderBy('district')
                ->get(['id', 'district', 'city', 'country_code'])
                ->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->district . ($location->city ? ', ' . $location->city : ''),
                        'district' => $location->district,
                        'city' => $location->city,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching locations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Get job types for dropdown
     */
    public function jobTypes(Request $request)
    {
        try {
            $jobTypes = \App\Models\Job\JobType::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(function($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name,
                        'slug' => $type->slug,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $jobTypes
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching job types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => []
            ]);
        }
    }

    /**
     * Format job data for API response
     */
    private function formatJobData($job, $detailed = false)
    {
        try {
            $isLegacy = !is_null($job->legacy_id);

            $baseData = [
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
                'street_address' => $job->street_address ?? '',
                'location_type' => $job->location_type ?? 'on-site',
                'work_hours' => $job->work_hours ?? '',
                'employment_type' => $job->employment_type ?? 'full-time',
                'salary_amount' => $job->salary_amount,
                'currency' => $job->currency ?? 'AUD',
                'payment_period' => $job->payment_period ?? 'monthly',

                'is_featured' => (bool) ($job->is_featured ?? false),
                'is_urgent' => (bool) ($job->is_urgent ?? false),
                'is_verified' => (bool) ($job->is_verified ?? false),
                'is_active' => (bool) ($job->is_active ?? true),
                'is_pinged' => (bool) ($job->is_pinged ?? false),
                'is_indexed' => (bool) ($job->is_indexed ?? false),
                'is_simple_job' => (bool) ($job->is_simple_job ?? false),
                'is_quick_gig' => (bool) ($job->is_quick_gig ?? false),
                'is_whatsapp_contact' => (bool) ($job->is_whatsapp_contact ?? false),
                'is_telephone_call' => (bool) ($job->is_telephone_call ?? false),

                'is_resume_required' => (bool) ($job->is_resume_required ?? true),
                'is_cover_letter_required' => (bool) ($job->is_cover_letter_required ?? false),
                'is_academic_documents_required' => (bool) ($job->is_academic_documents_required ?? false),
                'is_application_required' => (bool) ($job->is_application_required ?? false),

                'view_count' => (int) ($job->view_count ?? 0),
                'application_count' => (int) ($job->application_count ?? 0),
                'click_count' => (int) ($job->click_count ?? 0),
                'social_shares' => (int) ($job->click_count ?? 0),

                // Legacy records inherited a placeholder "stop publishing" date
                // (often decades out, e.g. 2076) rather than a real deadline -
                // don't hand that to the frontend as if it were trustworthy.
                'deadline' => (!$isLegacy && $job->deadline) ? $job->deadline->format('Y-m-d') : null,
                'has_real_deadline' => !$isLegacy && (bool) $job->deadline,

                'created_at' => $job->created_at ? $job->created_at->format('Y-m-d H:i:s') : null,
                'published_at' => $job->published_at ? $job->published_at->format('Y-m-d H:i:s') : null,

                'legacy_id' => $job->legacy_id,
                'is_legacy' => $isLegacy,
                // Legacy rows have empty responsibilities/qualifications/skills/
                // application_procedure - everything ended up in job_description
                // as one blob during migration. Tell the frontend so it can skip
                // rendering empty section headers instead of guessing per-field.
                'has_structured_content' => !$isLegacy || (
                    trim(strip_tags($job->responsibilities ?? '')) !== ''
                    || trim(strip_tags($job->qualifications ?? '')) !== ''
                    || trim($job->skills ?? '') !== ''
                ),
            ];

            $baseData['formatted_salary'] = $this->formatSalary($job);

            $baseData['company'] = $job->company ? [
                'id' => $job->company->id,
                'name' => $job->company->name ?? '',
                'logo' => $job->company->logo_url ?? null,
                'website' => $job->company->website ?? null,
                'description' => $job->company->description ?? '',
            ] : null;

            $baseData['job_location'] = $job->jobLocation ? [
                'id' => $job->jobLocation->id,
                'country' => $job->jobLocation->country ?? '',
                'district' => $job->jobLocation->district ?? '',
                'city' => $job->jobLocation->city ?? '',
                'name' => $job->jobLocation->district ?? $job->jobLocation->country ?? '',
            ] : null;

            $baseData['job_category'] = $job->jobCategory ? [
                'id' => $job->jobCategory->id,
                'name' => $job->jobCategory->name ?? '',
                'slug' => $job->jobCategory->slug ?? '',
            ] : null;

            $baseData['job_type'] = $job->jobType ? [
                'id' => $job->jobType->id,
                'name' => $job->jobType->name ?? '',
                'slug' => $job->jobType->slug ?? '',
            ] : ['name' => $job->employment_type ?? 'Full Time'];

            $baseData['experience_level'] = $job->experienceLevel ? [
                'id' => $job->experienceLevel->id,
                'name' => $job->experienceLevel->name ?? '',
                'min_years' => $job->experienceLevel->min_years ?? null,
                'max_years' => $job->experienceLevel->max_years ?? null,
            ] : null;

            $baseData['education_level'] = $job->educationLevel ? [
                'id' => $job->educationLevel->id,
                'name' => $job->educationLevel->name ?? '',
            ] : null;

            $baseData['salary_range'] = $job->salaryRange ? [
                'id' => $job->salaryRange->id,
                'name' => $job->salaryRange->name ?? '',
                'min' => $job->salaryRange->min_salary ?? null,
                'max' => $job->salaryRange->max_salary ?? null,
                'currency' => $job->salaryRange->currency ?? 'AUD',
            ] : null;

            $baseData['industry'] = $job->industry ? [
                'id' => $job->industry->id,
                'name' => $job->industry->name ?? '',
            ] : null;

            if ($detailed) {
                $baseData['poster'] = $job->poster ? [
                    'id' => $job->poster->id,
                    'name' => $job->poster->name ?? '',
                    'email' => $job->poster->email ?? '',
                ] : null;

                $baseData['meta_title'] = $job->meta_title ?? '';
                $baseData['meta_description'] = $job->meta_description ?? '';
                $baseData['keywords'] = $job->keywords ?? '';
                $baseData['canonical_url'] = $job->canonical_url ?? '';
                $baseData['seo_score'] = $job->seo_score ?? null;
                $baseData['content_quality_score'] = $job->content_quality_score ?? null;
                $baseData['google_rank'] = $job->google_rank ?? null;
                $baseData['search_impressions'] = (int) ($job->search_impressions ?? 0);
                $baseData['search_clicks'] = (int) ($job->search_clicks ?? 0);
                $baseData['click_through_rate'] = $job->click_through_rate ?? 0;

                $baseData['similar_jobs'] = $this->getSimilarJobs($job);
            }

            return $baseData;

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

    /**
     * Get similar jobs
     */
    private function getSimilarJobs($job, $limit = 4)
    {
        try {
            if (!$job || !$job->id) {
                return [];
            }

            $query = JobPost::with([
                'company',
                'jobLocation',
                'jobType'
            ])
            ->where('is_active', true)
            ->where('id', '!=', $job->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

            if ($job->job_category_id) {
                $query->where('job_category_id', $job->job_category_id);
            } elseif ($job->job_location_id) {
                $query->where('job_location_id', $job->job_location_id);
            } elseif ($job->company_id) {
                $query->where('company_id', $job->company_id);
            }

            $similarJobs = $query->orderByRaw('(legacy_id IS NULL) DESC')
                ->orderBy('published_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($similarJob) {
                    return [
                        'id' => $similarJob->id,
                        'job_title' => $similarJob->job_title ?? '',
                        'slug' => $similarJob->slug ?? '',
                        'duty_station' => $similarJob->duty_station ?? '',
                        'formatted_salary' => $this->formatSalary($similarJob),
                        'company' => $similarJob->company ? [
                            'name' => $similarJob->company->name ?? '',
                            'logo' => $similarJob->company->logo_url ?? null,
                        ] : ['name' => 'Unknown'],
                        'job_type' => $similarJob->jobType ? [
                            'name' => $similarJob->jobType->name ?? '',
                        ] : ['name' => $similarJob->employment_type ?? 'Full Time'],
                    ];
                });

            return $similarJobs;

        } catch (\Exception $e) {
            Log::error('Error fetching similar jobs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Record that a user opened the apply modal for this job.
     * Kept deliberately simple - a raw increment - since "opened the apply
     * modal" is an intent signal, not proof of a completed application; the
     * country-app side de-dupes repeat opens within the same session so
     * refreshing/reopening doesn't inflate the count.
     */
    public function trackApplication($id)
    {
        try {
            $job = JobPost::find($id);
    
            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job not found',
                ], 404);
            }
    
            $job->increment('application_count');
    
            return response()->json([
                'success' => true,
                'application_count' => $job->application_count,
            ]);
        } catch (\Exception $e) {
            Log::error('Error tracking application: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to track application',
            ], 500);
        }
    }


}