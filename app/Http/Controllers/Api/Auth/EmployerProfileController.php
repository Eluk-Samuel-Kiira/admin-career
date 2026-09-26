<?php
namespace App\Http\Controllers\Api\Auth;


use App\Http\Controllers\Controller;
use App\Models\EmployerProfile;
use App\Models\Job\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployerProfileController extends Controller
{
    /**
     * Return the authenticated employer's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Employer profile not found. Please contact support.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'profile' => $this->formatProfile($profile),
        ]);
    }

    /**
     * Update the employer's company profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            // Identity
            'company_name'         => 'sometimes|string|max:255',
            'legal_name'           => 'nullable|string|max:255',
            'trading_name'         => 'nullable|string|max:255',
            'registration_number'  => 'nullable|string|max:100',

            // Company details
            'industry'             => 'nullable|string|max:100',
            'company_size'         => 'nullable|string|max:50',
            'company_website'      => 'nullable|url|max:255',
            'company_description'  => 'nullable|string|max:5000',
            'year_founded'         => 'nullable|integer|min:1900|max:' . date('Y'),
            'company_email'        => 'nullable|email|max:255',
            'company_phone'        => 'nullable|string|max:30',
            'linkedin_url'         => 'nullable|url|max:255',
            'facebook_url'         => 'nullable|url|max:255',
            'twitter_url'          => 'nullable|url|max:255',

            // Contact person
            'contact_name'         => 'nullable|string|max:255',
            'contact_position'     => 'nullable|string|max:100',
            'contact_email'        => 'nullable|email|max:255',
            'contact_phone'        => 'nullable|string|max:30',

            // Location
            'address'              => 'nullable|string|max:255',
            'city'                 => 'nullable|string|max:100',
            'state'                => 'nullable|string|max:100',
            'postal_code'          => 'nullable|string|max:20',
            'country_code'         => 'nullable|string|size:2',
        ]);

        try {
            $profile->update($data);

            if ($profile->company_id) {
                $profile->company->update([
                    'name'           => $profile->company_name,
                    'company_size'   => $profile->company_size,
                    'website'        => $profile->company_website,
                    'description'    => $profile->company_description,
                    'contact_name'   => $profile->contact_name,
                    'contact_email'  => $profile->contact_email,
                    'contact_phone'  => $profile->contact_phone,
                    'address1'       => $profile->address,
                    'city'           => $profile->city,
                    'country_code'   => $profile->country_code,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company profile updated.',
                'profile' => $this->formatProfile($profile->fresh()),
            ]);

        } catch (\Throwable $e) {
            Log::error('Employer profile update failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload or replace the company logo.
     * Stores it on the linked Company record, using the same path convention as admin.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = \App\Models\EmployerProfile::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // ── 1. Get or create the linked Company ────────────────────
        $company = $profile->company;

        if (!$company) {
            $companyName = $profile->company_name ?: 'Unnamed Company';
            $country     = $profile->country_code ?: 'UG';

            // Reuse an existing company with the same name if it exists
            $company = \App\Models\Job\Company::firstOrCreate(
                [
                    'name'         => $companyName,
                    'country_code' => $country,
                ],
                [
                    'slug'         => \Illuminate\Support\Str::slug($companyName) . '-' . strtolower(\Illuminate\Support\Str::random(5)),
                    'company_size' => $profile->company_size,
                    'website'      => $profile->company_website,
                    'description'  => $profile->company_description,
                    'contact_name' => $profile->contact_name,
                    'contact_email'=> $profile->contact_email,
                    'contact_phone'=> $profile->contact_phone,
                    'is_active'    => true,
                    'is_verified'  => false,
                    'is_gold'      => false,
                    'is_featured'  => false,
                    'created_by'   => $user->id,
                ]
            );

            // ✅ SAVE THE LINK BACK — bypass fillable by direct assignment
            $profile->company_id = $company->id;
            $profile->save();

            // Re-resolve the relation so the rest of the request sees it
            $profile->refresh();
            $profile->load('company');

            \Log::info('Company linked to employer profile', [
                'profile_id' => $profile->id,
                'company_id' => $company->id,
            ]);
        }

        // ── 2. Replace the logo ────────────────────────────────────
        try {
            $company = $profile->company;   // fresh resolve

            if (!$company) {
                throw new \Exception('Company still not linked after creation.');
            }

            $company->replaceLogo($request->file('logo'));

            return response()->json([
                'success'   => true,
                'message'   => 'Logo uploaded.',
                'logo_url'  => $company->fresh()->logo_url,
                'logo_path' => $company->fresh()->logo_path,
            ]);

        } catch (\Throwable $e) {
            \Log::error('Employer logo upload failed', [
                'user_id'    => $user->id,
                'profile_id' => $profile->id,
                'company_id' => $profile->company_id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the current logo.
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = EmployerProfile::where('user_id', $user->id)->firstOrFail();

        if (!$profile->company_id || !$profile->company) {
            return response()->json([
                'success'  => true,
                'message'  => 'No logo to remove.',
                'logo_url' => asset('assets/media/avatars/blank.png'),
            ]);
        }

        $profile->company->clearLogo();

        return response()->json([
            'success'  => true,
            'message'  => 'Logo removed.',
            'logo_url' => asset('assets/media/avatars/blank.png'),
        ]);
    }



    /**
     * Shape the profile for the API response.
     */
    private function formatProfile(EmployerProfile $p): array
    {
        return [
            // Identity
            'id'                    => $p->id,
            'company_name'          => $p->company_name,
            'legal_name'            => $p->legal_name,
            'trading_name'          => $p->trading_name,
            'registration_number'   => $p->registration_number,
            'company_logo'          => $p->company_logo,
            'logo_url'              => $p->logo_url,

            // Details
            'industry'              => $p->industry,
            'company_size'          => $p->company_size,
            'company_website'       => $p->company_website,
            'company_description'   => $p->company_description,
            'year_founded'          => $p->year_founded,
            'company_email'         => $p->company_email,
            'company_phone'         => $p->company_phone,
            'linkedin_url'          => $p->linkedin_url,
            'facebook_url'          => $p->facebook_url,
            'twitter_url'           => $p->twitter_url,

            // Contact person
            'contact_name'          => $p->contact_name,
            'contact_position'      => $p->contact_position,
            'contact_email'         => $p->contact_email,
            'contact_phone'         => $p->contact_phone,

            // Location
            'address'               => $p->address,
            'city'                  => $p->city,
            'state'                 => $p->state,
            'postal_code'           => $p->postal_code,
            'country_code'          => $p->country_code,
            'full_address'          => $p->full_address,

            // Status
            'is_verified'           => (bool) $p->is_verified,
            'is_active'             => (bool) $p->is_active,
            'compliance_status'     => $p->compliance_status,
            'compliance_progress'   => $p->compliance_progress,
            'missing_documents'     => $p->missing_documents,
            'onboarding_complete'   => (bool) $p->onboarding_complete,

            // Tier
            'tier'                  => $p->tier,
            'tier_label'            => $p->tier_label,
            'active_job_limit'      => $p->active_job_limit,
            'active_jobs_count'     => $p->active_jobs_count,
            'can_post_job'          => $p->can_post_job,
        ];
    }
}
