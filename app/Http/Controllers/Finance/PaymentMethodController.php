<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Services\Payment\PaymentService;

class PaymentMethodController extends Controller
{
    protected $paymentService;

    /**
     * Constructor - inject PaymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display payment methods list.
     */
    public function index()
    {
        return view('finance.payment-methods.index');
    }

    /**
     * Get all payment methods with pagination and search.
     */
    public function getPaymentMethods(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = PaymentMethod::with('currency');
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('provider', 'like', '%' . $search . '%')
                  ->orWhere('account_name', 'like', '%' . $search . '%');
            });
        }
        
        $paymentMethods = $query->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $data = [
            'current_page' => $paymentMethods->currentPage(),
            'data' => collect($paymentMethods->items())->map(function($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'type' => $method->type,
                    'code' => $method->code,
                    'provider' => $method->provider,
                    'account_name' => $method->account_name,
                    'account_number' => $method->account_number,
                    'currency' => $method->currency ? $method->currency->code : null,
                    'currency_symbol' => $method->currency ? $method->currency->symbol : '$',
                    'current_balance' => $method->current_balance,
                    'formatted_balance' => $method->formatted_current_balance,
                    'is_active' => $method->is_active,
                    'is_default' => $method->is_default,
                    'created_at' => $method->created_at->format('M d, Y'),
                ];
            })->toArray(),
            'first_page_url' => $paymentMethods->url(1),
            'from' => $paymentMethods->firstItem(),
            'last_page' => $paymentMethods->lastPage(),
            'last_page_url' => $paymentMethods->url($paymentMethods->lastPage()),
            'next_page_url' => $paymentMethods->nextPageUrl(),
            'prev_page_url' => $paymentMethods->previousPageUrl(),
            'to' => $paymentMethods->lastItem(),
            'total' => $paymentMethods->total(),
            'per_page' => $perPage,
        ];
        
        return response()->json($data);
    }

    /**
     * Get all currencies for dropdown.
     */
    public function getCurrencies()
    {
        $currencies = Currency::where('is_active', true)->get(['id', 'code', 'name', 'symbol']);
        return response()->json($currencies);
    }

    /**
     * Store a new payment method.
     */
    public function storePaymentMethod(Request $request)
    {
        if (!auth()->user()->can('create payment methods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create payment methods.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,card,mobile_money,e_wallet,crypto,cheque',
            'code' => 'required|string|max:50|unique:payment_methods',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        try {
            $currency = Currency::find($request->currency_id);
            
            $data = [
                'name' => $request->name,
                'type' => $request->type,
                'code' => strtoupper($request->code),
                'currency_id' => $request->currency_id,
                'provider' => $request->provider,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'swift_bic' => $request->swift_bic,
                'phone_number' => $request->phone_number,
                'wallet_id' => $request->wallet_id,
                'wallet_email' => $request->wallet_email,
                'card_last_four' => $request->card_last_four,
                'card_type' => $request->card_type,
                'card_expiry_date' => $request->card_expiry_date,
                'transaction_fee_percentage' => (int) ($request->transaction_fee_percentage * 100), // Convert to basis points
                'min_balance_limit' => $currency->toCents($request->min_balance_limit ?? 0),
                'allow_negative_balance' => filter_var($request->allow_negative_balance, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
                'is_default' => filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN),
            ];
            
            // Convert monetary values to cents
            if ($request->has('current_balance_display')) {
                $data['current_balance'] = $currency->toCents($request->current_balance_display ?? 0);
                $data['available_balance'] = $data['current_balance'];
            }
            
            if ($request->has('min_transaction_amount_display')) {
                $data['min_transaction_amount'] = $currency->toCents($request->min_transaction_amount_display ?? 0);
            }
            
            if ($request->has('max_transaction_amount_display') && $request->max_transaction_amount_display) {
                $data['max_transaction_amount'] = $currency->toCents($request->max_transaction_amount_display);
            }
            
            if ($request->has('daily_limit_display') && $request->daily_limit_display) {
                $data['daily_limit'] = $currency->toCents($request->daily_limit_display);
            }
            
            if ($request->has('monthly_limit_display') && $request->monthly_limit_display) {
                $data['monthly_limit'] = $currency->toCents($request->monthly_limit_display);
            }
            
            if ($request->has('transaction_fee_fixed_display')) {
                $data['transaction_fee_fixed'] = $currency->toCents($request->transaction_fee_fixed_display ?? 0);
            }
            
            $paymentMethod = PaymentMethod::create($data);
            
            // If this is set as default, remove default from others
            if ($paymentMethod->is_default) {
                PaymentMethod::where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment method created successfully',
                'payment_method' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment method details for editing.
     */
    public function getPaymentMethod($id)
    {
        try {
            $paymentMethod = PaymentMethod::with('currency')->findOrFail($id);
            
            // Convert cents back to display amounts
            $currency = $paymentMethod->currency;
            if ($currency) {
                $paymentMethod->current_balance_display = $currency->fromCents($paymentMethod->current_balance);
                $paymentMethod->min_transaction_amount_display = $currency->fromCents($paymentMethod->min_transaction_amount);
                $paymentMethod->max_transaction_amount_display = $paymentMethod->max_transaction_amount ? $currency->fromCents($paymentMethod->max_transaction_amount) : null;
                $paymentMethod->daily_limit_display = $paymentMethod->daily_limit ? $currency->fromCents($paymentMethod->daily_limit) : null;
                $paymentMethod->monthly_limit_display = $paymentMethod->monthly_limit ? $currency->fromCents($paymentMethod->monthly_limit) : null;
                $paymentMethod->transaction_fee_fixed_display = $currency->fromCents($paymentMethod->transaction_fee_fixed);
                $paymentMethod->transaction_fee_percentage_display = $paymentMethod->transaction_fee_percentage / 100;
            }
            
            return response()->json($paymentMethod);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found'
            ], 404);
        }
    }

    /**
     * Update a payment method.
     */
    public function updatePaymentMethod(Request $request, $id)
    {
        if (!auth()->user()->can('edit payment methods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payment methods.'
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,bank,card,mobile_money,e_wallet,crypto,cheque',
            'code' => 'required|string|max:50|unique:payment_methods,code,' . $id,
            'currency_id' => 'required|exists:currencies,id',
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            $wasDefault = $paymentMethod->is_default;
            $currency = Currency::find($request->currency_id);
            
            $data = [
                'name' => $request->name,
                'type' => $request->type,
                'code' => strtoupper($request->code),
                'currency_id' => $request->currency_id,
                'provider' => $request->provider,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'iban' => $request->iban,
                'swift_bic' => $request->swift_bic,
                'phone_number' => $request->phone_number,
                'wallet_id' => $request->wallet_id,
                'wallet_email' => $request->wallet_email,
                'card_last_four' => $request->card_last_four,
                'card_type' => $request->card_type,
                'card_expiry_date' => $request->card_expiry_date,
                'transaction_fee_percentage' => (int) (($request->transaction_fee_percentage ?? 0) * 100),
                'min_balance_limit' => $currency->toCents($request->min_balance_limit ?? 0),
                'allow_negative_balance' => filter_var($request->allow_negative_balance, FILTER_VALIDATE_BOOLEAN),
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
                'is_default' => filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN),
            ];
            
            // Convert monetary values to cents
            if ($request->has('current_balance_display')) {
                $data['current_balance'] = $currency->toCents($request->current_balance_display ?? 0);
                $data['available_balance'] = $data['current_balance'];
            }
            
            if ($request->has('min_transaction_amount_display')) {
                $data['min_transaction_amount'] = $currency->toCents($request->min_transaction_amount_display ?? 0);
            }
            
            if ($request->has('max_transaction_amount_display') && $request->max_transaction_amount_display) {
                $data['max_transaction_amount'] = $currency->toCents($request->max_transaction_amount_display);
            }
            
            if ($request->has('daily_limit_display') && $request->daily_limit_display) {
                $data['daily_limit'] = $currency->toCents($request->daily_limit_display);
            }
            
            if ($request->has('monthly_limit_display') && $request->monthly_limit_display) {
                $data['monthly_limit'] = $currency->toCents($request->monthly_limit_display);
            }
            
            if ($request->has('transaction_fee_fixed_display')) {
                $data['transaction_fee_fixed'] = $currency->toCents($request->transaction_fee_fixed_display ?? 0);
            }
            
            $paymentMethod->update($data);
            
            // If this is set as default, remove default from others
            if ($paymentMethod->is_default && !$wasDefault) {
                PaymentMethod::where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
                'payment_method' => $paymentMethod
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod($id)
    {
        
        if (!auth()->user()->can('delete payment methods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete payment methods.'
            ]);
        }

        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            
            // Check if has transactions
            if ($paymentMethod->transactionLogs()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete payment method. It has transaction history.'
                ], 400);
            }
            
            $paymentMethod->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment method: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle payment method status.
     */
    public function togglePaymentMethodStatus($id)
    {
        
        if (!auth()->user()->can('edit payment methods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit payment methods.'
            ]);
        }

        try {
            $paymentMethod = PaymentMethod::findOrFail($id);
            
            $paymentMethod->is_active = !$paymentMethod->is_active;
            $paymentMethod->save();
            
            $status = $paymentMethod->is_active ? 'activated' : 'deactivated';
            
            return response()->json([
                'success' => true,
                'message' => "Payment method {$status} successfully",
                'is_active' => $paymentMethod->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle payment method status'
            ], 500);
        }
    }


    
    /**
     * Transfer funds between payment methods with currency conversion
     */
    public function transferBetweenMethods(Request $request)
    {
        $request->validate([
            'from_payment_method_id' => 'required|exists:payment_methods,id',
            'to_payment_method_id' => 'required|exists:payment_methods,id|different:from_payment_method_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $fromMethod = PaymentMethod::findOrFail($request->from_payment_method_id);
            $toMethod = PaymentMethod::findOrFail($request->to_payment_method_id);
            
            // Get currencies
            $fromCurrency = $fromMethod->currency;
            $toCurrency = $toMethod->currency;
            
            if (!$fromCurrency || !$toCurrency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found for one or both payment methods'
                ], 400);
            }

            // Convert amount to cents for source currency
            $amountInCents = $fromCurrency->toCents($request->amount);
            
            // Check if source has sufficient balance
            if (!$fromMethod->allow_negative_balance && $fromMethod->current_balance < $amountInCents) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance in source account'
                ], 400);
            }

            // Calculate converted amount for destination
            $convertedAmount = $this->convertCurrency(
                $request->amount,
                $fromCurrency,
                $toCurrency
            );
            
            $convertedAmountInCents = $toCurrency->toCents($convertedAmount);

            // Check destination transaction limits
            if ($convertedAmountInCents < $toMethod->min_transaction_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount is below minimum transaction for {$toMethod->name}"
                ], 400);
            }

            if ($toMethod->max_transaction_amount && $convertedAmountInCents > $toMethod->max_transaction_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount exceeds maximum transaction for {$toMethod->name}"
                ], 400);
            }

            // Use the PaymentService to process the transfer
            $transferResult = $this->paymentService->transfer([
                'from_payment_method_id' => $request->from_payment_method_id,
                'to_payment_method_id' => $request->to_payment_method_id,
                'from_payment_method_name' => $fromMethod->name,
                'to_payment_method_name' => $toMethod->name,
                'amount' => $request->amount,
                'description' => $request->description ?? 'Transfer between accounts',
                'user_id' => auth()->id(),
                'currency_id' => $fromCurrency->id,
                'transaction_date' => now(),
            ]);

            // Refresh models to get updated balances
            $fromMethod->refresh();
            $toMethod->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed successfully',
                'data' => [
                    'from_method' => $fromMethod->name,
                    'to_method' => $toMethod->name,
                    'amount_sent' => $fromCurrency->formatAmount($amountInCents),
                    'amount_received' => $toCurrency->formatAmount($convertedAmountInCents),
                    'exchange_rate' => $this->getExchangeRate($fromCurrency, $toCurrency),
                    'new_balance_from' => $fromMethod->formatted_current_balance,
                    'new_balance_to' => $toMethod->formatted_current_balance,
                    'transaction_ref' => $transferResult['withdrawal']->transaction_ref ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Transfer failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert currency amount with exchange rate
     */
    private function convertCurrency($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency->id === $toCurrency->id) {
            return $amount;
        }

        // If both currencies have exchange rates to USD, convert via USD
        if ($fromCurrency->exchange_rate_to_usd && $toCurrency->exchange_rate_to_usd) {
            $amountInUSD = $amount / $fromCurrency->exchange_rate_to_usd;
            $convertedAmount = $amountInUSD * $toCurrency->exchange_rate_to_usd;
            return round($convertedAmount, $toCurrency->decimal_places);
        }

        // Fallback: assume 1:1 if no rates available
        \Log::warning('Currency conversion using fallback: no exchange rate available', [
            'from' => $fromCurrency->code,
            'to' => $toCurrency->code
        ]);

        return $amount;
    }

    /**
     * Get exchange rate between two currencies
     */
    private function getExchangeRate($fromCurrency, $toCurrency)
    {
        if ($fromCurrency->id === $toCurrency->id) {
            return '1.0000';
        }

        if ($fromCurrency->exchange_rate_to_usd && $toCurrency->exchange_rate_to_usd) {
            $rate = $toCurrency->exchange_rate_to_usd / $fromCurrency->exchange_rate_to_usd;
            return number_format($rate, 4);
        }

        return '1.0000';
    }

}