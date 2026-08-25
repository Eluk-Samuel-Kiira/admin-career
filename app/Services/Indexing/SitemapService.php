<?php

namespace App\Services\Indexing;

use App\Models\Job\JobPost;
use App\Models\Job\Company;
use App\Models\Job\JobCategory;
use App\Models\Job\JobLocation;
use App\Models\Job\Country;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SitemapService
{
    private string $publicDir;

    public function __construct()
    {
        $this->publicDir = public_path('sitemaps');
        
        if (!is_dir($this->publicDir)) {
            mkdir($this->publicDir, 0755, true);
        }
    }

    /**
     * Get all active countries from database
     */
    private function getCountries(): array
    {
        $countries = [];
        
        $activeCountries = Country::where('is_active', true)
            ->whereNotNull('code')
            ->orderBy('sort_order')
            ->get();

        foreach ($activeCountries as $country) {
            $countryCode = strtolower($country->code);
            $domain = $country->domain ?? "great{$countryCode}jobs.com";
            $frontendUrl = $country->frontend_url ?? "https://www.{$domain}";
            
            $countries[$country->code] = [
                'code' => $countryCode,
                'domain' => $domain,
                'frontend_url' => $frontendUrl,
                'name' => $country->name,
                'country_code' => $country->code,
                'enabled' => $country->is_active,
            ];
        }

        return $countries;
    }

    /**
     * Get a specific country by code
     */
    private function getCountry(string $countryCode): ?array
    {
        $countries = $this->getCountries();
        return $countries[$countryCode] ?? null;
    }

    /**
     * Generate sitemap for all countries
     */
    public function generateAll(): array
    {
        $results = [];
        $countries = $this->getCountries();

        foreach ($countries as $code => $country) {
            if (!$country['enabled']) {
                continue;
            }
            $results[$code] = $this->generateCountrySitemap($code);
        }

        return $results;
    }

    /**
     * Generate sitemap for a specific country
     */
    public function generateCountrySitemap(string $countryCode): array
    {
        $country = $this->getCountry($countryCode);
        if (!$country || !$country['enabled']) {
            return ['error' => "Country {$countryCode} not supported or disabled"];
        }

        $frontendUrl = $country['frontend_url'];
        $countryCodeDb = $country['country_code'];
        $urls = [];

        // 1. Static pages for this country
        $urls = array_merge($urls, $this->getStaticUrls($frontendUrl));

        // 2. Job categories for this country
        $urls = array_merge($urls, $this->getCategoryUrls($frontendUrl, $countryCodeDb));

        // 3. Companies for this country
        $urls = array_merge($urls, $this->getCompanyUrls($frontendUrl, $countryCodeDb));

        // 4. Locations for this country
        $urls = array_merge($urls, $this->getLocationUrls($frontendUrl, $countryCodeDb));

        // 5. Job posts for this country
        $urls = array_merge($urls, $this->getJobUrls($frontendUrl, $countryCodeDb));

        // Remove duplicates and sort
        $urls = array_unique($urls, SORT_REGULAR);
        usort($urls, function($a, $b) {
            return strcmp($a['loc'], $b['loc']);
        });

        // Write to public/sitemaps/country_code/
        $countryDir = $this->publicDir . '/' . $country['code'];
        if (!is_dir($countryDir)) {
            mkdir($countryDir, 0755, true);
        }

        $filename = 'sitemap.xml';
        $filePath = $countryDir . '/' . $filename;
        $this->writeSitemap($filePath, $urls);

        $this->generateCountrySitemapIndex($country);

        return [
            'country' => $country['name'],
            'code' => $countryCode,
            'frontend_url' => $frontendUrl,
            'total_urls' => count($urls),
            'sitemap_path' => '/sitemaps/' . $country['code'] . '/sitemap.xml',
            'sitemap_index' => '/sitemaps/' . $country['code'] . '/sitemap_index.xml',
        ];
    }

    /**
     * Get static URLs for a frontend
     */
    private function getStaticUrls(string $frontendUrl): array
    {
        $urls = [];
        $staticPages = [
            ['url' => '/', 'freq' => 'daily', 'priority' => '1.0'],
            ['url' => '/jobs', 'freq' => 'hourly', 'priority' => '0.9'],
            ['url' => '/companies', 'freq' => 'daily', 'priority' => '0.8'],
            ['url' => '/pages/about', 'freq' => 'monthly', 'priority' => '0.5'],
            ['url' => '/pages/contact', 'freq' => 'monthly', 'priority' => '0.4'],
            ['url' => '/pages/privacy-policy', 'freq' => 'monthly', 'priority' => '0.3'],
            ['url' => '/pages/terms-conditions', 'freq' => 'monthly', 'priority' => '0.7'],
            // ['url' => '/career-tips', 'freq' => 'weekly', 'priority' => '0.6'],
            // ['url' => '/categories', 'freq' => 'daily', 'priority' => '0.7'],
            // ['url' => '/jobs/newest-jobs', 'freq' => 'always', 'priority' => '0.9'],
            // ['url' => '/jobs/weekly-jobs', 'freq' => 'always', 'priority' => '0.8'],
            // ['url' => '/jobs/related-jobs', 'freq' => 'always', 'priority' => '0.8'],
            // ['url' => '/jobseeker/job-alerts', 'freq' => 'weekly', 'priority' => '0.6'],
            // ['url' => '/jobseeker/cv-expert-look', 'freq' => 'weekly', 'priority' => '0.5'],
            // ['url' => '/employer/upload', 'freq' => 'weekly', 'priority' => '0.6'],
            // ['url' => '/employer/service-pricing', 'freq' => 'monthly', 'priority' => '0.5'],
            // ['url' => '/employer/employer-services', 'freq' => 'monthly', 'priority' => '0.5'],
            // ['url' => '/info', 'freq' => 'monthly', 'priority' => '0.3'],
            ['url' => '/social-media/featured', 'freq' => 'daily', 'priority' => '0.5'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $this->makeUrl(
                $frontendUrl . $page['url'],
                $page['freq'],
                $page['priority']
            );
        }

        return $urls;
    }

    /**
     * Get category URLs for a specific country
     */
    private function getCategoryUrls(string $frontendUrl, string $countryCode): array
    {
        $urls = [];
        
        $categories = JobCategory::where('is_active', true)
            ->where('country_code', $countryCode)
            ->withCount(['jobs' => function($q) use ($countryCode) {
                $q->where('is_active', true)
                ->where(function($sub) use ($countryCode) {
                    $sub->whereHas('jobLocation', function($loc) use ($countryCode) {
                        $loc->where('country_code', $countryCode);
                    })->orWhere('country_code', $countryCode);
                });
            }])
            ->having('jobs_count', '>', 0)
            ->get(['slug', 'updated_at', 'name']);

        foreach ($categories as $category) {
            $slug = $category->slug;
            // if (!str_starts_with($slug, 'category-')) {
            //     $slug = 'category-' . $slug;
            // }
            
            $url = $frontendUrl . '/jobs/category/' . $slug;
            
            $urls[] = $this->makeUrl(
                $url,
                'daily',
                '0.8',
                $category->updated_at?->toAtomString()
            );
        }

        return $urls;
    }

    /**
     * Get company URLs for a specific country
     */
    private function getCompanyUrls(string $frontendUrl, string $countryCode): array
    {
        $urls = [];
        
        $companies = Company::where('is_active', true)
            ->where('country_code', $countryCode)
            ->withCount(['jobs' => function($q) use ($countryCode) {
                $q->where('is_active', true)
                ->where(function($sub) use ($countryCode) {
                    $sub->whereHas('jobLocation', function($loc) use ($countryCode) {
                        $loc->where('country_code', $countryCode);
                    })->orWhere('country_code', $countryCode);
                });
            }])
            ->having('jobs_count', '>', 0)
            ->get(['slug', 'updated_at', 'name', 'legacy_id']);

        foreach ($companies as $company) {
            $slug = $company->slug;
            // if (!str_starts_with($slug, 'company-')) {
            //     $slug = 'company-' . $slug;
            // }
            
            $url = $frontendUrl . '/jobs/company/' . $slug;
            
            $urls[] = $this->makeUrl(
                $url,
                'weekly',
                '0.7',
                $company->updated_at?->toAtomString()
            );
        }

        return $urls;
    }

    /**
     * Get location URLs for a specific country
     */
    private function getLocationUrls(string $frontendUrl, string $countryCode): array
    {
        $urls = [];
        
        $locations = JobLocation::where('is_active', true)
            ->where('country_code', $countryCode)
            ->withCount(['jobPosts' => function($q) {
                $q->where('is_active', true);
            }])
            ->having('job_posts_count', '>', 0)
            ->get(['slug', 'updated_at', 'district']);

        foreach ($locations as $location) {
            $urls[] = $this->makeUrl(
                $frontendUrl . '/jobs/location/' . $location->slug,
                'weekly',
                '0.7',
                $location->updated_at?->toAtomString()
            );
        }

        return $urls;
    }

    /**
     * Get job URLs for a specific country
     * All jobs use: /job/{slug}
     */
    private function getJobUrls(string $frontendUrl, string $countryCode): array
    {
        $urls = [];
        
        $jobs = JobPost::where('is_active', true)
            ->whereNotNull('last_pinged_at')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where('country_code', $countryCode)
            ->get(['slug', 'updated_at', 'is_featured', 'is_urgent', 'legacy_id', 'id', 'created_at', 'published_at', 'published_until', 'last_pinged_at']);

        foreach ($jobs as $job) {
            // All jobs use the same URL structure: /job/{slug}
            $url = $frontendUrl . '/job/' . $job->slug;

            $lastmod = $job->last_pinged_at ?? $job->updated_at;
            $changefreq = ($job->is_featured || $job->is_urgent) ? 'always' : 'weekly';
            
            $urls[] = $this->makeUrl(
                $url,
                $changefreq,
                $job->is_featured ? '0.9' : '0.8',
                $lastmod?->toAtomString()
            );
        }

        return $urls;
    }

    /**
     * Generate sitemap index for a country
     */
    private function generateCountrySitemapIndex(array $country): void
    {
        $frontendUrl = $country['frontend_url'];
        $countryCode = $country['code'];
        $countryDir = $this->publicDir . '/' . $countryCode;
        $sitemaps = [];

        if (is_dir($countryDir)) {
            $files = scandir($countryDir);
            foreach ($files as $file) {
                if (str_starts_with($file, 'sitemap') && str_ends_with($file, '.xml') && $file !== 'sitemap_index.xml') {
                    $sitemaps[] = [
                        'loc' => $frontendUrl . '/sitemaps/' . $countryCode . '/' . $file,
                        'lastmod' => now()->toAtomString(),
                    ];
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($sitemaps as $sitemap) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($sitemap['loc']) . "</loc>\n";
            $xml .= "    <lastmod>" . $sitemap['lastmod'] . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }
        
        $xml .= '</sitemapindex>';

        $filePath = $countryDir . '/sitemap_index.xml';
        file_put_contents($filePath, $xml);
    }

    /**
     * Create a URL entry for sitemap
     */
    private function makeUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /**
     * Write sitemap XML to file
     */
    private function writeSitemap(string $filePath, array $urls): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        
        $xml .= '</urlset>';

        file_put_contents($filePath, $xml);
    }

    /**
     * Get sitemap statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_jobs' => JobPost::where('is_active', true)->count(),
            'legacy_jobs' => JobPost::where('is_active', true)->whereNotNull('legacy_id')->count(),
            'new_jobs' => JobPost::where('is_active', true)->whereNull('legacy_id')->count(),
            'countries' => [],
        ];

        $countries = $this->getCountries();
        foreach ($countries as $code => $country) {
            if (!$country['enabled']) {
                continue;
            }
            
            $countryCode = $country['country_code'];
            $stats['countries'][$code] = [
                'name' => $country['name'],
                'domain' => $country['domain'],
                'frontend_url' => $country['frontend_url'],
                'jobs' => JobPost::where('is_active', true)
                    ->where('country_code', $countryCode)
                    ->count(),
                'sitemap_exists' => is_dir($this->publicDir . '/' . $country['code']),
            ];
        }

        return $stats;
    }

    /**
     * Ping search engines for a specific country
     */
    public function pingSearchEngines(string $countryCode): array
    {
        $country = $this->getCountry($countryCode);
        if (!$country) {
            return ['error' => "Country {$countryCode} not supported"];
        }

        // STEP 1: Update ALL unpinged active jobs in this country (remove deadline check)
        try {
            $now = now();
            $fiftyYearsFromNow = $now->copy()->addYears(50);
            
            $updated = JobPost::where('is_active', true)
                ->where('country_code', $country['country_code'])
                ->whereNull('last_pinged_at')  // Only unpinged jobs
                ->update([
                    'last_pinged_at' => $now,
                    'is_pinged' => true,
                    'published_at' => $now,
                    'published_until' => $fiftyYearsFromNow,
                ]);
            
            Log::info("Updated {$updated} unpinged jobs in country {$countryCode}");
        } catch (\Exception $e) {
            Log::error("Failed to update unpinged jobs: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to update jobs: ' . $e->getMessage(),
                'country' => $countryCode,
            ];
        }

        // STEP 2: Regenerate the sitemap for this country
        Log::info("Regenerating sitemap for country: {$countryCode}");
        $sitemapResult = $this->generateCountrySitemap($countryCode);
        
        if (isset($sitemapResult['error'])) {
            Log::error("Failed to generate sitemap: " . $sitemapResult['error']);
            return [
                'status' => 'error',
                'message' => 'Failed to generate sitemap: ' . $sitemapResult['error'],
                'country' => $countryCode,
            ];
        }

        // STEP 3: Ping search engines with the updated sitemap
        $sitemapUrl = urlencode($country['frontend_url'] . '/sitemaps/' . $country['code'] . '/sitemap_index.xml');
        $results = [];

        $engines = [
            'google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
            'bing' => "https://www.bing.com/ping?sitemap={$sitemapUrl}",
            'yandex' => "https://webmaster.yandex.com/ping?sitemap={$sitemapUrl}",
        ];

        foreach ($engines as $name => $url) {
            try {
                $response = Http::timeout(10)->get($url);
                
                // Check if response is successful (2xx)
                $isSuccess = $response->successful();
                
                // For Google and Bing, 404 might mean the sitemap hasn't been submitted yet
                // But we still log it as a warning
                if ($response->status() === 404 && ($name === 'google' || $name === 'bing')) {
                    Log::warning("Sitemap not found for {$name} (Status 404). Make sure you've submitted your sitemap to {$name} Webmaster Tools.");
                }
                
                $results[$name] = [
                    'success' => $isSuccess,
                    'status' => $response->status(),
                    'message' => $isSuccess ? 'Ping successful' : 'Ping failed - Status: ' . $response->status(),
                ];
                
                if ($isSuccess) {
                    Log::info("Successfully pinged {$name} for country {$countryCode}");
                } else {
                    Log::warning("Failed to ping {$name} for country {$countryCode}: Status " . $response->status());
                }
            } catch (\Exception $e) {
                $results[$name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'status' => 'error',
                    'message' => 'Connection error: ' . $e->getMessage(),
                ];
                Log::warning("Search engine ping failed for {$name}: " . $e->getMessage());
            }
        }

        // Check if any ping was successful
        $pingSuccess = false;
        foreach ($results as $result) {
            if (isset($result['success']) && $result['success'] === true) {
                $pingSuccess = true;
                break;
            }
        }

        return [
            'status' => $pingSuccess ? 'success' : 'partial',
            'message' => $pingSuccess ? 'Sitemap generated and at least one search engine notified' : 'Sitemap generated but search engine ping failed',
            'country' => $countryCode,
            'jobs_updated' => $updated ?? 0,
            'sitemap' => $sitemapResult,
            'ping_results' => $results,
        ];
    }
}