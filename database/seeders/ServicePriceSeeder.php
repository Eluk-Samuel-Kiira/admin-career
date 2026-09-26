<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Job\Country;
use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use Illuminate\Database\Seeder;

class ServicePriceSeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $ugx = Currency::where('code', 'UGX')->first();
        $kes = Currency::where('code', 'KES')->first();

        if (!$usd) {
            $this->command->warn('USD currency not found — skipping price seed.');
            return;
        }

        $services = Service::all()->keyBy('key');

        $prices = [
            // =================================================================
            // CV REVIEW SERVICES — XX (USD fallback)
            // =================================================================
            ['service' => 'cv_review',             'country' => 'XX', 'currency' => $usd, 'amount' => 25.00,  'interval' => null],
            ['service' => 'cv_rewrite',            'country' => 'XX', 'currency' => $usd, 'amount' => 75.00,  'interval' => null],
            ['service' => 'cover_letter',          'country' => 'XX', 'currency' => $usd, 'amount' => 35.00,  'interval' => null],
            ['service' => 'linkedin_optimization', 'country' => 'XX', 'currency' => $usd, 'amount' => 60.00,  'interval' => null],
            ['service' => 'interview_coaching',    'country' => 'XX', 'currency' => $usd, 'amount' => 90.00,  'interval' => null],

            // =================================================================
            // SUBSCRIPTION SERVICES — XX (USD fallback)
            // =================================================================
            ['service' => 'premium_alerts',        'country' => 'XX', 'currency' => $usd, 'amount' => 9.99,   'interval' => 'month'],
            ['service' => 'featured_applicant',    'country' => 'XX', 'currency' => $usd, 'amount' => 14.99,  'interval' => 'month'],
            ['service' => 'career_mentorship',     'country' => 'XX', 'currency' => $usd, 'amount' => 149.00, 'interval' => 'month'],
            ['service' => 'recruiter_access',      'country' => 'XX', 'currency' => $usd, 'amount' => 29.99,  'interval' => 'month'],

            // =================================================================
            // FREE SERVICES — XX
            // =================================================================
            ['service' => 'basic_job_alerts',      'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'resume_builder',        'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'salary_calculator',     'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'career_resources',      'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],

            // =================================================================
            // JOB POSTING PACKAGES — XX (USD fallback)
            // =================================================================
            ['service' => 'job_post_free',         'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'job_post_standard',     'country' => 'XX', 'currency' => $usd, 'amount' => 15.00,  'interval' => null],
            ['service' => 'job_post_popular',      'country' => 'XX', 'currency' => $usd, 'amount' => 30.00,  'interval' => null],
            ['service' => 'job_post_priority',     'country' => 'XX', 'currency' => $usd, 'amount' => 45.00,  'interval' => null],
            ['service' => 'job_post_enterprise',   'country' => 'XX', 'currency' => $usd, 'amount' => 100.00, 'interval' => null],
        ];

        // =====================================================================
        // UGANDA — local currency (UGX)
        // =====================================================================
        if ($ugx) {
            // CV Review UG overrides
            $prices[] = ['service' => 'cv_review',      'country' => 'UG', 'currency' => $ugx, 'amount' => 95000,  'interval' => null];
            $prices[] = ['service' => 'cv_rewrite',     'country' => 'UG', 'currency' => $ugx, 'amount' => 280000, 'interval' => null];
            $prices[] = ['service' => 'premium_alerts', 'country' => 'UG', 'currency' => $ugx, 'amount' => 35000,  'interval' => 'month'];

            // Job Posting Packages UG
            $prices[] = ['service' => 'job_post_free',       'country' => 'UG', 'currency' => $ugx, 'amount' => 0,      'interval' => null];
            $prices[] = ['service' => 'job_post_standard',   'country' => 'UG', 'currency' => $ugx, 'amount' => 50000,  'interval' => null];
            $prices[] = ['service' => 'job_post_popular',    'country' => 'UG', 'currency' => $ugx, 'amount' => 100000, 'interval' => null];
            $prices[] = ['service' => 'job_post_priority',   'country' => 'UG', 'currency' => $ugx, 'amount' => 150000, 'interval' => null];
            $prices[] = ['service' => 'job_post_enterprise', 'country' => 'UG', 'currency' => $ugx, 'amount' => 350000, 'interval' => null];
        }

        // =====================================================================
        // KENYA — local currency (KES)
        // =====================================================================
        if ($kes) {
            // CV Review KE overrides
            $prices[] = ['service' => 'cv_review',      'country' => 'KE', 'currency' => $kes, 'amount' => 3500,  'interval' => null];
            $prices[] = ['service' => 'cv_rewrite',     'country' => 'KE', 'currency' => $kes, 'amount' => 11000, 'interval' => null];
            $prices[] = ['service' => 'premium_alerts', 'country' => 'KE', 'currency' => $kes, 'amount' => 1200,  'interval' => 'month'];

            // Job Posting Packages KE
            $prices[] = ['service' => 'job_post_free',       'country' => 'KE', 'currency' => $kes, 'amount' => 0,     'interval' => null];
            $prices[] = ['service' => 'job_post_standard',   'country' => 'KE', 'currency' => $kes, 'amount' => 2000,  'interval' => null];
            $prices[] = ['service' => 'job_post_popular',    'country' => 'KE', 'currency' => $kes, 'amount' => 4000,  'interval' => null];
            $prices[] = ['service' => 'job_post_priority',   'country' => 'KE', 'currency' => $kes, 'amount' => 6000,  'interval' => null];
            $prices[] = ['service' => 'job_post_enterprise', 'country' => 'KE', 'currency' => $kes, 'amount' => 13000, 'interval' => null];
        }

        // =====================================================================
        // Insert / update
        // =====================================================================
        $created = 0;
        $updated = 0;

        foreach ($prices as $p) {
            $service = $services[$p['service']] ?? null;

            if (!$service) {
                $this->command->warn("⚠️  Service '{$p['service']}' not found — skipping.");
                continue;
            }

            $existing = ServicePrice::where('service_id', $service->id)
                ->where('country_code', $p['country'])
                ->first();

            ServicePrice::updateOrCreate(
                [
                    'service_id'   => $service->id,
                    'country_code' => $p['country'],
                ],
                [
                    'currency_id'  => $p['currency']->id,
                    // Currency-aware: UGX 95000 stays 95000, USD 25.00 → 2500
                    'amount_cents' => $p['currency']->toCents((float) $p['amount']),
                    'interval'     => $p['interval'],
                    'is_active'    => true,
                ]
            );

            $existing ? $updated++ : $created++;
        }

        $this->command->info("✅ Seeded service prices — {$created} created, {$updated} updated.");

        // Summary per service family
        $jobPostCount = collect($prices)
            ->filter(fn($p) => str_starts_with($p['service'], 'job_post_'))
            ->count();

        $this->command->info("   → {$jobPostCount} job posting package prices.");
    }
}