<?php

namespace App\Services\Payment;

use App\Models\Service\Service;
use App\Models\Service\ServicePrice;
use App\Models\{ Currency };

class PricingService
{
    /**
     * Resolve the effective price for a service in a given country.
     * Uses country_code directly ('UG', 'KE', 'XX' for global).
     *
     * Returns null if no price exists — callers decide whether that's an error.
     */
    public function priceFor(string $serviceKey, string $countryCode): ?array
    {

        $service = Service::where('key', $serviceKey)
            ->where('is_active', true)
            ->first();


        if (!$service) {
            return null;
        }

        $countryCode = strtoupper($countryCode ?: ServicePrice::GLOBAL_CODE);

        // 1. Try the specific country first
        $price = $service->prices()
            ->where('is_active', true)
            ->where('country_code', $countryCode)
            ->first();
        

        // 2. Fall back to the global XX row
        if (!$price && $countryCode !== ServicePrice::GLOBAL_CODE) {
            $price = $service->prices()
                ->where('is_active', true)
                ->where('country_code', ServicePrice::GLOBAL_CODE)
                ->first();
        }

        if (!$price || !$price->currency) {
            return null;
        }

        return [
            'service'       => $service->key,
            'amount_cents'  => $price->amount_cents,
            'currency_code' => $price->currency->code,
            'formatted'     => $price->currency->formatAmount($price->amount_cents),
            'interval'      => $price->interval,
            'country_code'  => $price->country_code,
            'is_fallback'   => $price->country_code === ServicePrice::GLOBAL_CODE
                                 && $countryCode !== ServicePrice::GLOBAL_CODE,
        ];
    }

    /**
     * Same as priceFor(), but throws if unavailable.
     * Use this when the caller can't gracefully handle a null.
     */
    public function priceForOrFail(string $serviceKey, string $countryCode): array
    {
        $price = $this->priceFor($serviceKey, $countryCode);

        if ($price === null) {
            throw new \Exception("No price configured for '{$serviceKey}' in '{$countryCode}'.");
        }

        return $price;
    }
}