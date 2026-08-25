<?php

namespace App\Console\Commands;

use App\Models\Job\SalaryRange;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportSalaryRanges extends Command
{
    protected $signature = 'import:salary-ranges 
                            {country? : Specific country to import (AU, UG, KE, TZ, RW, ZM, MW, SG, ZA)}
                            {--force : Force update existing records}
                            {--dry-run : Show what would be imported without actually importing}';
    
    protected $description = 'Import salary ranges for supported countries';

    // Country-specific salary ranges
    protected const SALARY_DATA = [
        'AU' => [
            'currency' => 'AUD',
            'ranges' => [
                ['name' => 'Entry Level', 'min' => 45000, 'max' => 60000, 'sort' => 1],
                ['name' => 'Junior', 'min' => 60000, 'max' => 80000, 'sort' => 2],
                ['name' => 'Mid Level', 'min' => 80000, 'max' => 100000, 'sort' => 3],
                ['name' => 'Senior', 'min' => 100000, 'max' => 130000, 'sort' => 4],
                ['name' => 'Lead', 'min' => 130000, 'max' => 160000, 'sort' => 5],
                ['name' => 'Principal', 'min' => 160000, 'max' => 200000, 'sort' => 6],
                ['name' => 'Director', 'min' => 200000, 'max' => 250000, 'sort' => 7],
                ['name' => 'Executive', 'min' => 250000, 'max' => 350000, 'sort' => 8],
                ['name' => 'Under 50k', 'min' => 0, 'max' => 50000, 'sort' => 9],
                ['name' => '50k - 80k', 'min' => 50000, 'max' => 80000, 'sort' => 10],
                ['name' => '80k - 120k', 'min' => 80000, 'max' => 120000, 'sort' => 11],
                ['name' => '120k - 150k', 'min' => 120000, 'max' => 150000, 'sort' => 12],
                ['name' => '150k - 200k', 'min' => 150000, 'max' => 200000, 'sort' => 13],
                ['name' => '200k+', 'min' => 200000, 'max' => null, 'sort' => 14],
            ],
        ],
        'RW' => [
            'currency' => 'RWF',
            'ranges' => [
                ['name' => 'Entry Level', 'min' => 200000, 'max' => 350000, 'sort' => 1],
                ['name' => 'Junior', 'min' => 350000, 'max' => 500000, 'sort' => 2],
                ['name' => 'Mid Level', 'min' => 500000, 'max' => 800000, 'sort' => 3],
                ['name' => 'Senior', 'min' => 800000, 'max' => 1200000, 'sort' => 4],
                ['name' => 'Lead', 'min' => 1200000, 'max' => 1800000, 'sort' => 5],
                ['name' => 'Principal', 'min' => 1800000, 'max' => 2500000, 'sort' => 6],
                ['name' => 'Director', 'min' => 2500000, 'max' => 4000000, 'sort' => 7],
                ['name' => 'Executive', 'min' => 4000000, 'max' => 6000000, 'sort' => 8],
                ['name' => 'Under 300k', 'min' => 0, 'max' => 300000, 'sort' => 9],
                ['name' => '300k - 500k', 'min' => 300000, 'max' => 500000, 'sort' => 10],
                ['name' => '500k - 800k', 'min' => 500000, 'max' => 800000, 'sort' => 11],
                ['name' => '800k - 1.2M', 'min' => 800000, 'max' => 1200000, 'sort' => 12],
                ['name' => '1.2M - 2M', 'min' => 1200000, 'max' => 2000000, 'sort' => 13],
                ['name' => '2M+', 'min' => 2000000, 'max' => null, 'sort' => 14],
            ],
        ],
        'MW' => [
            'currency' => 'MWK',
            'ranges' => [
                ['name' => 'Entry Level', 'min' => 150000, 'max' => 250000, 'sort' => 1],
                ['name' => 'Junior', 'min' => 250000, 'max' => 400000, 'sort' => 2],
                ['name' => 'Mid Level', 'min' => 400000, 'max' => 700000, 'sort' => 3],
                ['name' => 'Senior', 'min' => 700000, 'max' => 1000000, 'sort' => 4],
                ['name' => 'Lead', 'min' => 1000000, 'max' => 1500000, 'sort' => 5],
                ['name' => 'Principal', 'min' => 1500000, 'max' => 2000000, 'sort' => 6],
                ['name' => 'Director', 'min' => 2000000, 'max' => 3500000, 'sort' => 7],
                ['name' => 'Executive', 'min' => 3500000, 'max' => 5000000, 'sort' => 8],
                ['name' => 'Under 200k', 'min' => 0, 'max' => 200000, 'sort' => 9],
                ['name' => '200k - 400k', 'min' => 200000, 'max' => 400000, 'sort' => 10],
                ['name' => '400k - 700k', 'min' => 400000, 'max' => 700000, 'sort' => 11],
                ['name' => '700k - 1M', 'min' => 700000, 'max' => 1000000, 'sort' => 12],
                ['name' => '1M - 1.5M', 'min' => 1000000, 'max' => 1500000, 'sort' => 13],
                ['name' => '1.5M+', 'min' => 1500000, 'max' => null, 'sort' => 14],
            ],
        ],
        'UG' => [
            'currency' => 'UGX',
            'ranges' => [
                ['name' => 'Entry Level', 'min' => 500000, 'max' => 800000, 'sort' => 1],
                ['name' => 'Junior', 'min' => 800000, 'max' => 1200000, 'sort' => 2],
                ['name' => 'Mid Level', 'min' => 1200000, 'max' => 2000000, 'sort' => 3],
                ['name' => 'Senior', 'min' => 2000000, 'max' => 3000000, 'sort' => 4],
                ['name' => 'Lead', 'min' => 3000000, 'max' => 4500000, 'sort' => 5],
                ['name' => 'Principal', 'min' => 4500000, 'max' => 6000000, 'sort' => 6],
                ['name' => 'Director', 'min' => 6000000, 'max' => 9000000, 'sort' => 7],
                ['name' => 'Executive', 'min' => 9000000, 'max' => 15000000, 'sort' => 8],
                ['name' => 'Under 500k', 'min' => 0, 'max' => 500000, 'sort' => 9],
                ['name' => '500k - 1M', 'min' => 500000, 'max' => 1000000, 'sort' => 10],
                ['name' => '1M - 2M', 'min' => 1000000, 'max' => 2000000, 'sort' => 11],
                ['name' => '2M - 3.5M', 'min' => 2000000, 'max' => 3500000, 'sort' => 12],
                ['name' => '3.5M - 5M', 'min' => 3500000, 'max' => 5000000, 'sort' => 13],
                ['name' => '5M+', 'min' => 5000000, 'max' => null, 'sort' => 14],
            ],
        ],
        'KE' => [
            'currency' => 'KES',
            'ranges' => [
                ['name' => 'Entry Level', 'min' => 30000, 'max' => 50000, 'sort' => 1],
                ['name' => 'Junior', 'min' => 50000, 'max' => 80000, 'sort' => 2],
                ['name' => 'Mid Level', 'min' => 80000, 'max' => 120000, 'sort' => 3],
                ['name' => 'Senior', 'min' => 120000, 'max' => 200000, 'sort' => 4],
                ['name' => 'Lead', 'min' => 200000, 'max' => 300000, 'sort' => 5],
                ['name' => 'Principal', 'min' => 300000, 'max' => 450000, 'sort' => 6],
                ['name' => 'Director', 'min' => 450000, 'max' => 700000, 'sort' => 7],
                ['name' => 'Executive', 'min' => 700000, 'max' => 1200000, 'sort' => 8],
                ['name' => 'Under 30k', 'min' => 0, 'max' => 30000, 'sort' => 9],
                ['name' => '30k - 60k', 'min' => 30000, 'max' => 60000, 'sort' => 10],
                ['name' => '60k - 100k', 'min' => 60000, 'max' => 100000, 'sort' => 11],
                ['name' => '100k - 150k', 'min' => 100000, 'max' => 150000, 'sort' => 12],
                ['name' => '150k - 250k', 'min' => 150000, 'max' => 250000, 'sort' => 13],
                ['name' => '250k+', 'min' => 250000, 'max' => null, 'sort' => 14],
            ],
        ],
    ];

    public function handle()
    {
        $country = $this->argument('country');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $countries = $country ? [strtoupper($country)] : array_keys(self::SALARY_DATA);

        $this->info('🚀 Starting salary ranges import...');
        $this->newLine();

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($countries as $countryCode) {
            if (!isset(self::SALARY_DATA[$countryCode])) {
                $this->error("❌ Country '{$countryCode}' not found in salary data.");
                continue;
            }

            $data = self::SALARY_DATA[$countryCode];
            $currency = $data['currency'];
            $ranges = $data['ranges'];

            $countryName = SalaryRange::getCountryName($countryCode);
            $flag = SalaryRange::getCountryFlag($countryCode);

            $this->info("📌 {$flag} Processing {$countryName} ({$countryCode}) - " . count($ranges) . " ranges");
            $this->newLine();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($ranges as $rangeData) {
                $slug = Str::slug($rangeData['name'] . '-' . $countryCode);
                
                $salaryData = [
                    'name' => $rangeData['name'],
                    'slug' => $slug,
                    'min_salary' => $rangeData['min'],
                    'max_salary' => $rangeData['max'],
                    'currency' => $currency,
                    'country_code' => $countryCode,
                    'meta_title' => "{$rangeData['name']} Jobs in {$countryName}",
                    'meta_description' => "Find {$rangeData['name']} jobs in {$countryName}. Browse positions paying {$currency} " . number_format($rangeData['min']) . " - " . ($rangeData['max'] ? number_format($rangeData['max']) : 'Unlimited'),
                    'is_active' => true,
                    'sort_order' => $rangeData['sort'],
                ];

                if ($dryRun) {
                    $this->line("  - Would import: {$rangeData['name']} ({$currency})");
                    continue;
                }

                $exists = SalaryRange::where('slug', $slug)
                    ->where('country_code', $countryCode)
                    ->first();

                if ($exists) {
                    if ($force) {
                        $exists->update($salaryData);
                        $updated++;
                        $this->line("  🔄 Updated: {$rangeData['name']}");
                    } else {
                        $skipped++;
                    }
                } else {
                    SalaryRange::create($salaryData);
                    $created++;
                }
            }

            $this->info("✅ {$flag} {$countryName} summary:");
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
            ]
        );

        $this->showSummary();
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('📊 Salary Range Summary:');
        $this->newLine();

        $summary = [];
        foreach (array_keys(self::SALARY_DATA) as $code) {
            $count = SalaryRange::where('country_code', $code)->count();
            $flag = SalaryRange::getCountryFlag($code);
            $name = SalaryRange::getCountryName($code);
            $currency = SalaryRange::getCurrencyForCountry($code);
            $summary[] = [$flag, $code, $name, $count, $currency];
        }

        $this->table(
            ['', 'Code', 'Country', 'Total Ranges', 'Currency'],
            $summary
        );
    }
}

// # Seed AU, RW, MW only
// php artisan db:seed --class=SalaryRangeSeeder

// # Import all countries
// php artisan import:salary-ranges

// # Import specific country
// php artisan import:salary-ranges AU
// php artisan import:salary-ranges RW
// php artisan import:salary-ranges MW

// # Force update existing
// php artisan import:salary-ranges --force

// # Dry run
// php artisan import:salary-ranges --dry-run