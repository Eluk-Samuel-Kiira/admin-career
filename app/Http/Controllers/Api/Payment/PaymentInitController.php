<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\PaymentSource;
use App\Services\Payment\MarzPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentInitController extends Controller
{
    public function __construct(protected MarzPayService $marzpay) {}

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents'      => 'required|integer|min:1',
            'currency_code'     => 'required|string|size:3',
            'purpose_type'      => 'required|string|max:64',
            'purpose_reference' => 'required|uuid',
            'description'       => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:25',
            'method'            => 'nullable|in:mobile_money,card',
        ]);

        $user   = $request->user();
        $method = $validated['method'] ?? 'mobile_money';

        $currency = Currency::getByCode($validated['currency_code']);
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => "Unknown currency {$validated['currency_code']}",
            ], 422);
        }

        $source = PaymentSource::active()->where('slug', 'marzpay')->first();
        if (!$source) {
            return response()->json([
                'success' => false,
                'message' => 'Payment source not configured',
            ], 500);
        }

        // Prevent duplicate pending deposits for the same purpose
        $existing = Deposit::where('purpose_type', $validated['purpose_type'])
            ->where('purpose_reference', $validated['purpose_reference'])
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(15))
            ->first();

        if ($existing) {
            return response()->json([
                'success'     => true,
                'reused'      => true,
                'deposit_ref' => $existing->deposit_ref,
                'message'     => 'A payment for this item is already in progress.',
            ]);
        }

        $deposit = Deposit::create([
            'deposit_ref'         => (string) Str::uuid(),
            'currency_id'         => $currency->id,
            'amount'              => $validated['amount_cents'],
            'fee'                 => 0,
            'net_amount'          => $validated['amount_cents'],
            'deposit_method'      => $method,
            'payment_channel'     => $method,
            'source_id'           => $source->id,
            'source_name_manual'  => 'MarzPay',
            'purpose_type'        => $validated['purpose_type'],
            'purpose_reference'   => $validated['purpose_reference'],
            'purpose_description' => $validated['description'] ?? 'Payment for ' . config('app.name'),
            'status'              => 'pending',
            'depositor_id'        => $user->id,
            'paid_by_user_id'     => $user->id,
            'depositor_phone'     => $validated['phone'] ?? $user->phone,
            'depositor_email'     => $user->email,
            'depositor_name'      => $user->name,
            'country_code'        => $user->country_code ?? config('app.country_code', 'UG'),
            'created_by'          => $user->id,
        ]);

        try {
            $response = $this->marzpay->collect($deposit, $validated['phone'], $method);
        } catch (\RuntimeException $e) {
            $deposit->update(['status' => 'failed', 'notes' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code'    => 'payment_rejected',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            $deposit->update(['status' => 'failed', 'notes' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'code'    => 'payment_error',
                'message' => $e->getMessage(),
            ], 422);
        }

        $deposit->update([
            'gateway_transaction_uuid' => $response['data']['transaction']['uuid'] ?? null,
            'source_reference'         => $response['data']['transaction']['uuid'] ?? null,
        ]);

        return response()->json([
            'success'          => true,
            'deposit_ref'      => $deposit->deposit_ref,
            'redirect_url'     => $response['data']['redirect_url'] ?? null,
            'amount_formatted' => $currency->formatAmount($validated['amount_cents']),
            'next_step'        => !empty($response['data']['redirect_url']) ? 'redirect' : 'await_pin',
            'message'          => !empty($response['data']['redirect_url'])
                ? 'Redirecting to the card gateway...'
                : 'A payment prompt has been sent to your phone. Enter your PIN to complete payment.',
        ]);
    }
}