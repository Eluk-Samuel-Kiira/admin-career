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

        // Persist every webhook — even malformed ones — so we have an audit trail.
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
            Log::warning('MarzPay webhook: missing reference', [
                'event_type' => $eventType,
            ]);
            return response('missing reference', 400);
        }

        // ─────────────────────────────────────────────────────────────
        // Find the deposit. Tolerate soft-deletes and duplicates so a
        // webhook can never get stuck because of data hygiene issues.
        // ─────────────────────────────────────────────────────────────
        $candidates = Deposit::withTrashed()
            ->where('deposit_ref', $reference)
            ->orderByRaw('CASE WHEN purpose_reference IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($candidates->isEmpty()) {
            Log::warning('MarzPay webhook: unknown reference', [
                'reference'  => $reference,
                'event_type' => $eventType,
            ]);
            // Return 200: MarzPay retrying won't help — this is a data problem
            // on our side. We still have the webhook row for reconciliation.
            return response('unknown reference', 200);
        }

        if ($candidates->count() > 1) {
            Log::warning('MarzPay webhook: multiple deposits for one reference', [
                'reference' => $reference,
                'ids'       => $candidates->pluck('id')->all(),
                'using_id'  => $candidates->first()->id,
            ]);
        }

        $deposit = $candidates->first();

        // Restore a soft-deleted deposit so downstream work isn't orphaned
        if (method_exists($deposit, 'trashed') && $deposit->trashed()) {
            Log::warning('MarzPay webhook: restoring soft-deleted deposit', [
                'reference'  => $reference,
                'deposit_id' => $deposit->id,
            ]);
            $deposit->restore();
        }

        // ─────────────────────────────────────────────────────────────
        // Only act on terminal statuses. Ignore anything else (pending,
        // processing) with a 200 so MarzPay stops retrying.
        // ─────────────────────────────────────────────────────────────
        $webhookStatus = $payload['transaction']['status'] ?? null;

        if (!in_array($webhookStatus, ['completed', 'failed'], true)) {
            Log::info('MarzPay webhook: ignored non-terminal status', [
                'reference' => $reference,
                'status'    => $webhookStatus,
                'event'     => $eventType,
            ]);
            return response('ignored', 200);
        }

        // ─────────────────────────────────────────────────────────────
        // Idempotency: if the deposit is already in the target state,
        // don't re-process. Prevents duplicate transaction logs on retry.
        // ─────────────────────────────────────────────────────────────
        $alreadyFinal = ($webhookStatus === 'completed' && $deposit->status === 'completed')
                     || ($webhookStatus === 'failed'    && $deposit->status === 'failed');

        if ($alreadyFinal) {
            Log::info('MarzPay webhook: already processed', [
                'reference' => $reference,
                'status'    => $deposit->status,
            ]);
            return response('already processed', 200);
        }

        // ─────────────────────────────────────────────────────────────
        // Amount sanity check. MarzPay adds its fee on top, so the
        // webhook amount should be >= what we charged (never below).
        // ─────────────────────────────────────────────────────────────
        $webhookAmount = (float) ($payload['transaction']['amount']['raw'] ?? 0);

        $expectedAmount = 0.0;
        if ($deposit->currency) {
            $expectedAmount = (float) $deposit->currency->fromCents($deposit->amount);
        } elseif ($deposit->amount) {
            $expectedAmount = (float) $deposit->amount / 100;
        }

        if (
            $webhookStatus === 'completed'
            && $webhookAmount > 0
            && $expectedAmount > 0
            && ($webhookAmount + 0.01) < $expectedAmount
        ) {
            Log::warning('MarzPay webhook: amount mismatch', [
                'reference' => $reference,
                'expected'  => $expectedAmount,
                'got'       => $webhookAmount,
            ]);
            return response('amount mismatch', 400);
        }

        // ─────────────────────────────────────────────────────────────
        // Apply the change
        // ─────────────────────────────────────────────────────────────
        try {
            DB::transaction(function () use ($deposit, $payload, $webhookStatus) {
                $txn        = $payload['transaction'] ?? [];
                $collection = $payload['collection']  ?? [];

                if ($webhookStatus === 'completed') {
                    $deposit->update([
                        'status'           => 'completed',
                        'cleared_date'     => now(),
                        'reference_number' => $collection['provider_transaction_id'] ?? null,
                        'fee'              => $this->toCents($txn['charge']['raw']     ?? 0, $deposit),
                        'net_amount'       => $this->toCents(
                            $txn['net_amount']['raw']
                            ?? $txn['amount']['raw']
                            ?? 0,
                            $deposit
                        ),
                    ]);

                    // Accounting log + wallet credit (idempotent on external_reference)
                    $this->transactionService->recordDeposit($deposit->fresh());

                    // Flip the domain row (CV review, cover letter, etc.)
                    $this->activate($deposit->fresh());

                    // Dispatch generation for letters that just became paid
                    if ($deposit->purpose_type === 'cover_letter') {
                        $letter = \App\Models\Service\LetterRequest::where('uuid', $deposit->purpose_reference)->first();
                        if ($letter && $letter->status === 'paid') {
                            \App\Jobs\GenerateLetterJob::dispatch($letter->id)->onQueue('default');
                        }
                    }

                } else {
                    // 'failed'
                    $reason = $collection['failure_reason']
                        ?? $txn['description']
                        ?? 'Payment failed';

                    $deposit->update([
                        'status' => 'failed',
                        'notes'  => trim(
                            ($deposit->notes ?? '') . "\n" . $reason
                        ),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('MarzPay webhook: processing failed', [
                'reference'  => $reference,
                'deposit_id' => $deposit->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            // 500 → MarzPay retries. Only transient errors should reach here.
            return response('processing error', 500);
        }

        // Mark the webhook row processed for audit
        try {
            PaymentWebhook::where('gateway', 'marzpay')
                ->where('gateway_reference', $reference)
                ->where('status', 'received')
                ->latest('id')
                ->first()
                ?->update([
                    'status'       => 'processed',
                    'processed_at' => now(),
                ]);
        } catch (\Throwable $e) {
            // Audit-only — never fail the webhook because of this
            Log::warning('MarzPay webhook: failed to mark processed', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
        }

        return response('ok', 200);
    }

    public function update(Request $request, $uuid)
    {
        if (!Session::has('user')) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Forward to the main app's update endpoint
        $params = $request->only(['job_title', 'company_name']);

        $files = [];
        if ($request->hasFile('cv_file')) {
            $files['cv_file'] = $request->file('cv_file');
        }

        $response = $this->countryService->api(
            "letters/{$uuid}/update",
            $params,
            'POST',      // or 'PUT' — see main app below
            0,
            false,
            $files
        );

        return response()->json(
            $response,
            ($response['success'] ?? false) ? 200 : ($response['status'] ?? 422)
        );
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
                ->whereNotIn('status', [
                    'paid', 'in_progress', 'delivered', 'revision_requested', 'completed',
                ])
                ->update([
                    'status'            => 'paid',
                    'payment_reference' => $deposit->deposit_ref,
                ]),

            'cover_letter' => \App\Models\Service\LetterRequest::where('uuid', $deposit->purpose_reference)
                ->whereIn('status', ['pending_payment'])
                ->update([
                    'status'            => 'paid',
                    'payment_reference' => $deposit->deposit_ref,
                    'paid_at'           => now(),
                ]),

            // Ready for when you build these:
            //                         ->first()?->markPaid($deposit->deposit_ref),
            // 'job_submission' => JobSubmission::where('uuid', $deposit->purpose_reference)
            //                         ->first()?->markPaid($deposit->deposit_ref),
            // 'subscription'   => Subscription::where('uuid', $deposit->purpose_reference)
            //                         ->first()?->activateFromDeposit($deposit),

            default => null,
        };
    }

    protected function toCents($raw, Deposit $deposit): int
    {
        if (!$deposit->currency) {
            return (int) round((float) $raw * 100);
        }
        return $deposit->currency->toCents((float) $raw);
    }
}