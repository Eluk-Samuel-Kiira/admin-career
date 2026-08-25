<?php
// MAIN APP: app/Models/Job/JobLocation.php

namespace App\Models\Job;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Helpers\CountryHelper;

class JobLocation extends Model
{
    use HasFactory;

    // Major cities coordinates for precise location data
    const CITIES_DATA = [
        // Australia
        'Sydney' => ['lat' => -33.8688, 'lng' => 151.2093, 'is_capital' => false],
        'Melbourne' => ['lat' => -37.8136, 'lng' => 144.9631, 'is_capital' => false],
        'Brisbane' => ['lat' => -27.4698, 'lng' => 153.0251, 'is_capital' => false],
        'Perth' => ['lat' => -31.9505, 'lng' => 115.8605, 'is_capital' => false],
        'Adelaide' => ['lat' => -34.9285, 'lng' => 138.6007, 'is_capital' => false],
        'Canberra' => ['lat' => -35.2809, 'lng' => 149.1300, 'is_capital' => true],
        'Gold Coast' => ['lat' => -28.0167, 'lng' => 153.4000, 'is_capital' => false],
        'Newcastle' => ['lat' => -32.9283, 'lng' => 151.7817, 'is_capital' => false],
        
        // Uganda
        'Kampala' => ['lat' => 0.3136, 'lng' => 32.5811, 'is_capital' => true],
        'Entebbe' => ['lat' => 0.0512, 'lng' => 32.4637, 'is_capital' => false],
        'Jinja' => ['lat' => 0.4246, 'lng' => 33.2042, 'is_capital' => false],
        'Gulu' => ['lat' => 2.7724, 'lng' => 32.2907, 'is_capital' => false],
        'Mbarara' => ['lat' => -0.6072, 'lng' => 30.6545, 'is_capital' => false],
        'Fort Portal' => ['lat' => 0.6712, 'lng' => 30.2750, 'is_capital' => false],
        'Mbale' => ['lat' => 1.0784, 'lng' => 34.1810, 'is_capital' => false],
        'Lira' => ['lat' => 2.2499, 'lng' => 32.8999, 'is_capital' => false],
        
        // Kenya
        'Nairobi' => ['lat' => -1.2921, 'lng' => 36.8219, 'is_capital' => true],
        'Mombasa' => ['lat' => -4.0435, 'lng' => 39.6682, 'is_capital' => false],
        'Kisumu' => ['lat' => -0.1022, 'lng' => 34.7617, 'is_capital' => false],
        'Nakuru' => ['lat' => -0.3031, 'lng' => 36.0800, 'is_capital' => false],
        'Eldoret' => ['lat' => 0.5143, 'lng' => 35.2698, 'is_capital' => false],
        'Thika' => ['lat' => -1.0388, 'lng' => 37.0833, 'is_capital' => false],
        'Malindi' => ['lat' => -3.2187, 'lng' => 40.1169, 'is_capital' => false],
        
        // Tanzania
        'Dar es Salaam' => ['lat' => -6.7924, 'lng' => 39.2083, 'is_capital' => false],
        'Dodoma' => ['lat' => -6.1629, 'lng' => 35.7516, 'is_capital' => true],
        'Arusha' => ['lat' => -3.3869, 'lng' => 36.6820, 'is_capital' => false],
        'Mwanza' => ['lat' => -2.5164, 'lng' => 32.8987, 'is_capital' => false],
        'Zanzibar' => ['lat' => -6.1659, 'lng' => 39.2026, 'is_capital' => false],
        'Mbeya' => ['lat' => -8.9000, 'lng' => 33.4500, 'is_capital' => false],
        'Tanga' => ['lat' => -5.0724, 'lng' => 39.0995, 'is_capital' => false],
        
        // Rwanda
        'Kigali' => ['lat' => -1.9441, 'lng' => 30.0619, 'is_capital' => true],
        'Musanze' => ['lat' => -1.5000, 'lng' => 29.6346, 'is_capital' => false],
        'Rubavu' => ['lat' => -1.6833, 'lng' => 29.2500, 'is_capital' => false],
        'Huye' => ['lat' => -2.6000, 'lng' => 29.7333, 'is_capital' => false],
        
        // South Africa
        'Johannesburg' => ['lat' => -26.2041, 'lng' => 28.0473, 'is_capital' => false],
        'Cape Town' => ['lat' => -33.9249, 'lng' => 18.4241, 'is_capital' => false],
        'Pretoria' => ['lat' => -25.7479, 'lng' => 28.2293, 'is_capital' => true],
        'Durban' => ['lat' => -29.8587, 'lng' => 31.0218, 'is_capital' => false],
        'Port Elizabeth' => ['lat' => -33.9608, 'lng' => 25.6022, 'is_capital' => false],
        
        // Singapore
        'Singapore' => ['lat' => 1.3521, 'lng' => 103.8198, 'is_capital' => true],
        'Jurong East' => ['lat' => 1.3294, 'lng' => 103.7430, 'is_capital' => false],
        'Woodlands' => ['lat' => 1.4360, 'lng' => 103.7880, 'is_capital' => false],
        'Tampines' => ['lat' => 1.3496, 'lng' => 103.9578, 'is_capital' => false],
    ];

