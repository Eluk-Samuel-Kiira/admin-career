<?php

namespace Database\Seeders;

use App\Models\Job\SocialMediaPlatform;
use App\Models\Job\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SocialMediaPlatformSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Starting Social Media Platform Seeder...');
        $this->command->newLine();

        $countries = Country::where('is_active', true)->get();

        foreach ($countries as $country) {
            $this->seedCountry($country);
        }

        $this->command->newLine();
        $this->command->info('✅ Social Media Platform Seeder completed successfully!');
        $this->showSummary();
    }

    private function seedCountry($country)
    {
        $countryCode = $country->code;
        $countryName = $country->name;
        $domain = strtolower($countryCode);

        $this->command->info("🌍 Seeding {$countryName} ({$countryCode})...");

        $platforms = [
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'facebook',
                'url' => "https://www.facebook.com/great{$domain}jobs",
                'handle' => "@great{$domain}jobs",
                'description' => "Follow us on Facebook for the latest job opportunities in {$countryName}.",
                'is_verified' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'twitter',
                'url' => "https://twitter.com/great{$domain}jobs",
                'handle' => "@great{$domain}jobs",
                'description' => "Stay updated with job alerts and career tips on Twitter.",
                'is_verified' => true,
                'sort_order' => 2,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'linkedin',
                'url' => "https://www.linkedin.com/company/great{$domain}jobs",
                'handle' => "great{$domain}jobs",
                'description' => "Connect with us on LinkedIn for professional networking and job opportunities.",
                'is_verified' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'instagram',
                'url' => "https://www.instagram.com/great{$domain}jobs",
                'handle' => "@great{$domain}jobs",
                'description' => "Follow us on Instagram for daily job updates and career inspiration.",
                'sort_order' => 4,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'youtube',
                'url' => "https://www.youtube.com/@great{$domain}jobs",
                'handle' => "great{$domain}jobs",
                'description' => "Subscribe to our YouTube channel for career advice and job search tips.",
                'sort_order' => 5,
            ],
            [
                'name' => "WhatsApp {$countryName}",
                'platform' => 'whatsapp',
                'url' => "https://wa.me/". $country->phone_code . "0000000",
                'handle' => "WhatsApp",
                'description' => "Get instant job alerts on WhatsApp. Join our community!",
                'sort_order' => 6,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'telegram',
                'url' => "https://t.me/great{$domain}jobs",
                'handle' => "great{$domain}jobs",
                'description' => "Join our Telegram channel for real-time job notifications.",
                'sort_order' => 7,
            ],
            [
                'name' => "Great Jobs {$countryName}",
                'platform' => 'tiktok',
                'url' => "https://www.tiktok.com/@great{$domain}jobs",
                'handle' => "@great{$domain}jobs",
                'description' => "Follow us on TikTok for creative career content and job tips.",
                'sort_order' => 8,
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($platforms as $data) {
            $data['country_code'] = $countryCode;
            $data['meta_title'] = "{$data['name']} - Follow us on {$data['platform']}";
            $data['meta_description'] = "Follow Great Jobs {$countryName} on {$data['platform']} for the latest job opportunities, career tips, and updates.";

            $slug = Str::slug($data['name'] . '-' . $data['platform'] . '-' . $countryCode);
            
            $exists = SocialMediaPlatform::where('slug', $slug)
                ->where('country_code', $countryCode)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $platform = SocialMediaPlatform::create($data);
            
            // Create initial follower history with random count
            $initialCount = rand(1000, 50000);
            $platform->recordFollowers($initialCount, 'Initial record');
            
            // Create some historical records for the past few months
            $this->createHistoricalRecords($platform, $initialCount);
            
            $created++;
        }

        $this->command->line("  ✅ Created: {$created}, Skipped: {$skipped}");
    }

    private function createHistoricalRecords($platform, $currentCount)
    {
        // Create records for the past 3 months
        for ($i = 1; $i <= 90; $i++) {
            $daysAgo = $i;
            $date = now()->subDays($daysAgo);
            
            // Random variation within 20% of current count
            $variation = rand(-20, 20);
            $count = max(0, $currentCount + ($currentCount * $variation / 100));
            
            $platform->followerHistories()->create([
                'followers_count' => (int)$count,
                'recorded_at' => $date,
                'note' => "Historical record from {$date->format('Y-m-d')}",
            ]);
        }
        
        // Ensure we have at least one record for today
        $platform->followerHistories()->create([
            'followers_count' => $currentCount,
            'recorded_at' => now(),
            'note' => 'Current record',
        ]);
    }

    private function showSummary()
    {
        $this->command->newLine();
        $this->command->info('📊 Social Media Platforms Summary:');
        $this->command->newLine();

        $summary = [];
        $countries = Country::where('is_active', true)->orderBy('name')->get();

        foreach ($countries as $country) {
            $count = SocialMediaPlatform::where('country_code', $country->code)->count();
            $summary[] = [
                $country->flag_emoji,
                $country->code,
                $country->name,
                $count,
            ];
        }

        $this->command->table(
            ['', 'Code', 'Country', 'Total Platforms'],
            $summary
        );
    }
}