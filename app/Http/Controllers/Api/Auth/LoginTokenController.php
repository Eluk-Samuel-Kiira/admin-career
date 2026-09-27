<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\{LoginToken, EmployerProfile, SeekerProfile };
use App\Mail\Auth\WebMagicLoginLink;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, Mail, Log, Artisan, DB, Hash};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class LoginTokenController extends Controller
{
    /**
     * API: Register seeker / employer
     * POST /api/auth/register
     */
    public function registerApi(Request $request): JsonResponse
    {
        $accountType = $request->input('role', 'job_seeker') === 'employer' ? 'employer' : 'seeker';
        $isEmployer  = $accountType === 'employer';

        // ── Resolve names ───────────────────────────────────────────
        $firstName = $request->input('first_name');
        $lastName  = $request->input('last_name');

        if ($isEmployer && trim((string) $firstName) === '') {
            $contactName = trim((string) $request->input('contact_name', ''));
            if ($contactName !== '') {
                $parts     = preg_split('/\s+/', $contactName, 2);
                $firstName = $parts[0] ?? 'Company';
                $lastName  = $parts[1] ?? 'Owner';
            } else {
                $firstName = 'Company';
                $lastName  = 'Owner';
            }
        }

        // ── Validate ────────────────────────────────────────────────
        $rules = [
            'email'        => 'required|email|max:255|unique:users,email',
            'phone'        => 'nullable|string|max:25',
            'role'         => 'nullable|string|in:job_seeker,employer',
            'country_code' => 'nullable|string|max:3',
            'terms'        => 'required|accepted',
        ];

        if ($isEmployer) {
            $rules['company_name'] = 'required|string|max:255';
            $rules['contact_name'] = 'required|string|max:255';
            $rules['company_size'] = 'nullable|string|max:50';
        } else {
            $rules['first_name']    = 'required|string|max:255';
            $rules['last_name']     = 'required|string|max:255';
            $rules['desired_title'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules, [
            'email.unique'   => 'An account with this email already exists. Try logging in instead.',
            'terms.accepted' => 'You must accept the Terms of Service to continue.',
        ]);

        DB::beginTransaction();

        try {
            // ── 1. Role ─────────────────────────────────────────────
            $roleName = $isEmployer ? 'employer' : 'job_seeker';
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            // ── 2. User ─────────────────────────────────────────────
            $user = User::create([
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'name'              => trim("{$firstName} {$lastName}"),
                'email'             => $validated['email'],
                'phone'             => $validated['phone'] ?? null,
                'role_id'           => $role->id,
                'country_code'      => $validated['country_code'] ?? 'UG',
                'is_active'         => true,
                'email_verified_at' => now(),
                'uuid'              => (string) Str::uuid(),
                'password'          => Hash::make(Str::random(32)),
            ]);

            $user->syncRoles([$role->name]);

            // ── 3. Profile ──────────────────────────────────────────
            if ($isEmployer) {
                // ── 1. Find or create the Company record ────────────────
                $companyName = $request->input('company_name');
                $countryCode = $validated['country_code'] ?? 'UG';

                $company = \App\Models\Job\Company::where('name', $companyName)
                    ->where('country_code', $countryCode)
                    ->first();

                if (!$company) {
                    $company = \App\Models\Job\Company::create([
                        'name'         => $companyName,
                        'slug'         => \Illuminate\Support\Str::slug($companyName) . '-' . strtolower(\Illuminate\Support\Str::random(5)),
                        'country_code' => $countryCode,
                        'company_size' => $request->input('company_size'),
                        'is_active'    => true,
                        'is_verified'  => false,
                        'is_gold'      => false,
                        'is_featured'  => false,
                        'created_by'   => $user->id,   // whoever triggered creation
                    ]);
                }

                // ── 2. Create the EmployerProfile and link it ───────────
                \App\Models\EmployerProfile::create([
                    'user_id'             => $user->id,
                    'company_id'          => $company->id,   // ⬅️ the link
                    'company_name'        => $companyName,
                    'company_size'        => $request->input('company_size'),
                    'contact_name'        => $request->input('contact_name'),
                    'contact_email'       => $validated['email'],
                    'contact_phone'       => $validated['phone'] ?? null,
                    'country_code'        => $countryCode,
                    'is_active'           => true,
                    'is_verified'         => false,
                    'compliance_status'   => 'incomplete',
                    'onboarding_complete' => false,
                ]);
            } else {
                SeekerProfile::create([
                    'user_id'            => $user->id,
                    'first_name'         => $firstName,
                    'last_name'          => $lastName,
                    'email'              => $validated['email'],
                    'phone'              => $validated['phone'] ?? null,
                    'country'            => $validated['country_code'] ?? 'UG',
                    'professional_title' => $request->input('desired_title'),
                    'is_public'          => true,
                    'is_active'          => true,
                ]);
            }

            // ── 4. Magic link ───────────────────────────────────────
            $token     = Str::random(64);
            $expiresAt = now()->addHours(24);

            LoginToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
            ]);

            $user->update([
                'magic_link_token'       => $token,
                'magic_link_sent_at'     => now(),
                'magic_link_expires_at'  => $expiresAt,
            ]);

            // ── 5. Send magic link ──────────────────────────────────
            Mail::to($user->email)->send(new WebMagicLoginLink($user, $token, true));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully! Check your email for the magic link.',
                'user' => [
                    'id'         => $user->id,
                    'email'      => $user->email,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'role'       => $roleName,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Registration API failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create account. Please try again.',
                'errors'  => ['general' => [$e->getMessage()]],
            ], 500);
        }
    }

    /**
     * API: Send magic login link
     * POST /api/auth/send-login-link
     */
    public function sendLoginLinkApi(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address. Please register first.',
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is deactivated. Please contact support.',
            ], 403);
        }

        // Delete existing unused tokens
        LoginToken::where('user_id', $user->id)->whereNull('used_at')->delete();

        $token = Str::random(64);
        $expiresAt = now()->addHours(24);

        LoginToken::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ]);

        $user->update([
            'magic_link_token' => $token,
            'magic_link_sent_at' => now(),
            'magic_link_expires_at' => $expiresAt,
        ]);

        try {
            // Use the unified WebMagicLoginLink for both job seekers and employers
            Mail::to($user->email)->send(new WebMagicLoginLink($user, $token, false));

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent to your email!'
            ]);

        } catch (\Exception $e) {
            Log::error('Magic link send failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to send email right now. Please try again.',
            ], 500);
        }
    }

    /**
     * API: Verify magic link token
     * POST /api/auth/verify-token
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $loginToken = LoginToken::with('user')
            ->where('token', $request->token)
            ->first();

        if (!$loginToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.'
            ], 401);
        }

        if (!$loginToken->isValid()) {
            $reason = $loginToken->used_at ? 'already been used' : 'expired';
            $loginToken->delete();
            return response()->json([
                'success' => false,
                'message' => "This login link has {$reason}. Please request a new one."
            ], 401);
        }

        if (!$loginToken->user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.'
            ], 401);
        }

        $user = $loginToken->user;

        // Update user record
        $user->updateLastLogin();

        // Delete old Sanctum tokens
        $user->tokens()->delete();

        // Issue fresh Sanctum token
        $apiToken = $user->createToken(
            'web-session',
            ['*'],
            now()->addDays(30)
        )->plainTextToken;

        // Mark token as used
        $loginToken->markAsUsed();

        // Log::info('User verified via magic link API', [
        //     'user_id' => $user->id,
        //     'email'   => $user->email,
        //     'role'    => $user->getRoleNameAttribute(),
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully authenticated',
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
            ],
            'api_token' => $apiToken,
        ]);
    }

    /**
     * API: Logout
     * POST /api/auth/logout
     */
    public function logoutApi(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            $user->tokens()->delete();
            // Log::info('User logged out via API', ['user_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

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
            ]
        ]);
    }
}