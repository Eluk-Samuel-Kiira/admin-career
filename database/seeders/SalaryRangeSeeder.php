<?php

namespace Database\Seeders;

use App\Models\Job\SalaryRange;
use App\Models\Job\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SalaryRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Salary Range Seeder...');
        $this->command->newLine();

        // Get all active countries from the database
        $countries = Country::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($countries->isEmpty()) {
            $this->command->error('❌ No active countries found in the database!');
            $this->command->info('Please seed countries first.');
            return;
        }

        foreach ($countries as $country) {
            $this->seedCountry($country);
        }

        $this->command->newLine();
        $this->command->info('✅ Salary Range Seeder completed successfully!');
        
        // Show summary
        $this->showSummary();
    }

    private function seedCountry(Country $country): void
    {
        $countryCode = $country->code;
        $countryName = $country->name;
        $currency = $country->currency ?? 'USD';
        $currencySymbol = $country->currency_symbol ?? '$';

        $this->command->info("🌍 Seeding {$countryName} ({$countryCode})...");

        // Get salary ranges based on country
        $ranges = $this->getSalaryRangesForCountry($countryCode, $countryName);

        $created = 0;
        $skipped = 0;

        foreach ($ranges as $data) {
            $slug = Str::slug($data['name'] . '-' . $countryCode);
            
            // Check if already exists for this country
            $exists = SalaryRange::where('slug', $slug)
                ->where('country_code', $countryCode)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $metaTitle = "{$data['name']} Jobs in {$countryName}";
            $metaDesc = "Find {$data['name']} jobs in {$countryName}. " .
                        "Browse positions paying " . $currencySymbol .
                        " " . number_format($data['min_salary']) . " - " . 
                        ($data['max_salary'] ? number_format($data['max_salary']) : 'Unlimited');

            SalaryRange::create([
                'name' => $data['name'],
                'slug' => $slug,
                'min_salary' => $data['min_salary'],
                'max_salary' => $data['max_salary'],
                'currency' => $currency,
                'country_code' => $countryCode,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDesc,
                'is_active' => true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            
            $created++;
        }

        $this->command->line("  ✅ Created: {$created}, Skipped: {$skipped}");
    }

    private function getSalaryRangesForCountry(string $countryCode, string $countryName): array
    {
        // Define ranges based on country
        switch ($countryCode) {
            case 'AU':
                return $this->getAustraliaRanges();
            case 'UG':
                return $this->getUgandaRanges();
            case 'KE':
                return $this->getKenyaRanges();
            case 'TZ':
                return $this->getTanzaniaRanges();
            case 'RW':
                return $this->getRwandaRanges();
            case 'MW':
                return $this->getMalawiRanges();
            case 'ZM':
                return $this->getZambiaRanges();
            case 'SG':
                return $this->getSingaporeRanges();
            default:
                // Default ranges for any other country
                return $this->getDefaultRanges($countryName);
        }
    }

    private function getAustraliaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 45000, 'max_salary' => 60000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 60000, 'max_salary' => 80000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 80000, 'max_salary' => 100000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 100000, 'max_salary' => 130000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 130000, 'max_salary' => 160000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 160000, 'max_salary' => 200000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 200000, 'max_salary' => 250000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 250000, 'max_salary' => 350000, 'sort_order' => 8],
            ['name' => 'Under 50k', 'min_salary' => 0, 'max_salary' => 50000, 'sort_order' => 9],
            ['name' => '50k - 80k', 'min_salary' => 50000, 'max_salary' => 80000, 'sort_order' => 10],
            ['name' => '80k - 120k', 'min_salary' => 80000, 'max_salary' => 120000, 'sort_order' => 11],
            ['name' => '120k - 150k', 'min_salary' => 120000, 'max_salary' => 150000, 'sort_order' => 12],
            ['name' => '150k - 200k', 'min_salary' => 150000, 'max_salary' => 200000, 'sort_order' => 13],
            ['name' => '200k+', 'min_salary' => 200000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getUgandaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 500000, 'max_salary' => 800000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 800000, 'max_salary' => 1200000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 1200000, 'max_salary' => 2000000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 2000000, 'max_salary' => 3000000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 3000000, 'max_salary' => 4000000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 4000000, 'max_salary' => 6000000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 6000000, 'max_salary' => 8000000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 8000000, 'max_salary' => 12000000, 'sort_order' => 8],
            ['name' => 'Under 500k', 'min_salary' => 0, 'max_salary' => 500000, 'sort_order' => 9],
            ['name' => '500k - 1M', 'min_salary' => 500000, 'max_salary' => 1000000, 'sort_order' => 10],
            ['name' => '1M - 2M', 'min_salary' => 1000000, 'max_salary' => 2000000, 'sort_order' => 11],
            ['name' => '2M - 3M', 'min_salary' => 2000000, 'max_salary' => 3000000, 'sort_order' => 12],
            ['name' => '3M - 5M', 'min_salary' => 3000000, 'max_salary' => 5000000, 'sort_order' => 13],
            ['name' => '5M+', 'min_salary' => 5000000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getKenyaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 25000, 'max_salary' => 50000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 50000, 'max_salary' => 80000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 80000, 'max_salary' => 120000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 120000, 'max_salary' => 180000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 180000, 'max_salary' => 250000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 250000, 'max_salary' => 350000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 350000, 'max_salary' => 500000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 500000, 'max_salary' => 750000, 'sort_order' => 8],
            ['name' => 'Under 25k', 'min_salary' => 0, 'max_salary' => 25000, 'sort_order' => 9],
            ['name' => '25k - 50k', 'min_salary' => 25000, 'max_salary' => 50000, 'sort_order' => 10],
            ['name' => '50k - 100k', 'min_salary' => 50000, 'max_salary' => 100000, 'sort_order' => 11],
            ['name' => '100k - 200k', 'min_salary' => 100000, 'max_salary' => 200000, 'sort_order' => 12],
            ['name' => '200k - 400k', 'min_salary' => 200000, 'max_salary' => 400000, 'sort_order' => 13],
            ['name' => '400k+', 'min_salary' => 400000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getTanzaniaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 400000, 'max_salary' => 600000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 600000, 'max_salary' => 1000000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 1000000, 'max_salary' => 1500000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 1500000, 'max_salary' => 2500000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 2500000, 'max_salary' => 3500000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 3500000, 'max_salary' => 5000000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 5000000, 'max_salary' => 7000000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 7000000, 'max_salary' => 10000000, 'sort_order' => 8],
            ['name' => 'Under 400k', 'min_salary' => 0, 'max_salary' => 400000, 'sort_order' => 9],
            ['name' => '400k - 800k', 'min_salary' => 400000, 'max_salary' => 800000, 'sort_order' => 10],
            ['name' => '800k - 1.5M', 'min_salary' => 800000, 'max_salary' => 1500000, 'sort_order' => 11],
            ['name' => '1.5M - 2.5M', 'min_salary' => 1500000, 'max_salary' => 2500000, 'sort_order' => 12],
            ['name' => '2.5M - 4M', 'min_salary' => 2500000, 'max_salary' => 4000000, 'sort_order' => 13],
            ['name' => '4M+', 'min_salary' => 4000000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getRwandaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 200000, 'max_salary' => 350000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 350000, 'max_salary' => 500000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 500000, 'max_salary' => 800000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 800000, 'max_salary' => 1200000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 1200000, 'max_salary' => 1800000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 1800000, 'max_salary' => 2500000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 2500000, 'max_salary' => 4000000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 4000000, 'max_salary' => 6000000, 'sort_order' => 8],
            ['name' => 'Under 300k', 'min_salary' => 0, 'max_salary' => 300000, 'sort_order' => 9],
            ['name' => '300k - 500k', 'min_salary' => 300000, 'max_salary' => 500000, 'sort_order' => 10],
            ['name' => '500k - 800k', 'min_salary' => 500000, 'max_salary' => 800000, 'sort_order' => 11],
            ['name' => '800k - 1.2M', 'min_salary' => 800000, 'max_salary' => 1200000, 'sort_order' => 12],
            ['name' => '1.2M - 2M', 'min_salary' => 1200000, 'max_salary' => 2000000, 'sort_order' => 13],
            ['name' => '2M+', 'min_salary' => 2000000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getMalawiRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 150000, 'max_salary' => 250000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 250000, 'max_salary' => 400000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 400000, 'max_salary' => 700000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 700000, 'max_salary' => 1000000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 1000000, 'max_salary' => 1500000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 1500000, 'max_salary' => 2000000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 2000000, 'max_salary' => 3500000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 3500000, 'max_salary' => 5000000, 'sort_order' => 8],
            ['name' => 'Under 200k', 'min_salary' => 0, 'max_salary' => 200000, 'sort_order' => 9],
            ['name' => '200k - 400k', 'min_salary' => 200000, 'max_salary' => 400000, 'sort_order' => 10],
            ['name' => '400k - 700k', 'min_salary' => 400000, 'max_salary' => 700000, 'sort_order' => 11],
            ['name' => '700k - 1M', 'min_salary' => 700000, 'max_salary' => 1000000, 'sort_order' => 12],
            ['name' => '1M - 1.5M', 'min_salary' => 1000000, 'max_salary' => 1500000, 'sort_order' => 13],
            ['name' => '1.5M+', 'min_salary' => 1500000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getZambiaRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 3000, 'max_salary' => 5000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 5000, 'max_salary' => 8000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 8000, 'max_salary' => 12000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 12000, 'max_salary' => 18000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 18000, 'max_salary' => 25000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 25000, 'max_salary' => 35000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 35000, 'max_salary' => 50000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 50000, 'max_salary' => 75000, 'sort_order' => 8],
            ['name' => 'Under 3k', 'min_salary' => 0, 'max_salary' => 3000, 'sort_order' => 9],
            ['name' => '3k - 5k', 'min_salary' => 3000, 'max_salary' => 5000, 'sort_order' => 10],
            ['name' => '5k - 10k', 'min_salary' => 5000, 'max_salary' => 10000, 'sort_order' => 11],
            ['name' => '10k - 20k', 'min_salary' => 10000, 'max_salary' => 20000, 'sort_order' => 12],
            ['name' => '20k - 40k', 'min_salary' => 20000, 'max_salary' => 40000, 'sort_order' => 13],
            ['name' => '40k+', 'min_salary' => 40000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getSingaporeRanges(): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 2500, 'max_salary' => 3500, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 3500, 'max_salary' => 5000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 5000, 'max_salary' => 7500, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 7500, 'max_salary' => 10000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 10000, 'max_salary' => 13000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 13000, 'max_salary' => 17000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 17000, 'max_salary' => 22000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 22000, 'max_salary' => 30000, 'sort_order' => 8],
            ['name' => 'Under 2.5k', 'min_salary' => 0, 'max_salary' => 2500, 'sort_order' => 9],
            ['name' => '2.5k - 4k', 'min_salary' => 2500, 'max_salary' => 4000, 'sort_order' => 10],
            ['name' => '4k - 6k', 'min_salary' => 4000, 'max_salary' => 6000, 'sort_order' => 11],
            ['name' => '6k - 9k', 'min_salary' => 6000, 'max_salary' => 9000, 'sort_order' => 12],
            ['name' => '9k - 15k', 'min_salary' => 9000, 'max_salary' => 15000, 'sort_order' => 13],
            ['name' => '15k+', 'min_salary' => 15000, 'max_salary' => null, 'sort_order' => 14],
        ];
    }

    private function getDefaultRanges(string $countryName): array
    {
        return [
            ['name' => 'Entry Level', 'min_salary' => 0, 'max_salary' => 10000, 'sort_order' => 1],
            ['name' => 'Junior', 'min_salary' => 10000, 'max_salary' => 20000, 'sort_order' => 2],
            ['name' => 'Mid Level', 'min_salary' => 20000, 'max_salary' => 40000, 'sort_order' => 3],
            ['name' => 'Senior', 'min_salary' => 40000, 'max_salary' => 60000, 'sort_order' => 4],
            ['name' => 'Lead', 'min_salary' => 60000, 'max_salary' => 80000, 'sort_order' => 5],
            ['name' => 'Principal', 'min_salary' => 80000, 'max_salary' => 100000, 'sort_order' => 6],
            ['name' => 'Director', 'min_salary' => 100000, 'max_salary' => 150000, 'sort_order' => 7],
            ['name' => 'Executive', 'min_salary' => 150000, 'max_salary' => 200000, 'sort_order' => 8],
        ];
    }

    private function showSummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Salary Range Summary:');
        $this->command->newLine();

        $summary = [];
        $countries = Country::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($countries as $country) {
            $count = SalaryRange::where('country_code', $country->code)->count();
            $summary[] = [
                $country->flag_emoji,
                $country->code,
                $country->name,
                $count,
                $country->currency ?? 'N/A',
            ];
        }

        $this->command->table(
            ['', 'Code', 'Country', 'Total Ranges', 'Currency'],
            $summary
        );
    }
}