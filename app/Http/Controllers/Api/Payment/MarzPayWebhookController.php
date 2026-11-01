<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Subscription\PaymentWebhook;
use App\Models\Service\CvReviewRequest;
use App\Services\Payment\MarzPayService;
use App\Services\Payment\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarzPayWebhookController extends Controller
{
    public function __construct(
        protected MarzPayService $marzpay,
        protected PaymentTransactionService $transactionService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload   = $request->all();
        $reference = $payload['transaction']['reference'] ?? null;
        $eventType = $payload['event_type'] ?? null;

        PaymentWebhook::create([
            'gateway'           => 'marzpay',
            'event_type'        => $eventType,
            'gateway_reference' => $reference,
            'payload'           => $payload,
            'signature'         => null,
            'signature_valid'   => false,
            'status'            => 'received',
        ]);

        if (!$reference) {
            return response('missing reference', 400);
        }

        // Find the deposit first so we know which country to query with
        $deposit = Deposit::where('deposit_ref', $reference)->first();
        if (!$deposit) {
            Log::warning('MarzPay webhook: unknown reference', ['reference' => $reference]);
            return response('unknown reference', 404);
        }

        // Re-query MarzPay for verification (they do not sign webhooks)
        $verified = $this->marzpay->fetchCollection(
            $reference,
            strtoupper($deposit->country_code ?: config('app.country_code', 'UG'))
        );

        if (!$verified || ($verified['data']['transaction']['reference'] ?? null) !== $reference) {
            Log::warning('MarzPay webhook: verification failed', ['reference' => $reference]);
            return response('verification failed', 400);
        }

        $verifiedStatus = $verified['data']['transaction']['status'] ?? null;

        try {
            DB::transaction(function () use ($deposit, $verified, $verifiedStatus) {
                $txn        = $verified['data']['transaction'] ?? [];
                $collection = $verified['data']['collection']  ?? [];

                if ($verifiedStatus === 'completed') {
                    $deposit->update([
                        'status'           => 'completed',
                        'cleared_date'     => now(),
                        'reference_number' => $collection['provider_transaction_id'] ?? null,
                        'fee'              => $this->toCents($txn['charge']['raw']     ?? 0, $deposit),
                        'net_amount'       => $this->toCents($txn['net_amount']['raw'] ?? $txn['amount']['raw'] ?? 0, $deposit),
                    ]);

                    // Record the transaction into the accounting log and credit the wallet
                    $this->transactionService->recordDeposit($deposit->fresh());

                    // Flip the CV review request (or any other domain row) to paid
                    $this->activate($deposit->fresh());
                } elseif ($verifiedStatus === 'failed') {
                    $deposit->update([
                        'status' => 'failed',
                        'notes'  => $collection['failure_reason'] ?? 'Payment failed',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('MarzPay webhook: processing failed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Return 200 so MarzPay does not retry forever on a bug we already logged.
            // If you want retries, return 500 instead.
            return response('processing error', 200);
        }

        return response('ok', 200);
    }

    /**
     * Flip the domain row that this deposit paid for.
     * Add one match arm per new service.
     */
    protected function activate(Deposit $deposit): void
    {
        if (!$deposit->purpose_type || !$deposit->purpose_reference) {
            return;
        }

        match ($deposit->purpose_type) {
            'cv_review' => CvReviewRequest::where('uuid', $deposit->purpose_reference)
                ->whereNotIn('status', ['paid', 'in_progress', 'delivered', 'revision_requested', 'completed'])
                ->update([
                    'status'            => 'paid',
                    'payment_reference' => $deposit->deposit_ref,
                ]),

            // Ready for when you build these:
            // 'cover_letter'    => CoverLetter::where('uuid', $deposit->purpose_reference)->first()?->markPaid($deposit->deposit_ref),
            // 'job_submission'  => JobSubmission::where('uuid', $deposit->purpose_reference)->first()?->markPaid($deposit->deposit_ref),
            // 'subscription'    => Subscription::where('uuid', $deposit->purpose_reference)->first()?->activateFromDeposit($deposit),

            default => null,
        };
    }

    protected function toCents($raw, Deposit $deposit): int
    {
        if (!$deposit->currency) {
            return (int) round($raw * 100);
        }
        return $deposit->currency->toCents((float) $raw);
    }
}