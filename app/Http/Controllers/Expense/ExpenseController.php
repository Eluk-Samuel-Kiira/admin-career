<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Currency;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display expenses list.
     */
    public function index(Request $request)
    {
        // Get base currency from request or default to USD
        $baseCurrencyCode = $request->get('base_currency', 'USD');
        $baseCurrency = Currency::where('code', $baseCurrencyCode)->first();
        
        if (!$baseCurrency) {
            $baseCurrency = Currency::where('is_default', true)->first();
            $baseCurrencyCode = $baseCurrency->code;
        }
        
        // Get all currencies for dropdown
        $currencies = Currency::where('is_active', true)->get();
        
        return view('expense.expenses.index', compact('currencies', 'baseCurrencyCode'));
    }



    /**
     * Get all expenses with pagination and search.
     */
    public function getExpenses(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = Expense::with(['category', 'department', 'paymentMethod.currency', 'employee', 'creator', 'approver']);

        // Apply search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('expense_number', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('vendor_name', 'like', '%' . $search . '%')
                ->orWhere('receipt_number', 'like', '%' . $search . '%');
            });
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('payment_status', $request->status);
        }

        // Apply category filter
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Get total count for the cards BEFORE pagination
        $totalCount = $query->count();
        $pendingCount = (clone $query)->where('payment_status', 'pending')->count();
        $approvedCount = (clone $query)->where('payment_status', 'approved')->count();
        $paidCount = (clone $query)->where('payment_status', 'paid')->count();

        $expenses = $query->orderBy('date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform data - display in native currency
        $data = [
            'current_page' => $expenses->currentPage(),
            'data' => collect($expenses->items())->map(function($expense) {
                // Get the currency from payment method
                $currency = $expense->paymentMethod?->currency ?? Currency::getDefault();
                
                // Format the amount in native currency
                $amountFormatted = $currency->formatAmount($expense->total_amount);
                
                return [
                    'id' => $expense->id,
                    'expense_number' => $expense->expense_number,
                    'date' => $expense->date,
                    'description' => $expense->description,
                    'category' => $expense->category,
                    'department' => $expense->department,
                    'payment_method' => $expense->paymentMethod,
                    'vendor_name' => $expense->vendor_name,
                    'total_amount' => $expense->total_amount,
                    'amount_formatted' => $amountFormatted,
                    'currency_code' => $currency->code,
                    'currency_symbol' => $currency->symbol,
                    'payment_status' => $expense->payment_status,
                    'created_at' => $expense->created_at,
                    'approved_at' => $expense->approved_at,
                    'paid_date' => $expense->paid_date,
                    'notes' => $expense->notes,
                ];
            })->toArray(),
            'first_page_url' => $expenses->url(1),
            'from' => $expenses->firstItem(),
            'last_page' => $expenses->lastPage(),
            'last_page_url' => $expenses->url($expenses->lastPage()),
            'next_page_url' => $expenses->nextPageUrl(),
            'prev_page_url' => $expenses->previousPageUrl(),
            'to' => $expenses->lastItem(),
            'total' => $expenses->total(),
            'per_page' => $perPage,
            // Add summary counts for cards
            'summary' => [
                'total_count' => $totalCount,
                'pending_count' => $pendingCount,
                'approved_count' => $approvedCount,
                'paid_count' => $paidCount,
            ]
        ];

        return response()->json($data);
    }


    /**
     * Get data for creating/editing expense.
     */
    public function getFormData()
    {
        $categories = ExpenseCategory::active()->orderBy('name')->get(['id', 'name', 'code']);
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::with('currency')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'currency_id']);
        $employees = User::active()->orderBy('name')->get(['id', 'name', 'email']);
        $currencies = Currency::active()->get();

        return response()->json([
            'categories' => $categories,
            'departments' => $departments,
            'payment_methods' => $paymentMethods,
            'employees' => $employees,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Store a new expense.
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create expenses.'
            ]);
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'category_id' => 'required|exists:expense_categories,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:users,id',
            'vendor_name' => 'nullable|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            // Get payment method and its currency
            $paymentMethod = PaymentMethod::with('currency')->find($request->payment_method_id);
            $currency = $paymentMethod?->currency ?? Currency::getDefault();
            
            // Convert amounts to cents using the currency's decimal places
            $grossAmount = $currency->toCents($request->gross_amount);
            $taxAmount = $currency->toCents($request->tax_amount ?? 0);
            $netAmount = $grossAmount - $taxAmount;
            $totalAmount = $grossAmount + $taxAmount;

            $expense = Expense::create([
                'expense_number' => 'EXP-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'date' => $request->date,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'department_id' => $request->department_id,
                'employee_id' => $request->employee_id,
                'created_by' => auth()->id(),
                'vendor_name' => $request->vendor_name,
                'vendor_contact' => $request->vendor_contact,
                'vendor_email' => $request->vendor_email,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'tax_breakdown' => $request->tax_breakdown,
                'payment_method_id' => $request->payment_method_id,
                'payment_status' => $request->payment_status ?? 'pending',
                'is_recurring' => $request->boolean('is_recurring'),
                'recurring_frequency' => $request->recurring_frequency,
                'next_recurring_date' => $request->next_recurring_date,
                'receipt_number' => $request->receipt_number,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
            ]);

            // If expense is marked as paid, process payment
            if ($expense->payment_status === 'paid' && $expense->payment_method_id) {
                $this->processExpensePayment($expense);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense details for editing.
     */
    public function show($id)
    {
        try {
            $expense = Expense::with(['category', 'department', 'paymentMethod', 'employee', 'creator', 'approver'])
                ->findOrFail($id);
            
            $currency = $expense->paymentMethod?->currency ?? Currency::getDefault();
            
            // Convert amounts back from cents using currency model
            $expense->gross_amount_display = $currency->fromCents($expense->gross_amount);
            $expense->tax_amount_display = $currency->fromCents($expense->tax_amount);
            $expense->net_amount_display = $currency->fromCents($expense->net_amount);
            $expense->total_amount_display = $currency->fromCents($expense->total_amount);

            return response()->json($expense);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }
    }

    /**
     * Update an expense.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit expenses.'
            ]);
        }

        $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'category_id' => 'required|exists:expense_categories,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'nullable|exists:users,id',
            'vendor_name' => 'nullable|string|max:255',
            'gross_amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            $expense = Expense::findOrFail($id);
            $paymentMethod = PaymentMethod::with('currency')->find($request->payment_method_id);
            $currency = $paymentMethod?->currency ?? Currency::getDefault();
            
            // Convert amounts to cents using currency model
            $grossAmount = $currency->toCents($request->gross_amount);
            $taxAmount = $currency->toCents($request->tax_amount ?? 0);
            $netAmount = $grossAmount - $taxAmount;
            $totalAmount = $grossAmount + $taxAmount;

            $expense->update([
                'date' => $request->date,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'department_id' => $request->department_id,
                'employee_id' => $request->employee_id,
                'vendor_name' => $request->vendor_name,
                'vendor_contact' => $request->vendor_contact,
                'vendor_email' => $request->vendor_email,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'tax_breakdown' => $request->tax_breakdown,
                'payment_method_id' => $request->payment_method_id,
                'is_recurring' => $request->boolean('is_recurring'),
                'recurring_frequency' => $request->recurring_frequency,
                'next_recurring_date' => $request->next_recurring_date,
                'receipt_number' => $request->receipt_number,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve an expense.
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->can('approve expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve expenses.'
            ]);
        }

        try {
            DB::beginTransaction();

            $expense = Expense::findOrFail($id);
            
            if ($expense->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense cannot be approved. Current status: ' . $expense->payment_status
                ], 400);
            }

            $expense->approve(auth()->id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense approved successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay an expense (process payment and deduct from account).
     */
    public function pay(Request $request, $id)
    {
        if (!auth()->user()->can('pay expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pay expenses.'
            ]);
        }

        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            $expense = Expense::with(['category', 'department'])->findOrFail($id);
            
            if (!in_array($expense->payment_status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense cannot be paid. Current status: ' . $expense->payment_status
                ], 400);
            }

            $this->processExpensePayment($expense, $request->payment_method_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Expense paid successfully. Payment deducted from account.',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to pay expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process expense payment through PaymentService.
     */
    private function processExpensePayment(Expense $expense, $paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?? $expense->payment_method_id;
        
        if (!$paymentMethodId) {
            throw new \Exception('Payment method is required to process expense payment.');
        }

        $paymentMethod = PaymentMethod::with('currency')->find($paymentMethodId);
        $currency = $paymentMethod->currency ?? Currency::getDefault();
        
        // Convert from cents to display amount using currency model
        $amountInDisplay = $currency->fromCents($expense->total_amount);

        $transaction = $this->paymentService->withdraw([
            'payment_method_id' => $paymentMethodId,
            'amount' => $amountInDisplay,
            'currency_id' => $currency->id,
            'user_id' => auth()->id(),
            'department_id' => $expense->department_id,
            'description' => $expense->description ?? 'Expense payment - ' . $expense->expense_number,
            'reference_table' => 'expenses',
            'reference_id' => $expense->id,
            'external_reference' => $expense->receipt_number,
            'metadata' => [
                'expense_number' => $expense->expense_number,
                'category' => $expense->category?->name,
                'department' => $expense->department?->name,
                'vendor' => $expense->vendor_name,
            ],
        ]);

        $expense->payment_status = 'paid';
        $expense->paid_date = now();
        $expense->payment_method_id = $paymentMethodId;
        $expense->save();

        return $transaction;
    }

    /**
     * Cancel an expense.
     */
    public function cancel(Request $request, $id)
    {
        
        if (!auth()->user()->can('cancel expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel expenses.'
            ]);
        }
        try {
            $expense = Expense::findOrFail($id);
            
            if (in_array($expense->payment_status, ['paid', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense cannot be cancelled. Current status: ' . $expense->payment_status
                ], 400);
            }

            $expense->cancel($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Expense cancelled successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an expense.
     */
    public function reject(Request $request, $id)
    {
        if (!auth()->user()->can('reject expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject expenses.'
            ]);
        }
        try {
            $expense = Expense::findOrFail($id);
            
            if ($expense->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense cannot be rejected. Current status: ' . $expense->payment_status
                ], 400);
            }

            $expense->reject($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Expense rejected successfully',
                'expense' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject expense: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an expense.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete expenses')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete expenses.'
            ]);
        }

        try {
            $expense = Expense::findOrFail($id);
            
            if ($expense->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid expense. Please reverse the payment first.'
                ], 400);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage()
            ], 500);
        }
    }
}