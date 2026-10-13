<?php

namespace Database\Seeders;

use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;
use App\Models\Subscription\PaymentWebhook;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubscriptionTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Pick a user to attach the transactions to
        $user = User::where('is_active', true)->orderBy('id')->first();

        if (!$user) {
            $this->command->warn('No active users found — aborting.');
            return;
        }

        $countryCode = strtoupper($user->country_code ?? 'UG');
        $this->command->info("Seeding for user #{$user->id} ({$user->email}) in {$countryCode}");

        // ── Subscription #1: premium_alerts, ACTIVE, paid
        $premium = Service::where('key', 'premium_alerts')->first();
        if ($premium) {
            $price = ServicePrice::resolve($premium->key, $countryCode);

            if ($price) {
                $sub = Subscription::create([
                    'user_id'              => $user->id,
                    'service_key'          => $premium->key,
                    'price_id'             => $price->id,
                    'country_code'         => $countryCode,
                    'currency'             => $price->currency->code,
                    'amount_cents'         => $price->amount_cents,
                    'interval'             => $price->interval ?? 'month',
                    'status'               => 'active',
                    'starts_at'            => now()->subDays(12),
                    'current_period_start' => now()->subDays(12),
                    'current_period_end'   => now()->addDays(18),
                    'feature_snapshot'     => $premium->meta ?? [],
                    'gateway'              => 'test',
                    'gateway_subscription_id' => 'sub_test_' . Str::random(16),
                ]);

                $this->seedPaidPayment($sub, 'succeeded', now()->subDays(12), 1);
                $this->seedPaidPayment($sub, 'succeeded', now()->subDays(42), 1);
                $this->seedPaidPayment($sub, 'refunded',  now()->subDays(72), 1);

                $this->command->info("Subscription: premium_alerts — active (3 payments)");
            }
        }

        // ── Subscription #2: featured_applicant, PAST_DUE
        $featured = Service::where('key', 'featured_applicant')->first();
        if ($featured) {
            $price = ServicePrice::resolve($featured->key, $countryCode);

            if ($price) {
                $sub = Subscription::create([
                    'user_id'              => $user->id,
                    'service_key'          => $featured->key,
                    'price_id'             => $price->id,
                    'country_code'         => $countryCode,
                    'currency'             => $price->currency->code,
                    'amount_cents'         => $price->amount_cents,
                    'interval'             => $price->interval ?? 'month',
                    'status'               => 'past_due',
                    'starts_at'            => now()->subDays(45),
                    'current_period_start' => now()->subDays(45),
                    'current_period_end'   => now()->subDays(15),
                    'feature_snapshot'     => $featured->meta ?? [],
                    'gateway'              => 'test',
                    'gateway_subscription_id' => 'sub_test_' . Str::random(16),
                ]);

                $this->seedPaidPayment($sub, 'succeeded', now()->subDays(45), 1);
                $this->seedPaidPayment($sub, 'failed',    now()->subDays(15), 1, 'Insufficient funds');
                $this->seedPaidPayment($sub, 'failed',    now()->subDays(12), 1, 'Card declined');

                $this->command->info("Subscription: featured_applicant — past_due (3 payments)");
            }
        }

        // ── Subscription #3: career_mentorship, CANCELLED (ending at period end)
        $mentorship = Service::where('key', 'career_mentorship')->first();
        if ($mentorship) {
            $price = ServicePrice::resolve($mentorship->key, $countryCode);

            if ($price) {
                $sub = Subscription::create([
                    'user_id'              => $user->id,
                    'service_key'          => $mentorship->key,
                    'price_id'             => $price->id,
                    'country_code'         => $countryCode,
                    'currency'             => $price->currency->code,
                    'amount_cents'         => $price->amount_cents,
                    'interval'             => $price->interval ?? 'month',
                    'status'               => 'active',
                    'starts_at'            => now()->subDays(8),
                    'current_period_start' => now()->subDays(8),
                    'current_period_end'   => now()->addDays(22),
                    'cancel_at_period_end' => true,
                    'cancelled_at'         => now()->subDays(2),
                    'feature_snapshot'     => $mentorship->meta ?? [],
                    'gateway'              => 'test',
                    'gateway_subscription_id' => 'sub_test_' . Str::random(16),
                ]);

                $this->seedPaidPayment($sub, 'succeeded', now()->subDays(8), 1);

                $this->command->info("Subscription: career_mentorship — active, cancels at period end (1 payment)");
            }
        }

        // ── A pending subscription with an unpaid payment attempt
        // Useful to see the "pending" state in the admin UI
        if ($premium) {
            $price = ServicePrice::resolve($premium->key, $countryCode);
            if ($price) {
                $pendingSub = Subscription::create([
                    'user_id'              => $user->id,
                    'service_key'          => $premium->key,
                    'price_id'             => $price->id,
                    'country_code'         => $countryCode,
                    'currency'             => $price->currency->code,
                    'amount_cents'         => $price->amount_cents,
                    'interval'             => $price->interval ?? 'month',
                    'status'               => 'trialing',
                    'trial_ends_at'        => now()->addDays(5),
                    'starts_at'            => now(),
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addDays(5),
                    'feature_snapshot'     => $premium->meta ?? [],
                    'gateway'              => 'test',
                    'gateway_subscription_id' => 'sub_test_' . Str::random(16),
                ]);

                SubscriptionPayment::create([
                    'subscription_id'       => $pendingSub->id,
                    'user_id'               => $user->id,
                    'gateway'               => 'test',
                    'gateway_reference'     => 'ref_test_' . Str::random(16),
                    'gateway_transaction_id'=> null,
                    'currency'              => $price->currency->code,
                    'amount_cents'          => $price->amount_cents,
                    'status'                => 'pending',
                    'paid_at'               => null,
                ]);

                $this->command->info("Subscription: premium_alerts — trialing with 1 pending payment");
            }
        }

        // ── One-time payments (CV services, job posting packages)
        $this->seedOneTimePayments($user, $countryCode);

        // ── A couple of raw webhooks for debugging
        $this->seedWebhooks();

        $this->command->info('Done.');
    }

    /**
     * Create a single payment row for a subscription.
     */
    protected function seedPaidPayment(
        Subscription $sub,
        string $status,
        \Carbon\Carbon $paidAt,
        int $multiplier = 1,
        ?string $failureReason = null,
    ): void {
        SubscriptionPayment::create([
            'subscription_id'        => $sub->id,
            'user_id'                => $sub->user_id,
            'gateway'                => $sub->gateway ?: 'test',
            'gateway_reference'      => 'ref_test_' . Str::random(16),
            'gateway_transaction_id' => $status === 'succeeded' ? 'txn_test_' . Str::random(16) : null,
            'currency'               => $sub->currency,
            'amount_cents'           => $sub->amount_cents * $multiplier,
            'status'                 => $status,
            'failure_reason'         => $failureReason,
            'paid_at'                => $status === 'succeeded' || $status === 'refunded' ? $paidAt : null,
            'gateway_payload'        => [
                'test'   => true,
                'status' => $status,
                'seeded' => true,
            ],
            'created_at'             => $paidAt,
            'updated_at'             => $paidAt,
        ]);
    }

    /**
     * One-time payments are recorded against subscriptions but with a different
     * note — they represent a single-charge purchase of a CV or job-post service.
     * In the current schema we do not have a separate table, so we log them as
     * `status = succeeded` payments tied to a matching subscription row.
     */
    protected function seedOneTimePayments(User $user, string $countryCode): void
    {
        $services = Service::whereIn('key', [
            'cv_review',
            'cv_rewrite',
            'cover_letter',
            'job_post_popular',
            'job_post_enterprise',
        ])->get();

        foreach ($services as $service) {
            $price = ServicePrice::resolve($service->key, $countryCode);
            if (!$price) continue;

            // For one-time services, we create a subscription record with the
            // same schema but a single period (starts and ends immediately)
            // so the admin list shows it as a completed transaction.
            $sub = Subscription::create([
                'user_id'              => $user->id,
                'service_key'          => $service->key,
                'price_id'             => $price->id,
                'country_code'         => $countryCode,
                'currency'             => $price->currency->code,
                'amount_cents'         => $price->amount_cents,
                'interval'             => 'one_time',
                'status'               => 'active',
                'starts_at'            => now()->subDays(rand(1, 60)),
                'current_period_start' => now()->subDays(rand(1, 60)),
                'current_period_end'   => now(),
                'feature_snapshot'     => $service->meta ?? [],
                'gateway'              => 'test',
                'gateway_subscription_id' => 'one_time_' . Str::random(16),
            ]);

            SubscriptionPayment::create([
                'subscription_id'        => $sub->id,
                'user_id'                => $user->id,
                'gateway'                => 'test',
                'gateway_reference'      => 'ref_test_' . Str::random(16),
                'gateway_transaction_id' => 'txn_test_' . Str::random(16),
                'currency'               => $price->currency->code,
                'amount_cents'           => $price->amount_cents,
                'status'                 => 'succeeded',
                'paid_at'                => now()->subDays(rand(1, 30)),
                'gateway_payload'        => ['test' => true, 'kind' => 'one_time', 'seeded' => true],
            ]);
        }

        $this->command->info("One-time payments seeded: {$services->count()} transactions");
    }

    protected function seedWebhooks(): void
    {
        $samples = [
            [
                'gateway'    => 'test',
                'event_type' => 'payment.succeeded',
                'payload'    => ['event' => 'payment.succeeded', 'reference' => 'ref_test_' . Str::random(12)],
                'signature_valid' => true,
                'status'     => 'processed',
                'processed_at' => now()->subMinutes(30),
            ],
            [
                'gateway'    => 'test',
                'event_type' => 'payment.failed',
                'payload'    => ['event' => 'payment.failed', 'reference' => 'ref_test_' . Str::random(12)],
                'signature_valid' => true,
                'status'     => 'processed',
                'processed_at' => now()->subMinutes(20),
            ],
            [
                'gateway'    => 'test',
                'event_type' => 'payment.succeeded',
                'payload'    => ['event' => 'payment.succeeded', 'reference' => 'ref_test_' . Str::random(12)],
                'signature_valid' => false,
                'status'     => 'failed',
                'error_message' => 'Invalid signature',
            ],
        ];

        foreach ($samples as $wh) {
            PaymentWebhook::create($wh);
        }

        $this->command->info('Payment webhooks seeded: ' . count($samples));
    }
}