<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (['au', 'ug', 'default'] as $country) {
            RateLimiter::for("alert-emails-{$country}", function () use ($country) {
                return [
                    Limit::perMinute(20)->by($country),
                    Limit::perHour(200)->by($country),
                ];
            });
        }
    }
}