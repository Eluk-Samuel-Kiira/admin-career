<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\CountryHelper;

class CountryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('country.helper', function () {
            return new CountryHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Clear country cache when countries are updated
        \App\Models\Job\Country::saved(function () {
            CountryHelper::clearCache();
        });

        \App\Models\Job\Country::deleted(function () {
            CountryHelper::clearCache();
        });
    }
}