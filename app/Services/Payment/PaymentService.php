<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use App\Models\PaymentTransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{

    /**
     * Process a deposit transaction (INCREASES balance)
     */
    public function deposit(array $data): PaymentTransactionLog
    {
        return $this->processTransaction(array_merge($data, [
            'transaction_type' => 'deposit',
            'transaction_category' => 'revenue',
            'is_credit' => true,
        ]));
    }

    /**
     * Process a withdrawal transaction (DECREASES balance)
     */
    public function withdraw(array $data): PaymentTransactionLog
    {
        return $this->processTransaction(array_merge($data, [
            'transaction_type' => 'withdrawal',
            'transaction_category' => 'expense',
            'is_credit' => false,
        ]));
    }

    /**
     * Process a refund transaction (INCREASES balance - money coming back)
     */
    public function refund(array $data): PaymentTransactionLog
    {
        return $this->processTransaction(array_merge($data, [
            'transaction_type' => 'refund',
            'transaction_category' => 'adjustment',
            'is_credit' => true,
        ]));
    }

    /**
     * Process a transfer between payment methods
     */
    public function transfer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            if (!isset($data['from_payment_method_id']) || !isset($data['to_payment_method_id'])) {
                throw new \InvalidArgumentException("Both from and to payment methods are required");
            }

            $fromMethod = PaymentMethod::with('currency')->findOrFail($data['from_payment_method_id']);
            $toMethod = PaymentMethod::with('currency')->findOrFail($data['to_payment_method_id']);
            
            // Get the amount in cents from the source currency
            $fromCurrency = $fromMethod->currency;
            if (!$fromCurrency) {
                throw new \RuntimeException("Source payment method has no currency configured");
            }
            
            $amountInCents = $this->toCents($data['amount'], $fromCurrency);

            // Calculate the amount in destination currency
            $toCurrency = $toMethod->currency;
            if (!$toCurrency) {
                throw new \RuntimeException("Destination payment method has no currency configured");
            }
            
            // Convert the amount if currencies differ
            $convertedAmount = $data['amount'];
            if ($fromCurrency->id !== $toCurrency->id) {
                // Convert via exchange rates
                if ($fromCurrency->exchange_rate_to_usd && $toCurrency->exchange_rate_to_usd) {
                    $amountInUSD = $data['amount'] / $fromCurrency->exchange_rate_to_usd;
                    $convertedAmount = $amountInUSD * $toCurrency->exchange_rate_to_usd;
                    $convertedAmount = round($convertedAmount, $toCurrency->decimal_places);
                } else {
                    // Fallback: use 1:1 conversion
                    Log::warning('No exchange rate available for transfer, using 1:1', [
                        'from' => $fromCurrency->code,
                        'to' => $toCurrency->code
                    ]);
                }
            }
            
            $convertedAmountInCents = $this->toCents($convertedAmount, $toCurrency);

            // Process withdrawal from source
            $withdrawalData = array_merge($data, [
                'payment_method_id' => $data['from_payment_method_id'],
                'transaction_type' => 'transfer_out', // Changed to lowercase
                'transaction_category' => 'transfer', // Changed to lowercase
                'is_credit' => false,
                'amount' => $data['amount'],
                'description' => ($data['description'] ?? '') . ' - Transfer to ' . ($data['to_payment_method_name'] ?? $toMethod->name),
                'currency_id' => $fromCurrency->id,
                'status' => 'completed', // Added status
            ]);

            $withdrawal = $this->processTransaction($withdrawalData);

            // Process deposit to destination
            $depositData = array_merge($data, [
                'payment_method_id' => $data['to_payment_method_id'],
                'transaction_type' => 'transfer_in', // Changed to lowercase
                'transaction_category' => 'transfer', // Changed to lowercase
                'is_credit' => true,
                'amount' => $convertedAmount,
                'description' => ($data['description'] ?? '') . ' - Transfer from ' . ($data['from_payment_method_name'] ?? $fromMethod->name),
                'transaction_fee' => 0,
                'currency_id' => $toCurrency->id,
                'exchange_rate' => $fromCurrency->id !== $toCurrency->id ? 
                    ($toCurrency->exchange_rate_to_usd / $fromCurrency->exchange_rate_to_usd) : 1,
                'status' => 'completed', // Added status
            ]);

            $deposit = $this->processTransaction($depositData);

            return [
                'withdrawal' => $withdrawal,
                'deposit' => $deposit,
            ];
        });
    }

    /**
     * Reverse a transaction
     */
    public function reverse(string $transactionRef, string $reason = null): PaymentTransactionLog
    {
        return DB::transaction(function () use ($transactionRef, $reason) {
            $originalTransaction = PaymentTransactionLog::where('transaction_ref', $transactionRef)
                ->lockForUpdate()
                ->firstOrFail();

            if ($originalTransaction->status === 'reversed') { // Changed to lowercase
                throw new \RuntimeException("Transaction already reversed");
            }

            // Determine reversal type
            $isCredit = $originalTransaction->transaction_type === 'withdrawal'; // Changed to lowercase
            $reverseType = $isCredit ? 'deposit' : 'withdrawal'; // Changed to lowercase

            // Process reversal
            $reverseData = [
                'user_id' => $originalTransaction->user_id,
                'payment_method_id' => $originalTransaction->payment_method_id,
                'amount' => $originalTransaction->amount,
                'transaction_type' => $reverseType,
                'transaction_category' => 'adjustment', // Changed to lowercase
                'currency_id' => $originalTransaction->currency_id,
                'exchange_rate' => $originalTransaction->exchange_rate,
                'description' => "Reversal of {$originalTransaction->transaction_ref}: " . ($reason ?? 'Transaction reversed'),
                'reference_table' => 'payment_transaction_logs',
                'reference_id' => $originalTransaction->id,
                'is_credit' => $isCredit,
                'status' => 'completed', // Added status
            ];

            $reversal = $this->processTransaction($reverseData);

            // Mark original as reversed
            $originalTransaction->status = 'reversed'; // Changed to lowercase
            $originalTransaction->notes = ($originalTransaction->notes ? $originalTransaction->notes . "\n" : '') . "Reversed by transaction: {$reversal->transaction_ref}";
            if ($reason) {
                $originalTransaction->notes .= "\nReason: {$reason}";
            }
            $originalTransaction->save();

            return $reversal;
        });
    }

    /**
     * Get payment method balance
     */
    public function getBalance(int $paymentMethodId): array
    {
        $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);
        
        return [
            'current_balance' => $paymentMethod->current_balance,
            'formatted_balance' => $paymentMethod->formatted_current_balance,
            'available_balance' => $paymentMethod->available_balance,
            'pending_balance' => $paymentMethod->pending_balance,
            'currency' => $paymentMethod->currency?->code ?? 'USD',
            'last_updated' => $paymentMethod->updated_at,
        ];
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory(int $paymentMethodId, array $filters = []): array
    {
        $query = PaymentTransactionLog::where('payment_method_id', $paymentMethodId)
            ->orderBy('transaction_date', 'desc');
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', strtolower($filters['transaction_type']));
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }
        
        $transactions = $query->paginate($filters['per_page'] ?? 50);
        
        return [
            'transactions' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ]
        ];
    }

    /**
     * Main transaction processor
     */
    private function processTransaction(array $data): PaymentTransactionLog
    {
        return DB::transaction(function () use ($data) {
            $this->validateTransactionData($data);

            $paymentMethod = PaymentMethod::where('id', $data['payment_method_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $currency = $paymentMethod->currency;
            $amountInCents = $this->toCents($data['amount'], $currency);

            $fee = $this->calculateFee($data, $paymentMethod, $amountInCents);

            $netAmount = $data['is_credit'] 
                ? $amountInCents - $fee
                : $amountInCents + $fee;

            if (!$data['is_credit']) {
                $this->validateBalance($paymentMethod, $netAmount);
            }

            $balanceBefore = $paymentMethod->current_balance;
            $balanceAfter = $data['is_credit'] 
                ? $balanceBefore + $netAmount
                : $balanceBefore - $netAmount;

            if (!$data['is_credit']) {
                $this->validateLimits($paymentMethod, $amountInCents);
            }

            $transaction = $this->createTransactionLog(
                $data,
                $paymentMethod,
                $amountInCents,
                $fee,
                $netAmount,
                $balanceBefore,
                $balanceAfter
            );

            $this->updatePaymentMethodBalance($paymentMethod, $balanceAfter, $amountInCents, $data['is_credit']);

            Log::info('Payment transaction processed', [
                'transaction_ref' => $transaction->transaction_ref,
                'payment_method' => $paymentMethod->name,
                'type' => $data['transaction_type'],
                'department_id' => $data['department_id'] ?? null,
                'depositor_id' => $data['depositor_id'] ?? null,
                'amount' => $amountInCents,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'user_id' => $data['user_id'],
            ]);

            return $transaction;
        });
    }

    /**
     * Update payment method balance after transaction
     */
    private function updatePaymentMethodBalance(PaymentMethod $paymentMethod, int $newBalance, int $amount, bool $isCredit): void
    {
        $paymentMethod->current_balance = $newBalance;
        $paymentMethod->available_balance = $newBalance;
        $paymentMethod->last_transaction_at = now();
        $paymentMethod->last_transaction_amount = $amount;
        $paymentMethod->last_transaction_type = $isCredit ? 'credit' : 'debit'; // Changed to lowercase
        $paymentMethod->save();
    }

    /**
     * Validate balance constraints for withdrawal
     */
    private function validateBalance(PaymentMethod $paymentMethod, int $netAmount): void
    {
        $newBalance = $paymentMethod->current_balance - $netAmount;

        if (!$paymentMethod->allow_negative_balance && $newBalance < 0) {
            throw new \RuntimeException(
                "Insufficient balance. Current balance: " . $paymentMethod->formatted_current_balance
            );
        }

        if ($paymentMethod->min_balance_limit && $newBalance < $paymentMethod->min_balance_limit) {
            $minBalanceFormatted = $paymentMethod->currency?->formatAmount($paymentMethod->min_balance_limit) ?? '$' . number_format($paymentMethod->min_balance_limit / 100, 2);
            throw new \RuntimeException(
                "Transaction would violate minimum balance limit of {$minBalanceFormatted}"
            );
        }
    }

    /**
     * Validate required transaction data
     */
    private function validateTransactionData(array $data): void
    {
        $required = ['payment_method_id', 'amount', 'transaction_type', 'currency_id', 'user_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException("Amount must be greater than zero");
        }
    }

    /**
     * Convert amount to cents based on currency decimal places
     */
    private function toCents($amount, $currency): int
    {
        if ($currency) {
            return $currency->toCents((float) $amount);
        }
        // Default to 2 decimal places if no currency
        return (int) round((float) $amount * 100);
    }

    /**
     * Calculate transaction fee
     */
    private function calculateFee(array $data, PaymentMethod $paymentMethod, int $amountInCents): int
    {
        // Use custom fee if provided
        if (isset($data['transaction_fee'])) {
            return (int) round((float) $data['transaction_fee'] * 100);
        }

        // Skip fees if specified
        if (isset($data['skip_fees']) && $data['skip_fees']) {
            return 0;
        }

        return $paymentMethod->calculateFee($amountInCents);
    }


    /**
     * Validate daily and monthly limits for withdrawals
     */
    private function validateLimits(PaymentMethod $paymentMethod, int $amountInCents): void
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Check daily limit
        if ($paymentMethod->daily_limit) {
            $dailyTotal = PaymentTransactionLog::where('payment_method_id', $paymentMethod->id)
                ->where('transaction_date', '>=', $today)
                ->whereIn('transaction_type', ['withdrawal', 'transfer_out']) // Changed to lowercase
                ->sum('amount');

            if ($dailyTotal + $amountInCents > $paymentMethod->daily_limit) {
                $remaining = $paymentMethod->daily_limit - $dailyTotal;
                $remainingFormatted = $paymentMethod->currency?->formatAmount($remaining) ?? '$' . number_format($remaining / 100, 2);
                throw new \RuntimeException("Daily limit exceeded. Remaining limit: {$remainingFormatted}");
            }
        }

        // Check monthly limit
        if ($paymentMethod->monthly_limit) {
            $monthlyTotal = PaymentTransactionLog::where('payment_method_id', $paymentMethod->id)
                ->where('transaction_date', '>=', $thisMonth)
                ->whereIn('transaction_type', ['withdrawal', 'transfer_out']) // Changed to lowercase
                ->sum('amount');

            if ($monthlyTotal + $amountInCents > $paymentMethod->monthly_limit) {
                throw new \RuntimeException("Monthly limit exceeded");
            }
        }
    }

    /**
     * Create transaction log entry
     */
    private function createTransactionLog(
        array $data,
        PaymentMethod $paymentMethod,
        int $amountInCents,
        int $fee,
        int $netAmount,
        int $balanceBefore,
        int $balanceAfter
    ): PaymentTransactionLog {
        // Ensure status is lowercase
        $status = isset($data['status']) ? strtolower($data['status']) : 'completed';
        
        return PaymentTransactionLog::create([
            'transaction_ref' => Str::uuid(),
            'payment_method_id' => $paymentMethod->id,
            'department_id' => $data['department_id'] ?? null,
            'depositor_id' => $data['depositor_id'] ?? null,
            'transaction_type' => strtolower($data['transaction_type']), // Ensure lowercase
            'transaction_category' => strtolower($data['transaction_category']), // Ensure lowercase
            'reference_table' => $data['reference_table'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'amount' => $amountInCents,
            'transaction_fee' => $fee,
            'net_amount' => $netAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'currency_id' => $data['currency_id'],
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'status' => $status, // Already lowercase
            'transaction_date' => $data['transaction_date'] ?? now(),
            'effective_date' => $data['effective_date'] ?? now(),
            'settlement_date' => $data['settlement_date'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'notes' => $data['notes'] ?? null,
            'external_reference' => $data['external_reference'] ?? null,
            'bank_reference' => $data['bank_reference'] ?? null,
            'receipt_number' => $this->generateReceiptNumber(),
            'user_id' => $data['user_id'],
            'counterparty_id' => $data['counterparty_id'] ?? null,
            'counterparty_name' => $data['counterparty_name'] ?? null,
            'counterparty_account' => $data['counterparty_account'] ?? null,
            'created_by' => $data['user_id'],
        ]);
    }

    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber(): string
    {
        return 'RCPT-' . date('Ymd') . '-' . strtoupper(Str::random(8));
    }
}