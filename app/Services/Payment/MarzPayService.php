<?php

namespace App\Services\Payment;

use App\Models\Deposit;
use App\Models\Job\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarzPayService
{
    /**
     * Resolve the MarzPay credentials for a country code.
     * Throws if that country is not configured.
     */
    protected function credentials(string $countryCode): array
    {
        $countryCode = strtoupper($countryCode);
        $payment = config("countries.{$countryCode}.payment");

        if (!$payment || empty($payment['api_key']) || empty($payment['api_secret'])) {
            throw new \RuntimeException("Payments are not configured for country {$countryCode}.");
        }

        if (($payment['gateway'] ?? null) !== 'marzpay') {
            throw new \RuntimeException("Country {$countryCode} does not use MarzPay.");
        }

        return $payment;
    }

    /**
     * Create a collection. Returns MarzPay's response body.
     */
    public function collect(Deposit $deposit, ?string $phone = null, string $method = 'mobile_money'): array
    {
        $countryCode = strtoupper(
            $deposit->country_code
            ?? $deposit->paidBy?->country_code
            ?? config('app.country_code', 'UG')
        );

        $cfg = $this->credentials($countryCode);

        // ── DEBUG: prove we got here and have creds ──────────────────
        Log::info('MarzPay::collect START', [
            'deposit_ref'   => $deposit->deposit_ref,
            'country'       => $countryCode,
            'method'        => $method,
            'base_url'      => $cfg['base_url']      ?? null,
            'callback_url'  => $cfg['callback_url']  ?? null,
            'api_key_len'   => strlen($cfg['api_key']    ?? ''),
            'api_secret_len'=> strlen($cfg['api_secret'] ?? ''),
            'api_key_tail'  => substr($cfg['api_key']    ?? '', -4),  // last 4 chars only, never log full keys
            'currency'      => $deposit->currency?->code,
            'amount_cents'  => $deposit->amount,
            'raw_phone'     => $phone,
        ]);

        $payload = [
            'amount'       => $this->amountForMarzPay($deposit),
            'country'      => $countryCode,
            'currency'     => $deposit->currency?->code ?? $cfg['currency'],
            'reference'    => $deposit->deposit_ref,
            'description'  => $deposit->purpose_description ?: ('Payment for ' . config('app.name')),
            'callback_url' => $cfg['callback_url'],
            'method'       => $method === 'card' ? 'card' : 'mobile_money',
            'metadata'     => [
                ['deposit_ref'       => $deposit->deposit_ref],
                ['purpose_type'      => $deposit->purpose_type],
                ['purpose_reference' => $deposit->purpose_reference],
            ],
        ];

        if ($method !== 'card') {
            $rawPhone = $phone ?? $deposit->depositor_phone;
            $phone = preg_replace('/[^\d+]/', '', (string) $rawPhone);
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . ltrim($phone, '+');
            }
            $payload['phone_number'] = $phone;
        }

        // ── DEBUG: what we're about to send ──────────────────────────
        Log::info('MarzPay::collect PAYLOAD', [
            'deposit_ref' => $deposit->deposit_ref,
            'url'         => rtrim($cfg['base_url'], '/') . '/collect-money',
            'payload'     => $payload,   // amount, phone, reference, etc — no secrets
        ]);

        // ── Wrap the request so we capture DNS / TLS / timeout failures ──
        try {
            $response = Http::withBasicAuth($cfg['api_key'], $cfg['api_secret'])
                ->timeout(30)
                ->withOptions([
                    'debug' => false,          // set true only locally to see cURL trace
                    'verify' => true,          // production MUST verify TLS
                ])
                ->post(rtrim($cfg['base_url'], '/') . '/collect-money', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // DNS failure, TLS handshake failure, connection timeout, etc.
            Log::error('MarzPay::collect CONNECTION FAILED', [
                'deposit_ref' => $deposit->deposit_ref,
                'url'         => rtrim($cfg['base_url'], '/') . '/collect-money',
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }

        // ── DEBUG: what MarzPay sent back ────────────────────────────
        Log::info('MarzPay::collect RESPONSE', [
            'deposit_ref' => $deposit->deposit_ref,
            'status_code' => $response->status(),
            'headers'     => $response->headers(),
            'body'        => $response->body(),        // full body, so you see the exact payload
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            $message = $body['message'] ?? 'Payment could not be started.';

            if (!empty($body['errors']) && is_array($body['errors'])) {
                $messages = [];
                foreach ($body['errors'] as $field => $list) {
                    foreach ((array) $list as $m) {
                        $messages[] = $m;
                    }
                }
                if (!empty($messages)) {
                    $message = implode(' ', $messages);
                }
            }

            Log::warning('MarzPay::collect rejected', [
                'deposit_ref' => $deposit->deposit_ref,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);

            throw new \RuntimeException($message);
        }

        return $response->json();
    }

    /**
     * Re-query a collection by reference for webhook verification.
     */
    public function fetchCollection(string $reference, string $countryCode): ?array
    {
        $cfg = $this->credentials($countryCode);

        $response = Http::withBasicAuth($cfg['api_key'], $cfg['api_secret'])
            ->timeout(30)
            ->get(rtrim($cfg['base_url'], '/') . '/collect-money/' . urlencode($reference));

        if (!$response->successful()) {
            Log::warning('MarzPay fetch failed', [
                'reference' => $reference,
                'status'    => $response->status(),
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Convert deposit cents into MarzPay whole units.
     * UGX has 0 decimals in your Currency model, so 16000 cents == 16000 UGX.
     */
    protected function amountForMarzPay(Deposit $deposit): float
    {
        if (!$deposit->currency) {
            return $deposit->amount / 100;
        }
        return $deposit->currency->fromCents($deposit->amount);
    }
}