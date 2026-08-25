<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job\JobLocation;
use Illuminate\Support\Str;

class JobLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only seed Australia (AU) locations
        $locations = [
            // Australian States/Cities
            [
                'country' => 'AU',
                'district' => 'New South Wales',
                'city' => 'Sydney',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Victoria',
                'city' => 'Melbourne',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Queensland',
                'city' => 'Brisbane',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Western Australia',
                'city' => 'Perth',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'South Australia',
                'city' => 'Adelaide',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Australian Capital Territory',
                'city' => 'Canberra',
                'is_capital' => true,
            ],
            [
                'country' => 'AU',
                'district' => 'Tasmania',
                'city' => 'Hobart',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Northern Territory',
                'city' => 'Darwin',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Gold Coast',
                'city' => 'Gold Coast',
                'is_capital' => false,
            ],
            [
                'country' => 'AU',
                'district' => 'Newcastle',
                'city' => 'Newcastle',
                'is_capital' => false,
            ],
        ];

        foreach ($locations as $locationData) {
            $country = $locationData['country'];
            $district = $locationData['district'];
            $city = $locationData['city'];
            $isCapital = $locationData['is_capital'];
            
            // Generate slug
            $slug = Str::slug($district) . '-jobs-in-' . strtolower($country);
            
            // Check if location already exists
            $exists = JobLocation::where('slug', $slug)->exists();
            
            if (!$exists) {
                JobLocation::create([
                    'country' => $country,
                    'country_code' => $country,
                    'district' => $district,
                    'city' => $city,
                    'region' => 'Oceania',
                    'slug' => $slug,
                    'description' => "Find jobs in {$district}, Australia. Browse career opportunities in {$city}, {$district}, Australia.",
                    'meta_title' => "Jobs in {$district}, Australia - Latest Career Opportunities",
                    'meta_description' => "Find latest jobs in {$district}, Australia. Browse career opportunities, vacancies, and employment in {$district}, Australia. Apply today!",
                    'is_active' => true,
                    'is_capital' => $isCapital,
                    'sort_order' => 1,
                    'latitude' => $locationData['latitude'] ?? null,
                    'longitude' => $locationData['longitude'] ?? null,
                    'timezone' => 'Australia/Sydney',
                ]);
                
                $this->command->info("Created location: {$district}, Australia");
            } else {
                $this->command->warn("Location already exists: {$district}, Australia");
            }
        }
        
        $this->command->info('✅ Australia job locations seeded successfully!');
    }
}