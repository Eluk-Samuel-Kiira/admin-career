<?php

namespace App\Services\Payment;

use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\PaymentTransactionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentTransactionService
{
    /**
     * Record a deposit into the accounting log and update the payment method balance.
     *
     * Idempotent: if a log entry already exists for this deposit (same deposit_ref),
     * it returns the existing log without creating a duplicate. That way the webhook
     * can be replayed safely, and the manual deposit path is protected too.
     *
     * @return PaymentTransactionLog|null
     */
    public function recordDeposit(Deposit $deposit): ?PaymentTransactionLog
    {
        // Nothing to do if there is no method to credit against
        $paymentMethod = $deposit->paymentMethod
            ?? $this->resolvePaymentMethod($deposit);

        if (!$paymentMethod) {
            Log::warning('PaymentTransactionService: deposit has no resolvable payment method', [
                'deposit_id'  => $deposit->id,
                'deposit_ref' => $deposit->deposit_ref,
            ]);
            return null;
        }

        // Idempotency: one log entry per deposit
        $existing = PaymentTransactionLog::where('external_reference', $deposit->deposit_ref)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($deposit, $paymentMethod) {
            $before = $paymentMethod->current_balance ?? 0;
            $fee    = $deposit->fee ?? 0;
            $amount = $deposit->amount;

            // Credit the method's wallet (deposit - fee)
            $paymentMethod->updateBalance($amount, 'deposit', $fee);
            $paymentMethod->refresh();
            $after = $paymentMethod->current_balance ?? 0;

            return PaymentTransactionLog::create([
                'transaction_ref'     => (string) Str::uuid(),
                'payment_method_id'   => $paymentMethod->id,
                'department_id'       => $deposit->department_id ?? null,
                'depositor_id'        => $deposit->depositor_id ?? null,
                'transaction_type'    => 'deposit',
                'transaction_category'=> 'revenue',
                'reference_table'     => 'deposits',
                'reference_id'        => $deposit->id,
                'amount'              => $amount,
                'transaction_fee'     => $fee,
                'net_amount'          => max(0, $amount - $fee),
                'balance_before'      => $before,
                'balance_after'       => $after,
                'currency_id'         => $deposit->currency_id,
                'exchange_rate'       => 1,
                'status'              => 'completed',
                'transaction_date'    => now(),
                'effective_date'      => $deposit->cleared_date ?? now(),
                'settlement_date'     => now(),
                'description'         => $deposit->purpose_description ?? 'Deposit',
                'metadata'            => [
                    'deposit_ref'         => $deposit->deposit_ref,
                    'purpose_type'        => $deposit->purpose_type,
                    'purpose_reference'   => $deposit->purpose_reference,
                    'payment_channel'     => $deposit->payment_channel,
                    'gateway_reference'   => $deposit->source_reference,
                    'provider_transaction_id' => $deposit->reference_number,
                    'is_manual'           => (bool) ($deposit->is_manual ?? false),
                ],
                'external_reference'  => $deposit->deposit_ref,
                'bank_reference'      => $deposit->reference_number,
                'user_id'             => $deposit->paid_by_user_id ?? $deposit->depositor_id,
                'counterparty_id'     => $deposit->depositor_id ? (string) $deposit->depositor_id : null,
                'counterparty_name'   => $deposit->depositor_name,
                'counterparty_account' => $deposit->depositor_phone ?? $deposit->depositor_email,
                'created_by'          => $deposit->verified_by ?? $deposit->created_by ?? null,
            ]);
        });
    }

    /**
     * Record a withdrawal (money leaving a payment method).
     */
    public function recordWithdrawal(
        PaymentMethod $paymentMethod,
        int $amountCents,
        ?int $feeCents = null,
        ?string $description = null,
        ?string $externalRef = null,
        ?int $userId = null,
        array $metadata = [],
    ): PaymentTransactionLog {
        return $this->recordDebit(
            paymentMethod: $paymentMethod,
            amountCents: $amountCents,
            feeCents: $feeCents ?? 0,
            type: 'withdrawal',
            category: 'expense',
            description: $description,
            externalRef: $externalRef,
            userId: $userId,
            metadata: $metadata,
        );
    }

    /**
     * Record an adjustment (manual credit or debit). Positive $delta credits the
     * method, negative debits it. Used by admin corrections, refunds, and reversals.
     */
    public function recordAdjustment(
        PaymentMethod $paymentMethod,
        int $deltaCents,
        ?string $description = null,
        ?int $userId = null,
        array $metadata = [],
    ): PaymentTransactionLog {
        $type      = $deltaCents >= 0 ? 'adjustment' : 'adjustment';
        $category  = $deltaCents >= 0 ? 'revenue'    : 'expense';
        $amount    = abs($deltaCents);

        if ($deltaCents >= 0) {
            return $this->recordCredit(
                paymentMethod: $paymentMethod,
                amountCents: $amount,
                feeCents: 0,
                type: $type,
                category: $category,
                description: $description,
                externalRef: null,
                userId: $userId,
                metadata: $metadata,
            );
        }

        return $this->recordDebit(
            paymentMethod: $paymentMethod,
            amountCents: $amount,
            feeCents: 0,
            type: $type,
            category: $category,
            description: $description,
            externalRef: null,
            userId: $userId,
            metadata: $metadata,
        );
    }

    /**
     * Record a refund against a previous deposit.
     */
    public function recordRefund(Deposit $original, int $refundCents, ?string $reason = null): ?PaymentTransactionLog
    {
        $paymentMethod = $original->paymentMethod;
        if (!$paymentMethod) {
            return null;
        }

        $log = $this->recordDebit(
            paymentMethod: $paymentMethod,
            amountCents: $refundCents,
            feeCents: 0,
            type: 'refund',
            category: 'expense',
            description: $reason ?? ('Refund of ' . $original->deposit_ref),
            externalRef: $original->deposit_ref,
            userId: $original->paid_by_user_id,
            metadata: [
                'original_deposit_ref' => $original->deposit_ref,
                'original_deposit_id'  => $original->id,
            ],
        );

        // Optionally flip the original deposit's status
        $original->update([
            'status' => 'refunded',
            'notes'  => trim(($original->notes ?? '') . "\nRefunded: " . ($reason ?? 'no reason provided')),
        ]);

        return $log;
    }

    // ─────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────

    protected function recordCredit(
        PaymentMethod $paymentMethod,
        int $amountCents,
        int $feeCents,
        string $type,
        string $category,
        ?string $description,
        ?string $externalRef,
        ?int $userId,
        array $metadata,
    ): PaymentTransactionLog {
        return DB::transaction(function () use ($paymentMethod, $amountCents, $feeCents, $type, $category, $description, $externalRef, $userId, $metadata) {
            $before = $paymentMethod->current_balance ?? 0;
            $paymentMethod->updateBalance($amountCents, 'deposit', $feeCents);
            $paymentMethod->refresh();
            $after = $paymentMethod->current_balance ?? 0;

            return PaymentTransactionLog::create([
                'transaction_ref'     => (string) Str::uuid(),
                'payment_method_id'   => $paymentMethod->id,
                'transaction_type'    => $type,
                'transaction_category'=> $category,
                'amount'              => $amountCents,
                'transaction_fee'     => $feeCents,
                'net_amount'          => max(0, $amountCents - $feeCents),
                'balance_before'      => $before,
                'balance_after'       => $after,
                'currency_id'         => $paymentMethod->currency_id,
                'exchange_rate'       => 1,
                'status'              => 'completed',
                'transaction_date'    => now(),
                'effective_date'      => now(),
                'settlement_date'     => now(),
                'description'         => $description,
                'metadata'            => $metadata ?: null,
                'external_reference'  => $externalRef,
                'user_id'             => $userId,
                'created_by'          => auth()->id() ?? $userId,
            ]);
        });
    }

    protected function recordDebit(
        PaymentMethod $paymentMethod,
        int $amountCents,
        int $feeCents,
        string $type,
        string $category,
        ?string $description,
        ?string $externalRef,
        ?int $userId,
        array $metadata,
    ): PaymentTransactionLog {
        return DB::transaction(function () use ($paymentMethod, $amountCents, $feeCents, $type, $category, $description, $externalRef, $userId, $metadata) {
            $before = $paymentMethod->current_balance ?? 0;
            $paymentMethod->updateBalance($amountCents, 'withdrawal', $feeCents);
            $paymentMethod->refresh();
            $after = $paymentMethod->current_balance ?? 0;

            return PaymentTransactionLog::create([
                'transaction_ref'     => (string) Str::uuid(),
                'payment_method_id'   => $paymentMethod->id,
                'transaction_type'    => $type,
                'transaction_category'=> $category,
                'amount'              => -$amountCents, // negative on a debit
                'transaction_fee'     => $feeCents,
                'net_amount'          => -max(0, $amountCents + $feeCents),
                'balance_before'      => $before,
                'balance_after'       => $after,
                'currency_id'         => $paymentMethod->currency_id,
                'exchange_rate'       => 1,
                'status'              => 'completed',
                'transaction_date'    => now(),
                'effective_date'      => now(),
                'settlement_date'     => now(),
                'description'         => $description,
                'metadata'            => $metadata ?: null,
                'external_reference'  => $externalRef,
                'user_id'             => $userId,
                'created_by'          => auth()->id() ?? $userId,
            ]);
        });
    }

    /**
     * Fall back to matching the deposit's channel to a PaymentMethod row.
     * Covers the case where the deposit was created before the provider
     * was known and payment_method_id was never set.
     */
    protected function resolvePaymentMethod(Deposit $deposit): ?PaymentMethod
    {
        $channel = $deposit->payment_channel ?? $deposit->deposit_method;

        return PaymentMethod::active()
            ->where('provider', 'marzpay')
            ->when($channel === 'card', fn($q) => $q->where('type', 'card'))
            ->when($channel === 'mobile_money', fn($q) => $q->where('type', 'mobile_money'))
            ->first();
    }
}