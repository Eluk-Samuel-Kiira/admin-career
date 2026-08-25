<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ EmployeePayment, Employee };
use App\Models\EmployeeSalary;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Currency;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeePaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display employee payments list.
     */
    public function index()
    {
        return view('compensation.payments.index');
    }

    /**
     * Get all employee payments with pagination and search.
     */
    public function getPayments(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');
        $paymentType = $request->get('payment_type', '');
        $userId = $request->get('user_id', '');

        $query = EmployeePayment::with(['user', 'department', 'paymentMethod.currency', 'employeeSalary', 'approver', 'creator']);

        // Apply search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('payment_number', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('reference_number', 'like', '%' . $search . '%');
            });
        }

        // Apply status filter
        if (!empty($status)) {
            $query->where('payment_status', $status);
        }

        // Apply department filter
        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        // Apply payment type filter
        if (!empty($paymentType)) {
            $query->where('payment_type', $paymentType);
        }

        // Apply user filter
        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        // Get total count for the cards BEFORE pagination
        $totalCount = $query->count();
        $pendingCount = (clone $query)->where('payment_status', 'pending')->count();
        $approvedCount = (clone $query)->where('payment_status', 'approved')->count();
        $paidCount = (clone $query)->where('payment_status', 'paid')->count();

        $payments = $query->orderBy('payment_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform data - use model accessors
        $data = [
            'current_page' => $payments->currentPage(),
            'data' => collect($payments->items())->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date,
                    'payment_type' => $payment->payment_type,
                    'payment_type_label' => $payment->payment_type_label,
                    'description' => $payment->description,
                    'user' => $payment->user ? [
                        'id' => $payment->user->id,
                        'name' => $payment->user->full_name ?? $payment->user->name,
                    ] : null,
                    'department' => $payment->department?->name ?? 'N/A',
                    'payment_method' => $payment->paymentMethod?->name ?? 'N/A',
                    'total_amount' => $payment->total_amount,
                    'amount_formatted' => $payment->formatted_total,
                    'currency_code' => $payment->currency?->code ?? 'UGX',
                    'payment_status' => $payment->payment_status,
                    'status_badge' => $payment->payment_status_badge,
                    'approved_at' => $payment->approved_at,
                    'paid_date' => $payment->paid_date,
                    'notes' => $payment->notes,
                    'created_at' => $payment->created_at,
                ];
            })->toArray(),
            'first_page_url' => $payments->url(1),
            'from' => $payments->firstItem(),
            'last_page' => $payments->lastPage(),
            'last_page_url' => $payments->url($payments->lastPage()),
            'next_page_url' => $payments->nextPageUrl(),
            'prev_page_url' => $payments->previousPageUrl(),
            'to' => $payments->lastItem(),
            'total' => $payments->total(),
            'per_page' => $perPage,
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
     * Get data for creating/editing employee payment.
     */
    public function getFormData()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $paymentMethods = PaymentMethod::with('currency')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'currency_id']);
        
        $employees = User::whereHas('employee', function($query) {
                $query->where('is_active', true);
            })
            ->with('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);
        
        $employeeSalaries = EmployeeSalary::with(['user', 'department'])
            ->where('is_active', true)
            ->get();

        $currencies = Currency::active()->get();

        return response()->json([
            'departments' => $departments,
            'payment_methods' => $paymentMethods,
            'employees' => $employees,
            'employee_salaries' => $employeeSalaries,
            'currencies' => $currencies,
            'payment_types' => [
                ['value' => 'salary', 'label' => 'Salary'],
                ['value' => 'bonus', 'label' => 'Bonus'],
                ['value' => 'commission', 'label' => 'Commission'],
                ['value' => 'advance', 'label' => 'Advance'],
                ['value' => 'reimbursement', 'label' => 'Reimbursement'],
            ],
        ]);
    }

    /**
     * Store a new employee payment.
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create salary payments.'
            ]);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_salary_id' => 'nullable|exists:employee_salaries,id',
            'department_id' => 'nullable|exists:departments,id',
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:salary,bonus,commission,advance,reimbursement',
            'description' => 'required|string|max:500',
            'gross_amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|array',
            'allowances' => 'nullable|array',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'pay_period_start' => 'nullable|date',
            'pay_period_end' => 'nullable|date|after_or_equal:pay_period_start',
            'hours_worked' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get payment method and its currency
            $paymentMethod = PaymentMethod::with('currency')->find($request->payment_method_id);
            $currency = $paymentMethod?->currency ?? Currency::getDefault();
            
            // Convert amounts to cents (multiply by 100 for USD, keep as-is for UGX)
            $grossAmount = $currency->toCents($request->gross_amount);
            $taxAmount = $currency->toCents($request->tax_amount ?? 0);
            $netAmount = $grossAmount - $taxAmount;
            $totalAmount = $grossAmount + $taxAmount;

            // Get user's department if not provided
            $departmentId = $request->department_id;
            if (!$departmentId && $request->user_id) {
                $user = User::with('employee')->find($request->user_id);
                $departmentId = $user?->employee?->department_id;
            }

            // Get employee salary if not provided
            $employeeSalaryId = $request->employee_salary_id;
            if (!$employeeSalaryId && $request->user_id) {
                $employeeSalary = EmployeeSalary::where('user_id', $request->user_id)
                    ->where('is_active', true)
                    ->first();
                $employeeSalaryId = $employeeSalary?->id;
            }

            $payment = EmployeePayment::create([
                'payment_number' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'employee_salary_id' => $employeeSalaryId,
                'user_id' => $request->user_id,
                'department_id' => $departmentId,
                'payment_method_id' => $request->payment_method_id,
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'description' => $request->description,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'deductions' => $request->deductions,
                'allowances' => $request->allowances,
                'payment_status' => 'pending',
                'pay_period_start' => $request->pay_period_start,
                'pay_period_end' => $request->pay_period_end,
                'hours_worked' => $request->hours_worked,
                'hourly_rate' => $request->hourly_rate,
                'breakdown' => $request->breakdown,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee payment created successfully',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee payment creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee payment details.
     */
    public function show($id)
    {
        try {
            $payment = EmployeePayment::with(['user', 'department', 'paymentMethod', 'employeeSalary', 'approver', 'creator'])
                ->findOrFail($id);
            
            return response()->json($payment);
        } catch (\Exception $e) {
            Log::error('Payment show failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
    }

    /**
     * Update an employee payment.
     */
    public function update(Request $request, $id)
    {
         
        if (!auth()->user()->can('edit salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit salary payments.'
            ]);
        }

        $request->validate([
            'payment_date' => 'required|date',
            'payment_type' => 'required|in:salary,bonus,commission,advance,reimbursement',
            'description' => 'required|string|max:500',
            'gross_amount' => 'required|numeric|min:0.01',
            'tax_amount' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|array',
            'allowances' => 'nullable|array',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'pay_period_start' => 'nullable|date',
            'pay_period_end' => 'nullable|date|after_or_equal:pay_period_start',
            'hours_worked' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $payment = EmployeePayment::findOrFail($id);
            
            // Don't allow editing if already paid
            if ($payment->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a paid payment'
                ], 400);
            }

            $paymentMethod = PaymentMethod::with('currency')->find($request->payment_method_id);
            $currency = $paymentMethod?->currency ?? Currency::getDefault();
            
            // Convert amounts to cents
            $grossAmount = $currency->toCents($request->gross_amount);
            $taxAmount = $currency->toCents($request->tax_amount ?? 0);
            $netAmount = $grossAmount - $taxAmount;
            $totalAmount = $grossAmount + $taxAmount;

            $payment->update([
                'payment_date' => $request->payment_date,
                'payment_type' => $request->payment_type,
                'description' => $request->description,
                'gross_amount' => $grossAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount,
                'total_amount' => $totalAmount,
                'deductions' => $request->deductions,
                'allowances' => $request->allowances,
                'payment_method_id' => $request->payment_method_id,
                'pay_period_start' => $request->pay_period_start,
                'pay_period_end' => $request->pay_period_end,
                'hours_worked' => $request->hours_worked,
                'hourly_rate' => $request->hourly_rate,
                'breakdown' => $request->breakdown,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'metadata' => $request->metadata,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee payment updated successfully',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee payment update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve an employee payment.
     */
    public function approve(Request $request, $id)
    {
        if (!auth()->user()->can('approve salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve salary payments.'
            ]);
        }

        try {
            DB::beginTransaction();

            $payment = EmployeePayment::findOrFail($id);
            
            if ($payment->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be approved. Current status: ' . $payment->payment_status
                ], 400);
            }

            $payment->approve(auth()->id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment approved successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pay an employee payment (process payment and deduct from account).
     */
    public function pay(Request $request, $id)
    {
        if (!auth()->user()->can('process salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to process salary payments.'
            ]);
        }

        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            $payment = EmployeePayment::with(['user', 'department'])->findOrFail($id);
            
            if (!in_array($payment->payment_status, ['pending', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be processed. Current status: ' . $payment->payment_status
                ], 400);
            }

            $this->processEmployeePayment($payment, $request->payment_method_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully. Amount deducted from account.',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process employee payment through PaymentService.
     */
    private function processEmployeePayment(EmployeePayment $payment, $paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?? $payment->payment_method_id;
        
        if (!$paymentMethodId) {
            throw new \Exception('Payment method is required to process employee payment.');
        }

        $paymentMethod = PaymentMethod::with('currency')->find($paymentMethodId);
        $currency = $paymentMethod->currency ?? Currency::getDefault();
        
        // Use the accessor to get display amount
        $amountInDisplay = $payment->total_amount_display;

        $transaction = $this->paymentService->withdraw([
            'payment_method_id' => $paymentMethodId,
            'amount' => $amountInDisplay,
            'currency_id' => $currency->id,
            'user_id' => auth()->id(),
            'department_id' => $payment->department_id,
            'description' => $payment->description ?? 'Employee payment - ' . $payment->payment_number,
            'reference_table' => 'employee_payments',
            'reference_id' => $payment->id,
            'external_reference' => $payment->reference_number,
            'metadata' => [
                'payment_number' => $payment->payment_number,
                'payment_type' => $payment->payment_type_label,
                'employee' => $payment->user?->full_name ?? $payment->user?->name,
                'department' => $payment->department?->name,
            ],
        ]);

        $payment->payment_status = 'paid';
        $payment->paid_date = now();
        $payment->payment_method_id = $paymentMethodId;
        $payment->save();

        return $transaction;
    }

    /**
     * Cancel an employee payment.
     */
    public function cancel(Request $request, $id)
    {
        
        if (!auth()->user()->can('cancel salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel salary payments.'
            ]);
        }

        try {
            $payment = EmployeePayment::findOrFail($id);
            
            if (in_array($payment->payment_status, ['paid', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be cancelled. Current status: ' . $payment->payment_status
                ], 400);
            }

            $payment->cancel($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an employee payment.
     */
    public function reject(Request $request, $id)
    {
        
        if (!auth()->user()->can('reject salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject salary payments.'
            ]);
        }
        try {
            $payment = EmployeePayment::findOrFail($id);
            
            if ($payment->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment cannot be rejected. Current status: ' . $payment->payment_status
                ], 400);
            }

            $payment->reject($request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected successfully',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an employee payment.
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete salary payments')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete salary payments.'
            ]);
        }
        try {
            $payment = EmployeePayment::findOrFail($id);
            
            if ($payment->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid payment. Please reverse the payment first.'
                ], 400);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate salary payments for employees.
     */
    public function generateSalaryPayments(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'payment_date' => 'required|date',
            'pay_period_start' => 'required|date',
            'pay_period_end' => 'required|date|after_or_equal:pay_period_start',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            // Get all active employees with their salary structures
            $employees = Employee::with(['user', 'department', 'employeeSalary.salaryStructure'])
                ->where('is_active', true)
                ->whereNotIn('employee_type', ['job_seeker', 'employer'])
                ->when($request->department_id, function($query) use ($request) {
                    return $query->where('department_id', $request->department_id);
                })
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active employees found to generate salaries.'
                ], 400);
            }

            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($employees as $employee) {
                try {
                    // Skip if no user
                    if (!$employee->user) {
                        $skipped++;
                        continue;
                    }

                    // Get or create employee salary record
                    $employeeSalary = $employee->employeeSalary;
                    if (!$employeeSalary) {
                        // Create a default employee salary record
                        $employeeSalary = EmployeeSalary::create([
                            'employee_id' => $employee->id,
                            'user_id' => $employee->user_id,
                            'department_id' => $employee->department_id,
                            'base_salary' => $employee->salary ?? 0,
                            'salary_type' => $employee->salary_type ?? 'fixed',
                            'is_recurring' => $employee->is_salary_recurring ?? true,
                            'recurring_day' => $employee->recurring_day ?? 1,
                            'hire_date' => $employee->hire_date ?? now(),
                            'phantom_equity_units' => 0,
                            'vested_units' => 0,
                            'units_vested_percentage' => 0,
                            'current_balance' => 0,
                            'is_active' => true,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    // Check if payment already exists for this period
                    $existing = EmployeePayment::where('employee_salary_id', $employeeSalary->id)
                        ->where('payment_type', 'salary')
                        ->whereBetween('payment_date', [$request->pay_period_start, $request->pay_period_end])
                        ->exists();

                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    // Calculate salary (values are already in cents)
                    $baseSalary = $employeeSalary->base_salary;
                    
                    // If base salary is 0, use employee's salary
                    if ($baseSalary == 0 && $employee->salary > 0) {
                        $baseSalary = $employee->salary;
                    }
                    
                    // If still 0, skip
                    if ($baseSalary == 0) {
                        $skipped++;
                        continue;
                    }

                    // Apply performance multiplier if available
                    $multiplier = $employeeSalary->performance_multiplier ?? 1.0;
                    $grossAmount = $baseSalary * $multiplier;

                    // Calculate tax (simplified - 10% tax)
                    $taxAmount = $grossAmount * 0.10;
                    $netAmount = $grossAmount - $taxAmount;

                    // Get payment method
                    $paymentMethodId = $request->payment_method_id;
                    if (!$paymentMethodId) {
                        // Try to get default payment method for the department
                        $defaultMethod = PaymentMethod::where('is_default', true)
                            ->where('is_active', true)
                            ->first();
                        $paymentMethodId = $defaultMethod?->id;
                    }

                    // Create payment - values are already in cents
                    EmployeePayment::create([
                        'payment_number' => 'SAL-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                        'employee_salary_id' => $employeeSalary->id,
                        'user_id' => $employee->user_id,
                        'department_id' => $employee->department_id,
                        'payment_method_id' => $paymentMethodId,
                        'payment_date' => $request->payment_date,
                        'payment_type' => 'salary',
                        'description' => 'Monthly salary payment for ' . ($employee->full_name ?? $employee->user?->name ?? 'Employee'),
                        'gross_amount' => (int) round($grossAmount),
                        'tax_amount' => (int) round($taxAmount),
                        'net_amount' => (int) round($netAmount),
                        'total_amount' => (int) round($grossAmount),
                        'payment_status' => 'pending',
                        'pay_period_start' => $request->pay_period_start,
                        'pay_period_end' => $request->pay_period_end,
                        'created_by' => auth()->id(),
                    ]);

                    $created++;

                } catch (\Exception $e) {
                    $errors[] = 'Error for employee ' . ($employee->full_name ?? $employee->user?->name ?? 'Unknown') . ': ' . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            $message = "Generated {$created} salary payments.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} employees.";
            }
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Salary generation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate salary payments: ' . $e->getMessage()
            ], 500);
        }
    }
}