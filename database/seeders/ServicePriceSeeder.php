<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Job\Country;   // ← correct namespace
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
            // XX fallback in USD
            ['service' => 'cv_review',             'country' => 'XX', 'currency' => $usd, 'amount' => 25.00,  'interval' => null],
            ['service' => 'cv_rewrite',            'country' => 'XX', 'currency' => $usd, 'amount' => 75.00,  'interval' => null],
            ['service' => 'cover_letter',          'country' => 'XX', 'currency' => $usd, 'amount' => 35.00,  'interval' => null],
            ['service' => 'linkedin_optimization', 'country' => 'XX', 'currency' => $usd, 'amount' => 60.00,  'interval' => null],
            ['service' => 'interview_coaching',    'country' => 'XX', 'currency' => $usd, 'amount' => 90.00,  'interval' => null],
            ['service' => 'premium_alerts',        'country' => 'XX', 'currency' => $usd, 'amount' => 9.99,   'interval' => 'month'],
            ['service' => 'featured_applicant',    'country' => 'XX', 'currency' => $usd, 'amount' => 14.99,  'interval' => 'month'],
            ['service' => 'career_mentorship',     'country' => 'XX', 'currency' => $usd, 'amount' => 149.00, 'interval' => 'month'],
            ['service' => 'recruiter_access',      'country' => 'XX', 'currency' => $usd, 'amount' => 29.99,  'interval' => 'month'],
            ['service' => 'basic_job_alerts',      'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'resume_builder',        'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'salary_calculator',     'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
            ['service' => 'career_resources',      'country' => 'XX', 'currency' => $usd, 'amount' => 0,      'interval' => null],
        ];

        // Country-specific overrides (local currency)
        if ($ugx) {
            $prices[] = ['service' => 'cv_review',      'country' => 'UG', 'currency' => $ugx, 'amount' => 95000,  'interval' => null];
            $prices[] = ['service' => 'cv_rewrite',     'country' => 'UG', 'currency' => $ugx, 'amount' => 280000, 'interval' => null];
            $prices[] = ['service' => 'premium_alerts', 'country' => 'UG', 'currency' => $ugx, 'amount' => 35000,  'interval' => 'month'];
        }

        if ($kes) {
            $prices[] = ['service' => 'cv_review',      'country' => 'KE', 'currency' => $kes, 'amount' => 3500,  'interval' => null];
            $prices[] = ['service' => 'cv_rewrite',     'country' => 'KE', 'currency' => $kes, 'amount' => 11000, 'interval' => null];
            $prices[] = ['service' => 'premium_alerts', 'country' => 'KE', 'currency' => $kes, 'amount' => 1200,  'interval' => 'month'];
        }

        foreach ($prices as $p) {
            $service = $services[$p['service']] ?? null;
            if (!$service) continue;

            ServicePrice::updateOrCreate(
                [
                    'service_id'   => $service->id,
                    'country_code' => $p['country'],
                ],
                [
                    'currency_id'  => $p['currency']->id,
                    // ✅ Currency-aware: UGX 95000 stays 95000, USD 25.00 → 2500
                    'amount_cents' => $p['currency']->toCents((float) $p['amount']),
                    'interval'     => $p['interval'],
                    'is_active'    => true,
                ]
            );
        }

        $this->command->info('✅ Seeded service prices.');
    }
}