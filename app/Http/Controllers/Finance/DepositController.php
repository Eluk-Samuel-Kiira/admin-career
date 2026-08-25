<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\{ Deposit, PaymentSource, PaymentPurpose, Department, User, DepositReceipt };
use App\Services\Payment\{ PaymentService };
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Storage, Log };

class DepositController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display deposits list.
     */
    public function deposits()
    {
        return view('finance.deposit.index');
    }


    public function getDeposits(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        
        $query = Deposit::with(['paymentMethod', 'currency', 'creator', 'source', 'purpose', 'department', 'depositor']);
        
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('deposit_ref', 'like', '%' . $search . '%')
                    ->orWhere('reference_number', 'like', '%' . $search . '%')
                    ->orWhere('source_reference', 'like', '%' . $search . '%')
                    ->orWhere('depositor_name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_id', 'like', '%' . $search . '%');
            });
        }
        
        $deposits = $query->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        $data = [
            'current_page' => $deposits->currentPage(),
            'data' => collect($deposits->items())->map(function($deposit) {
                return [
                    'id' => $deposit->id,
                    'deposit_ref' => $deposit->deposit_ref,
                    'payment_method_name' => $deposit->paymentMethod->name ?? 'N/A',
                    'formatted_amount' => $deposit->formatted_amount,
                    'formatted_fee' => $deposit->formatted_fee,
                    'deposit_method' => $deposit->deposit_method,
                    'source_name' => $deposit->source ? $deposit->source->name : ($deposit->source_name_manual ?? 'N/A'),
                    'source_reference' => $deposit->source_reference,
                    'purpose_name' => $deposit->purpose ? $deposit->purpose->name : 'N/A',
                    'purpose_description' => $deposit->purpose_description,
                    'department_name' => $deposit->department?->name,
                    'depositor_name' => $deposit->depositor?->name,
                    'status' => $deposit->status,
                    'deposit_date' => $deposit->deposit_date ? $deposit->deposit_date->format('M d, Y H:i:s') : 'N/A',
                    'created_at' => $deposit->created_at->format('M d, Y H:i:s'),
                    'description' => $deposit->description,
                ];
            })->toArray(),
            'first_page_url' => $deposits->url(1),
            'from' => $deposits->firstItem(),
            'last_page' => $deposits->lastPage(),
            'last_page_url' => $deposits->url($deposits->lastPage()),
            'next_page_url' => $deposits->nextPageUrl(),
            'prev_page_url' => $deposits->previousPageUrl(),
            'to' => $deposits->lastItem(),
            'total' => $deposits->total(),
            'per_page' => $perPage,
        ];
        
        return response()->json($data);
    }

    /**
     * Get departments for dropdown.
     */
    public function getDepartments()
    {
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        return response()->json($departments);
    }

    /**
     * Get users for depositor dropdown.
     */
    public function getUsers()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        return response()->json($users);
    }

    /**
     * Get payment methods for deposit dropdown.
     */
    public function getPaymentMethods()
    {
        $methods = PaymentMethod::with('currency')
            ->where('is_active', true)
            ->get(['id', 'name', 'code', 'currency_id'])
            ->map(function($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'currency_id' => $method->currency_id,
                    'currency_symbol' => $method->currency->symbol ?? '$',
                    'currency_code' => $method->currency->code ?? 'USD',
                ];
            });
        
        return response()->json($methods);
    }

    /**
     * Get currencies for deposit dropdown.
     */
    public function getCurrencies()
    {
        $currencies = Currency::where('is_active', true)->get(['id', 'code', 'name', 'symbol']);
        return response()->json($currencies);
    }

    /**
     * Store a new deposit.
     */
    public function storeDeposit(Request $request)
    {
            
        if (!auth()->user()->can('create deposits')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create deposits.'
            ]);
        }

        try {
            $validated = $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'currency_id' => 'required|exists:currencies,id',
                'amount' => 'required|numeric|min:0.01',
                'deposit_method' => 'required|in:cash,bank_transfer,mobile_money,card,cheque,e_wallet,crypto',
                'deposit_date' => 'required|date',
                'source_id' => 'required|exists:payment_sources,id',
                'purpose_id' => 'required|exists:payment_purposes,id',
            ]);

            DB::beginTransaction();
            
            $currency = Currency::findOrFail($request->currency_id);
            $amountInCents = $currency->toCents($request->amount);
            $feeInCents = $currency->toCents($request->fee ?? 0);
            
            $deposit = Deposit::create([
                'deposit_ref' => (string) Str::uuid(),
                'payment_method_id' => $request->payment_method_id,
                'currency_id' => $request->currency_id,
                'amount' => $amountInCents,
                'fee' => $feeInCents,
                'net_amount' => $amountInCents - $feeInCents,
                'deposit_method' => $request->deposit_method,
                'reference_number' => $request->reference_number,
                'cheque_number' => $request->cheque_number,
                'department_id' => $request->department_id,
                'depositor_id' => $request->depositor_id,
                'source_id' => $request->source_id,
                'source_reference' => $request->source_reference,
                'customer_id' => $request->customer_id,
                'invoice_number' => $request->invoice_number,
                'po_number' => $request->po_number,
                'contract_number' => $request->contract_number,
                'purpose_id' => $request->purpose_id,
                'purpose_description' => $request->purpose_description,
                'status' => 'pending',
                'deposit_date' => $request->deposit_date,
                'depositor_name' => $request->depositor_name,
                'depositor_phone' => $request->depositor_phone,
                'depositor_email' => $request->depositor_email,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Deposit created successfully. Awaiting approval.',
                'deposit' => $deposit
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Deposit creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deposit: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Approve and process deposit.
     */
    public function approveDeposit($id)
    {
           
        if (!auth()->user()->can('approve deposits')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve deposits.'
            ]);
        }

        try {
            DB::beginTransaction();
            
            $deposit = Deposit::with('currency', 'department', 'depositor')->findOrFail($id);
            
            if ($deposit->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Deposit cannot be approved. Current status: ' . $deposit->status
                ], 400);
            }
            
            $paymentMethod = PaymentMethod::findOrFail($deposit->payment_method_id);
            $amountInDisplay = $deposit->currency->fromCents($deposit->net_amount);
            
            Log::info('Approving deposit', [
                'deposit_id' => $deposit->id,
                'department_id' => $deposit->department_id,
                'depositor_id' => $deposit->depositor_id,
                'amount_in_display' => $amountInDisplay,
            ]);
            
            // Process deposit with department_id and depositor_id
            $transaction = $this->paymentService->deposit([
                'payment_method_id' => $deposit->payment_method_id,
                'amount' => $amountInDisplay,
                'currency_id' => $deposit->currency_id,
                'user_id' => auth()->id(),
                'department_id' => $deposit->department_id,      // NEW
                'depositor_id' => $deposit->depositor_id,        // NEW
                'description' => $deposit->description ?? 'Deposit to account',
                'reference_table' => 'deposits',
                'reference_id' => $deposit->id,
                'external_reference' => $deposit->reference_number,
                'metadata' => [
                    'deposit_method' => $deposit->deposit_method,
                    'source_id' => $deposit->source_id,
                    'source_name' => $deposit->source?->name,
                    'purpose_id' => $deposit->purpose_id,
                    'purpose_name' => $deposit->purpose?->name,
                    'department_name' => $deposit->department?->name,
                    'depositor_name' => $deposit->depositor?->name,
                ],
            ]);
            
            $deposit->status = 'completed';
            $deposit->approved_by = auth()->id();
            $deposit->approved_at = now();
            $deposit->cleared_date = now();
            $deposit->save();
            
            DB::commit();
            
            $updatedPaymentMethod = PaymentMethod::find($deposit->payment_method_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Deposit approved and processed successfully',
                'transaction_ref' => $transaction->transaction_ref,
                'new_balance' => $updatedPaymentMethod->formatted_current_balance,
                'deposit_amount' => $deposit->currency->formatAmount($deposit->net_amount),
                'balance_before' => $deposit->currency->fromCents($transaction->balance_before),
                'balance_after' => $deposit->currency->fromCents($transaction->balance_after),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve deposit: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'deposit_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel deposit.
     */
    public function cancelDeposit($id)
    {
          
        if (!auth()->user()->can('cancel deposits')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel deposits.'
            ]);
        }

        try {
            $deposit = Deposit::findOrFail($id);
            
            if (!in_array($deposit->status, ['pending', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deposit cannot be cancelled. Current status: ' . $deposit->status
                ], 400);
            }
            
            $deposit->status = 'cancelled';
            $deposit->notes = ($deposit->notes ? $deposit->notes . "\n" : '') . "Cancelled by: " . auth()->user()->name;
            $deposit->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Deposit cancelled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete deposit.
     */
    public function deleteDeposit($id)
    {
         
        if (!auth()->user()->can('delete deposits')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete deposits.'
            ]);
        }

        try {
            $deposit = Deposit::findOrFail($id);
            
            if ($deposit->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a completed deposit. Please reverse the transaction first.'
                ], 400);
            }
            
            $deposit->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Deposit deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deposit details.
     */
    public function getDeposit($id)
    {
        try {
            $deposit = Deposit::with(['paymentMethod', 'currency', 'creator', 'approver', 'source', 'purpose'])->findOrFail($id);
            
            return response()->json([
                'id' => $deposit->id,
                'deposit_ref' => $deposit->deposit_ref,
                'payment_method_name' => $deposit->paymentMethod->name ?? 'N/A',
                'currency_symbol' => $deposit->currency->symbol ?? '$',
                'amount' => $deposit->currency->fromCents($deposit->amount),
                'formatted_amount' => $deposit->formatted_amount,
                'deposit_date' => $deposit->deposit_date ? $deposit->deposit_date->format('Y-m-d H:i:s') : 'N/A',
                'source_type' => $deposit->source ? $deposit->source->name : ($deposit->source_type ?? 'N/A'),
                'source_name' => $deposit->source ? $deposit->source->name : ($deposit->source_name ?? 'N/A'),
                'source_reference' => $deposit->source_reference ?? 'N/A',
                'purpose' => $deposit->purpose ? $deposit->purpose->name : ($deposit->purpose_category ?? 'N/A'),
                'purpose_description' => $deposit->purpose_description ?? 'N/A',
                'reference_number' => $deposit->reference_number ?? 'N/A',
                'description' => $deposit->description ?? 'N/A',
                'status' => $deposit->status,
                'status_badge' => $this->getStatusBadge($deposit->status),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit not found'
            ], 404);
        }
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'processing' => '<span class="badge badge-light-info">Processing</span>',
            'completed' => '<span class="badge badge-light-success">Completed</span>',
            'failed' => '<span class="badge badge-light-danger">Failed</span>',
            'cancelled' => '<span class="badge badge-light-secondary">Cancelled</span>'
        ];
        return $badges[$status] ?? '<span class="badge badge-light-secondary">' . $status . '</span>';
    }

    // Add these methods to get sources and purposes
    public function getSources()
    {
        $sources = PaymentSource::where('is_active', true)->orderBy('sort_order')->get();
        return response()->json($sources);
    }

    public function getPurposes()
    {
        $purposes = PaymentPurpose::where('is_active', true)->orderBy('sort_order')->get();
        return response()->json($purposes);
    }


    /**
     * Get receipts for a deposit
     */
    public function getReceipts($depositId)
    {
        $receipts = DepositReceipt::where('deposit_id', $depositId)
            ->with('uploader')
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($receipt) {
                return [
                    'id' => $receipt->id,
                    'file_name' => $receipt->file_name,
                    'file_path' => $receipt->file_path,
                    'file_url' => asset('storage/' . $receipt->file_path),
                    'file_type' => $receipt->file_type,
                    'file_size' => $receipt->file_size,
                    'receipt_number' => $receipt->receipt_number,
                    'description' => $receipt->description,
                    'is_primary' => $receipt->is_primary,
                    'created_at' => $receipt->created_at,
                    'formatted_size' => $receipt->formatted_size,
                ];
            });
        
        return response()->json($receipts);
    }


    public function uploadReceipt(Request $request, $depositId)
    {
        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'receipt_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_primary' => 'boolean',
        ]);

        try {
            $deposit = Deposit::findOrFail($depositId);
            $file = $request->file('receipt');
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = 'deposits/receipts/' . $depositId;
            
            // Store file
            $storedPath = $file->storeAs($path, $filename, 'public');
            
            // Generate full URL using asset helper (will respect APP_URL)
            $fullUrl = asset('storage/' . $storedPath);
            
            // If this is set as primary, remove primary from other receipts
            $isPrimary = $request->boolean('is_primary');
            if ($isPrimary) {
                DepositReceipt::where('deposit_id', $depositId)->update(['is_primary' => false]);
            }
            
            // Create receipt record
            $receipt = DepositReceipt::create([
                'deposit_id' => $depositId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'receipt_number' => $request->receipt_number,
                'description' => $request->description,
                'is_primary' => $isPrimary,
                'uploaded_by' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'receipt' => [
                    'id' => $receipt->id,
                    'file_name' => $receipt->file_name,
                    'file_path' => $receipt->file_path,
                    'file_url' => $fullUrl,
                    'file_type' => $receipt->file_type,
                    'file_size' => $receipt->file_size,
                    'receipt_number' => $receipt->receipt_number,
                    'description' => $receipt->description,
                    'is_primary' => $receipt->is_primary,
                    'created_at' => $receipt->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete receipt
     */
    public function deleteReceipt($depositId, $receiptId)
    {
        try {
            $receipt = DepositReceipt::where('deposit_id', $depositId)
                ->where('id', $receiptId)
                ->firstOrFail();
            
            // Delete file from storage
            if (Storage::disk('public')->exists($receipt->file_path)) {
                Storage::disk('public')->delete($receipt->file_path);
            }
            
            $receipt->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Receipt deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set receipt as primary
     */
    public function setPrimaryReceipt($depositId, $receiptId)
    {
        try {
            // Remove primary from all receipts of this deposit
            DepositReceipt::where('deposit_id', $depositId)->update(['is_primary' => false]);
            
            // Set the selected receipt as primary
            $receipt = DepositReceipt::where('deposit_id', $depositId)
                ->where('id', $receiptId)
                ->firstOrFail();
            $receipt->is_primary = true;
            $receipt->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Primary receipt set successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set primary receipt: ' . $e->getMessage()
            ], 500);
        }
    }

}