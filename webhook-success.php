<?php
// webhook-success.php
// Simulate a successful MarzPay payment for a deposit — locally.
// Usage from tinker:
//   include base_path('webhook-success.php');
// Or with a specific deposit:
//   $depositRef = '50266a7a-...';
//   include base_path('webhook-success.php');

use App\Models\Deposit;
use App\Models\Subscription\PaymentWebhook;
use App\Models\Service\CvReviewRequest;
use App\Models\Service\LetterRequest;
use App\Jobs\GenerateCvRewriteJob;
use App\Jobs\GenerateLetterJob;
use App\Services\Payment\PaymentTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// ── EDIT THIS — the deposit you want to mark paid ─────────────
if (!isset($depositRef) || !$depositRef) {
    $depositRef = '50266a7a-544d-440e-b2cb-a13e8cf40fbb';
}
// ──────────────────────────────────────────────────────────────

$runId = 'sim-' . Str::random(6);
Log::info("[{$runId}] webhook-success: START", ['deposit_ref' => $depositRef]);
echo "[{$runId}] START\n";

$deposit = Deposit::withTrashed()
    ->where('deposit_ref', $depositRef)
    ->first();

if (!$deposit) {
    Log::error("[{$runId}] deposit not found", ['deposit_ref' => $depositRef]);
    echo "❌ Deposit not found: {$depositRef}\n";
    return;
}

if (method_exists($deposit, 'trashed') && $deposit->trashed()) {
    $deposit->restore();
    Log::info("[{$runId}] restored soft-deleted deposit");
    echo "[restored soft-deleted deposit]\n";
}

Log::info("[{$runId}] BEFORE", [
    'deposit_ref'       => $deposit->deposit_ref,
    'deposit_id'        => $deposit->id,
    'purpose_type'      => $deposit->purpose_type,
    'purpose_reference' => $deposit->purpose_reference,
    'amount_cents'      => $deposit->amount,
    'status'            => $deposit->status,
]);

echo "BEFORE\n";
echo "  deposit_ref:       {$deposit->deposit_ref}\n";
echo "  purpose_type:      {$deposit->purpose_type}\n";
echo "  purpose_reference: {$deposit->purpose_reference}\n";
echo "  amount (cents):    {$deposit->amount}\n";
echo "  status:            {$deposit->status}\n";

if ($deposit->status === 'completed') {
    Log::info("[{$runId}] skipped — already completed");
    echo "\n⏭️  Already 'completed' — nothing to do.\n";
    return;
}

// ── Build payload ──────────────────────────────────────────────
$currency   = $deposit->currency;
$rawAmount  = $currency
    ? (float) $currency->fromCents($deposit->amount)
    : (float) $deposit->amount;

$providerTxId = 'PROVIDER-' . strtoupper(Str::random(10));

$payload = [
    'event_type'  => 'collection.completed',
    'transaction' => [
        'reference'   => $deposit->deposit_ref,
        'status'      => 'completed',
        'amount'      => ['raw' => $rawAmount],
        'charge'      => ['raw' => 0],
        'net_amount'  => ['raw' => $rawAmount],
        'description' => 'Successful payment (simulated locally)',
    ],
    'collection' => [
        'provider_transaction_id' => $providerTxId,
        'failure_reason'          => null,
    ],
];

Log::info("[{$runId}] payload built", [
    'raw_amount'      => $rawAmount,
    'currency_code'   => $currency?->code,
    'provider_tx_id'  => $providerTxId,
]);

// ── 1. Audit row ───────────────────────────────────────────────
try {
    $wh = PaymentWebhook::create([
        'gateway'           => 'marzpay',
        'event_type'        => $payload['event_type'],
        'gateway_reference' => $deposit->deposit_ref,
        'payload'           => $payload,
        'signature'         => null,
        'signature_valid'   => false,
        'status'            => 'received',
    ]);
    Log::info("[{$runId}] PaymentWebhook audit created", ['id' => $wh->id]);
} catch (\Throwable $e) {
    Log::warning("[{$runId}] PaymentWebhook audit insert failed", [
        'error' => $e->getMessage(),
        'file'  => $e->getFile() . ':' . $e->getLine(),
    ]);
    echo "⚠️  PaymentWebhook audit insert failed: {$e->getMessage()}\n";
}

// ── 2. State-change transaction ────────────────────────────────
$jobDispatched = null;

Log::info("[{$runId}] entering DB::transaction");
echo "\n→ entering DB::transaction\n";

