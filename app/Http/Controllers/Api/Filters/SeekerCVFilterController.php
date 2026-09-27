<?php

namespace App\Http\Controllers\Api\Filters;

use App\Http\Controllers\Controller;
use App\Models\Job\Country;
use App\Models\SeekerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SeekerCVFilterController extends Controller
{
    /**
     * GET /api/seekers/filter
     *
     * Country-scoped seeker list with the same filter surface as the admin view,
     * but restricted to the country carried by X-Country-Code.
     */
    public function index(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        $search            = $request->get('search', '');
        $categoryId        = $request->get('job_category_id');
        $industryId        = $request->get('industry_id');
        $jobTypeId         = $request->get('job_type_id');
        $locationId        = $request->get('job_location_id');
        $experienceLevelId = $request->get('experience_level_id');
        $educationLevelId  = $request->get('education_level_id');
        $salaryRangeId     = $request->get('salary_range_id');
        $minExperience     = $request->get('min_experience');
        $hasCv             = $request->get('has_cv');           // '1' | ''
        $profileComplete   = $request->get('profile_complete'); // '1' | '0' | ''
        $page              = (int) $request->get('page', 1);
        $perPage           = min((int) $request->get('per_page', 15), 50);

        $query = SeekerProfile::with([
            'user',
            'jobCategory:id,name',
            'industry:id,name',
            'jobType:id,name',
            'jobLocation:id,district,city,country_code',
            'experienceLevel:id,name,min_years,max_years',
            'educationLevel:id,name',
            'salaryRange:id,name,currency,min_salary,max_salary',
        ])
        ->where('is_active', true);
        // ->where('is_public', true);

        $this->applyCountryScope($query, $countryCode);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('professional_title', 'like', "%{$search}%")
                  ->orWhere('professional_summary', 'like', "%{$search}%")
                  ->orWhere('skills', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (!empty($categoryId))        $query->where('job_category_id', $categoryId);
        if (!empty($industryId))        $query->where('industry_id', $industryId);
        if (!empty($jobTypeId))         $query->where('job_type_id', $jobTypeId);
        if (!empty($locationId))        $query->where('job_location_id', $locationId);
        if (!empty($experienceLevelId)) $query->where('experience_level_id', $experienceLevelId);
        if (!empty($educationLevelId))  $query->where('education_level_id', $educationLevelId);
        if (!empty($salaryRangeId))     $query->where('salary_range_id', $salaryRangeId);

        if ($minExperience !== null && $minExperience !== '') {
            $query->where('years_of_experience', '>=', (int) $minExperience);
        }

        if ($hasCv === '1') {
            $query->where(function ($q) {
                $q->whereNotNull('cv_file_path')->orWhereNotNull('cv_files');
            });
        }

        if ($profileComplete === '1') {
            $query->whereNotNull('job_category_id')
                  ->whereNotNull('industry_id')
                  ->whereNotNull('job_location_id')
                  ->whereNotNull('experience_level_id')
                  ->whereNotNull('education_level_id')
                  ->whereNotNull('professional_title')
                  ->whereNotNull('skills');
        }

        $seekers = $query->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $seekers->getCollection()->transform(fn($s) => $this->formatSeeker($s));

        return response()->json([
            'success' => true,
            'data'    => $seekers->items(),
            'meta'    => [
                'current_page' => $seekers->currentPage(),
                'last_page'    => $seekers->lastPage(),
                'per_page'     => $seekers->perPage(),
                'total'        => $seekers->total(),
                'from'         => $seekers->firstItem(),
                'to'           => $seekers->lastItem(),
                'country_code' => $countryCode,
            ],
        ]);
    }

    /**
     * GET /api/seekers/{id}
     * Returns the formatted seeker array (JSON, not HTML — the proxy renders it).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        $query = SeekerProfile::with([
            'user',
            'jobCategory:id,name',
            'industry:id,name',
            'jobType:id,name',
            'jobLocation:id,district,city,country_code',
            'experienceLevel:id,name,min_years,max_years',
            'educationLevel:id,name',
            'salaryRange:id,name,currency,min_salary,max_salary',
        ])
        ->where('is_active', true);
        // ->where('is_public', true);

        $this->applyCountryScope($query, $countryCode);

        $seeker = $query->find($id);

        if (!$seeker) {
            return response()->json(['success' => false, 'message' => 'Seeker not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatSeeker($seeker, true),
        ]);
    }

    /**
     * GET /api/seekers/{id}/cv?file=path
     * Streams a CV file (or the primary one when file is omitted).
     */
    public function viewCv(Request $request, int $id)
    {
        \Log::info($request->all());
        $seeker = SeekerProfile::findOrFail($id);

        // Preferred: explicit file from the query string
        $filePath = $request->get('file');

        if ($filePath) {
            $files = $this->cvFilesArray($seeker);
            $found = collect($files)->firstWhere('path', $filePath);

            if (!$found || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'Requested CV file not found');
            }

            return response()->file(Storage::disk('public')->path($filePath));
        }

        // Fallback 1: legacy single path column
        if ($seeker->cv_file_path && Storage::disk('public')->exists($seeker->cv_file_path)) {
            return response()->file(Storage::disk('public')->path($seeker->cv_file_path));
        }

        // Fallback 2: first entry in the cv_files array
        $first = $this->cvFilesArray($seeker)[0]['path'] ?? null;
        if ($first && Storage::disk('public')->exists($first)) {
            return response()->file(Storage::disk('public')->path($first));
        }

        abort(404, 'No CV available for this seeker');
    }

    // ─────────────────────────────────────────────────────────────
    // Country scope
    // ─────────────────────────────────────────────────────────────

    /**
     * Apply the country filter to any SeekerProfile query.
     *
     * Seeker profiles are inconsistent about how they store the country:
     *   - preferred_country_code  -> usually a code ("UG")
     *   - country                 -> historically a NAME ("Uganda")
     *   - users.country_code      -> usually a code ("UG")
     *
     * To stay correct against legacy rows, resolve the request's country
     * code to its name once and match against both representations.
     */
    protected function applyCountryScope(Builder $query, string $countryCode): Builder
    {
        $country = Country::where('code', $countryCode)->first(['code', 'name']);
        $countryName = $country?->name;

        return $query->where(function ($q) use ($countryCode, $countryName) {
            // Preferred: canonical code column
            $q->where('preferred_country_code', $countryCode);

            // Legacy: some rows store the code in the `country` column
            $q->orWhere('country', $countryCode);

            // Legacy: most rows store the NAME in the `country` column
            if ($countryName) {
                $q->orWhere('country', $countryName);
            }

            // Fallback: match on the owning user's country_code
            $q->orWhereHas('user', fn($uq) => $uq->where('country_code', $countryCode));
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Formatting
    // ─────────────────────────────────────────────────────────────

    /**
     * Format for the employer-facing endpoints.
     * $detailed = false -> table row shape
     * $detailed = true  -> detail modal shape (adds arrays, CV file URLs)
     */
    private function formatSeeker(SeekerProfile $seeker, bool $detailed = false): array
    {
        $user = $seeker->user;

        $fullName = trim(($seeker->first_name ?? '') . ' ' . ($seeker->last_name ?? ''));
        if ($fullName === '' && $user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Unknown';
        }

        $cvFiles = $this->cvFilesArray($seeker);
        $cvCount = count($cvFiles);
        $hasCv   = (bool) $seeker->cv_file_path || $cvCount > 0;

        $cvFilesWithUrls = array_map(function ($f) {
            if (!empty($f['path'])) {
                $f['url'] = Storage::disk('public')->url($f['path']);
            }
            return $f;
        }, $cvFiles);

        // Normalize the country representation regardless of what the DB holds
        [$displayCode, $displayName, $displayFlag] = $this->resolveCountryDisplay($seeker);

        $base = [
            'id'                   => $seeker->id,
            'avatar'               => $user?->avatar_url ?? asset('assets/media/avatars/blank.png'),
            'full_name'            => $fullName,
            'professional_title'   => $seeker->professional_title,
            'city'                 => $seeker->city,
            'country'              => $displayName ?? $displayCode,
            'country_code'         => $displayCode,
            'flag'                 => $displayFlag,
            'years_of_experience'  => (int) ($seeker->years_of_experience ?? 0),

            'job_category_id'      => $seeker->job_category_id,
            'job_category'         => $seeker->jobCategory?->name,
            'industry_id'          => $seeker->industry_id,
            'industry'             => $seeker->industry?->name,
            'job_type_id'          => $seeker->job_type_id,
            'job_type'             => $seeker->jobType?->name,
            'job_location_id'      => $seeker->job_location_id,
            'job_location'         => $seeker->jobLocation
                                        ? ($seeker->jobLocation->city
                                            ? "{$seeker->jobLocation->district} ({$seeker->jobLocation->city})"
                                            : $seeker->jobLocation->district)
                                        : null,
            'experience_level_id'  => $seeker->experience_level_id,
            'experience_level'     => $seeker->experienceLevel?->name,
            'education_level_id'   => $seeker->education_level_id,
            'education_level'      => $seeker->educationLevel?->name,
            'salary_range_id'      => $seeker->salary_range_id,
            'salary_range'         => $seeker->salaryRange?->name,

            'profile_complete'     => (bool) $seeker->is_profile_complete,
            'has_cv'               => $hasCv,
            'cv_count'             => $cvCount,
            'updated_at'           => $seeker->updated_at?->toISOString(),
        ];

        if (!$detailed) {
            return $base;
        }

        return array_merge($base, [
            'email'                => $seeker->email ?? $user?->email,
            'phone'                => $seeker->phone ?? $user?->phone,
            'professional_summary' => $seeker->professional_summary,
            'skills'               => $seeker->skills_array,
            'languages'            => $seeker->languages_array,
            'certifications'       => $seeker->certifications_array,
            'education'            => $seeker->education_array,
            'work_experience'      => $seeker->work_experience_array,
            'projects'             => $seeker->projects_array,
            'linkedin_url'         => $seeker->linkedin_url,
            'github_url'           => $seeker->github_url,
            'portfolio_url'        => $seeker->portfolio_url,
            'cv_files'             => $cvFilesWithUrls,
            'cv_file_path'         => $seeker->cv_file_path,
        ]);
    }

    /**
     * Figure out what country a seeker belongs to and return
     * [code, name, flag] — any of which may be null if unresolvable.
     *
     * Handles these cases:
     *   - preferred_country_code is a code           -> look up by code
     *   - country column is a code                   -> look up by code
     *   - country column is a name                   -> look up by name
     *   - fall back to user.country_code             -> look up by code
     */
    private function resolveCountryDisplay(SeekerProfile $seeker): array
    {
        $candidates = [
            $seeker->preferred_country_code,
            $seeker->country,
            $seeker->user?->country_code,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) continue;

            $candidate = trim((string) $candidate);

            // Try as a code first (2-3 chars, uppercase)
            if (strlen($candidate) <= 3) {
                $country = Country::where('code', strtoupper($candidate))->first(['code', 'name', 'flag']);
            } else {
                $country = Country::where('name', $candidate)->first(['code', 'name', 'flag']);
            }

            if ($country) {
                return [$country->code, $country->name, $country->flag];
            }
        }

        // Nothing matched — return whatever the profile has, no flag
        return [
            $seeker->preferred_country_code ?? $seeker->country,
            $seeker->country,
            null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function cvFilesArray(SeekerProfile $seeker): array
    {
        $files = $seeker->cv_files;
        if (is_null($files))   return [];
        if (is_string($files)) {
            $decoded = json_decode($files, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($files) ? $files : [];
    }
}