    protected $fillable = [
        'country_code',
        'district',
        'city',
        'region',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'featured_image',
        'is_active',
        'sort_order',
        'created_by',
        'latitude',
        'longitude',
        'timezone',
        'is_capital',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_capital' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get all available countries from CountryHelper
     */
    public static function getAvailableCountries(): array
    {
        return CountryHelper::getCountriesWithFlags();
    }

    /**
     * Get full country name from CountryHelper
     */
    public function getCountryNameAttribute(): string
    {
        return CountryHelper::getCountryName($this->country_code);
    }

    /**
     * Get country code for hreflang (lowercase)
     */
    public function getCountryCodeLowerAttribute(): string
    {
        return strtolower($this->country_code);
    }

    /**
     * Get region from CountryHelper
     */
    public function getRegionNameAttribute(): string
    {
        return CountryHelper::getRegion($this->country_code);
    }

    /**
     * Get timezone from CountryHelper
     */
    public function getTimezoneNameAttribute(): string
    {
        return CountryHelper::getTimezone($this->country_code);
    }

    /**
     * Get currency from CountryHelper
     */
    public function getCurrencyAttribute(): string
    {
        return CountryHelper::getCountryCurrency($this->country_code);
    }

    /**
     * Get flag emoji from CountryHelper
     */
    public function getFlagAttribute(): string
    {
        return CountryHelper::getCountryFlag($this->country_code);
    }

    /**
     * Get the country model relationship
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * Generate SEO-optimized URL with country prefix
     */
    public function getUrlAttribute(): string
    {
        $countryCode = strtolower($this->country_code);
        return url("/{$countryCode}/jobs/location/{$this->slug}");
    }

    /**
     * Get hreflang tags for international SEO
     */
    public function getHreflangTags(): array
    {
        $tags = [];
        $baseUrl = config('app.url');
        $countryCode = strtolower($this->country_code);
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => "en-{$countryCode}",
            'href' => "{$baseUrl}/{$countryCode}/jobs/location/{$this->slug}",
        ];
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => 'en',
            'href' => "{$baseUrl}/jobs/location/{$this->slug}",
        ];
        
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => 'x-default',
            'href' => "{$baseUrl}/jobs/location/{$this->slug}",
        ];
        
        return $tags;
    }

    /**
     * Get country-specific meta tags for better local SEO
     */
    public function getCountryMetaTags(): array
    {
        $countryName = $this->country_name;
        $locationName = $this->district ?? $this->city ?? 'Jobs';
        
        return [
            'title' => "Jobs in {$locationName}, {$countryName} - Latest Career Opportunities",
            'description' => "Find the latest jobs in {$locationName}, {$countryName}. Browse thousands of career opportunities, vacancies, and employment in {$locationName}, {$countryName}. Apply today!",
            'keywords' => "jobs in {$locationName}, {$locationName} {$countryName} jobs, employment {$locationName}, careers {$countryName}, vacancies {$countryName}, work in {$countryName}",
            'og_title' => "{$locationName}, {$countryName} Jobs - Stardena Works",
            'og_description' => "Discover career opportunities in {$locationName}, {$countryName}. Find your dream job today!",
            'twitter_title' => "Jobs in {$locationName}, {$countryName}",
            'twitter_description' => "Browse the latest job openings in {$locationName}, {$countryName}",
        ];
    }

    /**
     * Auto-set coordinates based on district/city
     */
    public function setCoordinatesFromCity(): void
    {
        // Try to find coordinates from city/district first
        if ($this->district && isset(self::CITIES_DATA[$this->district])) {
            $cityData = self::CITIES_DATA[$this->district];
            $this->latitude = $cityData['lat'];
            $this->longitude = $cityData['lng'];
            $this->is_capital = $cityData['is_capital'];
        } elseif ($this->city && isset(self::CITIES_DATA[$this->city])) {
            $cityData = self::CITIES_DATA[$this->city];
            $this->latitude = $cityData['lat'];
            $this->longitude = $cityData['lng'];
            $this->is_capital = $cityData['is_capital'];
        } elseif ($this->district) {
            // Try to match partial names
            foreach (self::CITIES_DATA as $city => $data) {
                if (stripos($this->district, $city) !== false || stripos($city, $this->district) !== false) {
                    $this->latitude = $data['lat'];
                    $this->longitude = $data['lng'];
                    $this->is_capital = $data['is_capital'];
                    break;
                }
            }
        }
        
        // If still no coordinates, use country default from CountryHelper
        if (!$this->latitude) {
            $country = CountryHelper::getCountry($this->country_code);
            if ($country) {
                $this->latitude = $country->default_lat;
                $this->longitude = $country->default_lng;
            }
        }
    }

    // Scopes
    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 50)
    {
        // Haversine formula to find nearby locations
        return $query->whereRaw(
            "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?",
            [$latitude, $longitude, $latitude, $radiusKm]
        );
    }

    // Relationships
    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'job_location_id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($location) {
            // Generate slug
            if (empty($location->slug)) {
                $countryCode = strtolower($location->country_code);
                $districtSlug = Str::slug($location->district ?? $location->city ?? 'jobs');
                $location->slug = "{$districtSlug}-jobs-in-{$countryCode}";
                
                // Ensure uniqueness
                $slug = $location->slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $location->slug . '-' . $counter++;
                }
                $location->slug = $slug;
            }
            
            // Set region from CountryHelper
            if (empty($location->region)) {
                $location->region = CountryHelper::getRegion($location->country_code);
            }
            
            // Set timezone from CountryHelper
            if (empty($location->timezone)) {
                $location->timezone = CountryHelper::getTimezone($location->country_code);
            }
            
            // Auto-set coordinates
            if (empty($location->latitude) || empty($location->longitude)) {
                $location->setCoordinatesFromCity();
            }
            
            // Generate meta title if empty
            if (empty($location->meta_title)) {
                $countryName = $location->country_name;
                $locationName = $location->district ?? $location->city ?? 'Jobs';
                $location->meta_title = "Jobs in {$locationName}, {$countryName} - Latest Career Opportunities";
            }
            
            // Generate meta description if empty
            if (empty($location->meta_description)) {
                $countryName = $location->country_name;
                $locationName = $location->district ?? $location->city ?? 'Jobs';
                $location->meta_description = "Find latest jobs in {$locationName}, {$countryName}. Browse career opportunities, vacancies, and employment in {$locationName}, {$countryName}. Apply today!";
            }
            
            // Set created_by if not set and user is authenticated
            if (empty($location->created_by) && auth()->check()) {
                $location->created_by = auth()->id();
            }
        });
        
        static::updating(function ($location) {
            // Update coordinates if district/city changed
            if ($location->isDirty('district') || $location->isDirty('city') || $location->isDirty('country_code')) {
                $location->setCoordinatesFromCity();
            }
            
            // Update region if country changed
            if ($location->isDirty('country_code')) {
                $location->region = CountryHelper::getRegion($location->country_code);
                $location->timezone = CountryHelper::getTimezone($location->country_code);
            }
            
            // Update slug if district changed
            if ($location->isDirty('district') || $location->isDirty('country_code')) {
                $countryCode = strtolower($location->country_code);
                $districtSlug = Str::slug($location->district ?? $location->city ?? 'jobs');
                $location->slug = "{$districtSlug}-jobs-in-{$countryCode}";
            }
        });
    }
}