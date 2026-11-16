<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\SeekerProfile;
use App\Models\User;
use App\Models\Job\Country;
use App\Models\Job\JobSeekerJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Jobs\AiService;
use App\Services\Jobs\CvTextExtractionService;
use App\Models\Job\JobCategory;

class SeekerController extends Controller
{
    private const MAX_CV_FILES = 2;

    public function __construct(
        protected AiService $aiService,
        protected CvTextExtractionService $textExtractor
    ) {}

    // ─────────────────────────────────────────────────────────────
    //  Listing / detail
    // ─────────────────────────────────────────────────────────────

    public function index()
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        return view('job-seeker.index');
    }

    /**
     * GET /admin/seekers/{id}/categories
     *
     * Returns every active category in the seeker's country, plus the
     * category ids currently saved in job_preferences.alert_by_category.
     * The drawer uses this to render and pre-check the checkbox list.
     */
    public function categoriesForSeeker($id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $seeker  = SeekerProfile::with('user')->findOrFail($id);
        $country = $this->alertCountryFor($seeker);

        $categories = JobCategory::query()
            ->where('is_active', true)
            ->where(function ($q) use ($country) {
                $q->where('country_code', $country)->orWhereNull('country_code');
            })
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => $c->name,
            ]);

        // Same shape the seeker's own page reads
        $prefs   = $this->normalisePrefs($seeker->job_preferences ?? []);
        $selected = array_values(array_map('intval', $prefs['alert_by_category'] ?? []));

        return response()->json([
            'success'    => true,
            'country'    => $country,
            'categories' => $categories,
            'selected'   => $selected,
        ]);
    }

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
        $profileComplete    = $request->get('profile_complete');
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

    public function show($id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view job seekers.');
        }

        $seeker = SeekerProfile::with(['user', 'jobSeekerJobs.jobPost.company'])
            ->findOrFail($id);

        $formattedSeeker = $this->formatSeekerData($seeker);

        return view('job-seeker.partials.details', compact('formattedSeeker'));
    }

    public function viewCv(Request $request, $id)
    {
        if (!auth()->user()->can('view seekers')) {
            abort(403, 'You do not have permission to view CVs.');
        }

        $seeker = SeekerProfile::findOrFail($id);
        $filePath = $request->get('file');

        if ($filePath) {
            $cvFiles = $this->getCvFilesArray($seeker);
            $found = false;
            foreach ($cvFiles as $cv) {
                if (isset($cv['path']) && $cv['path'] === $filePath) {
                    $found = true;
                    break;
                }
            }
            if (!$found) abort(404, 'CV file not found');
            if (!Storage::disk('public')->exists($filePath)) abort(404, 'CV file not found');

            return response()->file(Storage::disk('public')->path($filePath));
        }

        if (!$seeker->cv_file_path) abort(404, 'CV not found');
        if (!Storage::disk('public')->exists($seeker->cv_file_path)) abort(404, 'CV file not found');

        return response()->file(Storage::disk('public')->path($seeker->cv_file_path));
    }

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
                    'job_title'    => $item->jobPost->job_title ?? 'N/A',
                    'company_name' => $item->jobPost->company->name ?? 'N/A',
                    'location'     => $item->jobPost->jobLocation->district ?? $item->jobPost->duty_station ?? 'N/A',
                    'applied_at'   => $item->applied_at,
                    'status'       => $this->getApplicationStatus($item),
                    'salary'       => $item->jobPost->formatted_salary ?? 'Negotiable',
                    'job_type'     => $item->jobPost->jobType->name ?? $item->jobPost->employment_type ?? 'Full-time',
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $applications,
            'total'   => $applications->count(),
        ]);
    }

    public function getFilters(Request $request)
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'flag'])
            ->map(fn($c) => ['code' => $c->code, 'name' => $c->name, 'flag' => $c->flag ?? '']);

        $categories = \App\Models\Job\JobCategory::where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $industries = \App\Models\Job\Industry::where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $jobTypes = \App\Models\Job\JobType::where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $locations = \App\Models\Job\JobLocation::where('is_active', true)
            ->orderBy('district')->get(['id', 'district', 'city'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->city ? "{$c->district} ({$c->city})" : $c->district]);

        $experienceLevels = \App\Models\Job\ExperienceLevel::where('is_active', true)
            ->orderBy('sort_order')->get(['id', 'name', 'min_years', 'max_years'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => $c->min_years !== null
                    ? "{$c->name} ({$c->min_years}-" . ($c->max_years ?? '∞') . " yrs)"
                    : $c->name,
            ]);

        $educationLevels = \App\Models\Job\EducationLevel::where('is_active', true)
            ->orderBy('sort_order')->get(['id', 'name'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        $salaryRanges = \App\Models\Job\SalaryRange::where('is_active', true)
            ->orderBy('min_salary')->get(['id', 'name', 'currency'])
            ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]);

        return response()->json([
            'success'           => true,
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

    public function filtersByCountry(Request $request)
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false], 403);
        }

        $country = strtoupper((string) $request->query('country', 'UG'));

        return response()->json([
            'success' => true,
            'country' => $country,
            'filters' => $this->buildFiltersForForm($country),
        ]);
    }

    public function createForm(Request $request)
    {
        if (!auth()->user()->can('view seekers')) abort(403);

        $country = $this->resolveCountryCode(
            $request->query('country') ?? auth()->user()->country_code ?? 'UG'
        );

        return view('job-seeker.partials.form', [
            'seeker'  => null,
            'filters' => $this->buildFiltersForForm($country),
            'mode'    => 'create',
            'country' => $country,
        ]);
    }

    public function editForm($id)
    {
        if (!auth()->user()->can('view seekers')) abort(403);

        $seeker  = SeekerProfile::with(['user'])->findOrFail($id);
        $country = $this->resolveCountryCode(
            $seeker->country ?? auth()->user()->country_code ?? 'UG'
        );

        return view('job-seeker.partials.form', [
            'seeker'  => $seeker,
            'filters' => $this->buildFiltersForForm($country),
            'mode'    => 'edit',
            'country' => $country,
        ]);
    }



    // ─────────────────────────────────────────────────────────────
    //  Create — form only, NO files, NO AI
    // ─────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email'      => 'required|email|max:255|unique:users,email',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:25',
            'country'    => 'nullable|string|max:255',

            'professional_title'   => 'nullable|string|max:255',
            'professional_summary' => 'nullable|string|max:2000',
            'city'                 => 'nullable|string|max:255',
            'postal_code'          => 'nullable|string|max:20',
            'date_of_birth'        => 'nullable|date',
            'nationality'          => 'nullable|string|max:255',
            'years_of_experience'  => 'nullable|integer|min:0',
            'linkedin_url'         => 'nullable|string|max:255',
            'github_url'           => 'nullable|string|max:255',
            'portfolio_url'        => 'nullable|string|max:255',
            'skills'               => 'nullable|string',
            'languages'            => 'nullable|string',

            'job_category_id'     => 'nullable|integer|exists:job_categories,id',
            'industry_id'         => 'nullable|integer|exists:industries,id',
            'job_type_id'         => 'nullable|integer|exists:job_types,id',
            'job_location_id'     => 'nullable|integer|exists:job_locations,id',
            'experience_level_id' => 'nullable|integer|exists:experience_levels,id',
            'education_level_id'  => 'nullable|integer|exists:education_levels,id',
            'salary_range_id'     => 'nullable|integer|exists:salary_ranges,id',

            'alert_all_jobs'     => 'nullable|boolean',
            'alert_by_skill'     => 'nullable|boolean',
            'alert_threshold'    => 'nullable|integer|between:1,100',
            'alert_by_whatsapp'  => 'nullable|boolean',
            'alert_by_email'     => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $countryCode = $this->resolveCountryCode($validated['country'] ?? 'UG');

            $user = User::create([
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'name'              => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email'             => $validated['email'],
                'phone'             => $validated['phone'] ?? null,
                'country_code'      => $countryCode,
                'is_active'         => true,
                'email_verified_at' => now(),
                'uuid'              => (string) \Illuminate\Support\Str::uuid(),
                'password'          => \Hash::make(\Str::random(32)),
            ]);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'job_seeker', 'guard_name' => 'web']);
            $user->syncRoles([$role->name]);
            $user->role_id = $role->id;
            $user->save();

            $profile = SeekerProfile::create([
                'user_id'              => $user->id,
                'first_name'           => $validated['first_name'],
                'last_name'            => $validated['last_name'],
                'email'                => $validated['email'],
                'phone'                => $validated['phone'] ?? null,
                'professional_title'   => $validated['professional_title']   ?? null,
                'professional_summary' => $validated['professional_summary'] ?? null,
                'city'                 => $validated['city']                 ?? null,
                'country'              => $countryCode,
                'postal_code'          => $validated['postal_code']          ?? null,
                'date_of_birth'        => $validated['date_of_birth']        ?? null,
                'nationality'          => $validated['nationality']          ?? null,
                'years_of_experience'  => $validated['years_of_experience']  ?? null,
                'linkedin_url'         => $validated['linkedin_url']         ?? null,
                'github_url'           => $validated['github_url']           ?? null,
                'portfolio_url'        => $validated['portfolio_url']        ?? null,
                'skills'               => $this->splitCsv($validated['skills']    ?? ''),
                'languages'            => $this->splitCsv($validated['languages'] ?? ''),
                'job_category_id'      => $validated['job_category_id']     ?? null,
                'industry_id'          => $validated['industry_id']         ?? null,
                'job_type_id'          => $validated['job_type_id']         ?? null,
                'job_location_id'      => $validated['job_location_id']     ?? null,
                'experience_level_id'  => $validated['experience_level_id'] ?? null,
                'education_level_id'   => $validated['education_level_id']  ?? null,
                'salary_range_id'      => $validated['salary_range_id']     ?? null,
                'job_preferences'      => [
                    'all_job_alerts'         => (bool) ($validated['alert_all_jobs']    ?? false),
                    'alert_by_skill_match'   => (bool) ($validated['alert_by_skill']    ?? false),
                    'skill_match_threshold'  => (int)  ($validated['alert_threshold']   ?? 60),
                    'send_alert_by_whatsapp' => (bool) ($validated['alert_by_whatsapp'] ?? false),
                    'send_alert_by_email'    => (bool) ($validated['alert_by_email']    ?? true),
                ],
                'is_active' => true,
                'is_public' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Seeker created. Add CV and documents from the Edit drawer.',
                'id'      => $profile->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Seeker create failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create seeker: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Update — form only, NO files, NO AI
    // ─────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile = SeekerProfile::with('user')->findOrFail($id);
        $user    = $profile->user;

        $validated = $request->validate([
            'email'      => 'required|email|max:255|unique:users,email,' . $user->id,
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:25',
            'country'    => 'nullable|string|max:255',

            'professional_title'   => 'nullable|string|max:255',
            'professional_summary' => 'nullable|string|max:2000',
            'city'                 => 'nullable|string|max:255',
            'postal_code'          => 'nullable|string|max:20',
            'date_of_birth'        => 'nullable|date',
            'nationality'          => 'nullable|string|max:255',
            'years_of_experience'  => 'nullable|integer|min:0',
            'linkedin_url'         => 'nullable|string|max:255',
            'github_url'           => 'nullable|string|max:255',
            'portfolio_url'        => 'nullable|string|max:255',
            'skills'               => 'nullable|string',
            'languages'            => 'nullable|string',

            'job_category_id'     => 'nullable|integer|exists:job_categories,id',
            'industry_id'         => 'nullable|integer|exists:industries,id',
            'job_type_id'         => 'nullable|integer|exists:job_types,id',
            'job_location_id'     => 'nullable|integer|exists:job_locations,id',
            'experience_level_id' => 'nullable|integer|exists:experience_levels,id',
            'education_level_id'  => 'nullable|integer|exists:education_levels,id',
            'salary_range_id'     => 'nullable|integer|exists:salary_ranges,id',

            'alert_all_jobs'      => 'nullable|boolean',
            'alert_by_skill'      => 'nullable|boolean',
            'alert_threshold'     => 'nullable|integer|between:1,100',
            'alert_by_whatsapp'   => 'nullable|boolean',
            'alert_by_email'      => 'nullable|boolean',

            // ── The categories picked on the Alerts tab ──
            'alert_by_category'   => 'nullable|array',
            'alert_by_category.*' => 'integer|exists:job_categories,id',
        ]);

        try {
            DB::beginTransaction();

            $countryCode = $this->resolveCountryCode($validated['country'] ?? $profile->country ?? 'UG');

            // ── 1. Merge the alert preferences into whatever is already stored ──
            $existingPrefs = $this->normalisePrefs($profile->job_preferences ?? []);

            $existingPrefs['all_job_alerts']         = (bool) ($validated['alert_all_jobs']    ?? false);
            $existingPrefs['alert_by_skill_match']   = (bool) ($validated['alert_by_skill']    ?? false);
            $existingPrefs['skill_match_threshold']  = (int)  ($validated['alert_threshold']   ?? 60);
            $existingPrefs['send_alert_by_whatsapp'] = (bool) ($validated['alert_by_whatsapp'] ?? false);
            $existingPrefs['send_alert_by_email']    = (bool) ($validated['alert_by_email']    ?? true);

            $existingPrefs['alert_by_category'] = array_values(array_unique(array_map(
                'intval',
                (array) ($validated['alert_by_category'] ?? [])
            )));

            // ── 2. User table ──
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'name'       => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email'      => $validated['email'],
                'phone'      => $validated['phone'] ?? null,
            ]);

            // ── 3. Profile fields (everything EXCEPT job_preferences) ──
            $profile->fill([
                'first_name'           => $validated['first_name'],
                'last_name'            => $validated['last_name'],
                'email'                => $validated['email'],
                'phone'                => $validated['phone'] ?? null,
                'professional_title'   => $validated['professional_title']   ?? null,
                'professional_summary' => $validated['professional_summary'] ?? null,
                'city'                 => $validated['city']                 ?? null,
                'country'              => $countryCode,
                'postal_code'          => $validated['postal_code']          ?? null,
                'date_of_birth'        => $validated['date_of_birth']        ?? null,
                'nationality'          => $validated['nationality']          ?? null,
                'years_of_experience'  => $validated['years_of_experience']  ?? null,
                'linkedin_url'         => $validated['linkedin_url']         ?? null,
                'github_url'           => $validated['github_url']           ?? null,
                'portfolio_url'        => $validated['portfolio_url']        ?? null,
                'skills'               => $this->splitCsv($validated['skills']    ?? ''),
                'languages'            => $this->splitCsv($validated['languages'] ?? ''),
                'job_category_id'      => $validated['job_category_id']     ?? null,
                'industry_id'          => $validated['industry_id']         ?? null,
                'job_type_id'          => $validated['job_type_id']         ?? null,
                'job_location_id'      => $validated['job_location_id']     ?? null,
                'experience_level_id'  => $validated['experience_level_id'] ?? null,
                'education_level_id'   => $validated['education_level_id']  ?? null,
                'salary_range_id'      => $validated['salary_range_id']     ?? null,
                // ← job_preferences is NOT set here — it's already merged above
            ]);

            // ── 4. Attach the merged prefs AFTER fill() so it isn't overwritten ──
            $profile->job_preferences = $existingPrefs;

            $profile->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Seeker updated.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Seeker update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update seeker: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  CV & Academic — dedicated endpoints (edit drawer only)
    // ─────────────────────────────────────────────────────────────

    /**
     * POST /admin/seekers/{id}/cv
     *
     * Uploads a CV, sends it to the AI, and updates the profile fields
     * with whatever the AI extracted. Fields the AI returns empty are left
     * unchanged so the admin's earlier values aren't overwritten by nulls.
     *
     * Respects the 2-CV limit.
     */
    public function uploadCv(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $profile = SeekerProfile::findOrFail($id);
        $currentCvs = is_array($profile->cv_files) ? $profile->cv_files : [];

        if (count($currentCvs) >= self::MAX_CV_FILES) {
            return response()->json([
                'success' => false,
                'message' => 'This seeker already has ' . self::MAX_CV_FILES . ' CVs. Delete one before uploading another.',
            ], 422);
        }

        $file = $request->file('cv');

        // 1. Store the file first — even if AI fails, the file is kept.
        $path = $file->store('cvs', 'public');
        $newCv = [
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'uploaded_at'   => now()->toISOString(),
        ];

        $currentCvs[] = $newCv;

        // 2. Try AI extraction. Any failure is non-fatal — the file is still saved.
        $extracted = [];
        $aiStatus  = 'skipped';

        try {
            $extracted = $this->aiService->extractCvDataFromFile($file, $this->textExtractor);
            $aiStatus  = 'ok';
        } catch (\App\Exceptions\NotACvException $e) {
            $aiStatus = 'not_a_cv';
            Log::info('Admin CV upload rejected as non-CV', [
                'seeker' => $id,
                'reason' => $e->reason,
            ]);
        } catch (\Throwable $e) {
            $aiStatus = 'error';
            Log::warning('Admin CV extraction failed', [
                'seeker' => $id,
                'error'  => $e->getMessage(),
            ]);
        }

        // 3. Apply extracted values — only where they're non-empty.
        if (!empty($extracted)) {
            $this->applyExtractedToProfile($profile, $extracted);
        }

        $profile->cv_files = $currentCvs;
        $profile->save();
        $profile->refresh();

        return response()->json([
            'success'   => true,
            'message'   => $aiStatus === 'ok'
                ? 'CV uploaded and profile updated from it.'
                : ($aiStatus === 'not_a_cv'
                    ? 'File stored, but AI says it doesn\'t look like a CV. Fill the fields manually.'
                    : 'File stored. AI extraction failed — fill the fields manually.'),
            'ai_status' => $aiStatus,
            'cv_files'  => $profile->cv_files ?? [],
            'profile'   => $profile,
        ]);
    }

    /**
     * DELETE /admin/seekers/{id}/cv?path=cvs/abc.pdf
     */
    public function deleteCv(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate(['path' => 'required|string']);

        $profile = SeekerProfile::findOrFail($id);
        $cvs = is_array($profile->cv_files) ? $profile->cv_files : [];
        $path = $request->query('path');

        $kept = [];
        $found = false;
        foreach ($cvs as $cv) {
            if (($cv['path'] ?? null) === $path) {
                $found = true;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                continue;
            }
            $kept[] = $cv;
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'CV not found on this profile.'], 404);
        }

        $profile->cv_files = $kept;
        $profile->save();
        $profile->refresh();

        return response()->json([
            'success'  => true,
            'message'  => 'CV deleted.',
            'cv_files' => $profile->cv_files ?? [],
        ]);
    }

    /**
     * POST /admin/seekers/{id}/academic
     * Replaces the seeker's single academic PDF.
     */
    public function uploadAcademic(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'academic_document' => 'required|file|mimes:pdf|max:10240',
        ]);

        $profile = SeekerProfile::findOrFail($id);

        // Delete any existing file first
        if ($profile->academic_documents_path && Storage::disk('public')->exists($profile->academic_documents_path)) {
            Storage::disk('public')->delete($profile->academic_documents_path);
        }

        $file = $request->file('academic_document');
        $path = $file->store('academic-documents', 'public');

        $profile->academic_documents_path          = $path;
        $profile->academic_documents_original_name = $file->getClientOriginalName();
        $profile->academic_documents_size          = $file->getSize();
        $profile->academic_documents_uploaded_at   = now();
        $profile->save();
        $profile->refresh();

        return response()->json([
            'success'  => true,
            'message'  => 'Academic document uploaded.',
            'document' => [
                'path'          => $profile->academic_documents_path,
                'original_name' => $profile->academic_documents_original_name,
                'size'          => $profile->academic_documents_size,
                'uploaded_at'   => $profile->academic_documents_uploaded_at?->toISOString(),
            ],
        ]);
    }

    /**
     * DELETE /admin/seekers/{id}/academic
     */
    public function deleteAcademic($id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile = SeekerProfile::findOrFail($id);

        if ($profile->academic_documents_path && Storage::disk('public')->exists($profile->academic_documents_path)) {
            Storage::disk('public')->delete($profile->academic_documents_path);
        }

        $profile->academic_documents_path          = null;
        $profile->academic_documents_original_name = null;
        $profile->academic_documents_size          = null;
        $profile->academic_documents_uploaded_at   = null;
        $profile->save();

        return response()->json(['success' => true, 'message' => 'Academic document deleted.']);
    }

    /**
     * POST /admin/seekers/{id}/re-extract
     * Re-runs AI on the profile's first stored CV. Does NOT save — returns what it found.
     */
    public function reExtractCv($id): JsonResponse
    {
        if (!auth()->user()->can('view seekers')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $profile = SeekerProfile::findOrFail($id);
        $cvs = is_array($profile->cv_files) ? $profile->cv_files : [];

        if (empty($cvs)) {
            return response()->json(['success' => false, 'message' => 'This seeker has no CV to extract from.'], 422);
        }

        $cv   = $cvs[0];
        $path = $cv['path'] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'The stored CV file is missing on disk.'], 404);
        }

        $uploaded = new \Illuminate\Http\UploadedFile(
            Storage::disk('public')->path($path),
            $cv['original_name'] ?? basename($path),
            $cv['mime_type']     ?? 'application/pdf',
            null,
            true
        );

        try {
            $extracted = $this->aiService->extractCvDataFromFile($uploaded, $this->textExtractor);

            return response()->json([
                'success'   => true,
                'extracted' => $extracted,
            ]);
        } catch (\App\Exceptions\NotACvException $e) {
            return response()->json(['success' => false, 'code' => 'not_a_cv', 'message' => $e->reason], 422);
        } catch (\Throwable $e) {
            Log::error('Re-extract failed', ['seeker' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Extraction failed: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Shared helpers
    // ─────────────────────────────────────────────────────────────

    /** Apply AI-extracted fields to a profile — only non-empty values. */
    private function applyExtractedToProfile(SeekerProfile $profile, array $extracted): void
    {
        $scalar = [
            'professional_title', 'professional_summary', 'phone',
            'city', 'postal_code', 'nationality', 'date_of_birth',
            'years_of_experience', 'linkedin_url', 'github_url', 'portfolio_url',
        ];

        foreach ($scalar as $field) {
            $value = $extracted[$field] ?? null;
            if ($value !== null && $value !== '' && empty($profile->{$field})) {
                $profile->{$field} = $value;
            }
        }

        // Country comes back as ISO from AiService::resolveCountryCode()
        if (!empty($extracted['country']) && empty($profile->country)) {
            $profile->country = $extracted['country'];
        }

        // Arrays — fill only when the profile's array is empty
        foreach (['skills', 'languages', 'work_experience', 'education', 'certifications', 'projects'] as $arrayField) {
            if (empty($profile->{$arrayField}) && !empty($extracted[$arrayField])) {
                $profile->{$arrayField} = $extracted[$arrayField];
            }
        }
    }

    /** Resolve any country shape → ISO code. (Unchanged from before.) */
    private function resolveCountryCode(?string $raw): string
    {
        $raw = $raw !== null ? trim($raw) : '';
        if ($raw === '') return 'UG';

        $up = strtoupper($raw);
        if (preg_match('/^[A-Z]{2}$/', $up)) {
            return Country::where('code', $up)->exists() ? $up : 'UG';
        }

        $byName = Country::where('name', $raw)->value('code');
        if ($byName) return strtoupper($byName);

        foreach (Country::all(['code', 'name']) as $c) {
            if ($c->name && mb_stripos($raw, $c->name) !== false) {
                return strtoupper($c->code);
            }
        }
        return 'UG';
    }

    /** "a, b, c" → ['a','b','c'] */
    private function splitCsv(?string $value): array
    {
        if ($value === null || trim($value) === '') return [];
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

        private function getCvFilesArray($seeker): array
    {
        if (!$seeker) return [];
        $files = $seeker->cv_files;
        if (is_null($files)) return [];
        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($files) ? $files : [];
    }

    private function formatSeekerData($seeker)
    {
        $user = $seeker->user;
        $fullName = trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? ''));
        if ($fullName === '' && $user) $fullName = $user->name ?? 'Unknown';

        $cvFiles = $this->getCvFilesArray($seeker);
        $cvCount = count($cvFiles);
        $hasCv   = !is_null($seeker->cv_file_path) || $cvCount > 0;

        $appliedCount = $seeker->jobSeekerJobs()->where('is_applied', true)->count();
        $savedCount   = $seeker->jobSeekerJobs()->where('is_saved', true)->count();

        $flag = $seeker->country
            ? (Country::where('code', $seeker->country)->value('flag') ?? '')
            : '';

        $cvFilesWithUrls = [];
        foreach ($cvFiles as $cv) {
            if (isset($cv['path'])) $cv['url'] = Storage::disk('public')->url($cv['path']);
            $cvFilesWithUrls[] = $cv;
        }

        return [
            'id'                    => $seeker->id,
            'user_id'               => $seeker->user_id,
            'avatar'                => $user ? $user->avatar_url : asset('assets/media/avatars/blank.png'),
            'full_name'             => $fullName,
            'email'                 => $seeker->email ?? ($user->email ?? 'N/A'),
            'phone'                 => $seeker->phone ?? ($user->phone ?? 'N/A'),
            'professional_title'    => $seeker->professional_title ?? 'N/A',
            'professional_summary'  => $seeker->professional_summary ?? 'N/A',
            'country'               => $seeker->country ?? 'N/A',
            'flag'                  => $flag,
            'city'                  => $seeker->city ?? 'N/A',
            'years_of_experience'   => $seeker->years_of_experience ?? 0,
            'skills'                => $seeker->skills ?? [],
            'languages'             => $seeker->languages ?? [],
            'linkedin_url'          => $seeker->linkedin_url,
            'github_url'            => $seeker->github_url,
            'portfolio_url'         => $seeker->portfolio_url,
            'has_cv'                => $hasCv,
            'cv_count'              => $cvCount,
            'cv_file_path'          => $seeker->cv_file_path,
            'cv_files'              => $cvFilesWithUrls,
            'applied_count'         => $appliedCount,
            'saved_count'           => $savedCount,
            'is_public'             => $seeker->is_public,
            'created_at'            => $seeker->created_at,
            'updated_at'            => $seeker->updated_at,
            'status_badge'          => $this->getStatusBadge($seeker),
            'cv_badge'              => $this->getCvBadge($seeker),
            'job_category_id'       => $seeker->job_category_id,
            'job_category'          => $seeker->jobCategory?->name,
            'industry_id'           => $seeker->industry_id,
            'industry'              => $seeker->industry?->name,
            'job_type_id'           => $seeker->job_type_id,
            'job_type'              => $seeker->jobType?->name,
            'job_location_id'       => $seeker->job_location_id,
            'job_location'          => $seeker->jobLocation
                ? ($seeker->jobLocation->city
                    ? "{$seeker->jobLocation->district} ({$seeker->jobLocation->city})"
                    : $seeker->jobLocation->district)
                : null,
            'experience_level_id'   => $seeker->experience_level_id,
            'experience_level'      => $seeker->experienceLevel?->name,
            'education_level_id'    => $seeker->education_level_id,
            'education_level'       => $seeker->educationLevel?->name,
            'salary_range_id'       => $seeker->salary_range_id,
            'salary_range'          => $seeker->salaryRange?->name,
            'profile_complete'      => (bool) $seeker->is_profile_complete,
        ];
    }

    private function getApplicationStatus($jobSeekerJob): string
    {
        if ($jobSeekerJob->is_got_job) return 'hired';
        if ($jobSeekerJob->is_called_for_interview) return 'interviewing';
        if ($jobSeekerJob->is_rejected) return 'rejected';
        return 'applied';
    }

    private function getStatusBadge($seeker)
    {
        return $seeker->is_public
            ? '<span class="badge badge-light-success">Public</span>'
            : '<span class="badge badge-light-secondary">Private</span>';
    }

    private function getCvBadge($seeker)
    {
        $cvCount = count($this->getCvFilesArray($seeker));
        if ($seeker->cv_file_path || $cvCount > 0) {
            return '<span class="badge badge-light-success">✅ ' . ($cvCount > 0 ? $cvCount . ' CV(s)' : 'Uploaded') . '</span>';
        }
        return '<span class="badge badge-light-danger">❌ Not Uploaded</span>';
    }

    private function buildFiltersForForm(?string $countryCode = null): array
    {
        $cc = $countryCode ? strtoupper($countryCode) : null;

        $scope = function ($query) use ($cc) {
            if ($cc === null || $cc === '') return $query;
            return $query->where(function ($q) use ($cc) {
                $q->where('country_code', $cc)->orWhereNull('country_code');
            });
        };

        return [
            'categories'        => $scope(\App\Models\Job\JobCategory::where('is_active', true))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'locations'         => $scope(\App\Models\Job\JobLocation::where('is_active', true))
                ->orderBy('district')->get(['id', 'district', 'city'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->city ? "{$c->district} ({$c->city})" : $c->district]),

            'education_levels'  => $scope(\App\Models\Job\EducationLevel::where('is_active', true))
                ->orderBy('sort_order')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'salary_ranges'     => $scope(\App\Models\Job\SalaryRange::where('is_active', true))
                ->orderBy('min_salary')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'industries'        => \App\Models\Job\Industry::where('is_active', true)
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'job_types'         => \App\Models\Job\JobType::where('is_active', true)
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'experience_levels' => \App\Models\Job\ExperienceLevel::where('is_active', true)
                ->orderBy('sort_order')->get(['id', 'name'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name]),

            'countries'         => Country::where('is_active', true)
                ->orderBy('name')->get(['code', 'name', 'flag'])
                ->map(fn($c) => ['code' => $c->code, 'label' => ($c->flag ?? '') . ' ' . $c->name]),
        ];
    }

    /**
     * The country whose categories this seeker is allowed to be alerted about.
     *
     * Resolution order:
     *   1. seeker_profiles.country
     *   2. users.country_code
     *   3. 'UG'
     *
     * Returns a 2-letter ISO code that exists in `countries.code`.
     */
    private function alertCountryFor(SeekerProfile $seeker): string
    {
        $seeker->loadMissing('user');

        $candidates = [
            $seeker->country,
            $seeker->user?->country_code,
            'UG',
        ];

        foreach ($candidates as $raw) {
            if ($raw === null || trim((string) $raw) === '') {
                continue;
            }

            // resolveCountryCode() already exists in this controller and
            // handles "Uganda", "+256", "ug", "UG", etc.
            $iso = $this->resolveCountryCode((string) $raw);

            if ($iso && Country::where('code', $iso)->exists()) {
                return $iso;
            }
        }

        return 'UG';
    }

    /** Ensure job_preferences is always an array (JSON column may be a string on some drivers). */
    private function normalisePrefs($prefs): array
    {
        if (is_array($prefs)) {
            return $prefs;
        }
        if (is_string($prefs)) {
            return json_decode($prefs, true) ?: [];
        }
        return [];
    }
    

}