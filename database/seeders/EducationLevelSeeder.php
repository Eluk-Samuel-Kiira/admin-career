<?php

namespace Database\Seeders;

use App\Models\Job\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Education Level Seeder...');
        $this->command->newLine();

        // Seed all countries
        $this->seedAllCountries();

        $this->command->newLine();
        $this->command->info('✅ Education Level Seeder completed successfully!');
        
        // Show summary
        $this->showSummary();
    }

    private function seedAllCountries(): void
    {
        $countries = [
            'AU' => [
                'levels' => $this->getAustraliaLevels(),
                'flag' => '🇦🇺',
                'name' => 'Australia'
            ],
            'KE' => [
                'levels' => $this->getKenyaLevels(),
                'flag' => '🇰🇪',
                'name' => 'Kenya'
            ],
            'UG' => [
                'levels' => $this->getUgandaLevels(),
                'flag' => '🇺🇬',
                'name' => 'Uganda'
            ],
            'TZ' => [
                'levels' => $this->getTanzaniaLevels(),
                'flag' => '🇹🇿',
                'name' => 'Tanzania'
            ],
            'RW' => [
                'levels' => $this->getRwandaLevels(),
                'flag' => '🇷🇼',
                'name' => 'Rwanda'
            ],
            'ZM' => [
                'levels' => $this->getZambiaLevels(),
                'flag' => '🇿🇲',
                'name' => 'Zambia'
            ],
            'MW' => [
                'levels' => $this->getMalawiLevels(),
                'flag' => '🇲🇼',
                'name' => 'Malawi'
            ],
            'SG' => [
                'levels' => $this->getSingaporeLevels(),
                'flag' => '🇸🇬',
                'name' => 'Singapore'
            ],
        ];

        foreach ($countries as $code => $data) {
            $this->command->info("{$data['flag']} Seeding {$data['name']} ({$code})...");
            $this->createEducationLevels($data['levels'], $code);
            $this->command->info("✅ {$data['name']} seeded successfully!");
            $this->command->newLine();
        }
    }

    private function createEducationLevels(array $levels, string $countryCode): void
    {
        $created = 0;
        $skipped = 0;

        foreach ($levels as $data) {
            $slug = \Illuminate\Support\Str::slug($data['name'] . '-' . $countryCode);
            
            // Check if already exists for this country
            $exists = EducationLevel::where('slug', $slug)
                ->where('country_code', $countryCode)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $countryName = EducationLevel::getCountryName($countryCode);
            $metaTitle = $data['meta_title'] ?? "{$data['name']} Jobs in {$countryName} - Education Requirements";
            $metaDesc = $data['meta_description'] ?? "Find {$data['name']} level jobs in {$countryName}. Browse career opportunities requiring {$data['name']} education level.";

            EducationLevel::create([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'country_code' => $countryCode,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDesc,
                'is_active' => true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            
            $created++;
        }

        $this->command->line("  - Created: {$created}, Skipped: {$skipped}");
    }

    private function getAustraliaLevels(): array
    {
        return [
            ['name' => 'High School (Year 10)', 'sort_order' => 1],
            ['name' => 'High School (Year 12)', 'sort_order' => 2],
            ['name' => 'Certificate III', 'sort_order' => 3],
            ['name' => 'Certificate IV', 'sort_order' => 4],
            ['name' => 'Diploma', 'sort_order' => 5],
            ['name' => 'Advanced Diploma', 'sort_order' => 6],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 7],
            ['name' => 'Honours Degree', 'sort_order' => 8],
            ['name' => 'Master\'s Degree', 'sort_order' => 9],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 10],
            ['name' => 'Graduate Certificate', 'sort_order' => 11],
            ['name' => 'Graduate Diploma', 'sort_order' => 12],
        ];
    }

    private function getKenyaLevels(): array
    {
        return [
            ['name' => 'KCSE (High School)', 'sort_order' => 1],
            ['name' => 'Certificate', 'sort_order' => 2],
            ['name' => 'Diploma', 'sort_order' => 3],
            ['name' => 'Higher Diploma', 'sort_order' => 4],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 5],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 6],
            ['name' => 'Master\'s Degree', 'sort_order' => 7],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 8],
        ];
    }

    private function getUgandaLevels(): array
    {
        return [
            ['name' => 'UCE (O-Level)', 'sort_order' => 1],
            ['name' => 'UACE (A-Level)', 'sort_order' => 2],
            ['name' => 'Certificate', 'sort_order' => 3],
            ['name' => 'Diploma', 'sort_order' => 4],
            ['name' => 'Advanced Diploma', 'sort_order' => 5],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 6],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 7],
            ['name' => 'Master\'s Degree', 'sort_order' => 8],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 9],
        ];
    }

    private function getTanzaniaLevels(): array
    {
        return [
            ['name' => 'Primary Education', 'sort_order' => 1],
            ['name' => 'Secondary Education (O-Level)', 'sort_order' => 2],
            ['name' => 'Secondary Education (A-Level)', 'sort_order' => 3],
            ['name' => 'Certificate', 'sort_order' => 4],
            ['name' => 'Diploma', 'sort_order' => 5],
            ['name' => 'Advanced Diploma', 'sort_order' => 6],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 7],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 8],
            ['name' => 'Master\'s Degree', 'sort_order' => 9],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 10],
        ];
    }

    private function getRwandaLevels(): array
    {
        return [
            ['name' => 'Primary Education', 'sort_order' => 1],
            ['name' => 'O-Level', 'sort_order' => 2],
            ['name' => 'A-Level', 'sort_order' => 3],
            ['name' => 'Certificate', 'sort_order' => 4],
            ['name' => 'Diploma', 'sort_order' => 5],
            ['name' => 'Advanced Diploma', 'sort_order' => 6],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 7],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 8],
            ['name' => 'Master\'s Degree', 'sort_order' => 9],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 10],
        ];
    }

    private function getZambiaLevels(): array
    {
        return [
            ['name' => 'Grade 7', 'sort_order' => 1],
            ['name' => 'Grade 9', 'sort_order' => 2],
            ['name' => 'Grade 12', 'sort_order' => 3],
            ['name' => 'Certificate', 'sort_order' => 4],
            ['name' => 'Diploma', 'sort_order' => 5],
            ['name' => 'Advanced Diploma', 'sort_order' => 6],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 7],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 8],
            ['name' => 'Master\'s Degree', 'sort_order' => 9],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 10],
        ];
    }

    private function getMalawiLevels(): array
    {
        return [
            ['name' => 'Primary Education', 'sort_order' => 1],
            ['name' => 'Secondary Education (JCE)', 'sort_order' => 2],
            ['name' => 'Secondary Education (MSCE)', 'sort_order' => 3],
            ['name' => 'Certificate', 'sort_order' => 4],
            ['name' => 'Diploma', 'sort_order' => 5],
            ['name' => 'Advanced Diploma', 'sort_order' => 6],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 7],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 8],
            ['name' => 'Master\'s Degree', 'sort_order' => 9],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 10],
        ];
    }

    private function getSingaporeLevels(): array
    {
        return [
            ['name' => 'Primary Education', 'sort_order' => 1],
            ['name' => 'Secondary Education (O-Level)', 'sort_order' => 2],
            ['name' => 'GCE A-Level', 'sort_order' => 3],
            ['name' => 'ITE Certificate', 'sort_order' => 4],
            ['name' => 'Polytechnic Diploma', 'sort_order' => 5],
            ['name' => 'Bachelor\'s Degree', 'sort_order' => 6],
            ['name' => 'Postgraduate Diploma', 'sort_order' => 7],
            ['name' => 'Master\'s Degree', 'sort_order' => 8],
            ['name' => 'Doctorate (PhD)', 'sort_order' => 9],
        ];
    }

    private function showSummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Education Level Summary:');
        $this->command->newLine();

        $summary = [];
        $countries = EducationLevel::getAvailableCountries();
        
        foreach (array_keys($countries) as $code) {
            $count = EducationLevel::where('country_code', $code)->count();
            $info = $countries[$code];
            $summary[] = [
                $info['flag'],
                $code,
                $info['name'],
                $count,
            ];
        }

        $this->command->table(
            ['', 'Code', 'Country', 'Total Levels'],
            $summary
        );
    }
}