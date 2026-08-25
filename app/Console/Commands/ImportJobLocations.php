<?php
// app/Console/Commands/ImportJobLocations.php

namespace App\Console\Commands;

use App\Models\Job\JobLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportJobLocations extends Command
{
    protected $signature = 'import:job-locations 
                            {country? : Specific country to import (UG, KE, TZ, RW, ZM, MW, SG)}
                            {--force : Force import even if records exist}
                            {--only-major : Import only major cities (skip counties/states)}
                            {--dry-run : Show what would be imported without actually importing}';
    
    protected $description = 'Import job locations for supported countries (UG, KE, TZ, RW, ZM, MW, SG)';

    // Supported countries with their region and timezone
    protected const SUPPORTED_COUNTRIES = [
        'UG' => ['name' => 'Uganda', 'region' => 'East Africa', 'timezone' => 'Africa/Kampala', 'currency' => 'UGX', 'flag' => '🇺🇬'],
        'KE' => ['name' => 'Kenya', 'region' => 'East Africa', 'timezone' => 'Africa/Nairobi', 'currency' => 'KES', 'flag' => '🇰🇪'],
        'TZ' => ['name' => 'Tanzania', 'region' => 'East Africa', 'timezone' => 'Africa/Dar_es_Salaam', 'currency' => 'TZS', 'flag' => '🇹🇿'],
        'RW' => ['name' => 'Rwanda', 'region' => 'East Africa', 'timezone' => 'Africa/Kigali', 'currency' => 'RWF', 'flag' => '🇷🇼'],
        'ZM' => ['name' => 'Zambia', 'region' => 'Southern Africa', 'timezone' => 'Africa/Lusaka', 'currency' => 'ZMW', 'flag' => '🇿🇲'],
        'MW' => ['name' => 'Malawi', 'region' => 'Southern Africa', 'timezone' => 'Africa/Blantyre', 'currency' => 'MWK', 'flag' => '🇲🇼'],
        'SG' => ['name' => 'Singapore', 'region' => 'Southeast Asia', 'timezone' => 'Asia/Singapore', 'currency' => 'SGD', 'flag' => '🇸🇬'],
    ];

    // Country-specific location data
    protected const LOCATION_DATA = [
        // Uganda - Districts
        'UG' => [
            // Major cities
            ['district' => 'Kampala', 'city' => 'Kampala', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Entebbe', 'city' => 'Entebbe', 'sort_order' => 2],
            ['district' => 'Jinja', 'city' => 'Jinja', 'sort_order' => 3],
            ['district' => 'Gulu', 'city' => 'Gulu', 'sort_order' => 4],
            ['district' => 'Mbarara', 'city' => 'Mbarara', 'sort_order' => 5],
            // Major districts
            ['district' => 'Fort Portal', 'city' => 'Fort Portal'],
            ['district' => 'Mbale', 'city' => 'Mbale'],
            ['district' => 'Lira', 'city' => 'Lira'],
            ['district' => 'Soroti', 'city' => 'Soroti'],
            ['district' => 'Arua', 'city' => 'Arua'],
            ['district' => 'Masaka', 'city' => 'Masaka'],
            ['district' => 'Mukono', 'city' => 'Mukono'],
            ['district' => 'Wakiso', 'city' => 'Wakiso'],
            ['district' => 'Busia', 'city' => 'Busia'],
            ['district' => 'Tororo', 'city' => 'Tororo'],
            ['district' => 'Kabale', 'city' => 'Kabale'],
            ['district' => 'Kasese', 'city' => 'Kasese'],
            ['district' => 'Hoima', 'city' => 'Hoima'],
            ['district' => 'Masindi', 'city' => 'Masindi'],
        ],

        // Kenya - Counties
        'KE' => [
            // Major cities
            ['district' => 'Nairobi', 'city' => 'Nairobi', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Mombasa', 'city' => 'Mombasa', 'is_capital' => false, 'sort_order' => 2],
            ['district' => 'Kisumu', 'city' => 'Kisumu', 'is_capital' => false, 'sort_order' => 3],
            ['district' => 'Nakuru', 'city' => 'Nakuru', 'is_capital' => false, 'sort_order' => 4],
            ['district' => 'Eldoret', 'city' => 'Eldoret', 'is_capital' => false, 'sort_order' => 5],
            // All 47 Counties
            ['district' => 'Baringo', 'city' => 'Kabarnet'],
            ['district' => 'Bomet', 'city' => 'Bomet'],
            ['district' => 'Bungoma', 'city' => 'Bungoma'],
            ['district' => 'Busia', 'city' => 'Busia'],
            ['district' => 'Elgeyo-Marakwet', 'city' => 'Iten'],
            ['district' => 'Embu', 'city' => 'Embu'],
            ['district' => 'Garissa', 'city' => 'Garissa'],
            ['district' => 'Homa Bay', 'city' => 'Homa Bay'],
            ['district' => 'Isiolo', 'city' => 'Isiolo'],
            ['district' => 'Kajiado', 'city' => 'Kajiado'],
            ['district' => 'Kakamega', 'city' => 'Kakamega'],
            ['district' => 'Kericho', 'city' => 'Kericho'],
            ['district' => 'Kiambu', 'city' => 'Kiambu'],
            ['district' => 'Kilifi', 'city' => 'Kilifi'],
            ['district' => 'Kirinyaga', 'city' => 'Kerugoya'],
            ['district' => 'Kisii', 'city' => 'Kisii'],
            ['district' => 'Kitui', 'city' => 'Kitui'],
            ['district' => 'Kwale', 'city' => 'Kwale'],
            ['district' => 'Laikipia', 'city' => 'Nanyuki'],
            ['district' => 'Lamu', 'city' => 'Lamu'],
            ['district' => 'Machakos', 'city' => 'Machakos'],
            ['district' => 'Makueni', 'city' => 'Wote'],
            ['district' => 'Mandera', 'city' => 'Mandera'],
            ['district' => 'Marsabit', 'city' => 'Marsabit'],
            ['district' => 'Meru', 'city' => 'Meru'],
            ['district' => 'Migori', 'city' => 'Migori'],
            ['district' => 'Murang\'a', 'city' => 'Murang\'a'],
            ['district' => 'Nandi', 'city' => 'Kapsabet'],
            ['district' => 'Narok', 'city' => 'Narok'],
            ['district' => 'Nyamira', 'city' => 'Nyamira'],
            ['district' => 'Nyandarua', 'city' => 'Ol Kalou'],
            ['district' => 'Nyeri', 'city' => 'Nyeri'],
            ['district' => 'Samburu', 'city' => 'Maralal'],
            ['district' => 'Siaya', 'city' => 'Siaya'],
            ['district' => 'Taita-Taveta', 'city' => 'Voi'],
            ['district' => 'Tana River', 'city' => 'Hola'],
            ['district' => 'Tharaka-Nithi', 'city' => 'Chuka'],
            ['district' => 'Trans-Nzoia', 'city' => 'Kitale'],
            ['district' => 'Turkana', 'city' => 'Lodwar'],
            ['district' => 'Uasin Gishu', 'city' => 'Eldoret'],
            ['district' => 'Vihiga', 'city' => 'Vihiga'],
            ['district' => 'Wajir', 'city' => 'Wajir'],
            ['district' => 'West Pokot', 'city' => 'Kapenguria'],
        ],

        // Tanzania - Regions
        'TZ' => [
            // Major cities
            ['district' => 'Dar es Salaam', 'city' => 'Dar es Salaam', 'is_capital' => false, 'sort_order' => 1],
            ['district' => 'Dodoma', 'city' => 'Dodoma', 'is_capital' => true, 'sort_order' => 2],
            ['district' => 'Arusha', 'city' => 'Arusha', 'sort_order' => 3],
            ['district' => 'Mwanza', 'city' => 'Mwanza', 'sort_order' => 4],
            ['district' => 'Zanzibar', 'city' => 'Zanzibar', 'sort_order' => 5],
            // Major regions
            ['district' => 'Mbeya', 'city' => 'Mbeya'],
            ['district' => 'Tanga', 'city' => 'Tanga'],
            ['district' => 'Morogoro', 'city' => 'Morogoro'],
            ['district' => 'Kigoma', 'city' => 'Kigoma'],
            ['district' => 'Tabora', 'city' => 'Tabora'],
            ['district' => 'Iringa', 'city' => 'Iringa'],
            ['district' => 'Songea', 'city' => 'Songea'],
            ['district' => 'Shinyanga', 'city' => 'Shinyanga'],
            ['district' => 'Mtwara', 'city' => 'Mtwara'],
            ['district' => 'Kilimanjaro', 'city' => 'Moshi'],
            ['district' => 'Manyara', 'city' => 'Babati'],
            ['district' => 'Pwani', 'city' => 'Kibaha'],
            ['district' => 'Geita', 'city' => 'Geita'],
            ['district' => 'Katavi', 'city' => 'Mpanda'],
            ['district' => 'Njombe', 'city' => 'Njombe'],
            ['district' => 'Simiyu', 'city' => 'Bariadi'],
        ],

        // Rwanda - Districts
        'RW' => [
            ['district' => 'Kigali', 'city' => 'Kigali', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Musanze', 'city' => 'Musanze', 'sort_order' => 2],
            ['district' => 'Rubavu', 'city' => 'Rubavu', 'sort_order' => 3],
            ['district' => 'Huye', 'city' => 'Huye', 'sort_order' => 4],
            ['district' => 'Rusizi', 'city' => 'Rusizi'],
            ['district' => 'Nyagatare', 'city' => 'Nyagatare'],
            ['district' => 'Gatsibo', 'city' => 'Gatsibo'],
            ['district' => 'Kayonza', 'city' => 'Kayonza'],
            ['district' => 'Bugesera', 'city' => 'Bugesera'],
            ['district' => 'Rwamagana', 'city' => 'Rwamagana'],
            ['district' => 'Muhanga', 'city' => 'Muhanga'],
            ['district' => 'Kamonyi', 'city' => 'Kamonyi'],
            ['district' => 'Ruhango', 'city' => 'Ruhango'],
            ['district' => 'Nyanza', 'city' => 'Nyanza'],
            ['district' => 'Nyamagabe', 'city' => 'Nyamagabe'],
            ['district' => 'Karongi', 'city' => 'Karongi'],
            ['district' => 'Rutsiro', 'city' => 'Rutsiro'],
            ['district' => 'Ngororero', 'city' => 'Ngororero'],
            ['district' => 'Burera', 'city' => 'Burera'],
            ['district' => 'Gakenke', 'city' => 'Gakenke'],
            ['district' => 'Rulindo', 'city' => 'Rulindo'],
        ],

        // Zambia - Provinces
        'ZM' => [
            ['district' => 'Lusaka', 'city' => 'Lusaka', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Copperbelt', 'city' => 'Ndola', 'sort_order' => 2],
            ['district' => 'Central', 'city' => 'Kabwe', 'sort_order' => 3],
            ['district' => 'Southern', 'city' => 'Choma', 'sort_order' => 4],
            ['district' => 'Eastern', 'city' => 'Chipata', 'sort_order' => 5],
            ['district' => 'Western', 'city' => 'Mongu'],
            ['district' => 'North-Western', 'city' => 'Solwezi'],
            ['district' => 'Northern', 'city' => 'Kasama'],
            ['district' => 'Luapula', 'city' => 'Mansa'],
            ['district' => 'Muchinga', 'city' => 'Chinsali'],
        ],

        // Malawi - Regions and Districts
        'MW' => [
            ['district' => 'Lilongwe', 'city' => 'Lilongwe', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Blantyre', 'city' => 'Blantyre', 'sort_order' => 2],
            ['district' => 'Mzuzu', 'city' => 'Mzuzu', 'sort_order' => 3],
            ['district' => 'Zomba', 'city' => 'Zomba', 'sort_order' => 4],
            // Central Region
            ['district' => 'Central Region', 'city' => 'Lilongwe'],
            // Southern Region
            ['district' => 'Southern Region', 'city' => 'Blantyre'],
            // Northern Region
            ['district' => 'Northern Region', 'city' => 'Mzuzu'],
            // Additional districts
            ['district' => 'Machinga', 'city' => 'Machinga'],
            ['district' => 'Zomba District', 'city' => 'Zomba'],
            ['district' => 'Thyolo', 'city' => 'Thyolo'],
            ['district' => 'Mulanje', 'city' => 'Mulanje'],
            ['district' => 'Chiradzulu', 'city' => 'Chiradzulu'],
            ['district' => 'Ntcheu', 'city' => 'Ntcheu'],
            ['district' => 'Dedza', 'city' => 'Dedza'],
            ['district' => 'Ntchisi', 'city' => 'Ntchisi'],
            ['district' => 'Kasungu', 'city' => 'Kasungu'],
            ['district' => 'Mchinji', 'city' => 'Mchinji'],
            ['district' => 'Salima', 'city' => 'Salima'],
        ],

        // Singapore - Regions
        'SG' => [
            ['district' => 'Central', 'city' => 'Singapore', 'is_capital' => true, 'sort_order' => 1],
            ['district' => 'Jurong East', 'city' => 'Jurong East', 'sort_order' => 2],
            ['district' => 'Woodlands', 'city' => 'Woodlands', 'sort_order' => 3],
            ['district' => 'Tampines', 'city' => 'Tampines', 'sort_order' => 4],
            ['district' => 'Bedok', 'city' => 'Bedok', 'sort_order' => 5],
            ['district' => 'Choa Chu Kang', 'city' => 'Choa Chu Kang'],
            ['district' => 'Toa Payoh', 'city' => 'Toa Payoh'],
            ['district' => 'Ang Mo Kio', 'city' => 'Ang Mo Kio'],
            ['district' => 'Bishan', 'city' => 'Bishan'],
            ['district' => 'Bukit Panjang', 'city' => 'Bukit Panjang'],
            ['district' => 'Clementi', 'city' => 'Clementi'],
            ['district' => 'Geylang', 'city' => 'Geylang'],
            ['district' => 'Hougang', 'city' => 'Hougang'],
            ['district' => 'Kallang', 'city' => 'Kallang'],
            ['district' => 'Marine Parade', 'city' => 'Marine Parade'],
            ['district' => 'Pasir Ris', 'city' => 'Pasir Ris'],
            ['district' => 'Punggol', 'city' => 'Punggol'],
            ['district' => 'Queenstown', 'city' => 'Queenstown'],
            ['district' => 'Sembawang', 'city' => 'Sembawang'],
            ['district' => 'Sengkang', 'city' => 'Sengkang'],
            ['district' => 'Serangoon', 'city' => 'Serangoon'],
        ],
    ];

    public function handle()
    {
        $country = $this->argument('country');
        $force = $this->option('force');
        $onlyMajor = $this->option('only-major');
        $dryRun = $this->option('dry-run');

        // Get countries to import
        $countries = $country ? [strtoupper($country)] : array_keys(self::SUPPORTED_COUNTRIES);

        $this->info('🚀 Starting import of job locations...');
        $this->newLine();

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalUpdated = 0;

        foreach ($countries as $countryCode) {
            // Validate country
            if (!isset(self::SUPPORTED_COUNTRIES[$countryCode])) {
                $this->error("❌ Country '{$countryCode}' is not supported.");
                $this->info("Supported countries: " . implode(', ', array_keys(self::SUPPORTED_COUNTRIES)));
                continue;
            }

            $countryInfo = self::SUPPORTED_COUNTRIES[$countryCode];
            $locations = self::LOCATION_DATA[$countryCode] ?? [];

            if (empty($locations)) {
                $this->warn("⚠️ No location data found for {$countryInfo['name']} ({$countryCode})");
                continue;
            }

            // Filter for major cities only
            if ($onlyMajor) {
                $locations = array_filter($locations, fn($loc) => isset($loc['sort_order']));
            }

            $this->info("📌 Processing {$countryInfo['flag']} {$countryInfo['name']} ({$countryCode}) - " . count($locations) . " locations");
            $this->newLine();

            $created = 0;
            $skipped = 0;
            $updated = 0;

            $bar = $this->output->createProgressBar(count($locations));
            $bar->start();

            foreach ($locations as $locationData) {
                $district = $locationData['district'];
                $city = $locationData['city'];
                $isCapital = $locationData['is_capital'] ?? false;
                $sortOrder = $locationData['sort_order'] ?? 0;

                // Generate slug
                $slug = Str::slug($district) . '-jobs-in-' . strtolower($countryCode);

                // Build location data
                $location = [
                    'country' => $countryCode,
                    'country_code' => $countryCode,
                    'district' => $district,
                    'city' => $city,
                    'region' => $countryInfo['region'],
                    'slug' => $slug,
                    'description' => "Find jobs in {$district}, {$countryInfo['name']}. Browse career opportunities in {$city}, {$district}, {$countryInfo['name']}.",
                    'meta_title' => "Jobs in {$district}, {$countryInfo['name']} - Latest Career Opportunities",
                    'meta_description' => "Find latest jobs in {$district}, {$countryInfo['name']}. Browse career opportunities, vacancies, and employment in {$district}, {$countryInfo['name']}. Apply today!",
                    'is_active' => true,
                    'is_capital' => $isCapital,
                    'sort_order' => $sortOrder,
                    'timezone' => $countryInfo['timezone'],
                ];

                if ($dryRun) {
                    $this->line("  - Would import: {$district}, {$city} ({$countryCode})");
                    $bar->advance();
                    continue;
                }

                // Check if location already exists
                $exists = JobLocation::where('country', $countryCode)
                    ->where('district', $district)
                    ->first();

                if ($exists) {
                    if ($force) {
                        // Update existing
                        $exists->update($location);
                        $updated++;
                        $this->line("  🔄 Updated: {$district}, {$countryInfo['name']}");
                    } else {
                        $skipped++;
                    }
                } else {
                    // Create new
                    JobLocation::create($location);
                    $created++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ {$countryInfo['flag']} {$countryInfo['name']} summary:");
            $this->line("  - Created: {$created}");
            $this->line("  - Updated: {$updated}");
            $this->line("  - Skipped: {$skipped}");
            $this->newLine();

            $totalCreated += $created;
            $totalUpdated += $updated;
            $totalSkipped += $skipped;
        }

        $this->newLine();
        $this->info("🎉 Import complete!");
        $this->table(
            ['Metric', 'Total'],
            [
                ['Created', $totalCreated],
                ['Updated', $totalUpdated],
                ['Skipped', $totalSkipped],
                ['Total Processed', $totalCreated + $totalUpdated + $totalSkipped],
            ]
        );

        // Show summary by country
        $this->newLine();
        $this->info("📊 Summary by country:");
        $this->newLine();
        
        $summary = [];
        foreach (array_keys(self::SUPPORTED_COUNTRIES) as $code) {
            $count = JobLocation::where('country', $code)->count();
            $info = self::SUPPORTED_COUNTRIES[$code];
            $summary[] = [
                $info['flag'],
                $code,
                $info['name'],
                $count,
            ];
        }
        
        $this->table(
            ['', 'Code', 'Country', 'Total Locations'],
            $summary
        );
    }

    /**
     * Get country info by code
     */
    public static function getCountryInfo(string $code): ?array
    {
        return self::SUPPORTED_COUNTRIES[$code] ?? null;
    }

    /**
     * Get all supported countries
     */
    public static function getSupportedCountries(): array
    {
        return self::SUPPORTED_COUNTRIES;
    }

    /**
     * Get locations for a specific country
     */
    public static function getLocations(string $countryCode): array
    {
        return self::LOCATION_DATA[$countryCode] ?? [];
    }
}


// # Import all countries (UG, KE, TZ, RW, ZM, MW, SG)
// php artisan import:job-locations

// # Import only Uganda
// php artisan import:job-locations UG

// # Import only Kenya
// php artisan import:job-locations KE

// # Import only Tanzania
// php artisan import:job-locations TZ

// # Import only Rwanda
// php artisan import:job-locations RW

// # Import only Zambia
// php artisan import:job-locations ZM

// # Import only Malawi
// php artisan import:job-locations MW

// # Import only Singapore
// php artisan import:job-locations SG

// # Force update existing records
// php artisan import:job-locations --force

// # Force update only Kenya
// php artisan import:job-locations KE --force

// # Import only major cities (skip counties/states)
// php artisan import:job-locations --only-major

// # Dry run - see what would be imported
// php artisan import:job-locations --dry-run

// # Dry run for specific country
// php artisan import:job-locations KE --dry-run