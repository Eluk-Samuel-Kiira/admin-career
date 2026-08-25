<?php

namespace App\Http\Controllers\Job\JobPosting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Job\JobIndex\JobPostRequest;
use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Models\Job\JobCategory;
use App\Models\Job\Industry;
use App\Models\Job\JobLocation;
use App\Models\Job\JobType;
use App\Models\Job\ExperienceLevel;
use App\Models\Job\EducationLevel;
use App\Models\Job\SalaryRange;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JobPostController extends Controller
{
    /**
     * Normalize a contact field (email or phone) for duplicate detection.
     * Returns an empty string if the input is null or empty after normalization.
     */
    private function normalizeContactField(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', '', $value);
        
        // If it looks like a phone number, strip everything except digits and '+'
        if (preg_match('/^\+?[0-9]/', $value)) {
            $value = preg_replace('/[^0-9+]/', '', $value);
        }
        
        return trim($value);
    }

    /**
     * Simplified duplicate detection for fresh (non-migrated) admin-created posts.
     * legacy_id is intentionally never referenced here - this only guards new
     * organic submissions, so there's nothing to inherit from a legacy record.
     */
    private function findDuplicateJob(array $data): ?JobPost
    {
        $jobTitle = $data['job_title'] ?? null;
        $companyId = $data['company_id'] ?? null;
        if (!$jobTitle || !$companyId) {
            return null;
        }

        $locationId = $data['job_location_id'] ?? null;
        $email = $data['email'] ?? null;
        $telephone = $data['telephone'] ?? null;

        $candidates = JobPost::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(13))
            ->get(['id', 'job_title', 'job_location_id', 'email', 'telephone', 'created_at']);

        // Normalize the input values (handles nulls safely)
        $normalizedEmail = $this->normalizeContactField($email);
        $normalizedPhone = $this->normalizeContactField($telephone);
        $hasEmail = !empty($normalizedEmail);
        $hasPhone = !empty($normalizedPhone);

        foreach ($candidates as $existing) {
            // Check title match
            $titleMatch = strcasecmp(trim($jobTitle), trim($existing->job_title)) === 0;
            if (!$titleMatch) {
                continue;
            }

            // Check location match (or both null)
            $locationMatch = false;
            if ($locationId && $existing->job_location_id) {
                $locationMatch = $locationId == $existing->job_location_id;
            } elseif (!$locationId && !$existing->job_location_id) {
                $locationMatch = true;
            }

            if (!$locationMatch) {
                continue;
            }

            // Normalize existing values (handles nulls safely)
            $existingEmail = $this->normalizeContactField($existing->email);
            $existingPhone = $this->normalizeContactField($existing->telephone);
            $hasExistingEmail = !empty($existingEmail);
            $hasExistingPhone = !empty($existingPhone);

            // Check contact matches
            $emailMatch = $hasEmail && $hasExistingEmail && $normalizedEmail === $existingEmail;
            $phoneMatch = $hasPhone && $hasExistingPhone && $normalizedPhone === $existingPhone;

            // --- DUPLICATE CONDITIONS ---

            // Case 1: Both have NO contact info → DUPLICATE
            if (!$hasEmail && !$hasPhone && !$hasExistingEmail && !$hasExistingPhone) {
                return $existing;
            }

            // Case 2: Both have contact info and at least one matches → DUPLICATE
            if ($hasEmail && $hasExistingEmail && $emailMatch) {
                return $existing;
            }
            
            if ($hasPhone && $hasExistingPhone && $phoneMatch) {
                return $existing;
            }

            // Case 3: One has contact info, the other doesn't → NOT a duplicate
            // (This preserves the job with more information)
        }

        return null;
    }

    /**
     * Generate a unique SEO-friendly slug with country code
     */
    private function generateSlug(string $title, ?int $companyId = null, ?int $locationId = null): string
    {
        $cleanTitle = preg_replace('/[^\w\s-]/', '', $title);
        $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));
        $cleanTitle = Str::slug($cleanTitle);

        $slugParts = [$cleanTitle];

        if (!preg_match('/\b(job|position|career|opportunity|vacancy)\b/i', $cleanTitle)) {
            $slugParts[] = 'job';
        }

        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->name) {
                $companyName = preg_replace('/[^\w\s-]/', '', $company->name);
                $companyName = Str::slug($companyName);
                $slugParts[] = 'at';
                $slugParts[] = $companyName;
            }
        }

        if ($locationId) {
            $location = JobLocation::find($locationId);
            if ($location) {
                $slugParts[] = 'in';

                if ($location->district) {
                    $districtName = preg_replace('/[^\w\s-]/', '', $location->district);
                    $districtName = Str::slug($districtName);
                    $slugParts[] = $districtName;
                }

                if ($location->country) {
                    $slugParts[] = strtolower($location->country);
                }
            }
        }

        $slug = implode('-', $slugParts);
        $originalSlug = $slug;

        $attempts = 0;
        while (JobPost::where('slug', $slug)->exists() && $attempts < 10) {
            $slug = $originalSlug . '-' . random_int(100000, 999999);
            $attempts++;
        }

        if (JobPost::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . uniqid();
        }

        return $slug;
    }

    /**
     * Generate dynamic SEO-optimized meta description
     */
    private function generateDynamicMetaDescription(array $validated, $company, $location): string
    {
        $description = '';
        
        $description .= "{$validated['job_title']} position";
        
        if ($company && $company->name) {
            $description .= " at {$company->name}";
        }
        
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $description .= " in {$locationName}";
            }
        }
        
        $description .= ". ";
        
        $highlights = [];
        
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $currency = $validated['currency'] ?? 'UGX';
            $period = $validated['payment_period'] ?? 'monthly';
            $periodMap = ['hourly' => 'hour', 'daily' => 'day', 'weekly' => 'week', 'monthly' => 'month', 'yearly' => 'year'];
            $highlights[] = "{$currency} {$salary} per {$periodMap[$period]}";
        }
        
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => 'Full-time position',
                'part-time' => 'Part-time opportunity',
                'contract' => 'Contract role',
                'internship' => 'Internship opportunity',
                'volunteer' => 'Volunteer position',
                'temporary' => 'Temporary role'
            ];
            $highlights[] = $typeMap[$validated['employment_type']] ?? $validated['employment_type'];
        }
        
        if (!empty($validated['location_type']) && $validated['location_type'] === 'remote') {
            $highlights[] = 'Work from home';
        } elseif (!empty($validated['location_type']) && $validated['location_type'] === 'hybrid') {
            $highlights[] = 'Hybrid work model';
        }
        
        if (!empty($validated['is_urgent'])) {
            $highlights[] = 'Urgent hiring';
        }
        
        if (!empty($validated['is_featured'])) {
            $highlights[] = 'Featured job';
        }
        
        if (!empty($validated['experience_level_id'])) {
            $experience = ExperienceLevel::find($validated['experience_level_id']);
            if ($experience) {
                $highlights[] = $experience->name . ' level';
            }
        }
        
        if (!empty($validated['education_level_id'])) {
            $education = EducationLevel::find($validated['education_level_id']);
            if ($education) {
                $highlights[] = $education->name . ' required';
            }
        }
        
        if (!empty($highlights)) {
            $description .= implode(' • ', $highlights) . ". ";
        }
        
        $deadline = !empty($validated['deadline']) ? \Carbon\Carbon::parse($validated['deadline'])->format('M d, Y') : 'soon';
        $description .= "Apply now before {$deadline}. ";
        $description .= "Find the best jobs on Stardena Works. ";
        
        if (!empty($validated['is_simple_job'])) {
            $description .= "Quick application process. ";
        }
        
        if (!empty($validated['is_quick_gig'])) {
            $description .= "Short-term opportunity. ";
        }
        
        $words = str_word_count($description, 1);
        if (count($words) > 200) {
            $truncated = array_slice($words, 0, 200);
            $description = implode(' ', $truncated) . '...';
        }
        
        return Str::limit($description, 300);
    }

    /**
     * Generate dynamic SEO-optimized meta title
     */
    private function generateDynamicMetaTitle(array $validated, $company, $location): string
    {
        $title = $validated['job_title'];
        $parts = [];
        
        $parts[] = $title;
        
        if ($company && $company->name) {
            $parts[] = "at {$company->name}";
        }
        
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $parts[] = "in {$locationName}";
            }
        }
        
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $currency = $validated['currency'] ?? 'UGX';
            $period = $validated['payment_period'] ?? 'monthly';
            
            $periodMap = [
                'hourly' => '/hr',
                'daily' => '/day', 
                'weekly' => '/week',
                'monthly' => '/month',
                'yearly' => '/year'
            ];
            
            $suffix = $periodMap[$period] ?? '';
            $parts[] = "{$currency} {$salary}{$suffix}";
        }
        
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => 'Full Time',
                'part-time' => 'Part Time',
                'contract' => 'Contract',
                'internship' => 'Internship',
                'volunteer' => 'Volunteer',
                'temporary' => 'Temporary'
            ];
            $parts[] = $typeMap[$validated['employment_type']] ?? $validated['employment_type'];
        }
        
        if (!empty($validated['is_urgent']) || !empty($validated['is_featured'])) {
            $parts[] = 'Hiring Now';
        }
        
        if (!empty($validated['location_type']) && $validated['location_type'] === 'remote') {
            $parts[] = 'Remote';
        }
        
        $parts[] = now()->year;
        
        $metaTitle = implode(' | ', $parts);
        
        return Str::limit($metaTitle, 60);
    }

    /**
     * Generate dynamic SEO keywords
     */
    private function generateDynamicKeywords(array $validated, $company, $location): string
    {
        $keywords = [];
        
        $keywords[] = $validated['job_title'];
        $keywords[] = $validated['job_title'] . ' jobs';
        $keywords[] = $validated['job_title'] . ' vacancy';
        
        if ($company && $company->name) {
            $keywords[] = $company->name;
            $keywords[] = $company->name . ' careers';
        }
        
        if ($location) {
            $locationName = $location->district ?? $location->country ?? '';
            if ($locationName) {
                $keywords[] = "jobs in {$locationName}";
                $keywords[] = "{$locationName} careers";
            }
        }
        
        if (!empty($validated['employment_type'])) {
            $typeMap = [
                'full-time' => ['full time', 'full-time jobs', 'permanent jobs'],
                'part-time' => ['part time', 'part-time jobs', 'flexible hours'],
                'contract' => ['contract jobs', 'contract work', 'temporary contract'],
                'internship' => ['internship', 'intern', 'graduate program'],
                'volunteer' => ['volunteer', 'volunteering', 'community service'],
                'temporary' => ['temp jobs', 'temporary work', 'short term']
            ];
            
            if (isset($typeMap[$validated['employment_type']])) {
                $keywords = array_merge($keywords, $typeMap[$validated['employment_type']]);
            }
        }
        
        if (!empty($validated['location_type'])) {
            $keywords[] = $validated['location_type'] . ' jobs';
            if ($validated['location_type'] === 'remote') {
                $keywords[] = 'work from home';
                $keywords[] = 'remote work';
            }
        }
        
        if (!empty($validated['salary_amount'])) {
            $salary = number_format($validated['salary_amount']);
            $keywords[] = "{$salary} salary";
            $keywords[] = "jobs paying {$salary}";
        }
        
        if (!empty($validated['is_urgent'])) {
            $keywords[] = 'urgent hiring';
            $keywords[] = 'immediate hiring';
            $keywords[] = 'apply now';
        }
        
        $keywords[] = 'Stardena Works';
        $keywords[] = 'job portal';
        $keywords[] = 'career opportunities';
        
        if ($location && $location->country) {
            $keywords[] = "jobs in {$location->country}";
            $keywords[] = "{$location->country} careers";
        }
        
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 15);
        
        return implode(', ', $keywords);
    }

    public function store(JobPostRequest $request)
    {
        if (!auth()->user()->can('create jobs')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create jobs.'
            ]);
        }

        try {
            $data = $request->validatedWithDefaults();

            // Duplicate guard - last 13 days, same company/title/location + (email or phone).
            if (empty($data['force_post'])) {
                $duplicate = $this->findDuplicateJob($data);
                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'duplicate' => "A similar job '{$duplicate->job_title}' was already posted "
                            . $duplicate->created_at->diffForHumans() . " for this company. "
                            . "Please check for existing jobs before posting a duplicate."
                    ]);
                }
            }
            unset($data['force_post']);

            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug(
                    $data['job_title'],
                    $data['company_id'] ?? null,
                    $data['job_location_id'] ?? null
                );
            }

            if (empty($data['poster_id'])) {
                $data['poster_id'] = auth()->id();
            }

            $company = !empty($data['company_id']) ? Company::find($data['company_id']) : null;
            $location = !empty($data['job_location_id']) ? JobLocation::find($data['job_location_id']) : null;

            if (empty($data['currency']) && $location && $location->country) {
                $countryCode = strtoupper(trim($location->country));
                if (method_exists(JobPost::class, 'resolveCurrencyFromCode')) {
                    $data['currency'] = JobPost::resolveCurrencyFromCode($countryCode);
                }
            }

            if (empty($data['meta_title'])) {
                $data['meta_title'] = $this->generateDynamicMetaTitle($data, $company, $location);
            }

            if (empty($data['meta_description'])) {
                $data['meta_description'] = $this->generateDynamicMetaDescription($data, $company, $location);
            }

            if (empty($data['keywords'])) {
                $data['keywords'] = $this->generateDynamicKeywords($data, $company, $location);
            }

            $jobPost = JobPost::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Job post created successfully!',
                'data' => $jobPost->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to create job post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create job post: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(JobPostRequest $request, $id)
    {
        
        if (!auth()->user()->can('edit jobs')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit jobs.'
            ]);
        }

        try {
            $jobPost = JobPost::findOrFail($id);
            $data = $request->validatedWithDefaults();
            
            // Generate new slug if title changed and slug not provided
            if (isset($data['job_title']) && $jobPost->job_title !== $data['job_title'] && empty($data['slug'])) {
                $data['slug'] = $this->generateSlug(
                    $data['job_title'],
                    $data['company_id'] ?? $jobPost->company_id,
                    $data['job_location_id'] ?? $jobPost->job_location_id
                );
            } elseif (empty($data['slug'])) {
                // Keep existing slug if no new slug provided
                $data['slug'] = $jobPost->slug;
            }

            // If this is a legacy migration and not already migrated
            if (!empty($data['legacy_id']) && empty($jobPost->migrated_at)) {
                $data['migrated_at'] = now();
            }

            // Look up company and location for SEO generation
            $companyId = $data['company_id'] ?? $jobPost->company_id;
            $locationId = $data['job_location_id'] ?? $jobPost->job_location_id;
            
            $company = !empty($companyId) ? Company::find($companyId) : null;
            $location = !empty($locationId) ? JobLocation::find($locationId) : null;

            // Currency resolution if not set
            if (empty($data['currency']) && $location && $location->country) {
                $countryCode = strtoupper(trim($location->country));
                if (method_exists(JobPost::class, 'resolveCurrencyFromCode')) {
                    $data['currency'] = JobPost::resolveCurrencyFromCode($countryCode);
                }
            }

            // Update SEO metadata - only if the user left them blank
            // For update, we check if the field is being sent and is empty, or if it wasn't sent at all
            if (($request->has('meta_title') && empty($data['meta_title'])) || !$request->has('meta_title')) {
                $data['meta_title'] = $this->generateDynamicMetaTitle($data, $company, $location);
            }

            if (($request->has('meta_description') && empty($data['meta_description'])) || !$request->has('meta_description')) {
                $data['meta_description'] = $this->generateDynamicMetaDescription($data, $company, $location);
            }

            if (($request->has('keywords') && empty($data['keywords'])) || !$request->has('keywords')) {
                $data['keywords'] = $this->generateDynamicKeywords($data, $company, $location);
            }

            $jobPost->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Job post updated successfully!',
                'data' => $jobPost->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to update job post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update job post: ' . $e->getMessage()
            ], 500);
        }
    }

}