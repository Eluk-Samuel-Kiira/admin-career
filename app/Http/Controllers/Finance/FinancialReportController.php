<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ PaymentMethod, PaymentTransactionLog, Currency, PaymentSource, PaymentPurpose, Department, User };
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    /**
     * Payment Methods Report with Currency Conversion
     */
    public function paymentMethods(Request $request)
    {
        // Get base currency from request or default to USD
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        $paymentMethods = PaymentMethod::with('currency')
            ->orderBy('name')
            ->get();
        
        $methodsData = [];
        $chartData = [];
        $totalBalanceConverted = 0;
        
        foreach ($paymentMethods as $method) {
            $currency = $method->currency;
            
            // IMPORTANT: Get balance in actual display amount (not cents)
            // The current_balance is stored in cents/base units, so we need to convert to display amount
            $balanceInDisplay = $currency->fromCents($method->current_balance);
            
            // Convert balance to target base currency
            // Step 1: Convert to USD using the currency's exchange rate
            $balanceInUSD = $balanceInDisplay / $currency->exchange_rate_to_usd;
            // Step 2: Convert from USD to target currency using target's exchange rate
            $balanceInTarget = $balanceInUSD * $baseCurrency->exchange_rate_to_usd;
            
            // Format the converted balance
            if ($baseCurrency->decimal_places == 0) {
                $balanceConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($balanceInTarget, 0);
            } else {
                $balanceConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($balanceInTarget, 2);
            }
            
            // For native balance display, use the model's formatted accessor
            $nativeBalanceFormatted = $method->formatted_current_balance;
            
            $methodsData[] = [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type,
                'type_label' => $this->getTypeLabel($method->type),
                'code' => $method->code,
                'provider' => $method->provider,
                'account_number' => $method->account_number,
                'phone_number' => $method->phone_number,
                'wallet_email' => $method->wallet_email,
                'cash_location' => $method->cash_location,
                'balance_raw_cents' => $method->current_balance,
                'balance_display' => $balanceInDisplay,
                'balance_formatted' => $nativeBalanceFormatted,
                'balance_converted' => $balanceConvertedFormatted,
                'balance_converted_raw' => $balanceInTarget,
                'currency_code' => $currency->code,
                'currency_symbol' => $currency->symbol,
                'is_active' => $method->is_active,
                'is_default' => $method->is_default,
                'last_transaction_at' => $method->last_transaction_at,
            ];
            
            $totalBalanceConverted += $balanceInTarget;
            
            // Chart data
            $chartData[] = [
                'name' => $method->name,
                'balance' => round($balanceInTarget, 2),
            ];
        }
        
        // Format total balance
        if ($baseCurrency->decimal_places == 0) {
            $totalBalanceFormatted = $baseCurrency->symbol . ' ' . number_format($totalBalanceConverted, 0);
        } else {
            $totalBalanceFormatted = $baseCurrency->symbol . ' ' . number_format($totalBalanceConverted, 2);
        }
        
        $stats = [
            'total_payment_methods' => $paymentMethods->count(),
            'total_balance_formatted' => $totalBalanceFormatted,
            'total_balance_raw' => $totalBalanceConverted,
            'active_methods' => $paymentMethods->where('is_active', true)->count(),
            'inactive_methods' => $paymentMethods->where('is_active', false)->count(),
        ];
        
        // Get all currencies for dropdown
        $currencies = Currency::where('is_active', true)->get();
        
        return view('finance.reports.payment-methods', compact('methodsData', 'stats', 'currencies', 'baseCurrencyCode', 'chartData', 'baseCurrency'));
    }
    
    private function getTypeLabel($type)
    {
        $labels = [
            'cash' => 'Cash',
            'bank' => 'Bank',
            'card' => 'Card',
            'mobile_money' => 'Mobile Money',
            'e_wallet' => 'E-Wallet',
            'crypto' => 'Crypto',
            'cheque' => 'Cheque'
        ];
        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Account Balances Report
     */
    public function accountBalances(Request $request)
    {
        // Get base currency from request or default to USD
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        $accounts = PaymentMethod::with('currency')
            ->orderBy('name')
            ->get();
        
        $accountsData = [];
        $chartData = [];
        $totalCurrentConverted = 0;
        $totalAvailableConverted = 0;
        $totalPendingConverted = 0;
        
        foreach ($accounts as $account) {
            $currency = $account->currency;
            
            // Convert balances from cents/base units to display amount
            $currentBalanceDisplay = $currency->fromCents($account->current_balance);
            $availableBalanceDisplay = $currency->fromCents($account->available_balance);
            $pendingBalanceDisplay = $currency->fromCents($account->pending_balance);
            
            // Convert to target base currency
            $currentInUSD = $currentBalanceDisplay / $currency->exchange_rate_to_usd;
            $availableInUSD = $availableBalanceDisplay / $currency->exchange_rate_to_usd;
            $pendingInUSD = $pendingBalanceDisplay / $currency->exchange_rate_to_usd;
            
            $currentInTarget = $currentInUSD * $baseCurrency->exchange_rate_to_usd;
            $availableInTarget = $availableInUSD * $baseCurrency->exchange_rate_to_usd;
            $pendingInTarget = $pendingInUSD * $baseCurrency->exchange_rate_to_usd;
            
            // Format converted balances
            if ($baseCurrency->decimal_places == 0) {
                $currentConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($currentInTarget, 0);
                $availableConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($availableInTarget, 0);
                $pendingConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($pendingInTarget, 0);
            } else {
                $currentConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($currentInTarget, 2);
                $availableConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($availableInTarget, 2);
                $pendingConvertedFormatted = $baseCurrency->symbol . ' ' . number_format($pendingInTarget, 2);
            }
            
            $accountsData[] = [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'type_label' => $this->getTypeLabel($account->type),
                'code' => $account->code,
                'provider' => $account->provider,
                'account_number' => $account->account_number,
                'current_balance_raw' => $account->current_balance,
                'current_balance_native' => $account->formatted_current_balance,
                'current_balance_converted' => $currentConvertedFormatted,
                'current_balance_converted_raw' => $currentInTarget,
                'available_balance_native' => $currency->formatAmount($account->available_balance),
                'available_balance_converted' => $availableConvertedFormatted,
                'pending_balance_native' => $currency->formatAmount($account->pending_balance),
                'pending_balance_converted' => $pendingConvertedFormatted,
                'currency_code' => $currency->code,
                'currency_symbol' => $currency->symbol,
                'is_active' => $account->is_active,
                'last_transaction_at' => $account->last_transaction_at,
            ];
            
            $totalCurrentConverted += $currentInTarget;
            $totalAvailableConverted += $availableInTarget;
            $totalPendingConverted += $pendingInTarget;
            
            // Chart data
            $chartData[] = [
                'name' => $account->name,
                'current_balance' => round($currentInTarget, 2),
                'available_balance' => round($availableInTarget, 2),
            ];
        }
        
        // Format totals
        if ($baseCurrency->decimal_places == 0) {
            $totalCurrentFormatted = $baseCurrency->symbol . ' ' . number_format($totalCurrentConverted, 0);
            $totalAvailableFormatted = $baseCurrency->symbol . ' ' . number_format($totalAvailableConverted, 0);
            $totalPendingFormatted = $baseCurrency->symbol . ' ' . number_format($totalPendingConverted, 0);
        } else {
            $totalCurrentFormatted = $baseCurrency->symbol . ' ' . number_format($totalCurrentConverted, 2);
            $totalAvailableFormatted = $baseCurrency->symbol . ' ' . number_format($totalAvailableConverted, 2);
            $totalPendingFormatted = $baseCurrency->symbol . ' ' . number_format($totalPendingConverted, 2);
        }
        
        $summary = [
            'accounts_count' => $accounts->count(),
            'total_current' => $totalCurrentFormatted,
            'total_available' => $totalAvailableFormatted,
            'total_pending' => $totalPendingFormatted,
            'active_accounts' => $accounts->where('is_active', true)->count(),
            'inactive_accounts' => $accounts->where('is_active', false)->count(),
        ];
        
        // Recent transactions with depositor and department information
        $recentTransactions = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transaction) use ($baseCurrency) {
                $currency = $transaction->currency;
                $amountDisplay = $currency->fromCents($transaction->net_amount);
                $amountInUSD = $amountDisplay / $currency->exchange_rate_to_usd;
                $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
                
                if ($baseCurrency->decimal_places == 0) {
                    $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amountInTarget, 0);
                } else {
                    $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amountInTarget, 2);
                }
                
                // Get status badge
                $statusBadge = $this->getStatusBadge($transaction->status);
                
                return [
                    'id' => $transaction->id,
                    'transaction_ref' => $transaction->transaction_ref,
                    'type' => $transaction->transaction_type,
                    'type_label' => ucfirst($transaction->transaction_type),
                    'amount_formatted' => $amountFormatted,
                    'payment_method' => $transaction->paymentMethod->name ?? 'N/A',
                    'department_name' => $transaction->department?->name ?? 'N/A',
                    'depositor_name' => $transaction->depositor?->name ?? ($transaction->user?->name ?? 'N/A'),
                    'status' => $transaction->status,
                    'status_badge' => $statusBadge,  // Added this line
                    'date' => $transaction->transaction_date->format('M d, Y H:i'),
                ];
            });
        
        $currencies = Currency::where('is_active', true)->get();
        
        return view('finance.reports.account-balances', compact('accountsData', 'summary', 'currencies', 'baseCurrencyCode', 'chartData', 'recentTransactions'));
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'processing' => '<span class="badge badge-light-info">Processing</span>',
            'completed' => '<span class="badge badge-light-success">Completed</span>',
            'failed' => '<span class="badge badge-light-danger">Failed</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>',
            'refunded' => '<span class="badge badge-light-info">Refunded</span>'
        ];
        return $badges[$status] ?? '<span class="badge badge-light-secondary">' . $status . '</span>';
    }
    
    /**
     * Transaction Ledger Report
     */
    public function transactionLedger(Request $request)
    {
        // Get base currency from request or default to USD
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        // Filters
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $transactionType = $request->get('transaction_type');
        $paymentMethodId = $request->get('payment_method_id');
        $status = $request->get('status');
        $sourceId = $request->get('source_id');
        $purposeId = $request->get('purpose_id');
        $departmentId = $request->get('department_id');      // NEW
        $depositorId = $request->get('depositor_id');        // NEW
        $search = $request->get('search');
        
        $query = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor']);
        
        // Date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        
        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }
        
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        // Department filter - NEW
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        // Depositor filter - NEW
        if ($depositorId) {
            $query->where('depositor_id', $depositorId);
        }
        
        // Source filter
        if ($sourceId) {
            $query->where(function($q) use ($sourceId) {
                $q->where('reference_table', 'deposits')
                ->whereIn('reference_id', function($sub) use ($sourceId) {
                    $sub->select('id')->from('deposits')->where('source_id', $sourceId);
                });
            });
        }
        
        // Purpose filter
        if ($purposeId) {
            $query->where(function($q) use ($purposeId) {
                $q->where('reference_table', 'deposits')
                ->whereIn('reference_id', function($sub) use ($purposeId) {
                    $sub->select('id')->from('deposits')->where('purpose_id', $purposeId);
                });
            });
        }
        
        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('transaction_ref', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('counterparty_name', 'like', '%' . $search . '%')
                ->orWhere('receipt_number', 'like', '%' . $search . '%')
                ->orWhere('external_reference', 'like', '%' . $search . '%');
            });
        }
        
        // Get summary statistics BEFORE pagination
        $summaryQuery = clone $query;
        $totalTransactions = $summaryQuery->count();
        $totalAmountRaw = $summaryQuery->sum('net_amount');
        
        // Calculate average transaction
        $averageTransactionRaw = $totalTransactions > 0 ? $totalAmountRaw / $totalTransactions : 0;
        
        // Get deposit total (income)
        $depositTotalRaw = (clone $query)->whereIn('transaction_type', ['deposit', 'refund'])->sum('net_amount');
        
        // Get withdrawal total (expense)
        $withdrawalTotalRaw = (clone $query)->whereIn('transaction_type', ['withdrawal', 'fee'])->sum('net_amount');
        
        // Convert amounts from cents to display amounts based on each transaction's currency
        $totalAmountConverted = 0;
        $depositTotalConverted = 0;
        $withdrawalTotalConverted = 0;
        $averageTransactionConverted = 0;
        
        // Get all transactions for accurate conversion
        $allTransactions = (clone $query)->get();
        
        foreach ($allTransactions as $transaction) {
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            $totalAmountConverted += $amountInTarget;
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $depositTotalConverted += $amountInTarget;
            } else {
                $withdrawalTotalConverted += $amountInTarget;
            }
        }
        
        $averageTransactionConverted = $totalTransactions > 0 ? $totalAmountConverted / $totalTransactions : 0;
        
        // Format totals based on base currency decimal places
        if ($baseCurrency->decimal_places == 0) {
            $totalAmountFormatted = $baseCurrency->symbol . ' ' . number_format($totalAmountConverted, 0);
            $averageTransactionFormatted = $baseCurrency->symbol . ' ' . number_format($averageTransactionConverted, 0);
            $depositTotalFormatted = $baseCurrency->symbol . ' ' . number_format($depositTotalConverted, 0);
            $withdrawalTotalFormatted = $baseCurrency->symbol . ' ' . number_format($withdrawalTotalConverted, 0);
        } else {
            $totalAmountFormatted = $baseCurrency->symbol . ' ' . number_format($totalAmountConverted, 2);
            $averageTransactionFormatted = $baseCurrency->symbol . ' ' . number_format($averageTransactionConverted, 2);
            $depositTotalFormatted = $baseCurrency->symbol . ' ' . number_format($depositTotalConverted, 2);
            $withdrawalTotalFormatted = $baseCurrency->symbol . ' ' . number_format($withdrawalTotalConverted, 2);
        }
        
        // Get transactions for pagination
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate(20);
        
        // Transform transactions with currency conversion
        $transactionsData = collect($transactions->items())->map(function($transaction) use ($baseCurrency) {
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amountInTarget, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amountInTarget, 2);
            }
            
            return [
                'id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'type' => $transaction->transaction_type,
                'type_label' => ucfirst($transaction->transaction_type),
                'category' => $transaction->transaction_category,
                'amount_raw' => $transaction->net_amount,
                'amount_formatted' => $amountFormatted,
                'amount_display' => $currency->formatAmount($transaction->net_amount),
                'payment_method_id' => $transaction->payment_method_id,
                'payment_method_name' => $transaction->paymentMethod->name ?? 'N/A',
                'department_id' => $transaction->department_id,
                'department_name' => $transaction->department?->name,      // NEW
                'depositor_id' => $transaction->depositor_id,
                'depositor_name' => $transaction->depositor?->name,        // NEW
                'status' => $transaction->status,
                'date' => $transaction->transaction_date->format('M d, Y H:i:s'),
                'description' => $transaction->description ?? 'N/A',
                'counterparty' => $transaction->counterparty_name ?? 'N/A',
                'reference' => $transaction->external_reference ?? $transaction->bank_reference ?? 'N/A',
                'receipt_number' => $transaction->receipt_number ?? 'N/A',
                'balance_before' => $transaction->balance_before,
                'balance_after' => $transaction->balance_after,
                'fee' => $transaction->transaction_fee,
            ];
        });
        
        // Summary statistics for cards
        $summary = [
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmountFormatted,
            'average_transaction' => $averageTransactionFormatted,
            'total_deposits' => $depositTotalFormatted,
            'total_withdrawals' => $withdrawalTotalFormatted,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_range' => (new \DateTime($startDate))->diff(new \DateTime($endDate))->days + 1,
        ];
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $transactionTypes = PaymentTransactionLog::distinct('transaction_type')->pluck('transaction_type');
        $statuses = ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'];
        $sources = PaymentSource::where('is_active', true)->orderBy('name')->get();
        $purposes = PaymentPurpose::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();  // NEW
        $users = User::orderBy('name')->get();  // NEW - for depositors
        $currencies = Currency::where('is_active', true)->get();
        
        return view('finance.reports.transaction-ledger', compact(
            'transactions', 'transactionsData', 'paymentMethods', 'transactionTypes',
            'statuses', 'sources', 'purposes', 'departments', 'users', 'currencies', 'baseCurrencyCode',
            'startDate', 'endDate', 'transactionType', 'paymentMethodId', 'status',
            'sourceId', 'purposeId', 'departmentId', 'depositorId', 'search', 'summary'
        ));
    }
            
    /**
     * Get Transaction Details for Modal
     */
    public function getTransactionDetails($id)
    {
        $transaction = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->findOrFail($id);
        
        $currency = $transaction->currency;
        
        return response()->json([
            'success' => true,
            'id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'type' => ucfirst($transaction->transaction_type),
            'transaction_type' => $transaction->transaction_type,
            'amount_formatted' => $currency->formatAmount($transaction->net_amount),
            'amount_raw' => $transaction->net_amount,
            'fee_formatted' => $currency->formatAmount($transaction->transaction_fee),
            'fee_raw' => $transaction->transaction_fee,
            'balance_before' => $currency->formatAmount($transaction->balance_before),
            'balance_before_raw' => $transaction->balance_before,
            'balance_after' => $currency->formatAmount($transaction->balance_after),
            'balance_after_raw' => $transaction->balance_after,
            'payment_method_id' => $transaction->payment_method_id,
            'payment_method' => $transaction->paymentMethod->name ?? 'N/A',
            'payment_method_type' => $transaction->paymentMethod->type ?? 'N/A',
            // Department information - NEW
            'department_id' => $transaction->department_id,
            'department_name' => $transaction->department?->name,
            'department_code' => $transaction->department?->code,
            // Depositor information - NEW
            'depositor_id' => $transaction->depositor_id,
            'depositor_name' => $transaction->depositor?->name,
            'depositor_email' => $transaction->depositor?->email,
            'depositor_phone' => $transaction->depositor?->phone,
            // User information (for backward compatibility)
            'user_id' => $transaction->user_id,
            'user_name' => $transaction->user?->name,
            'status' => $transaction->status,
            'date' => $transaction->transaction_date->format('F d, Y H:i:s'),
            'date_raw' => $transaction->transaction_date,
            'effective_date' => $transaction->effective_date?->format('F d, Y H:i:s'),
            'settlement_date' => $transaction->settlement_date?->format('F d, Y H:i:s'),
            'description' => $transaction->description ?? 'N/A',
            'counterparty_id' => $transaction->counterparty_id,
            'counterparty' => $transaction->counterparty_name ?? 'N/A',
            'counterparty_account' => $transaction->counterparty_account ?? 'N/A',
            'reference' => $transaction->external_reference ?? $transaction->bank_reference ?? 'N/A',
            'receipt_number' => $transaction->receipt_number ?? 'N/A',
            'transaction_category' => $transaction->transaction_category ?? 'N/A',
            'reference_table' => $transaction->reference_table,
            'reference_id' => $transaction->reference_id,
            'currency_code' => $currency->code,
            'currency_symbol' => $currency->symbol,
            'metadata' => $transaction->metadata,
            'notes' => $transaction->notes,
            'created_at' => $transaction->created_at?->format('F d, Y H:i:s'),
            'created_by_name' => $transaction->creator?->name,
        ]);
    }


    /**
     * Income Statement Report
     */
    public function incomeStatement(Request $request)
    {
        // Get base currency
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        // Period filters
        $period = $request->get('period', 'month');
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $quarter = $request->get('quarter', ceil(date('m') / 3));
        
        // Calculate date range based on period
        if ($period === 'month') {
            $startDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        } elseif ($period === 'quarter') {
            $startMonth = ($quarter - 1) * 3 + 1;
            $startDate = $year . '-' . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01';
            $endDate = date('Y-m-t', strtotime($year . '-' . ($startMonth + 2) . '-01'));
        } else {
            $startDate = $year . '-01-01';
            $endDate = $year . '-12-31';
        }
        
        // Additional filters
        $paymentMethodId = $request->get('payment_method_id');
        $sourceId = $request->get('source_id');
        $purposeId = $request->get('purpose_id');
        $departmentId = $request->get('department_id');      // NEW
        $depositorId = $request->get('depositor_id');        // NEW
        
        // Build revenue query
        $revenueQuery = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('transaction_type', ['deposit', 'refund'])
            ->where('status', 'completed');
        
        // Build expense query
        $expenseQuery = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('transaction_type', ['withdrawal', 'fee'])
            ->where('status', 'completed');
        
        // Apply additional filters
        if ($paymentMethodId) {
            $revenueQuery->where('payment_method_id', $paymentMethodId);
            $expenseQuery->where('payment_method_id', $paymentMethodId);
        }
        
        // Department filter - NEW
        if ($departmentId) {
            $revenueQuery->where('department_id', $departmentId);
            $expenseQuery->where('department_id', $departmentId);
        }
        
        // Depositor filter - NEW
        if ($depositorId) {
            $revenueQuery->where('depositor_id', $depositorId);
            $expenseQuery->where('depositor_id', $depositorId);
        }
        
        if ($sourceId) {
            $revenueQuery->whereHas('deposit', function($q) use ($sourceId) {
                $q->where('source_id', $sourceId);
            });
        }
        
        if ($purposeId) {
            $revenueQuery->whereHas('deposit', function($q) use ($purposeId) {
                $q->where('purpose_id', $purposeId);
            });
        }
        
        // Get revenue transactions
        $revenueTransactions = $revenueQuery->get();
        $expenseTransactions = $expenseQuery->get();
        
        // Calculate totals with currency conversion
        $totalRevenue = 0;
        $totalExpenses = 0;
        
        // Revenue by source (from deposits)
        $revenueBySource = [];
        // Revenue by purpose
        $revenueByPurpose = [];
        // Revenue by department - NEW
        $revenueByDepartment = [];
        // Revenue by depositor - NEW
        $revenueByDepositor = [];
        // Expenses by category
        $expensesByCategory = [];
        // Expenses by department - NEW
        $expensesByDepartment = [];
        
        foreach ($revenueTransactions as $transaction) {
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            $totalRevenue += $amountInTarget;
            
            // Group by department - NEW
            if ($transaction->department_id && $transaction->department) {
                $deptName = $transaction->department->name;
                if (!isset($revenueByDepartment[$deptName])) {
                    $revenueByDepartment[$deptName] = 0;
                }
                $revenueByDepartment[$deptName] += $amountInTarget;
            }
            
            // Group by depositor - NEW
            if ($transaction->depositor_id && $transaction->depositor) {
                $depositorName = $transaction->depositor->name;
                if (!isset($revenueByDepositor[$depositorName])) {
                    $revenueByDepositor[$depositorName] = 0;
                }
                $revenueByDepositor[$depositorName] += $amountInTarget;
            }
            
            // Group by source (from deposit reference)
            if ($transaction->reference_table === 'deposits' && $transaction->reference_id) {
                $deposit = \App\Models\Deposit::with('source', 'purpose')->find($transaction->reference_id);
                if ($deposit) {
                    $sourceName = $deposit->source ? $deposit->source->name : 'Other';
                    if (!isset($revenueBySource[$sourceName])) {
                        $revenueBySource[$sourceName] = 0;
                    }
                    $revenueBySource[$sourceName] += $amountInTarget;
                    
                    $purposeName = $deposit->purpose ? $deposit->purpose->name : 'Other';
                    if (!isset($revenueByPurpose[$purposeName])) {
                        $revenueByPurpose[$purposeName] = 0;
                    }
                    $revenueByPurpose[$purposeName] += $amountInTarget;
                }
            }
        }
        
        foreach ($expenseTransactions as $transaction) {
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            $totalExpenses += $amountInTarget;
            
            // Group by department for expenses - NEW
            if ($transaction->department_id && $transaction->department) {
                $deptName = $transaction->department->name;
                if (!isset($expensesByDepartment[$deptName])) {
                    $expensesByDepartment[$deptName] = 0;
                }
                $expensesByDepartment[$deptName] += $amountInTarget;
            }
            
            // Group expenses by category
            $category = $transaction->transaction_category ?? 'Other';
            if (!isset($expensesByCategory[$category])) {
                $expensesByCategory[$category] = 0;
            }
            $expensesByCategory[$category] += $amountInTarget;
        }
        
        $netIncome = $totalRevenue - $totalExpenses;
        
        // Format amounts
        if ($baseCurrency->decimal_places == 0) {
            $totalRevenueFormatted = $baseCurrency->symbol . ' ' . number_format($totalRevenue, 0);
            $totalExpensesFormatted = $baseCurrency->symbol . ' ' . number_format($totalExpenses, 0);
            $netIncomeFormatted = $baseCurrency->symbol . ' ' . number_format($netIncome, 0);
        } else {
            $totalRevenueFormatted = $baseCurrency->symbol . ' ' . number_format($totalRevenue, 2);
            $totalExpensesFormatted = $baseCurrency->symbol . ' ' . number_format($totalExpenses, 2);
            $netIncomeFormatted = $baseCurrency->symbol . ' ' . number_format($netIncome, 2);
        }
        
        // Prepare revenue breakdown for table
        $revenueBreakdown = [];
        foreach ($revenueBySource as $source => $amount) {
            $percentage = $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0;
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 2);
            }
            $revenueBreakdown[] = [
                'name' => $source,
                'amount' => $amount,
                'amount_formatted' => $amountFormatted,
                'percentage' => $percentage,
            ];
        }
        
        // Revenue by department breakdown - NEW
        $revenueByDepartmentBreakdown = [];
        foreach ($revenueByDepartment as $dept => $amount) {
            $percentage = $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0;
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 2);
            }
            $revenueByDepartmentBreakdown[] = [
                'name' => $dept,
                'amount' => $amount,
                'amount_formatted' => $amountFormatted,
                'percentage' => $percentage,
            ];
        }
        
        // Revenue by depositor breakdown - NEW
        $revenueByDepositorBreakdown = [];
        foreach ($revenueByDepositor as $depositor => $amount) {
            $percentage = $totalRevenue > 0 ? ($amount / $totalRevenue) * 100 : 0;
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 2);
            }
            $revenueByDepositorBreakdown[] = [
                'name' => $depositor,
                'amount' => $amount,
                'amount_formatted' => $amountFormatted,
                'percentage' => $percentage,
            ];
        }
        
        // Prepare expense breakdown for table
        $expenseBreakdown = [];
        foreach ($expensesByCategory as $category => $amount) {
            $percentage = $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0;
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 2);
            }
            $expenseBreakdown[] = [
                'name' => ucfirst($category),
                'amount' => $amount,
                'amount_formatted' => $amountFormatted,
                'percentage' => $percentage,
            ];
        }
        
        // Expenses by department breakdown - NEW
        $expensesByDepartmentBreakdown = [];
        foreach ($expensesByDepartment as $dept => $amount) {
            $percentage = $totalExpenses > 0 ? ($amount / $totalExpenses) * 100 : 0;
            if ($baseCurrency->decimal_places == 0) {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 0);
            } else {
                $amountFormatted = $baseCurrency->symbol . ' ' . number_format($amount, 2);
            }
            $expensesByDepartmentBreakdown[] = [
                'name' => $dept,
                'amount' => $amount,
                'amount_formatted' => $amountFormatted,
                'percentage' => $percentage,
            ];
        }
        
        // Sort by amount descending
        usort($revenueBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($revenueByDepartmentBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($revenueByDepositorBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($expenseBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($expensesByDepartmentBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        // Chart data
        $revenueChartData = array_map(function($item) {
            return [
                'name' => $item['name'],
                'value' => round($item['amount'], 2),
            ];
        }, $revenueBreakdown);
        
        $expenseChartData = array_map(function($item) {
            return [
                'name' => $item['name'],
                'value' => round($item['amount'], 2),
            ];
        }, $expenseBreakdown);
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $sources = PaymentSource::where('is_active', true)->orderBy('name')->get();
        $purposes = PaymentPurpose::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();  // NEW
        $users = User::orderBy('name')->get();  // NEW - for depositors
        $currencies = Currency::where('is_active', true)->get();
        
        $years = range(date('Y') - 2, date('Y'));
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $quarters = [
            1 => 'Q1 (Jan - Mar)',
            2 => 'Q2 (Apr - Jun)',
            3 => 'Q3 (Jul - Sep)',
            4 => 'Q4 (Oct - Dec)'
        ];
        
        $summary = [
            'total_revenue' => $totalRevenueFormatted,
            'total_expenses' => $totalExpensesFormatted,
            'net_income' => $netIncomeFormatted,
            'net_income_color' => $netIncome >= 0 ? 'success' : 'danger',
            'net_income_sign' => $netIncome >= 0 ? '+' : '',
            'start_date' => date('M d, Y', strtotime($startDate)),
            'end_date' => date('M d, Y', strtotime($endDate)),
            'days_range' => (new \DateTime($startDate))->diff(new \DateTime($endDate))->days + 1,
        ];
        
        return view('finance.reports.income-statement', compact(
            'summary', 'revenueBreakdown', 'expenseBreakdown', 'revenueChartData', 'expenseChartData',
            'revenueByDepartmentBreakdown', 'revenueByDepositorBreakdown', 'expensesByDepartmentBreakdown', // NEW
            'paymentMethods', 'sources', 'purposes', 'departments', 'users', 'currencies', 'baseCurrencyCode', // NEW
            'period', 'year', 'month', 'quarter', 'years', 'months', 'quarters',
            'paymentMethodId', 'sourceId', 'purposeId', 'departmentId', 'depositorId', // NEW
            'totalRevenue', 'totalExpenses', 'netIncome'
        ));
    }



    /**
     * Cash Flow Report
     */
    public function cashFlow(Request $request)
    {
        // Get base currency
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        // Date range filters
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $paymentMethodId = $request->get('payment_method_id');
        $transactionType = $request->get('transaction_type');
        $departmentId = $request->get('department_id');      // NEW
        $depositorId = $request->get('depositor_id');        // NEW
        
        // Build base query
        $query = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed');
        
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }
        
        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }
        
        // Department filter - NEW
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        // Depositor filter - NEW
        if ($depositorId) {
            $query->where('depositor_id', $depositorId);
        }
        
        // Get all transactions for accurate conversion
        $allTransactions = (clone $query)->get();
        
        // Process daily cash flow
        $dailyData = [];
        $totalCashIn = 0;
        $totalCashOut = 0;
        $runningBalance = 0;
        $maxCashInDay = ['amount' => 0, 'date' => null];
        $maxCashOutDay = ['amount' => 0, 'date' => null];
        
        foreach ($allTransactions as $transaction) {
            $date = $transaction->transaction_date->format('Y-m-d');
            $currency = $transaction->currency;
            
            // Get the display amount (already converted from cents for USD)
            $displayAmount = $currency->fromCents($transaction->net_amount);
            
            // Convert to USD first
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            
            // Convert to target base currency
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'count' => 0,
                ];
            }
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $dailyData[$date]['cash_in'] += $amountInTarget;
                $totalCashIn += $amountInTarget;
            } else {
                $dailyData[$date]['cash_out'] += $amountInTarget;
                $totalCashOut += $amountInTarget;
            }
            $dailyData[$date]['count']++;
        }
        
        // After processing all transactions, find max cash in/out days
        foreach ($dailyData as $date => $data) {
            if ($data['cash_in'] > $maxCashInDay['amount']) {
                $maxCashInDay['amount'] = $data['cash_in'];
                $maxCashInDay['date'] = $date;
            }
            if ($data['cash_out'] > $maxCashOutDay['amount']) {
                $maxCashOutDay['amount'] = $data['cash_out'];
                $maxCashOutDay['date'] = $date;
            }
        }
        
        // Sort by date
        ksort($dailyData);
        
        // Build daily cash flow array
        $dailyCashFlow = [];
        $previousNetFlow = 0;
        
        foreach ($dailyData as $date => $data) {
            $cashIn = $data['cash_in'];
            $cashOut = $data['cash_out'];
            $netFlow = $cashIn - $cashOut;
            $runningBalance += $netFlow;
            
            // Calculate trend
            $trend = 0;
            if ($previousNetFlow != 0) {
                $trend = (($netFlow - $previousNetFlow) / abs($previousNetFlow)) * 100;
            }
            
            $dailyCashFlow[] = [
                'date' => $date,
                'date_formatted' => date('M d, Y', strtotime($date)),
                'day_name' => date('D', strtotime($date)),
                'cash_in' => $cashIn,
                'cash_in_formatted' => $this->formatAmount($cashIn, $baseCurrency),
                'cash_out' => $cashOut,
                'cash_out_formatted' => $this->formatAmount($cashOut, $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'trend' => round($trend, 1),
                'trend_color' => $trend >= 0 ? 'success' : 'danger',
                'trend_icon' => $trend >= 0 ? 'arrow-up' : 'arrow-down',
                'transaction_count' => $data['count'],
                'running_balance' => $runningBalance,
                'running_balance_formatted' => $this->formatAmount($runningBalance, $baseCurrency),
            ];
            
            $previousNetFlow = $netFlow;
        }
        
        // Process cash flow by payment method
        $methodData = [];
        foreach ($allTransactions as $transaction) {
            $methodId = $transaction->payment_method_id;
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            if (!isset($methodData[$methodId])) {
                $methodData[$methodId] = [
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'count' => 0,
                ];
            }
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $methodData[$methodId]['cash_in'] += $amountInTarget;
            } else {
                $methodData[$methodId]['cash_out'] += $amountInTarget;
            }
            $methodData[$methodId]['count']++;
        }
        
        // Build cash flow by method array
        $cashFlowByMethod = [];
        foreach ($methodData as $methodId => $data) {
            $paymentMethod = PaymentMethod::find($methodId);
            if (!$paymentMethod) continue;
            
            $cashIn = $data['cash_in'];
            $cashOut = $data['cash_out'];
            $netFlow = $cashIn - $cashOut;
            $totalTransactions = $data['count'];
            
            // Calculate in/out ratio
            $inOutRatio = 0;
            if ($cashOut > 0) {
                $inOutRatio = ($cashIn / $cashOut) * 100;
            } elseif ($cashIn > 0) {
                $inOutRatio = 100;
            }
            
            // Calculate average transaction
            $avgTransaction = $totalTransactions > 0 ? ($cashIn + $cashOut) / $totalTransactions : 0;
            
            $cashFlowByMethod[] = [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type,
                'type_label' => ucfirst(str_replace('_', ' ', $paymentMethod->type)),
                'cash_in' => $cashIn,
                'cash_in_formatted' => $this->formatAmount($cashIn, $baseCurrency),
                'cash_out' => $cashOut,
                'cash_out_formatted' => $this->formatAmount($cashOut, $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'transaction_count' => $totalTransactions,
                'in_out_ratio' => round($inOutRatio, 1),
                'avg_transaction' => $this->formatAmount($avgTransaction, $baseCurrency),
            ];
        }
        
        // Sort by net flow descending
        usort($cashFlowByMethod, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Process cash flow by department - NEW
        $departmentData = [];
        foreach ($allTransactions as $transaction) {
            if (!$transaction->department_id) continue;
            
            $deptId = $transaction->department_id;
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            if (!isset($departmentData[$deptId])) {
                $departmentData[$deptId] = [
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'count' => 0,
                ];
            }
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $departmentData[$deptId]['cash_in'] += $amountInTarget;
            } else {
                $departmentData[$deptId]['cash_out'] += $amountInTarget;
            }
            $departmentData[$deptId]['count']++;
        }
        
        // Build cash flow by department array - NEW
        $cashFlowByDepartment = [];
        foreach ($departmentData as $deptId => $data) {
            $department = Department::find($deptId);
            if (!$department) continue;
            
            $cashIn = $data['cash_in'];
            $cashOut = $data['cash_out'];
            $netFlow = $cashIn - $cashOut;
            
            $cashFlowByDepartment[] = [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'cash_in' => $cashIn,
                'cash_in_formatted' => $this->formatAmount($cashIn, $baseCurrency),
                'cash_out' => $cashOut,
                'cash_out_formatted' => $this->formatAmount($cashOut, $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'transaction_count' => $data['count'],
            ];
        }
        
        usort($cashFlowByDepartment, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Process cash flow by depositor - NEW
        $depositorData = [];
        foreach ($allTransactions as $transaction) {
            if (!$transaction->depositor_id) continue;
            
            $depId = $transaction->depositor_id;
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            if (!isset($depositorData[$depId])) {
                $depositorData[$depId] = [
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'count' => 0,
                ];
            }
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $depositorData[$depId]['cash_in'] += $amountInTarget;
            } else {
                $depositorData[$depId]['cash_out'] += $amountInTarget;
            }
            $depositorData[$depId]['count']++;
        }
        
        // Build cash flow by depositor array - NEW
        $cashFlowByDepositor = [];
        foreach ($depositorData as $depId => $data) {
            $depositor = User::find($depId);
            if (!$depositor) continue;
            
            $cashIn = $data['cash_in'];
            $cashOut = $data['cash_out'];
            $netFlow = $cashIn - $cashOut;
            
            $cashFlowByDepositor[] = [
                'id' => $depositor->id,
                'name' => $depositor->name,
                'email' => $depositor->email,
                'cash_in' => $cashIn,
                'cash_in_formatted' => $this->formatAmount($cashIn, $baseCurrency),
                'cash_out' => $cashOut,
                'cash_out_formatted' => $this->formatAmount($cashOut, $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'transaction_count' => $data['count'],
            ];
        }
        
        usort($cashFlowByDepositor, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Format highest cash in/out days
        $maxCashInFormatted = $maxCashInDay['amount'] > 0 ? $this->formatAmount($maxCashInDay['amount'], $baseCurrency) : 'N/A';
        $maxCashOutFormatted = $maxCashOutDay['amount'] > 0 ? $this->formatAmount($maxCashOutDay['amount'], $baseCurrency) : 'N/A';
        $maxCashInDayFormatted = $maxCashInDay['date'] ? date('M d, Y', strtotime($maxCashInDay['date'])) : 'N/A';
        $maxCashOutDayFormatted = $maxCashOutDay['date'] ? date('M d, Y', strtotime($maxCashOutDay['date'])) : 'N/A';
        
        // Summary
        $netCashFlow = $totalCashIn - $totalCashOut;
        $summary = [
            'total_cash_in' => $this->formatAmount($totalCashIn, $baseCurrency),
            'total_cash_out' => $this->formatAmount($totalCashOut, $baseCurrency),
            'net_cash_flow' => $this->formatAmount($netCashFlow, $baseCurrency),
            'net_cash_flow_color' => $netCashFlow >= 0 ? 'success' : 'danger',
            'total_transactions' => $allTransactions->count(),
            'start_date' => date('M d, Y', strtotime($startDate)),
            'end_date' => date('M d, Y', strtotime($endDate)),
            'days_range' => (new \DateTime($startDate))->diff(new \DateTime($endDate))->days + 1,
            'average_daily_in' => $this->formatAmount(count($dailyData) > 0 ? $totalCashIn / count($dailyData) : 0, $baseCurrency),
            'average_daily_out' => $this->formatAmount(count($dailyData) > 0 ? $totalCashOut / count($dailyData) : 0, $baseCurrency),
            // Fixed: Added highest cash in/out day information
            'max_cash_in' => $maxCashInFormatted,
            'max_cash_in_day' => $maxCashInDayFormatted,
            'max_cash_out' => $maxCashOutFormatted,
            'max_cash_out_day' => $maxCashOutDayFormatted,
        ];
        
        // Chart data
        $chartData = [
            'dates' => array_column($dailyCashFlow, 'date_formatted'),
            'cash_in' => array_column($dailyCashFlow, 'cash_in'),
            'cash_out' => array_column($dailyCashFlow, 'cash_out'),
            'net_flow' => array_column($dailyCashFlow, 'net_flow'),
        ];
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $transactionTypes = ['deposit', 'withdrawal', 'refund', 'fee'];
        $currencies = Currency::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();  // NEW
        $users = User::orderBy('name')->get();  // NEW - for depositors
        
        return view('finance.reports.cash-flow', compact(
            'dailyCashFlow', 'cashFlowByMethod', 'cashFlowByDepartment', 'cashFlowByDepositor', // NEW
            'summary', 'chartData',
            'paymentMethods', 'transactionTypes', 'currencies', 'departments', 'users', 'baseCurrencyCode', // NEW
            'startDate', 'endDate', 'paymentMethodId', 'transactionType', 'departmentId', 'depositorId' // NEW
        ));
    }

    private function formatAmount($amount, $currency)
    {
        if ($currency->decimal_places == 0) {
            return $currency->symbol . ' ' . number_format($amount, 0);
        }
        return $currency->symbol . ' ' . number_format($amount, 2);
    }


    /**
     * Flexible Financial Report
     */
    public function flexibleReport(Request $request)
    {
        // Get base currency
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        // Date range selection
        $range = $request->get('range', 'this_month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $quarter = $request->get('quarter', ceil(date('m') / 3));
        
        // Department and Depositor filters - NEW
        $departmentId = $request->get('department_id');
        $depositorId = $request->get('depositor_id');
        
        // Calculate date range based on selection
        switch ($range) {
            case 'today':
                $startDate = now()->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $rangeLabel = 'Today - ' . now()->format('M d, Y');
                break;
            case 'yesterday':
                $startDate = now()->subDay()->format('Y-m-d');
                $endDate = now()->subDay()->format('Y-m-d');
                $rangeLabel = 'Yesterday - ' . now()->subDay()->format('M d, Y');
                break;
            case 'last_7_days':
                $startDate = now()->subDays(7)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $rangeLabel = 'Last 7 Days';
                break;
            case 'last_30_days':
                $startDate = now()->subDays(30)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $rangeLabel = 'Last 30 Days';
                break;
            case 'this_week':
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
                $rangeLabel = 'This Week';
                break;
            case 'last_week':
                $startDate = now()->subWeek()->startOfWeek()->format('Y-m-d');
                $endDate = now()->subWeek()->endOfWeek()->format('Y-m-d');
                $rangeLabel = 'Last Week';
                break;
            case 'this_month':
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->endOfMonth()->format('Y-m-d');
                $rangeLabel = 'This Month - ' . now()->format('F Y');
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                $rangeLabel = 'Last Month - ' . now()->subMonth()->format('F Y');
                break;
            case 'this_quarter':
                $currentQuarter = ceil(date('m') / 3);
                $startMonth = ($currentQuarter - 1) * 3 + 1;
                $startDate = date('Y') . '-' . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01';
                $endDate = date('Y-m-t', strtotime($startDate . ' +2 months'));
                $rangeLabel = 'This Quarter - Q' . $currentQuarter . ' ' . date('Y');
                break;
            case 'this_year':
                $startDate = date('Y') . '-01-01';
                $endDate = date('Y') . '-12-31';
                $rangeLabel = 'This Year - ' . date('Y');
                break;
            case 'last_year':
                $startDate = (date('Y') - 1) . '-01-01';
                $endDate = (date('Y') - 1) . '-12-31';
                $rangeLabel = 'Last Year - ' . (date('Y') - 1);
                break;
            case 'custom':
                $rangeLabel = date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
                break;
            default:
                $startDate = now()->startOfMonth()->format('Y-m-d');
                $endDate = now()->format('Y-m-d');
                $rangeLabel = 'This Month';
        }
        
        // Build query
        $query = PaymentTransactionLog::with(['paymentMethod', 'currency', 'department', 'depositor'])
            ->whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'completed');
        
        // Apply department filter - NEW
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        // Apply depositor filter - NEW
        if ($depositorId) {
            $query->where('depositor_id', $depositorId);
        }
        
        // Get all transactions for the period
        $transactions = $query->get();
        
        // Calculate totals with proper currency conversion
        $totalDeposits = 0;
        $totalWithdrawals = 0;
        $totalFees = 0;
        $transactionCount = 0;
        
        // Daily breakdown data
        $dailyData = [];
        // Payment method breakdown
        $methodData = [];
        // Category breakdown
        $categoryData = [];
        // Source breakdown
        $sourceData = [];
        // Department breakdown - NEW
        $departmentData = [];
        // Depositor breakdown - NEW
        $depositorData = [];
        
        foreach ($transactions as $transaction) {
            $currency = $transaction->currency;
            $displayAmount = $currency->fromCents($transaction->net_amount);
            $amountInUSD = $displayAmount / $currency->exchange_rate_to_usd;
            $amountInTarget = $amountInUSD * $baseCurrency->exchange_rate_to_usd;
            
            $date = $transaction->transaction_date->format('Y-m-d');
            $methodId = $transaction->payment_method_id;
            $category = $transaction->transaction_category ?? 'Other';
            $deptId = $transaction->department_id;
            $depId = $transaction->depositor_id;
            
            $transactionCount++;
            
            // Track daily data
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'deposits' => 0,
                    'withdrawals' => 0,
                    'count' => 0,
                ];
            }
            $dailyData[$date]['count']++;
            
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $totalDeposits += $amountInTarget;
                $dailyData[$date]['deposits'] += $amountInTarget;
            } else {
                $totalWithdrawals += $amountInTarget;
                $dailyData[$date]['withdrawals'] += $amountInTarget;
            }
            
            if ($transaction->transaction_type === 'fee') {
                $totalFees += $amountInTarget;
            }
            
            // Track by payment method
            if (!isset($methodData[$methodId])) {
                $methodData[$methodId] = [
                    'deposits' => 0,
                    'withdrawals' => 0,
                    'count' => 0,
                ];
            }
            $methodData[$methodId]['count']++;
            if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                $methodData[$methodId]['deposits'] += $amountInTarget;
            } else {
                $methodData[$methodId]['withdrawals'] += $amountInTarget;
            }
            
            // Track by category
            if (!isset($categoryData[$category])) {
                $categoryData[$category] = 0;
            }
            $categoryData[$category] += $amountInTarget;
            
            // Track by department - NEW
            if ($deptId) {
                if (!isset($departmentData[$deptId])) {
                    $departmentData[$deptId] = [
                        'deposits' => 0,
                        'withdrawals' => 0,
                        'count' => 0,
                    ];
                }
                $departmentData[$deptId]['count']++;
                if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                    $departmentData[$deptId]['deposits'] += $amountInTarget;
                } else {
                    $departmentData[$deptId]['withdrawals'] += $amountInTarget;
                }
            }
            
            // Track by depositor - NEW
            if ($depId) {
                if (!isset($depositorData[$depId])) {
                    $depositorData[$depId] = [
                        'deposits' => 0,
                        'withdrawals' => 0,
                        'count' => 0,
                    ];
                }
                $depositorData[$depId]['count']++;
                if (in_array($transaction->transaction_type, ['deposit', 'refund'])) {
                    $depositorData[$depId]['deposits'] += $amountInTarget;
                } else {
                    $depositorData[$depId]['withdrawals'] += $amountInTarget;
                }
            }
            
            // Track by source (from deposit reference)
            if ($transaction->reference_table === 'deposits' && $transaction->reference_id) {
                $deposit = \App\Models\Deposit::with('source')->find($transaction->reference_id);
                if ($deposit && $deposit->source) {
                    $sourceName = $deposit->source->name;
                    if (!isset($sourceData[$sourceName])) {
                        $sourceData[$sourceName] = 0;
                    }
                    $sourceData[$sourceName] += $amountInTarget;
                }
            }
        }
        
        $netCashFlow = $totalDeposits - $totalWithdrawals;
        
        // Sort daily data by date
        ksort($dailyData);
        
        // Build daily breakdown array
        $dailyBreakdown = [];
        foreach ($dailyData as $date => $data) {
            $dailyBreakdown[] = [
                'date' => $date,
                'date_formatted' => date('M d, Y', strtotime($date)),
                'day_name' => date('D', strtotime($date)),
                'deposits' => $data['deposits'],
                'deposits_formatted' => $this->formatAmount($data['deposits'], $baseCurrency),
                'withdrawals' => $data['withdrawals'],
                'withdrawals_formatted' => $this->formatAmount($data['withdrawals'], $baseCurrency),
                'net_flow' => $data['deposits'] - $data['withdrawals'],
                'net_flow_formatted' => $this->formatAmount($data['deposits'] - $data['withdrawals'], $baseCurrency),
                'net_flow_color' => ($data['deposits'] - $data['withdrawals']) >= 0 ? 'success' : 'danger',
                'count' => $data['count'],
            ];
        }
        
        // Build payment method breakdown
        $methodBreakdown = [];
        foreach ($methodData as $methodId => $data) {
            $paymentMethod = PaymentMethod::find($methodId);
            if (!$paymentMethod) continue;
            
            $netFlow = $data['deposits'] - $data['withdrawals'];
            $methodBreakdown[] = [
                'id' => $methodId,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type,
                'deposits' => $data['deposits'],
                'deposits_formatted' => $this->formatAmount($data['deposits'], $baseCurrency),
                'withdrawals' => $data['withdrawals'],
                'withdrawals_formatted' => $this->formatAmount($data['withdrawals'], $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'count' => $data['count'],
            ];
        }
        
        // Sort by net flow descending
        usort($methodBreakdown, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Build category breakdown
        $categoryBreakdown = [];
        foreach ($categoryData as $category => $amount) {
            $percentage = $totalDeposits > 0 ? ($amount / $totalDeposits) * 100 : 0;
            $categoryBreakdown[] = [
                'name' => ucfirst($category),
                'amount' => $amount,
                'amount_formatted' => $this->formatAmount($amount, $baseCurrency),
                'percentage' => $percentage,
            ];
        }
        usort($categoryBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        // Build source breakdown
        $sourceBreakdown = [];
        foreach ($sourceData as $source => $amount) {
            $percentage = $totalDeposits > 0 ? ($amount / $totalDeposits) * 100 : 0;
            $sourceBreakdown[] = [
                'name' => $source,
                'amount' => $amount,
                'amount_formatted' => $this->formatAmount($amount, $baseCurrency),
                'percentage' => $percentage,
            ];
        }
        usort($sourceBreakdown, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        // Build department breakdown - NEW
        $departmentBreakdown = [];
        foreach ($departmentData as $deptId => $data) {
            $department = Department::find($deptId);
            if (!$department) continue;
            
            $netFlow = $data['deposits'] - $data['withdrawals'];
            $departmentBreakdown[] = [
                'id' => $deptId,
                'name' => $department->name,
                'code' => $department->code,
                'deposits' => $data['deposits'],
                'deposits_formatted' => $this->formatAmount($data['deposits'], $baseCurrency),
                'withdrawals' => $data['withdrawals'],
                'withdrawals_formatted' => $this->formatAmount($data['withdrawals'], $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'count' => $data['count'],
            ];
        }
        usort($departmentBreakdown, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Build depositor breakdown - NEW
        $depositorBreakdown = [];
        foreach ($depositorData as $depId => $data) {
            $depositor = User::find($depId);
            if (!$depositor) continue;
            
            $netFlow = $data['deposits'] - $data['withdrawals'];
            $depositorBreakdown[] = [
                'id' => $depId,
                'name' => $depositor->name,
                'email' => $depositor->email,
                'deposits' => $data['deposits'],
                'deposits_formatted' => $this->formatAmount($data['deposits'], $baseCurrency),
                'withdrawals' => $data['withdrawals'],
                'withdrawals_formatted' => $this->formatAmount($data['withdrawals'], $baseCurrency),
                'net_flow' => $netFlow,
                'net_flow_formatted' => $this->formatAmount($netFlow, $baseCurrency),
                'net_flow_color' => $netFlow >= 0 ? 'success' : 'danger',
                'count' => $data['count'],
            ];
        }
        usort($depositorBreakdown, function($a, $b) {
            return abs($b['net_flow']) <=> abs($a['net_flow']);
        });
        
        // Summary
        $daysInPeriod = (new \DateTime($startDate))->diff(new \DateTime($endDate))->days + 1;
        $summary = [
            'total_transactions' => $transactionCount,
            'total_deposits' => $this->formatAmount($totalDeposits, $baseCurrency),
            'total_deposits_raw' => $totalDeposits,
            'total_withdrawals' => $this->formatAmount($totalWithdrawals, $baseCurrency),
            'total_fees' => $this->formatAmount($totalFees, $baseCurrency),
            'net_cash_flow' => $this->formatAmount($netCashFlow, $baseCurrency),
            'net_cash_flow_color' => $netCashFlow >= 0 ? 'success' : 'danger',
            'days_in_period' => $daysInPeriod,
            'average_daily_deposit' => $this->formatAmount($daysInPeriod > 0 ? $totalDeposits / $daysInPeriod : 0, $baseCurrency),
            'average_daily_withdrawal' => $this->formatAmount($daysInPeriod > 0 ? $totalWithdrawals / $daysInPeriod : 0, $baseCurrency),
            'average_transaction' => $this->formatAmount($transactionCount > 0 ? ($totalDeposits + $totalWithdrawals) / $transactionCount : 0, $baseCurrency),
            'start_date' => date('M d, Y', strtotime($startDate)),
            'end_date' => date('M d, Y', strtotime($endDate)),
            'range_label' => $rangeLabel,
        ];
        
        // Chart data
        $chartData = [
            'dates' => array_column($dailyBreakdown, 'date_formatted'),
            'deposits' => array_column($dailyBreakdown, 'deposits'),
            'withdrawals' => array_column($dailyBreakdown, 'withdrawals'),
            'net_flow' => array_column($dailyBreakdown, 'net_flow'),
        ];
        
        // Get filter options
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();  // NEW
        $users = User::orderBy('name')->get();  // NEW - for depositors
        $years = range(date('Y') - 2, date('Y') + 1);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $quarters = [
            1 => 'Q1 (Jan - Mar)',
            2 => 'Q2 (Apr - Jun)',
            3 => 'Q3 (Jul - Sep)',
            4 => 'Q4 (Oct - Dec)'
        ];
        
        return view('finance.reports.flexible-report', compact(
            'summary', 'dailyBreakdown', 'methodBreakdown', 'categoryBreakdown', 'sourceBreakdown',
            'departmentBreakdown', 'depositorBreakdown',  // NEW
            'chartData', 'paymentMethods', 'currencies', 'departments', 'users', 'baseCurrencyCode',  // NEW
            'range', 'startDate', 'endDate', 'year', 'month', 'quarter',
            'years', 'months', 'quarters', 'departmentId', 'depositorId'  // NEW
        ));
    }


    

}