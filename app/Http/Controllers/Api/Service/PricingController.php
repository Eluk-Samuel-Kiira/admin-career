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
     * Returns all active services with their effective price for the
     * current country (falling back to GLOBAL/XX where no country price exists).
     *
     * Only the fields needed by the public pricing page are returned.
     */
    public function index(Request $request): JsonResponse
    {
        $countryCode = strtoupper($request->input('country_code', 'UG'));

        $services = Service::active()
            ->ordered()
            ->get();

        $data = $services->map(function (Service $service) use ($countryCode) {
            $price = ServicePrice::resolve($service->key, $countryCode);

            return [
                'key'               => $service->key,
                'name'              => $service->name,
                'description'       => $service->description,
                'billing_type'      => $service->billing_type,
                'billing_label'     => $service->billing_type_label,
                'turnaround_label'  => $service->turnaround_label,
                'family'            => $service->meta['family']        ?? 'other',
                'tagline'           => $service->meta['tagline']       ?? null,
                'badge'             => $service->meta['badge']         ?? null,
                'icon'              => $service->meta['icon']          ?? null,

                // Price info (may be null for a service with no active price)
                'price'             => $price ? [
                    'amount_cents'  => $price->amount_cents,
                    'amount'        => $price->amount,              // float
                    'formatted'     => $price->formatted_amount,    // e.g. "USh 95,000"
                    'currency'      => $price->currency?->code,
                    'currency_sym'  => $price->currency?->symbol,
                    'interval'      => $price->interval,
                    'country_code'  => $price->country_code,
                    'is_global'     => $price->country_code === ServicePrice::GLOBAL_CODE,
                ] : null,

                // Everything else the pricing page might want, grouped under "features"
                'features'          => $this->extractFeatures($service),
            ];
        });

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

        if ($family === 'free_service') {
            return ['Free forever'];
        }

        return [];
    }
}