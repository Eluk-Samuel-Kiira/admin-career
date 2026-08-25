<?php

use App\Helpers\CountryHelper;

if (!function_exists('country')) {
    /**
     * Get country helper instance or country data
     */
    function country($code = null)
    {
        if ($code === null) {
            return app('country.helper');
        }
        return CountryHelper::getCountryData($code);
    }
}

if (!function_exists('country_name')) {
    /**
     * Get country name by code
     */
    function country_name($code)
    {
        return CountryHelper::getCountryName($code);
    }
}

if (!function_exists('country_flag')) {
    /**
     * Get country flag by code
     */
    function country_flag($code)
    {
        return CountryHelper::getCountryFlag($code);
    }
}

if (!function_exists('country_currency')) {
    /**
     * Get country currency by code
     */
    function country_currency($code)
    {
        return CountryHelper::getCountryCurrency($code);
    }
}

if (!function_exists('country_phone_code')) {
    /**
     * Get country phone code by code
     */
    function country_phone_code($code)
    {
        return CountryHelper::getPhoneCode($code);
    }
}

if (!function_exists('country_timezone')) {
    /**
     * Get country timezone by code
     */
    function country_timezone($code)
    {
        return CountryHelper::getTimezone($code);
    }
}

if (!function_exists('country_region')) {
    /**
     * Get country region by code
     */
    function country_region($code)
    {
        return CountryHelper::getRegion($code);
    }
}

if (!function_exists('country_capital')) {
    /**
     * Get country capital by code
     */
    function country_capital($code)
    {
        return CountryHelper::getCapital($code);
    }
}

if (!function_exists('country_has_feature')) {
    /**
     * Check if country has a specific feature
     */
    function country_has_feature($code, $feature)
    {
        return CountryHelper::hasFeature($code, $feature);
    }
}

if (!function_exists('countries_list')) {
    /**
     * Get list of all countries
     */
    function countries_list()
    {
        return CountryHelper::getCountriesArray();
    }
}

if (!function_exists('countries_with_flags')) {
    /**
     * Get countries with flags for dropdowns
     */
    function countries_with_flags()
    {
        return CountryHelper::getCountriesWithFlags();
    }
}

if (!function_exists('country_frontend_url')) {
    /**
     * Get country frontend URL
     */
    function country_frontend_url($code)
    {
        return CountryHelper::getFrontendUrl($code);
    }
}

if (!function_exists('country_domain')) {
    /**
     * Get country domain
     */
    function country_domain($code)
    {
        return CountryHelper::getDomain($code);
    }
}

// // How to us helpers
// <!-- Get country name -->
// {{ country_name('AU') }} <!-- Output: Australia -->

// <!-- Get country flag -->
// {{ country_flag('UG') }} <!-- Output: 🇺🇬 -->

// <!-- Get countries list for dropdown -->
// <select name="country_code">
//     @foreach(countries_with_flags() as $country)
//         <option value="{{ $country['code'] }}">
//             {{ $country['full_name'] }}
//         </option>
//     @endforeach
// </select>

// <!-- Check if country has feature -->
// @if(country_has_feature('AU', 'can_view_salary_insights'))
//     <div>Salary Insights Available</div>
// @endif




// use App\Helpers\CountryHelper;

// // Get country data
// $countryData = CountryHelper::getCountryData('AU');

// // Get all active countries
// $countries = CountryHelper::getActiveCountries();

// // Check feature
// if (CountryHelper::hasFeature('UG', 'can_post_featured_jobs')) {
//     // Show featured job posting option
// }

// // Get enabled features
// $features = CountryHelper::getEnabledFeatures('AU');




// $countryName = CountryHelper::getCountryName($this->country_code);
// $flag = CountryHelper::getCountryFlag($this->country_code);
// $currency = CountryHelper::getCountryCurrency($this->country_code);


// Route::get('/test-helpers', function () {
//     dd(
//         country_name('AU'),
//         country_flag('UG'),
//         countries_list()
//     );
// });