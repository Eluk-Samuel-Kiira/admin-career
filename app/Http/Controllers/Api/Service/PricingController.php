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
        $countryCode = $this->resolveCountryCode($request);

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
     * GET /api/services/pricing/auto-apply
     * Returns the price for the auto-apply subscription.
     */
    public function autoApply(Request $request): JsonResponse
    {
        $countryCode = $this->resolveCountryCode($request);

        $price = ServicePrice::resolve('premium_alerts', $countryCode);

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing is not available in your region yet.',
            ], 404);
        }

        return response()->json([
            'success'      => true,
            'country_code' => $countryCode,
            'price' => [
                'amount_cents' => (int) $price->amount_cents,
                'currency'     => $price->currency->code,
                'formatted'    => $price->formatted_amount,
                'interval'     => $price->interval ?? 'month',
            ],
        ]);
    }

    /**
     * GET /api/services/pricing/available
     *
     * Returns only subscription-billed services with an effective price
     * for the current country (falls back to GLOBAL).
     */
    public function available(Request $request): JsonResponse
    {
        $countryCode = $this->resolveCountryCode($request);

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
            'success'      => true,
            'country_code' => $countryCode,
            'data'         => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Country resolution — one source of truth for every endpoint
    // ─────────────────────────────────────────────────────────────────
    //
    // Priority:
    //   1. X-Country-Code header         (what CountryService sends from proxies)
    //   2. country_code query/body param (direct API callers, tests)
    //   3. request->user()->country_code (logged-in fallback)
    //   4. null                          (ServicePrice falls back to GLOBAL)
    //
    // Always returns a 2-letter uppercase code, or null.
    protected function resolveCountryCode(Request $request): ?string
    {
        $candidates = [
            $request->header('X-Country-Code'),
            $request->input('country_code'),
            optional($request->user())->country_code,
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) continue;

            $candidate = strtoupper(trim($candidate));

            if (preg_match('/^[A-Z]{2}$/', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function coverLetter(Request $request): JsonResponse
    {
        $countryCode = $this->resolveCountryCode($request);

        $price = ServicePrice::resolve('cover_letter', $countryCode ?? 'GLOBAL');

        if (!$price) {
            return response()->json([
                'success' => false,
                'message' => 'Cover letter pricing is not available in your region yet.',
            ], 404);
        }

        return response()->json([
            'success'      => true,
            'country_code' => $countryCode,
            'price' => [
                'amount_cents'  => (int) $price->amount_cents,
                'formatted'     => $price->formatted_amount,
                'currency_code' => $price->currency->code,
                'currency_sym'  => $price->currency->symbol,
                'interval'      => $price->interval,
                'is_free'       => (int) $price->amount_cents === 0,
            ],
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
}