<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\Controller;
use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * GET /api/services/pricing
     *
     * Returns all active services that have an effective price for the
     * current country (falling back to GLOBAL where no country price exists).
     *
     * Services without any active price are skipped entirely.
     */
    public function index(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        $services = Service::active()
            ->ordered()
            ->get();

        $data = $services
            ->map(function (Service $service) use ($countryCode) {
                $price = ServicePrice::resolve($service->key, $countryCode);

                // Skip services that have no active price at all
                if (!$price) {
                    return null;
                }

                return [
                    'key'              => $service->key,
                    'name'             => $service->name,
                    'description'      => $service->description,
                    'billing_type'     => $service->billing_type,
                    'billing_label'    => $service->billing_type_label,
                    'turnaround_label' => $service->turnaround_label,
                    'family'           => $service->meta['family']  ?? 'other',
                    'tagline'          => $service->meta['tagline'] ?? null,
                    'badge'            => $service->meta['badge']   ?? null,
                    'icon'             => $service->meta['icon']    ?? null,

                    'price' => [
                        'amount_cents' => $price->amount_cents,
                        'amount'       => $price->amount,
                        'formatted'    => $price->formatted_amount,
                        'currency'     => $price->currency?->code,
                        'currency_sym' => $price->currency?->symbol,
                        'interval'     => $price->interval,
                        'country_code' => $price->country_code,
                        'is_global'    => $price->country_code === ServicePrice::GLOBAL_CODE,
                    ],

                    'features' => $this->extractFeatures($service),
                ];
            })
            ->filter()          // drop the nulls
            ->values();         // re-index so JSON is a clean array, not an object

        return response()->json([
            'success'      => true,
            'country_code' => $countryCode,
            'data'         => $data,
        ]);
    }

    /**
     * Turn the service's meta block into a clean list of human-readable feature
     * bullets, so the frontend does not have to know about the raw meta keys.
     */
    protected function extractFeatures(Service $service): array
    {
        $meta = $service->meta ?? [];
        $family = $meta['family'] ?? null;

        if ($family === 'job_posting') {
            $features = [];
            $features[] = ($meta['listing_days'] ?? 0) . '-day listing';
            if (!empty($meta['is_featured'])) {
                $features[] = ($meta['featured_days'] ?? 0) . '-day featured placement';
            }
            if (!empty($meta['is_urgent'])) {
                $features[] = 'Urgent badge';
            }
            if (($meta['whatsapp_distribution'] ?? 'none') === 'unlimited') {
                $features[] = 'Unlimited WhatsApp distribution';
            } elseif (($meta['whatsapp_count'] ?? 0) > 0) {
                $features[] = $meta['whatsapp_count'] . ' WhatsApp group shares';
            }
            if (($meta['email_alerts'] ?? 'none') === 'unlimited') {
                $features[] = 'Email alerts until deadline';
            } elseif (($meta['email_alert_count'] ?? 0) > 0) {
                $features[] = $meta['email_alert_count'] . ' email alert batches';
            }
            if (!empty($meta['popup_devices'])) {
                $features[] = 'Pop-up on ' . implode(' + ', $meta['popup_devices']);
            }
            if (!empty($meta['priority_placement'])) {
                $features[] = 'Priority placement';
            }
            if (!empty($meta['paid_ads_boost'])) {
                $features[] = 'Paid ads boost';
            }
            if (!empty($meta['enterprise_ats'])) {
                $features[] = 'Enterprise ATS included';
            }
            return $features;
        }

        if ($family === 'cv_service') {
            $features = [];
            if (!empty($meta['revisions_allowed'])) {
                $features[] = $meta['revisions_allowed'] . ' revision' . ($meta['revisions_allowed'] > 1 ? 's' : '');
            }
            if (!empty($meta['delivery_method'])) {
                $features[] = ucwords(str_replace('_', ' ', $meta['delivery_method']));
            }
            if (!empty($meta['skills_focus'])) {
                foreach ((array) $meta['skills_focus'] as $focus) {
                    $features[] = ucwords(str_replace('_', ' ', $focus));
                }
            }
            return $features;
        }

        if ($family === 'job_seeker_subscription') {
            $features = [];
            if (!empty($meta['frequency'])) {
                $features[] = 'Frequency: ' . ucwords(str_replace('_', ' ', $meta['frequency']));
            }
            if (!empty($meta['channels'])) {
                $features[] = 'Via ' . implode(' + ', array_map(
                    fn($c) => ucwords(str_replace('_', ' ', $c)),
                    (array) $meta['channels']
                ));
            }
            if (!empty($meta['trial_days'])) {
                $features[] = $meta['trial_days'] . '-day free trial';
            }
            if (!empty($meta['features'])) {
                foreach ((array) $meta['features'] as $f) {
                    $features[] = ucwords(str_replace('_', ' ', $f));
                }
            }
            return $features;
        }

        return [];
    }




    // NOT YET USED TILL PAYMENT GATEWAY IS INTEGRATED
    public function subscribe(User $user, string $serviceKey, string $countryCode): array
    {
        // 1. Load the service and enforce that it is a subscription service
        $service = Service::where('key', $serviceKey)->where('is_active', true)->first();
        if (!$service) {
            throw new \InvalidArgumentException("Unknown service: {$serviceKey}");
        }

        if ($service->billing_type !== 'subscription') {
            throw new \InvalidArgumentException("Service {$serviceKey} is not subscription-based.");
        }

        // 2. Resolve the price for the user's country, with GLOBAL fallback
        $price = ServicePrice::resolve($serviceKey, $countryCode);
        if (!$price) {
            throw new \RuntimeException("No active price for {$serviceKey} in {$countryCode}.");
        }

        // 3. Idempotency — is the user already subscribed to this service?
        $existing = Subscription::where('user_id', $user->id)
            ->where('service_key', $serviceKey)
            ->whereIn('status', ['trialing', 'active'])
            ->where('current_period_end', '>', now())
            ->first();

        if ($existing) {
            return ['subscription' => $existing, 'redirect_url' => null];
        }

        // 4. Create the subscription inside a transaction
        return DB::transaction(function () use ($user, $service, $price, $countryCode) {
            $now = now();
            $trialDays = (int) ($service->meta['trial_days'] ?? 0);
            $trialEnds = $trialDays > 0 ? $now->copy()->addDays($trialDays) : null;
            $periodEnd = $this->computePeriodEnd($now, $price->interval ?? 'month');

            $subscription = Subscription::create([
                'user_id'              => $user->id,
                'service_key'          => $service->key,
                'price_id'             => $price->id,
                'country_code'         => strtoupper($countryCode),
                'currency'             => $price->currency->code,
                'amount_cents'         => $price->amount_cents,
                'interval'             => $price->interval ?? 'month',
                'status'               => $trialEnds ? 'trialing' : 'active',
                'trial_ends_at'        => $trialEnds,
                'starts_at'            => $now,
                'current_period_start' => $now,
                'current_period_end'   => $trialEnds ?: $periodEnd,
                'feature_snapshot'     => $service->meta ?? [],
            ]);

            $redirectUrl = null;

            // 5. If the plan is paid, start checkout
            if ($price->amount_cents > 0) {
                try {
                    $checkout = $this->payments->gateway()->startCheckout($user, $subscription);
                    $subscription->update([
                        'gateway' => $this->payments->gateway()->name(),
                        'gateway_subscription_id' => $checkout['gateway_reference'],
                    ]);

                    $redirectUrl = $checkout['redirect_url'];

                    SubscriptionPayment::create([
                        'subscription_id'       => $subscription->id,
                        'user_id'               => $user->id,
                        'gateway'               => $this->payments->gateway()->name(),
                        'gateway_reference'     => $checkout['gateway_reference'],
                        'currency'              => $price->currency->code,
                        'amount_cents'          => $price->amount_cents,
                        'status'                => 'pending',
                    ]);
                } catch (\RuntimeException $e) {
                    Log::warning('Payment gateway not configured; subscription created without payment', [
                        'user_id' => $user->id,
                        'service_key' => $service->key,
                    ]);
                }
            }

            $this->gate->forget($user);

            return ['subscription' => $subscription, 'redirect_url' => $redirectUrl];
        });
    }

    protected function computePeriodEnd(\Carbon\Carbon $from, string $interval): \Carbon\Carbon
    {
        return $interval === 'year'
            ? $from->copy()->addYear()
            : $from->copy()->addMonth();
    }


    public function available(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        $services = Service::where('billing_type', 'subscription')
            ->where('is_active', true)
            ->ordered()
            ->get();

        $data = $services->map(function (Service $service) use ($countryCode) {
            $price = ServicePrice::resolve($service->key, $countryCode);
            if (!$price) return null;

            return [
                'service_key'    => $service->key,
                'name'           => $service->name,
                'description'    => $service->description,
                'tagline'        => $service->meta['tagline'] ?? null,
                'badge'          => $service->meta['badge'] ?? null,
                'icon'           => $service->meta['icon'] ?? null,
                'features'       => $this->extractFeatures($service),
                'trial_days'     => (int) ($service->meta['trial_days'] ?? 0),
                'price' => [
                    'amount_cents' => $price->amount_cents,
                    'formatted'    => $price->formatted_amount,
                    'currency'     => $price->currency->code,
                    'interval'     => $price->interval,
                    'country_code' => $price->country_code,
                    'is_global'    => $price->country_code === ServicePrice::GLOBAL_CODE,
                ],
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'country_code' => $countryCode,
            'data' => $data,
        ]);
    }


    // public function subscribe(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'service_key'  => 'required|string',
    //         'country_code' => 'required|string|size:2',
    //     ]);

    //     $user = $request->user();

    //     try {
    //         $result = $this->service->subscribe(
    //             $user,
    //             $validated['service_key'],
    //             strtoupper($validated['country_code']),
    //         );
    //     } catch (\InvalidArgumentException | \RuntimeException $e) {
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => $result['redirect_url']
    //             ? 'Redirect the user to complete payment.'
    //             : 'Subscription started.',
    //         'subscription' => [
    //             'uuid'          => $result['subscription']->uuid,
    //             'service_key'   => $result['subscription']->service_key,
    //             'status'        => $result['subscription']->status,
    //             'trial_ends_at' => $result['subscription']->trial_ends_at?->toISOString(),
    //             'period_end'    => $result['subscription']->current_period_end->toISOString(),
    //         ],
    //         'redirect_url' => $result['redirect_url'],
    //     ]);
    // }


}