try {
    DB::transaction(function () use ($deposit, $payload, $providerTxId, $runId, &$jobDispatched) {
        $txn        = $payload['transaction'];
        $collection = $payload['collection'];

        // a) Flip deposit
        Log::info("[{$runId}] step 2a: updating deposit to completed");
        echo "  → step 2a: updating deposit\n";

        $netCents = $deposit->currency
            ? $deposit->currency->toCents((float) $txn['net_amount']['raw'])
            : (int) round($txn['net_amount']['raw'] * 100);

        $deposit->update([
            'status'           => 'completed',
            'cleared_date'     => now(),
            'reference_number' => $collection['provider_transaction_id'],
            'fee'              => 0,
            'net_amount'       => $netCents,
        ]);
        Log::info("[{$runId}] step 2a: deposit updated", ['net_cents' => $netCents]);

        // b) Accounting
        Log::info("[{$runId}] step 2b: recordDeposit (may warn if no payment method)");
        echo "  → step 2b: recordDeposit\n";
        try {
            app(PaymentTransactionService::class)->recordDeposit($deposit->fresh());
            Log::info("[{$runId}] step 2b: recordDeposit OK");
        } catch (\Throwable $e) {
            Log::warning("[{$runId}] step 2b: recordDeposit failed (non-fatal)", [
                'error' => $e->getMessage(),
            ]);
            echo "⚠️  PaymentTransactionService::recordDeposit failed: {$e->getMessage()}\n";
        }

        // c) Domain row
        Log::info("[{$runId}] step 2c: flipping domain row", ['purpose' => $deposit->purpose_type]);
        echo "  → step 2c: flipping {$deposit->purpose_type}\n";

        if ($deposit->purpose_type === 'cv_review') {
            $cv = CvReviewRequest::where('uuid', $deposit->purpose_reference)->first();
            if (!$cv) {
                Log::error("[{$runId}] CvReviewRequest not found", ['uuid' => $deposit->purpose_reference]);
                throw new \Exception("CvReviewRequest not found: {$deposit->purpose_reference}");
            }

            Log::info("[{$runId}] step 2c: cv_review found", [
                'id'           => $cv->id,
                'status'       => $cv->status,
                'rewrite_mode' => $cv->rewrite_mode,
            ]);

            if ($cv->rewrite_mode === 'ai') {
                $cv->update([
                    'status'            => 'paid_ai',
                    'payment_reference' => $deposit->deposit_ref,
                ]);
                $jobDispatched = ['type' => 'cv_rewrite', 'id' => $cv->id];
                Log::info("[{$runId}] step 2c: cv → paid_ai, queued for dispatch", ['cv_id' => $cv->id]);
                echo "  → CV status: {$cv->status} (mode: {$cv->rewrite_mode})\n";
            } else {
                $cv->update([
                    'status'            => 'paid_human',
                    'payment_reference' => $deposit->deposit_ref,
                ]);
                Log::info("[{$runId}] step 2c: cv → paid_human (no job)", ['cv_id' => $cv->id]);
                echo "  → CV status: {$cv->status} (mode: {$cv->rewrite_mode})\n";
            }

        } elseif ($deposit->purpose_type === 'cover_letter') {
            $affected = LetterRequest::where('uuid', $deposit->purpose_reference)
                ->whereIn('status', ['pending_payment', 'paid'])
                ->update([
                    'status'            => 'paid',
                    'payment_reference' => $deposit->deposit_ref,
                    'paid_at'           => now(),
                    'error_message'     => null,
                ]);
            Log::info("[{$runId}] step 2c: letter updated", ['affected_rows' => $affected]);

            $letter = LetterRequest::where('uuid', $deposit->purpose_reference)->first();
            if ($letter) {
                $jobDispatched = ['type' => 'cover_letter', 'id' => $letter->id];
                Log::info("[{$runId}] step 2c: letter → paid, queued for dispatch", ['letter_id' => $letter->id]);
                echo "  → Letter status: {$letter->status}\n";
            }
        }
    });

    Log::info("[{$runId}] ✅ transaction committed");
    echo "\n✅ Transaction committed.\n";

} catch (\Throwable $e) {
    Log::error("[{$runId}] ❌ transaction failed", [
        'class'   => get_class($e),
        'message' => $e->getMessage(),
        'file'    => $e->getFile() . ':' . $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ]);

    echo "\n❌ Exception: " . get_class($e) . "\n";
    echo "   Message: {$e->getMessage()}\n";
    echo "   File:    {$e->getFile()}:{$e->getLine()}\n";
    echo "\n   Trace (first 5 lines):\n";
    foreach (array_slice(explode("\n", $e->getTraceAsString()), 0, 5) as $line) {
        echo "     {$line}\n";
    }
    return;
}

// ── 3. Mark webhook processed ──────────────────────────────────
try {
    PaymentWebhook::where('gateway', 'marzpay')
        ->where('gateway_reference', $deposit->deposit_ref)
        ->latest('id')
        ->first()
        ?->update(['status' => 'processed', 'processed_at' => now()]);
    Log::info("[{$runId}] PaymentWebhook marked processed");
} catch (\Throwable $e) {
    Log::warning("[{$runId}] failed to mark webhook processed", ['error' => $e->getMessage()]);
}

// ── 4. Dispatch ────────────────────────────────────────────────
Log::info("[{$runId}] dispatch phase", ['jobDispatched' => $jobDispatched]);
echo "\n[debug] jobDispatched = " . json_encode($jobDispatched) . "\n";

