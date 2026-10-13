<?php

namespace App\Console\Commands;

use App\Models\Subscription\Subscription;
use App\Models\Subscription\SubscriptionPayment;
use App\Services\Payment\PaymentManager;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewSubscriptionsCommand extends Command
{
    protected $signature   = 'subscriptions:renew';
    protected $description = 'Renew subscriptions whose period has ended and attempt payment.';

    public function handle(
        PaymentManager $payments,
        SubscriptionService $subscriptions,
    ): int {
        $renewed = 0;
        $failed  = 0;
        $skipped = 0;

        Subscription::with(['price', 'service', 'user'])
            ->whereIn('status', ['active', 'past_due'])
            ->where('cancel_at_period_end', false)
            ->where('current_period_end', '<=', now())
            ->orderBy('current_period_end')
            ->chunkById(100, function ($subs) use (&$renewed, &$failed, &$skipped, $payments) {
                foreach ($subs as $sub) {
                    if (!$sub->price || $sub->amount_cents === 0) {
                        // Free plan — just roll the period
                        $sub->update([
                            'current_period_start' => now(),
                            'current_period_end'   => $sub->interval === 'year'
                                ? now()->addYear() : now()->addMonth(),
                            'status' => 'active',
                        ]);
                        $renewed++;
                        continue;
                    }

                    try {
                        // Ask the gateway to charge the stored payment method
                        $charge = $payments->gateway()
                            ->chargeRecurring($sub);

                        SubscriptionPayment::create([
                            'subscription_id' => $sub->id,
                            'user_id'         => $sub->user_id,
                            'gateway'         => $sub->gateway ?: 'unknown',
                            'gateway_reference'=> $charge['gateway_reference'] ?? null,
                            'currency'        => $sub->currency,
                            'amount_cents'    => $sub->amount_cents,
                            'status'          => $charge['status'] ?? 'failed',
                            'failure_reason'  => $charge['failure_reason'] ?? null,
                            'paid_at'         => ($charge['status'] ?? '') === 'succeeded' ? now() : null,
                        ]);

                        if (($charge['status'] ?? '') === 'succeeded') {
                            $sub->update([
                                'current_period_start' => now(),
                                'current_period_end'   => $sub->interval === 'year'
                                    ? now()->addYear() : now()->addMonth(),
                                'status' => 'active',
                            ]);
                            $renewed++;
                        } else {
                            $sub->update(['status' => 'past_due']);
                            $failed++;
                        }
                    } catch (\RuntimeException $e) {
                        // Gateway not configured yet — just roll the period so subscribers keep access
                        $sub->update([
                            'current_period_start' => now(),
                            'current_period_end'   => $sub->interval === 'year'
                                ? now()->addYear() : now()->addMonth(),
                            'status' => 'active',
                        ]);
                        $skipped++;
                    }
                }
            });

        $this->info("Renewed: {$renewed}, Failed: {$failed}, Skipped (no gateway): {$skipped}");
        return self::SUCCESS;
    }
}