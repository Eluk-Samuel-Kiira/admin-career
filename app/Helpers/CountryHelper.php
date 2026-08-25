<?php

namespace App\Helpers;

use App\Models\Job\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class CountryHelper
{
    /**
     * Get country by code
     */
    public static function getCountry(string $code): ?Country
    {
        return Cache::remember("country.{$code}", 3600, function () use ($code) {
            return Country::where('code', strtoupper($code))->first();
        });
    }

    /**
     * Get country name by code
     */
    public static function getCountryName(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->name ?? $code;
    }

    /**
     * Get country flag by code
     */
    public static function getCountryFlag(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->flag ?? '🌍';
    }

    /**
     * Get country currency by code
     */
    public static function getCountryCurrency(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->currency ?? 'USD';
    }

    /**
     * Get country phone code by code
     */
    public static function getPhoneCode(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->phone_code ?? '';
    }

    /**
     * Get country timezone by code
     */
    public static function getTimezone(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->timezone ?? 'UTC';
    }

    /**
     * Get country region by code
     */
    public static function getRegion(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->region ?? '';
    }

    /**
     * Get country capital by code
     */
    public static function getCapital(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->capital ?? '';
    }

    /**
     * Get country frontend URL
     */
    public static function getFrontendUrl(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->frontend_url ?? config('app.url');
    }

    /**
     * Get country domain
     */
    public static function getDomain(string $code): string
    {
        $country = self::getCountry($code);
        return $country?->domain ?? parse_url(config('app.url'), PHP_URL_HOST);
    }

    /**
     * Get all active countries
     */
    public static function getActiveCountries(): Collection
    {
        return Cache::remember('countries.active', 3600, function () {
            return Country::active()->ordered()->get();
        });
    }

    /**
     * Get all countries as array with code as key
     */
    public static function getCountriesArray(): array
    {
        return Cache::remember('countries.array', 3600, function () {
            return Country::active()->ordered()->pluck('name', 'code')->toArray();
        });
    }

    /**
     * Get countries with flags for dropdowns
     */
    public static function getCountriesWithFlags(): array
    {
        return Cache::remember('countries.with_flags', 3600, function () {
            return Country::active()->ordered()->get()->map(function ($country) {
                return [
                    'code' => $country->code,
                    'name' => $country->name,
                    'flag' => $country->flag_emoji,
                    'full_name' => $country->flag_emoji . ' ' . $country->name,
                ];
            })->toArray();
        });
    }

    /**
     * Check if a country has a specific feature
     */
    public static function hasFeature(string $countryCode, string $feature): bool
    {
        $country = self::getCountry($countryCode);
        if (!$country) {
            return false;
        }
        return (bool) ($country->$feature ?? false);
    }

    /**
     * Get all unique currencies with symbols
     */
    public static function getCurrencies(): array
    {
        return Cache::remember('countries.currencies', 3600, function () {
            return Country::active()
                ->whereNotNull('currency')
                ->orderBy('currency')
                ->get()
                ->map(function ($country) {
                    return [
                        'code' => $country->currency,
                        'symbol' => $country->currency_symbol ?? $country->currency,
                    ];
                })
                ->unique('code')
                ->values()
                ->toArray();
        });
    }

    /**
     * Get all enabled features for a country
     */
    public static function getEnabledFeatures(string $countryCode): array
    {
        $country = self::getCountry($countryCode);
        if (!$country) {
            return [];
        }

        $features = [];
        $featureFields = [
            'can_view_casual_workers' => 'View Casual Workers',
            'can_view_blue_collar_workers' => 'View Blue Collar Workers',
            'can_accept_cv_services' => 'CV Services',
            'can_offer_exam_services' => 'Exam Services',
            'can_view_salary_insights' => 'Salary Insights',
            'can_view_cost_of_living_tools' => 'Cost of Living Tools',
            'can_use_social_media_services' => 'Social Media Services',
            'can_view_employer_services' => 'Employer Services',
            'can_view_jobseeker_services' => 'Jobseeker Services',
            'can_access_subscription' => 'Subscription Access',
            'can_view_company_profiles' => 'Company Profiles',
            'can_view_industry_insights' => 'Industry Insights',
            'can_access_career_advice' => 'Career Advice',
            'can_view_job_alerts' => 'Job Alerts',
            'can_use_resume_builder' => 'Resume Builder',
            'can_view_employer_reviews' => 'Employer Reviews',
            'can_access_skill_assessment' => 'Skill Assessment',
            'can_view_market_trends' => 'Market Trends',
            'can_use_job_comparison_tools' => 'Job Comparison Tools',
            'can_access_networking_events' => 'Networking Events',
            'can_view_training_courses' => 'Training Courses',
            'can_use_chat_support' => 'Chat Support',
            'can_access_premium_content' => 'Premium Content',
            'can_view_verified_employers' => 'Verified Employers',
            'can_use_priority_application' => 'Priority Application',
            'can_view_exclusive_jobs' => 'Exclusive Jobs',
            'can_access_interview_coaching' => 'Interview Coaching',
            'can_view_salary_negotiation_tips' => 'Salary Negotiation Tips',
            'can_post_jobs' => 'Post Jobs',
            'can_post_featured_jobs' => 'Post Featured Jobs',
            'can_post_urgent_jobs' => 'Post Urgent Jobs',
            'can_use_job_analytics' => 'Job Analytics',
            'can_manage_applications' => 'Manage Applications',
        ];

        foreach ($featureFields as $field => $label) {
            if ($country->$field) {
                $features[] = [
                    'field' => $field,
                    'label' => $label,
                    'enabled' => true,
                ];
            }
        }

        return $features;
    }

    /**
     * Get country data with all details
     */
    public static function getCountryData(string $code): ?array
    {
        $country = self::getCountry($code);
        if (!$country) {
            return null;
        }

        return [
            'id' => $country->id,
            'code' => $country->code,
            'name' => $country->name,
            'region' => $country->region,
            'timezone' => $country->timezone,
            'currency' => $country->currency,
            'currency_symbol' => $country->currency_symbol,
            'flag' => $country->flag_emoji,
            'capital' => $country->capital,
            'capital_lat' => $country->capital_lat,
            'capital_lng' => $country->capital_lng,
            'phone_code' => $country->phone_code,
            'is_active' => $country->is_active,
            'sort_order' => $country->sort_order,
            'frontend_url' => $country->frontend_url,
            'domain' => $country->domain,
            'features' => self::getEnabledFeatures($code),
            'feature_flags' => $country->feature_flags,
        ];
    }

    /**
     * Clear country cache
     */
    public static function clearCache(?string $code = null): void
    {
        if ($code) {
            Cache::forget("country.{$code}");
        } else {
            Cache::forget('countries.active');
            Cache::forget('countries.array');
            Cache::forget('countries.with_flags');
            $countries = Country::all();
            foreach ($countries as $country) {
                Cache::forget("country.{$country->code}");
            }
        }
    }
}