if ($jobDispatched) {
    try {
        if ($jobDispatched['type'] === 'cv_rewrite') {
            Log::info("[{$runId}] dispatching GenerateCvRewriteJob", ['id' => $jobDispatched['id']]);
            echo "[debug] calling GenerateCvRewriteJob::dispatch({$jobDispatched['id']})...\n";

            $job = GenerateCvRewriteJob::dispatch($jobDispatched['id'])->onQueue('letters');

            Log::info("[{$runId}] GenerateCvRewriteJob dispatched", [
                'returned_class' => get_class($job),
                'queue'          => 'letters',
            ]);
            echo "[debug] dispatch returned: " . get_class($job) . "\n";
            echo "📤 Dispatched GenerateCvRewriteJob (cv_review #{$jobDispatched['id']})\n";

        } elseif ($jobDispatched['type'] === 'cover_letter') {
            Log::info("[{$runId}] dispatching GenerateLetterJob", ['id' => $jobDispatched['id']]);
            echo "[debug] calling GenerateLetterJob::dispatch({$jobDispatched['id']})...\n";

            $job = GenerateLetterJob::dispatch($jobDispatched['id'])->onQueue('default');

            Log::info("[{$runId}] GenerateLetterJob dispatched", [
                'returned_class' => get_class($job),
                'queue'          => 'default',
            ]);
            echo "[debug] dispatch returned: " . get_class($job) . "\n";
            echo "📤 Dispatched GenerateLetterJob (letter #{$jobDispatched['id']})\n";
        }

        // ── Verify the row is actually in the jobs table ────────
        $queue = $jobDispatched['type'] === 'cv_rewrite' ? 'letters' : 'default';
        $count = DB::table('jobs')->where('queue', $queue)->count();
        Log::info("[{$runId}] jobs table check", ['queue' => $queue, 'count' => $count]);
        echo "[debug] jobs on '{$queue}': {$count}\n";

    } catch (\Throwable $e) {
        Log::error("[{$runId}] ❌ dispatch failed", [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ]);
        echo "⚠️  Job dispatch failed: " . get_class($e) . ": " . $e->getMessage() . "\n";
        echo "     File: {$e->getFile()}:{$e->getLine()}\n";
    }
} else {
    Log::warning("[{$runId}] jobDispatched is NULL — dispatch skipped");
    echo "[debug] jobDispatched is NULL/false — dispatch was skipped\n";
}

// ── 5. AFTER state ─────────────────────────────────────────────
$deposit->refresh();

Log::info("[{$runId}] AFTER deposit", [
    'status'           => $deposit->status,
    'cleared_date'     => $deposit->cleared_date,
    'reference_number' => $deposit->reference_number,
    'net_amount'       => $deposit->net_amount,
    'fee'              => $deposit->fee,
]);

echo "\nAFTER (deposit)\n";
echo "  status:           {$deposit->status}\n";
echo "  cleared_date:     {$deposit->cleared_date}\n";
echo "  reference_number: {$deposit->reference_number}\n";
echo "  net_amount:       {$deposit->net_amount}\n";
echo "  fee:              {$deposit->fee}\n";

if ($deposit->purpose_type === 'cv_review') {
    $cv = CvReviewRequest::where('uuid', $deposit->purpose_reference)->first();
    if ($cv) {
        Log::info("[{$runId}] AFTER cv_review_request", [
            'id'                    => $cv->id,
            'status'                => $cv->status,
            'rewrite_mode'          => $cv->rewrite_mode,
            'amount_cents'          => $cv->amount_cents,
            'payment_reference'     => $cv->payment_reference,
            'rewrite_generated_at'  => $cv->rewrite_generated_at,
            'rewrite_error_message' => $cv->rewrite_error_message,
        ]);

        echo "\nAFTER (cv_review_request)\n";
        echo "  status:               {$cv->status}\n";
        echo "  rewrite_mode:         " . ($cv->rewrite_mode ?? '-') . "\n";
        echo "  amount_cents:         {$cv->amount_cents}\n";
        echo "  payment_reference:    " . ($cv->payment_reference ?? '-') . "\n";
        echo "  rewrite_generated_at: " . ($cv->rewrite_generated_at ?? '-') . "\n";
        echo "  rewrite_error:        " . ($cv->rewrite_error_message ?? '-') . "\n";
    }
}

if ($deposit->purpose_type === 'cover_letter') {
    $letter = LetterRequest::where('uuid', $deposit->purpose_reference)->first();
    if ($letter) {
        Log::info("[{$runId}] AFTER letter_request", [
            'status'            => $letter->status,
            'payment_reference' => $letter->payment_reference,
            'paid_at'           => $letter->paid_at,
        ]);

        echo "\nAFTER (letter_request)\n";
        echo "  status:            {$letter->status}\n";
        echo "  payment_reference: " . ($letter->payment_reference ?? '-') . "\n";
        echo "  paid_at:           " . ($letter->paid_at ?? '-') . "\n";
    }
}

Log::info("[{$runId}] webhook-success: DONE");
echo "\nDone.\n";