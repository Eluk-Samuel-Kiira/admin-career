<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Job\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Auth, Storage, Log, Hash };
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        // Get all active countries for the dropdown
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('profile.edit', [
            'user' => $request->user(),
            'countries' => $countries,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Get validated data
        $validated = $request->validated();
        
        // If country_code is provided, find the country by phone_code
        if ($request->has('country_code') && !empty($request->country_code)) {
            $country = Country::where('phone_code', $request->country_code)->first();
            
            if ($country) {
                // Store the country code (e.g., 'UG', 'KE') in the user's country_code field
                $validated['country_code'] = $country->code;
            } else {
                // If no country found, store the phone code as-is
                $validated['country_code'] = $request->country_code;
            }
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update user avatar.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);
        
        $user = auth()->user();
        
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
        
        return back()->with('status', 'profile-updated');
    }

    /**
     * Get countries for API/AJAX calls.
     */
    public function getCountries(Request $request)
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone_code', 'flag']);

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get country by phone code.
     */
    public function getCountryByPhoneCode($phoneCode)
    {
        $country = Country::where('phone_code', $phoneCode)
            ->where('is_active', true)
            ->first();

        if ($country) {
            return response()->json([
                'success' => true,
                'data' => $country
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Country not found'
        ], 404);
    }
}