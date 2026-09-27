<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\SeekerProfile;
use App\Models\User;
use App\Models\Job\Country;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SeekerController extends Controller
{
    /**
     * Display a listing of job seekers.
     */
    public function index()
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        return view('job-seeker.index');
    }

    /**
     * Get data for DataTable.
     */
    public function getData(Request $request)
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $search             = $request->get('search', '');
        $country            = $request->get('country', '');
        $status             = $request->get('status', '');
        $jobCategoryId      = $request->get('job_category_id');
        $industryId         = $request->get('industry_id');
        $jobTypeId          = $request->get('job_type_id');
        $jobLocationId      = $request->get('job_location_id');
        $experienceLevelId  = $request->get('experience_level_id');
        $educationLevelId   = $request->get('education_level_id');
        $salaryRangeId      = $request->get('salary_range_id');
        $minExperience      = $request->get('min_experience');
        $profileComplete    = $request->get('profile_complete'); // '1' | '0' | ''
        $page               = (int) $request->get('page', 1);
        $perPage            = (int) $request->get('per_page', 15);

        $query = SeekerProfile::with([
            'user',
            'jobCategory:id,name',
            'industry:id,name',
            'jobType:id,name',
            'jobLocation:id,district,city',
            'experienceLevel:id,name,min_years,max_years',
            'educationLevel:id,name',
            'salaryRange:id,name,currency,min_salary,max_salary',
        ])->where('is_active', true);

        // ── Search ─────────────────────────────────────────────────────
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('professional_title', 'like', "%{$search}%")
                ->orWhere('professional_summary', 'like', "%{$search}%")
                ->orWhere('skills', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        // ── Basic filters ──────────────────────────────────────────────
        if (!empty($country)) {
            $query->where('country', $country);
        }

        if (!empty($status)) {
            if ($status === 'has_cv') {
                $query->where(function ($q) {
                    $q->whereNotNull('cv_file_path')
                    ->orWhereNotNull('cv_files');
                });
            } elseif ($status === 'no_cv') {
                $query->whereNull('cv_file_path')->whereNull('cv_files');
            } elseif ($status === 'has_applied') {
                $query->whereHas('jobSeekerJobs', fn($q) => $q->where('is_applied', true));
            }
        }

        // ── Filter FK filters ──────────────────────────────────────────
        if (!empty($jobCategoryId))     $query->where('job_category_id', $jobCategoryId);
        if (!empty($industryId))        $query->where('industry_id', $industryId);
        if (!empty($jobTypeId))         $query->where('job_type_id', $jobTypeId);
        if (!empty($jobLocationId))     $query->where('job_location_id', $jobLocationId);
        if (!empty($experienceLevelId)) $query->where('experience_level_id', $experienceLevelId);
        if (!empty($educationLevelId))  $query->where('education_level_id', $educationLevelId);
        if (!empty($salaryRangeId))     $query->where('salary_range_id', $salaryRangeId);

        if ($minExperience !== null && $minExperience !== '') {
            $query->where('years_of_experience', '>=', (int) $minExperience);
        }

        // ── Profile complete filter ────────────────────────────────────
        if ($profileComplete === '1') {
            $query->whereNotNull('job_category_id')
                ->whereNotNull('industry_id')
                ->whereNotNull('job_location_id')
                ->whereNotNull('experience_level_id')
                ->whereNotNull('education_level_id')
                ->whereNotNull('professional_title')
                ->whereNotNull('skills');
        } elseif ($profileComplete === '0') {
            $query->where(function ($q) {
                $q->whereNull('job_category_id')
                ->orWhereNull('industry_id')
                ->orWhereNull('job_location_id')
                ->orWhereNull('experience_level_id')
                ->orWhereNull('education_level_id')
                ->orWhereNull('professional_title')
                ->orWhereNull('skills');
            });
        }

        $seekers = $query->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $seekers->getCollection()->transform(fn($item) => $this->formatSeekerData($item));

        return response()->json($seekers);
    }

    /**
     * Display a specific job seeker - returns HTML for modal.
     */
    public function show($id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        $seeker = SeekerProfile::with(['user', 'jobSeekerJobs.jobPost.company'])
            ->findOrFail($id);

        $formattedSeeker = $this->formatSeekerData($seeker);
        
        // Return HTML for modal
        return view('job-seeker.partials.details', compact('formattedSeeker'));
    }

    /**
     * View CV of a job seeker - supports multiple CVs.
     */
    public function viewCv(Request $request, $id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view CVs.');
        }

        $seeker = SeekerProfile::findOrFail($id);
        
        // Check if specific file is requested
        $filePath = $request->get('file');
        
        if ($filePath) {
            // View specific CV from cv_files array
            $cvFiles = $this->getCvFilesArray($seeker);
            $found = false;
            foreach ($cvFiles as $cv) {
                if (isset($cv['path']) && $cv['path'] === $filePath) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                abort(404, 'CV file not found');
            }
            if (!Storage::disk('public')->exists($filePath)) {
                abort(404, 'CV file not found');
            }
            return response()->file(Storage::disk('public')->path($filePath));
        }
        
        // Fallback to single CV file
        if (!$seeker->cv_file_path) {
            abort(404, 'CV not found');
        }

        if (!Storage::disk('public')->exists($seeker->cv_file_path)) {
            abort(404, 'CV file not found');
        }

        return response()->file(Storage::disk('public')->path($seeker->cv_file_path));
    }

    /**
     * Get applications for a specific seeker.
     */
    public function applications(Request $request, $id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view applications.');
        }

        $seeker = SeekerProfile::with(['user'])->findOrFail($id);

        $applications = JobSeekerJob::with(['jobPost.company', 'jobPost.jobLocation', 'jobPost.jobType'])
            ->where('seeker_profile_id', $id)
            ->where('is_applied', true)
            ->orderBy('applied_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'job_title' => $item->jobPost->job_title ?? 'N/A',
                    'company_name' => $item->jobPost->company->name ?? 'N/A',
                    'location' => $item->jobPost->jobLocation->district ?? $item->jobPost->duty_station ?? 'N/A',
                    'applied_at' => $item->applied_at,
                    'status' => $this->getApplicationStatus($item),
                    'salary' => $item->jobPost->formatted_salary ?? 'Negotiable',
                    'job_type' => $item->jobPost->jobType->name ?? $item->jobPost->employment_type ?? 'Full-time',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $applications,
            'total' => $applications->count(),
        ]);
    }

    /**
     * Get filters for dropdowns.
     */
    public function getFilters(Request $request)
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'flag'])
            ->map(fn($c) => [
                'code'  => $c->code,
                'name'  => $c->name,
                'flag'  => $c->flag ?? '',
            ]);

        $categories = \App\Models\Job\JobCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $industries = \App\Models\Job\Industry::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $jobTypes = \App\Models\Job\JobType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $locations = \App\Models\Job\JobLocation::where('is_active', true)
            ->orderBy('district')
            ->get(['id', 'district', 'city'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => $c->city ? "{$c->district} ({$c->city})" : $c->district,
            ]);

        $experienceLevels = \App\Models\Job\ExperienceLevel::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'min_years', 'max_years'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => $c->min_years !== null
                    ? "{$c->name} ({$c->min_years}-" . ($c->max_years ?? '∞') . " yrs)"
                    : $c->name,
            ]);

        $educationLevels = \App\Models\Job\EducationLevel::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $salaryRanges = \App\Models\Job\SalaryRange::where('is_active', true)
            ->orderBy('min_salary')
            ->get(['id', 'name', 'currency'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        return response()->json([
            'success' => true,
            'countries'         => $countries,
            'categories'        => $categories,
            'industries'        => $industries,
            'job_types'         => $jobTypes,
            'locations'         => $locations,
            'experience_levels' => $experienceLevels,
            'education_levels'  => $educationLevels,
            'salary_ranges'     => $salaryRanges,
        ]);
    }

    /**
     * Get CV files as array - ensures it's always an array.
     */
    private function getCvFilesArray($seeker): array
    {
        if (!$seeker) {
            return [];
        }
        
        $files = $seeker->cv_files;
        
        if (is_null($files)) {
            return [];
        }
        
        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($files)) {
            return $files;
        }
        
        return [];
    }

    /**
     * Format seeker data for table display.
     */
    private function formatSeekerData($seeker)
    {
        $user = $seeker->user;
        $fullName = trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? ''));
        if ($fullName === '' && $user) {
            $fullName = $user->name ?? 'Unknown';
        }

        $cvFiles = $this->getCvFilesArray($seeker);
        $cvCount = count($cvFiles);
        $hasCv   = !is_null($seeker->cv_file_path) || $cvCount > 0;

        $appliedCount = $seeker->jobSeekerJobs()->where('is_applied', true)->count();
        $savedCount   = $seeker->jobSeekerJobs()->where('is_saved', true)->count();

        $flag = '';
        if ($seeker->country) {
            $flag = Country::where('code', $seeker->country)->value('flag') ?? '';
        }

        $cvFilesWithUrls = [];
        foreach ($cvFiles as $cv) {
            if (isset($cv['path'])) {
                $cv['url'] = Storage::disk('public')->url($cv['path']);
            }
            $cvFilesWithUrls[] = $cv;
        }

        return [
            'id'            => $seeker->id,
            'user_id'       => $seeker->user_id,
            'avatar'        => $user ? $user->avatar_url : asset('assets/media/avatars/blank.png'),
            'full_name'     => $fullName,
            'email'         => $seeker->email ?? ($user->email ?? 'N/A'),
            'phone'         => $seeker->phone ?? ($user->phone ?? 'N/A'),
            'professional_title'   => $seeker->professional_title ?? 'N/A',
            'professional_summary' => $seeker->professional_summary ?? 'N/A',
            'country'       => $seeker->country ?? 'N/A',
            'flag'          => $flag,
            'city'          => $seeker->city ?? 'N/A',
            'years_of_experience' => $seeker->years_of_experience ?? 0,
            'skills'        => $seeker->skills ?? [],
            'languages'     => $seeker->languages ?? [],
            'linkedin_url'  => $seeker->linkedin_url,
            'github_url'    => $seeker->github_url,
            'portfolio_url' => $seeker->portfolio_url,

            'has_cv'        => $hasCv,
            'cv_count'      => $cvCount,
            'cv_file_path'  => $seeker->cv_file_path,
            'cv_files'      => $cvFilesWithUrls,

            'applied_count' => $appliedCount,
            'saved_count'   => $savedCount,
            'is_public'     => $seeker->is_public,
            'created_at'    => $seeker->created_at,
            'updated_at'    => $seeker->updated_at,
            'status_badge'  => $this->getStatusBadge($seeker),
            'cv_badge'      => $this->getCvBadge($seeker),

            // New: filter FK info
            'job_category_id'     => $seeker->job_category_id,
            'job_category'        => $seeker->jobCategory?->name,
            'industry_id'         => $seeker->industry_id,
            'industry'            => $seeker->industry?->name,
            'job_type_id'         => $seeker->job_type_id,
            'job_type'            => $seeker->jobType?->name,
            'job_location_id'     => $seeker->job_location_id,
            'job_location'        => $seeker->jobLocation
                                        ? ($seeker->jobLocation->city
                                            ? "{$seeker->jobLocation->district} ({$seeker->jobLocation->city})"
                                            : $seeker->jobLocation->district)
                                        : null,
            'experience_level_id' => $seeker->experience_level_id,
            'experience_level'    => $seeker->experienceLevel?->name,
            'education_level_id'  => $seeker->education_level_id,
            'education_level'     => $seeker->educationLevel?->name,
            'salary_range_id'     => $seeker->salary_range_id,
            'salary_range'        => $seeker->salaryRange?->name,
            'profile_complete'    => (bool) $seeker->is_profile_complete,
        ];
    }

    /**
     * Get application status.
     */
    private function getApplicationStatus($jobSeekerJob): string
    {
        if ($jobSeekerJob->is_got_job) return 'hired';
        if ($jobSeekerJob->is_called_for_interview) return 'interviewing';
        if ($jobSeekerJob->is_rejected) return 'rejected';
        return 'applied';
    }

    /**
     * Get status badge HTML.
     */
    private function getStatusBadge($seeker)
    {
        if ($seeker->is_public) {
            return '<span class="badge badge-light-success">Public</span>';
        }
        return '<span class="badge badge-light-secondary">Private</span>';
    }

    /**
     * Get CV badge HTML.
     */
    private function getCvBadge($seeker)
    {
        $cvCount = count($this->getCvFilesArray($seeker));
        if ($seeker->cv_file_path || $cvCount > 0) {
            return '<span class="badge badge-light-success">✅ ' . ($cvCount > 0 ? $cvCount . ' CV(s)' : 'Uploaded') . '</span>';
        }
        return '<span class="badge badge-light-danger">❌ Not Uploaded</span>';
    }
}