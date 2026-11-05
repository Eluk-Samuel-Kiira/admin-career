<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Service\CvReviewRequest;
use App\Models\Service\LetterRequest;
use App\Models\Subscription\PaymentWebhook;
use App\Services\Payment\MarzPayService;
use App\Services\Payment\PaymentTransactionService;
use App\Jobs\GenerateCvRewriteJob;
use App\Jobs\GenerateLetterJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarzPayWebhookController extends Controller
{
    public function __construct(
        protected MarzPayService $marzpay,
        protected PaymentTransactionService $transactionService,
    ) {}

    /**
     * POST /api/payments/marzpay/webhook
     *
     * MarzPay calls this endpoint on every collection event. We:
     *   1. Log every call (audit row in payment_webhooks).
     *   2. Find the matching deposit.
     *   3. Idempotently flip the deposit + domain row inside a transaction.
     *   4. Dispatch follow-up jobs AFTER the transaction commits.
     *
     * Never dispatch a job inside DB::transaction(): a rollback would
     * silently delete the job row from `jobs`, leaving the domain row
     * stuck in "paid_ai" with no worker to advance it.
     */
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
            // 200 — MarzPay retrying won't help. We have the audit row.
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

        if (method_exists($deposit, 'trashed') && $deposit->trashed()) {
            Log::warning('MarzPay webhook: restoring soft-deleted deposit', [
                'reference'  => $reference,
                'deposit_id' => $deposit->id,
            ]);
            $deposit->restore();
        }

        // ─────────────────────────────────────────────────────────────
        // Only act on terminal statuses.
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
        // Idempotency: deposit already in the target state?
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
        // Amount sanity check.
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
        // Commit the deposit + domain row inside a transaction.
        // NOTE: NO job dispatch happens in here. See dispatchFollowUpJobs().
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
                } else {
                    // 'failed'
                    $reason = $collection['failure_reason']
                        ?? $txn['description']
                        ?? 'Payment failed';

                    $deposit->update([
                        'status' => 'failed',
                        'notes'  => trim(($deposit->notes ?? '') . "\n" . $reason),
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
            // 500 — MarzPay retries. Only transient errors should reach here.
            return response('processing error', 500);
        }

        // ─────────────────────────────────────────────────────────────
        // Dispatch follow-up jobs AFTER the transaction has committed.
        // Dispatch failures here are non-fatal: the deposit is committed
        // and the user can recover via /kick on the next page load.
        // ─────────────────────────────────────────────────────────────
        if ($webhookStatus === 'completed') {
            try {
                $this->dispatchFollowUpJobs($deposit->fresh());
            } catch (\Throwable $e) {
                Log::error('MarzPay webhook: dispatch failed (non-fatal)', [
                    'reference'  => $reference,
                    'deposit_id' => $deposit->id,
                    'error'      => $e->getMessage(),
                ]);
                // Return 200 anyway — a webhook retry would create
                // duplicate jobs once the queue recovers.
            }
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

    /**
     * Flip the domain row that this deposit paid for.
     * Does NOT dispatch any job. Handled by dispatchFollowUpJobs().
     */
    protected function activate(Deposit $deposit): void
    {
        if (!$deposit->purpose_type || !$deposit->purpose_reference) {
            return;
        }

        match ($deposit->purpose_type) {
            'cv_review' => $this->activateCvReview($deposit),
            'cover_letter' => $this->activateCoverLetter($deposit),
            default => null,
        };
    }

    protected function activateCvReview(Deposit $deposit): void
    {
        $cv = CvReviewRequest::where('uuid', $deposit->purpose_reference)->first();
        if (!$cv) {
            Log::warning('[activate] cv_review not found', [
                'deposit_ref' => $deposit->deposit_ref,
                'uuid'        => $deposit->purpose_reference,
            ]);
            return;
        }

        // Don't downgrade an already-advanced row.
        if (in_array($cv->status, ['generated', 'generating', 'completed'], true)) {
            Log::info('[activate] cv already advanced, skipping', [
                'uuid'   => $cv->uuid,
                'status' => $cv->status,
            ]);
            return;
        }

        $newStatus = $cv->rewrite_mode === 'ai' ? 'paid_ai' : 'paid_human';

        $cv->update([
            'status'            => $newStatus,
            'payment_reference' => $deposit->deposit_ref,
        ]);

        Log::info('[activate] cv status flipped', [
            'uuid'   => $cv->uuid,
            'status' => $newStatus,
            'mode'   => $cv->rewrite_mode,
        ]);
    }

    protected function activateCoverLetter(Deposit $deposit): void
    {
        $letter = LetterRequest::where('uuid', $deposit->purpose_reference)
            ->whereIn('status', ['pending_payment'])
            ->first();

        if (!$letter) {
            Log::info('[activate] cover_letter not in pending_payment', [
                'uuid' => $deposit->purpose_reference,
            ]);
            return;
        }

        $letter->update([
            'status'            => 'paid',
            'payment_reference' => $deposit->deposit_ref,
            'paid_at'           => now(),
        ]);

        Log::info('[activate] letter status flipped', [
            'uuid'   => $letter->uuid,
            'status' => 'paid',
        ]);
    }

    /**
     * After the payment transaction commits, decide whether a follow-up
     * generation job is needed and dispatch it idempotently.
     *
     * Idempotency rules:
     *   - Skip if the domain row already reached a post-generation state.
     *   - Skip if the job is already in the queue (webhook retry safety).
     *   - Skip if a running-lock is set (worker is currently processing).
     */
    protected function dispatchFollowUpJobs(Deposit $deposit): void
    {
        match ($deposit->purpose_type) {
            'cv_review'    => $this->dispatchCvRewriteJob($deposit),
            'cover_letter' => $this->dispatchCoverLetterJob($deposit),
            default        => null,
        };
    }

    protected function dispatchCvRewriteJob(Deposit $deposit): void
    {
        $cv = CvReviewRequest::where('uuid', $deposit->purpose_reference)->first();
        if (!$cv) {
            return;
        }

        if ($cv->rewrite_mode !== 'ai') {
            return;
        }

        if ($cv->status !== 'paid_ai') {
            \Log::info('[dispatch] cv status not paid_ai, skipping', [
                'uuid'   => $cv->uuid,
                'status' => $cv->status,
            ]);
            return;
        }

        // Already queued? Skip.
        $isQueued = $this->isJobQueued('cv-rewrite', '"cvReviewRequestId":' . $cv->id);
        if ($isQueued) {
            \Log::info('[dispatch] cv job already queued, skipping', ['uuid' => $cv->uuid]);
            return;
        }

        // Currently running? Skip.
        if (\Cache::has("cv-rewrite-running:{$cv->id}")) {
            \Log::info('[dispatch] cv job running now, skipping', ['uuid' => $cv->uuid]);
            return;
        }

        \Log::info('[dispatch] CV rewrite job dispatching', [
            'cv_id' => $cv->id,
            'uuid'  => $cv->uuid,
        ]);

        // Take the lock BEFORE dispatch — blocks a rapid kick() from duplicating.
        \Cache::put("cv-rewrite-running:{$cv->id}", true, 300);

        try {
            GenerateCvRewriteJob::dispatch($cv->id);
        } catch (\Throwable $e) {
            // Dispatch failed — release the lock so kick() can recover later.
            \Cache::forget("cv-rewrite-running:{$cv->id}");

            \Log::error('[dispatch] cv dispatch failed', [
                'cv_id' => $cv->id,
                'uuid'  => $cv->uuid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function dispatchCoverLetterJob(Deposit $deposit): void
    {
        $letter = LetterRequest::where('uuid', $deposit->purpose_reference)->first();
        if (!$letter) {
            return;
        }

        if ($letter->status !== 'paid') {
            return;
        }

        if (!empty($letter->generated_at)) {
            return;
        }

        // Already queued? Skip.
        $isQueued = $this->isJobQueued('default', '"letterRequestId":' . $letter->id);
        if ($isQueued) {
            Log::info('[dispatch] letter job already queued, skipping', ['uuid' => $letter->uuid]);
            return;
        }

        Log::info('[dispatch] letter job dispatched', [
            'letter_id' => $letter->id,
            'uuid'      => $letter->uuid,
        ]);

        GenerateLetterJob::dispatch($letter->id)->onQueue('default');
    }

    /**
     * Does the `jobs` table already contain a job whose serialized payload
     * contains the given needle? Cheap and reliable enough for idempotency.
     */
    protected function isJobQueued(string $queue, string $needle): bool
    {
        return DB::table('jobs')
            ->where('queue', $queue)
            ->where('payload', 'like', '%' . $needle . '%')
            ->exists();
    }

    protected function toCents($raw, Deposit $deposit): int
    {
        if (!$deposit->currency) {
            return (int) round((float) $raw * 100);
        }
        return $deposit->currency->toCents((float) $raw);
    }
}