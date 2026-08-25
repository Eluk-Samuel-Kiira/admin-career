<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Record last login
        $this->recordLastLogin($request->user());

        // Clear cache on login
        $this->clearCache();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Record the user's last login timestamp.
     */
    protected function recordLastLogin($user): void
    {
        $user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    /**
     * Clear application cache on login.
     */
    protected function clearCache(): void
    {
        try {
            // Clear all caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            // Clear specific cache tags if using tags
            Cache::flush();
            
            // Log cache clearing
            // \Log::info('Cache cleared on login by user: ' . (auth()->id() ?? 'Unknown'));
        } catch (\Exception $e) {
            // Log error but don't break login
            \Log::error('Failed to clear cache on login: ' . $e->getMessage());
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}