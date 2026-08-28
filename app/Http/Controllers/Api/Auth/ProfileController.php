<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, Mail, Log, Storage, DB, Hash};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{

    /**
     * API: Get authenticated user
     * GET /api/auth/user
     */
    public function userApi(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Load relationships
        $user->load(['seekerProfile', 'employerProfile']);

        return response()->json([
            'success' => true,
            'user' => [
                'id'           => $user->id,
                'uuid'         => $user->uuid,
                'email'        => $user->email,
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'full_name'    => $user->full_name,
                'phone'        => $user->phone,
                'role'         => $user->getRoleNameAttribute(),
                'role_id'      => $user->role_id,
                'country_code' => $user->country_code,
                'avatar'       => $user->avatar_url,
                'bio'          => $user->bio,
                'is_active'    => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'profile_completion' => $user->profile_completion,
                
                // ── Seeker Profile Fields ──
                'professional_title' => $user->seekerProfile?->professional_title,
                'years_of_experience' => $user->seekerProfile?->years_of_experience,
                'skills' => $user->seekerProfile?->skills,
                'address' => $user->seekerProfile?->address,
                'city' => $user->seekerProfile?->city,
                'country' => $user->seekerProfile?->country,
                'postal_code' => $user->seekerProfile?->postal_code,
                'date_of_birth' => $user->seekerProfile?->date_of_birth,
                'nationality' => $user->seekerProfile?->nationality,
                'professional_summary' => $user->seekerProfile?->professional_summary,
                'linkedin_url' => $user->seekerProfile?->linkedin_url,
                'github_url' => $user->seekerProfile?->github_url,
                'portfolio_url' => $user->seekerProfile?->portfolio_url,
                'languages' => $user->seekerProfile?->languages,
                'certifications' => $user->seekerProfile?->certifications,
                'education' => $user->seekerProfile?->education,
                'work_experience' => $user->seekerProfile?->work_experience,
                'projects' => $user->seekerProfile?->projects,
                'cv_file_path' => $user->seekerProfile?->cv_file_path,
                'cv_original_name' => $user->seekerProfile?->cv_original_name,
                'job_preferences' => $user->seekerProfile?->job_preferences,
                'is_public' => $user->seekerProfile?->is_public,
                
                // ── Employer Profile Fields ──
                'company_name' => $user->employerProfile?->company_name,
                'company_logo' => $user->employerProfile?->company_logo,
                'company_website' => $user->employerProfile?->company_website,
                'company_description' => $user->employerProfile?->company_description,
                'company_size' => $user->employerProfile?->company_size,
                'industry' => $user->employerProfile?->industry,
                'contact_name' => $user->employerProfile?->contact_name,
                'contact_position' => $user->employerProfile?->contact_position,
                'contact_phone' => $user->employerProfile?->contact_phone,
                'contact_email' => $user->employerProfile?->contact_email,
            ]
        ]);
    }

    /**
     * API: Update user profile
     * PUT /api/auth/user/update
     */
    public function updateUserApi(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'country_code' => 'nullable|string|max:3',
            'bio' => 'nullable|string|max:500',
            'professional_title' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|integer|min:0',
            'skills' => 'nullable|array',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',

            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'professional_summary' => 'nullable|string|max:2000',
            'languages' => 'nullable|array',
            'certifications' => 'nullable|array',
            'education' => 'nullable|array',
            'work_experience' => 'nullable|array',
            'projects' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // ── 1. Update User table ──
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? $user->phone,
                'country_code' => $validated['country_code'] ?? $user->country_code,
                'bio' => $validated['bio'] ?? $user->bio,
            ]);

            // ── 2. Update Seeker Profile (if user is a job seeker) ──
            if ($user->seekerProfile) {
                $seekerData = [];
                
                // Only update fields that are sent in the request
                if ($request->has('professional_title')) {
                    $seekerData['professional_title'] = $validated['professional_title'];
                }
                if ($request->has('years_of_experience')) {
                    $seekerData['years_of_experience'] = $validated['years_of_experience'];
                }
                if ($request->has('skills')) {
                    $seekerData['skills'] = $validated['skills'];
                }
                if ($request->has('address')) {
                    $seekerData['address'] = $validated['address'];
                }
                if ($request->has('city')) {
                    $seekerData['city'] = $validated['city'];
                }
                if ($request->has('linkedin_url')) {
                    $seekerData['linkedin_url'] = $validated['linkedin_url'];
                }
                if ($request->has('github_url')) {
                    $seekerData['github_url'] = $validated['github_url'];
                }
                if ($request->has('portfolio_url')) {
                    $seekerData['portfolio_url'] = $validated['portfolio_url'];
                }

                if (!empty($seekerData)) {
                    $user->seekerProfile->update($seekerData);
                }
            }

            // ── 3. Update Employer Profile (if user is an employer) ──
            if ($user->employerProfile) {
                $employerData = [];
                
                // Only update address and city for employer
                if ($request->has('address')) {
                    $employerData['address'] = $validated['address'];
                }
                if ($request->has('city')) {
                    $employerData['city'] = $validated['city'];
                }

                if (!empty($employerData)) {
                    $user->employerProfile->update($employerData);
                }
            }

            DB::commit();

            // Log::info('User profile updated via API', ['user_id' => $user->id]);

            // Reload user with relationships
            $user->refresh();
            $user->load(['seekerProfile', 'employerProfile']);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id'           => $user->id,
                    'first_name'   => $user->first_name,
                    'last_name'    => $user->last_name,
                    'email'        => $user->email,
                    'phone'        => $user->phone,
                    'country_code' => $user->country_code,
                    'avatar'       => $user->avatar_url,
                    'bio'          => $user->bio,
                    'role'         => $user->getRoleNameAttribute(),
                    'professional_title' => $user->seekerProfile?->professional_title,
                    'years_of_experience' => $user->seekerProfile?->years_of_experience,
                
                    'skills' => $user->seekerProfile?->skills,
                    'address' => $user->seekerProfile?->address ?? $user->employerProfile?->address,
                    'city' => $user->seekerProfile?->city ?? $user->employerProfile?->city,
                    'linkedin_url' => $user->seekerProfile?->linkedin_url,
                    'github_url' => $user->seekerProfile?->github_url,
                    'portfolio_url' => $user->seekerProfile?->portfolio_url,
                    'company_name' => $user->employerProfile?->company_name,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Update user avatar
     * POST /api/auth/user/avatar
     */
    public function updateAvatarApi(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        try {
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar) {
                    $oldPath = str_replace('/storage/', '', $user->avatar);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = '/storage/' . $avatarPath;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'avatar' => $user->avatar_url,
                'user' => [
                    'id' => $user->id,
                    'avatar' => $user->avatar_url,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Avatar update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update avatar: ' . $e->getMessage()
            ], 500);
        }
    }

}