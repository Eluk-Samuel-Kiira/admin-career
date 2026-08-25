<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\ProfitShareDistribution;
use App\Models\DepartmentProfitShare;
use App\Models\EmployeeSalary;
use App\Models\User;
use App\Models\{ Department, Employee };
use App\Models\PaymentMethod;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfitShareDistributionController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }


    /**
     * Display profit share distributions list.
     */
    public function index()
    {
        return view('compensation.profit-share.index');
    }

    /**
     * Get profit share distributions data for datatable.
     */
    public function getDistributions(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');
        $userId = $request->get('user_id', '');

        $query = ProfitShareDistribution::with(['user', 'department', 'employeeSalary', 'departmentProfitShare', 'paidBy']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        $distributions = $query->orderBy('distribution_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total_distributions' => ProfitShareDistribution::count(),
            'pending_count' => ProfitShareDistribution::where('status', 'pending')->count(),
            'paid_count' => ProfitShareDistribution::where('status', 'paid')->count(),
            'total_amount' => ProfitShareDistribution::where('status', 'paid')->sum('total_amount'),
        ];

        $data = [
            'current_page' => $distributions->currentPage(),
            'data' => collect($distributions->items())->map(function($distribution) {
                // For UGX, no division by 100 - use the value as-is
                $totalAmount = $distribution->total_amount; // No division
                $unitValue = $distribution->unit_value; // No division
                
                return [
                    'id' => $distribution->id,
                    'reference' => $distribution->reference,
                    'user' => $distribution->user ? [
                        'id' => $distribution->user->id,
                        'name' => $distribution->user->full_name ?? $distribution->user->name,
                    ] : null,
                    'department' => $distribution->department?->name ?? 'N/A',
                    'units_held' => $distribution->units_held,
                    'vested_units' => $distribution->vested_units,
                    'unit_value' => $unitValue,
                    'total_amount' => $totalAmount,
                    'formatted_total' => 'UGX ' . number_format($totalAmount, 0),
                    'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
                    'distribution_date' => $distribution->distribution_date,
                    'status' => $distribution->status,
                    'status_badge' => $this->getStatusBadge($distribution->status),
                    'notes' => $distribution->notes,
                    'created_at' => $distribution->created_at,
                ];
            })->toArray(),
            'first_page_url' => $distributions->url(1),
            'from' => $distributions->firstItem(),
            'last_page' => $distributions->lastPage(),
            'last_page_url' => $distributions->url($distributions->lastPage()),
            'next_page_url' => $distributions->nextPageUrl(),
            'prev_page_url' => $distributions->previousPageUrl(),
            'to' => $distributions->lastItem(),
            'total' => $distributions->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data.
     */
    public function getFormData(Request $request)
    {
        $users = User::whereHas('employee', function($query) {
                $query->where('is_active', true);
            })
            ->with('employee')
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email']);

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $employeeSalaries = EmployeeSalary::with(['user', 'department'])
            ->where('is_active', true)
            ->get();

        // Get department profit shares with remaining balance
        $departmentProfitShares = DepartmentProfitShare::whereIn('status', ['calculated', 'pending'])
            ->with('department')
            ->get()
            ->map(function($period) {
                // Calculate already distributed amount for this period
                $distributed = ProfitShareDistribution::where('department_profit_share_id', $period->id)
                    ->where('status', '!=', 'failed')
                    ->sum('total_amount');
                
                $remaining = $period->profit_share_amount - $distributed;
                
                return [
                    'id' => $period->id,
                    'financial_year' => $period->financial_year,
                    'department_id' => $period->department_id,
                    'department_name' => $period->department?->name ?? 'All Departments',
                    'total_amount' => $period->profit_share_amount,
                    'formatted_total' => 'UGX ' . number_format($period->profit_share_amount / 100, 0),
                    'distributed_amount' => $distributed,
                    'formatted_distributed' => 'UGX ' . number_format($distributed / 100, 0),
                    'remaining_amount' => $remaining,
                    'formatted_remaining' => 'UGX ' . number_format($remaining / 100, 0),
                    'has_remaining' => $remaining > 0,
                ];
            })
            ->filter(function($period) {
                return $period['has_remaining'];
            })
            ->values();

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'employee_salaries' => $employeeSalaries,
            'department_profit_shares' => $departmentProfitShares,
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'failed', 'label' => 'Failed'],
            ],
        ]);
    }

    /**
     * Get employees for a specific department.
     */
    public function getDepartmentEmployees($departmentId)
    {
        try {
            $employees = Employee::with(['user', 'employeeSalary'])
                ->where('department_id', $departmentId)
                ->where('is_active', true)
                ->whereNotIn('employee_type', ['job_seeker', 'employer'])
                ->get()
                ->map(function($employee) {
                    return [
                        'id' => $employee->user_id,
                        'name' => $employee->full_name,
                        'email' => $employee->email,
                        'employee_salary_id' => $employee->employeeSalary?->id,
                        'phantom_equity_units' => $employee->employeeSalary?->phantom_equity_units ?? 0,
                        'vested_units' => $employee->employeeSalary?->vested_units ?? 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'employees' => $employees,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get department employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get department employees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available balance for a profit share period.
     */
    public function getAvailableBalance($periodId)
    {
        try {
            $period = DepartmentProfitShare::findOrFail($periodId);
            
            $distributed = ProfitShareDistribution::where('department_profit_share_id', $periodId)
                ->where('status', '!=', 'failed')
                ->sum('total_amount');
            
            $remaining = $period->profit_share_amount - $distributed;

            return response()->json([
                'success' => true,
                'period' => [
                    'id' => $period->id,
                    'financial_year' => $period->financial_year,
                    'total_amount' => $period->profit_share_amount,
                    'formatted_total' => 'UGX ' . number_format($period->profit_share_amount / 100, 0),
                    'distributed_amount' => $distributed,
                    'formatted_distributed' => 'UGX ' . number_format($distributed / 100, 0),
                    'remaining_amount' => $remaining,
                    'formatted_remaining' => 'UGX ' . number_format($remaining / 100, 0),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new profit share distribution.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create profit shares.'
            ]);
        }

        $request->validate([
            'department_profit_share_id' => 'required|exists:department_profit_shares,id',
            'user_id' => 'required|exists:users,id',
            'employee_salary_id' => 'nullable|exists:employee_salaries,id',
            'department_id' => 'required|exists:departments,id',
            'units_held' => 'required|integer|min:0',
            'vested_units' => 'required|integer|min:0',
            'unit_value' => 'required|numeric|min:0',
            'distribution_date' => 'required|date',
            'status' => 'required|in:pending,paid,failed',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Get the profit share period
            $period = DepartmentProfitShare::findOrFail($request->department_profit_share_id);
            
            // Calculate total amount for this distribution
            $totalAmount = $request->unit_value * $request->vested_units;
            
            // FOR UGX - Store as-is (no multiplication by 100)
            // Since UGX has decimal_places = 0, we store the actual value
            $totalAmountInCents = (int) round($totalAmount);

            // Check if department matches
            if ($period->department_id && $period->department_id != $request->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This profit share period is for a different department.'
                ], 400);
            }

            // Check available balance (period->profit_share_amount is already in cents/UGX)
            $distributed = ProfitShareDistribution::where('department_profit_share_id', $period->id)
                ->where('status', '!=', 'failed')
                ->sum('total_amount');
            
            $remaining = $period->profit_share_amount - $distributed;

            if ($totalAmountInCents > $remaining) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Available: UGX ' . number_format($remaining, 0) . ', Requested: UGX ' . number_format($totalAmountInCents, 0)
                ], 400);
            }

            // Get employee salary if not provided
            $employeeSalaryId = $request->employee_salary_id;
            if (!$employeeSalaryId && $request->user_id) {
                $employeeSalary = EmployeeSalary::where('user_id', $request->user_id)
                    ->where('is_active', true)
                    ->first();
                $employeeSalaryId = $employeeSalary?->id;
            }

            // Store unit_value as-is (no multiplication)
            $distribution = ProfitShareDistribution::create([
                'department_profit_share_id' => $request->department_profit_share_id,
                'employee_salary_id' => $employeeSalaryId,
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'units_held' => $request->units_held,
                'vested_units' => $request->vested_units,
                'unit_value' => (int) round($request->unit_value), // No multiplication
                'total_amount' => $totalAmountInCents, // No multiplication
                'distribution_date' => $request->distribution_date,
                'status' => $request->status,
                'reference' => 'PS-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profit share distribution created successfully',
                'distribution' => $distribution,
                'remaining_balance' => $remaining - $totalAmountInCents,
                'formatted_remaining' => 'UGX ' . number_format(($remaining - $totalAmountInCents), 0),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profit share distribution creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create distribution: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk distribute profit share to all eligible employees in a department.
     */
    public function bulkDistribute(Request $request)
    {
        if (!auth()->user()->can('distribute profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to distribute profit shares.'
            ]);
        }

        $request->validate([
            'department_profit_share_id' => 'required|exists:department_profit_shares,id',
            'distribution_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $period = DepartmentProfitShare::findOrFail($request->department_profit_share_id);
            
            // Get all active employees in the department
            $departmentId = $period->department_id;
            $employees = Employee::with(['user', 'employeeSalary'])
                ->where('department_id', $departmentId)
                ->where('is_active', true)
                ->whereNotIn('employee_type', ['job_seeker', 'employer'])
                ->get();

            if ($employees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active employees found in this department.'
                ], 400);
            }

            // Calculate total vested units
            $totalVestedUnits = $employees->sum(function($emp) {
                return $emp->employeeSalary?->vested_units ?? 0;
            });

            if ($totalVestedUnits == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No vested units found for employees in this department.'
                ], 400);
            }

            // Calculate unit value based on remaining balance
            $distributed = ProfitShareDistribution::where('department_profit_share_id', $period->id)
                ->where('status', '!=', 'failed')
                ->sum('total_amount');
            
            $remaining = $period->profit_share_amount - $distributed;
            
            // For UGX, unit value is in UGX (no cents conversion)
            $unitValue = (int) round($remaining / $totalVestedUnits);

            if ($unitValue <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance for distribution.'
                ], 400);
            }

            $created = 0;
            $skipped = 0;
            $totalDistributed = 0;

            foreach ($employees as $employee) {
                $employeeSalary = $employee->employeeSalary;
                if (!$employeeSalary || $employeeSalary->vested_units <= 0) {
                    $skipped++;
                    continue;
                }

                $vestedUnits = $employeeSalary->vested_units;
                $amount = $vestedUnits * $unitValue;

                // Check if we have enough remaining balance
                if ($amount > $remaining) {
                    $skipped++;
                    continue;
                }

                // Check if already distributed for this period
                $existing = ProfitShareDistribution::where('department_profit_share_id', $period->id)
                    ->where('user_id', $employee->user_id)
                    ->where('status', '!=', 'failed')
                    ->exists();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                ProfitShareDistribution::create([
                    'department_profit_share_id' => $period->id,
                    'employee_salary_id' => $employeeSalary->id,
                    'user_id' => $employee->user_id,
                    'department_id' => $departmentId,
                    'units_held' => $employeeSalary->phantom_equity_units,
                    'vested_units' => $vestedUnits,
                    'unit_value' => $unitValue, // No multiplication
                    'total_amount' => $amount, // No multiplication
                    'distribution_date' => $request->distribution_date,
                    'status' => 'pending',
                    'reference' => 'PS-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                    'notes' => 'Bulk distribution for ' . $period->financial_year,
                ]);

                $created++;
                $totalDistributed += $amount;
                $remaining -= $amount;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk distribution completed. Created: {$created}, Skipped: {$skipped}",
                'created' => $created,
                'skipped' => $skipped,
                'total_distributed' => $totalDistributed,
                'formatted_total' => 'UGX ' . number_format($totalDistributed, 0),
                'remaining_balance' => $remaining,
                'formatted_remaining' => 'UGX ' . number_format($remaining, 0),
                'unit_value' => $unitValue,
                'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk distribution failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk distribute: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Show distribution details.
     */
    public function show($id)
    {
        try {
            $distribution = ProfitShareDistribution::with(['user', 'department', 'employeeSalary', 'departmentProfitShare', 'paidBy'])
                ->findOrFail($id);

            // For UGX, no division by 100 - use the value as-is
            $unitValue = $distribution->unit_value; // No division
            $totalAmount = $distribution->total_amount; // No division

            return response()->json([
                'id' => $distribution->id,
                'reference' => $distribution->reference,
                'department_profit_share' => $distribution->departmentProfitShare ? [
                    'id' => $distribution->departmentProfitShare->id,
                    'financial_year' => $distribution->departmentProfitShare->financial_year,
                ] : null,
                'user' => $distribution->user ? [
                    'id' => $distribution->user->id,
                    'name' => $distribution->user->full_name ?? $distribution->user->name,
                    'email' => $distribution->user->email,
                ] : null,
                'department' => $distribution->department ? [
                    'id' => $distribution->department->id,
                    'name' => $distribution->department->name,
                ] : null,
                'employee_salary' => $distribution->employeeSalary ? [
                    'id' => $distribution->employeeSalary->id,
                    'base_salary' => $distribution->employeeSalary->base_salary,
                ] : null,
                'units_held' => $distribution->units_held,
                'vested_units' => $distribution->vested_units,
                'unit_value' => $unitValue,
                'total_amount' => $totalAmount,
                'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
                'formatted_total' => 'UGX ' . number_format($totalAmount, 0),
                'distribution_date' => $distribution->distribution_date,
                'status' => $distribution->status,
                'status_badge' => $this->getStatusBadge($distribution->status),
                'reference' => $distribution->reference,
                'notes' => $distribution->notes,
                'paid_by' => $distribution->paidBy ? [
                    'id' => $distribution->paidBy->id,
                    'name' => $distribution->paidBy->full_name ?? $distribution->paidBy->name,
                ] : null,
                'created_at' => $distribution->created_at,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Distribution not found'
            ], 404);
        }
    }

    /**
     * Update distribution.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit profit shares.'
            ]);
        }

        $request->validate([
            'units_held' => 'required|integer|min:0',
            'vested_units' => 'required|integer|min:0',
            'unit_value' => 'required|numeric|min:0',
            'distribution_date' => 'required|date',
            'status' => 'required|in:pending,paid,failed',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $distribution = ProfitShareDistribution::findOrFail($id);
            
            // If status is being changed to paid, check remaining balance
            if ($request->status === 'paid' && $distribution->status !== 'paid') {
                $period = DepartmentProfitShare::findOrFail($distribution->department_profit_share_id);
                
                // For UGX, no multiplication by 100
                $totalAmount = (int) round($request->unit_value * $request->vested_units);
                
                // Check if we have enough balance (excluding current distribution)
                $distributed = ProfitShareDistribution::where('department_profit_share_id', $period->id)
                    ->where('status', '!=', 'failed')
                    ->where('id', '!=', $id)
                    ->sum('total_amount');
                
                $remaining = $period->profit_share_amount - $distributed;
                
                if ($totalAmount > $remaining) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient balance for this distribution.'
                    ], 400);
                }
            }

            // For UGX, no multiplication by 100
            $totalAmount = (int) round($request->unit_value * $request->vested_units);

            $distribution->update([
                'units_held' => $request->units_held,
                'vested_units' => $request->vested_units,
                'unit_value' => (int) round($request->unit_value), // No multiplication
                'total_amount' => $totalAmount, // No multiplication
                'distribution_date' => $request->distribution_date,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Distribution updated successfully',
                'distribution' => $distribution
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profit share distribution update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update distribution: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Mark distribution as paid with payment processing.
     */
    public function markAsPaid(Request $request, $id)
    {
        
        if (!auth()->user()->can('pay profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pay profit shares.'
            ]);
        }

        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        try {
            DB::beginTransaction();

            $distribution = ProfitShareDistribution::with(['user', 'department'])
                ->findOrFail($id);

            // Check if already paid
            if ($distribution->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This distribution is already marked as paid.'
                ], 400);
            }

            // Get payment method
            $paymentMethod = PaymentMethod::with('currency')->find($request->payment_method_id);
            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not found.'
                ], 404);
            }

            // Get the amount - for UGX it's already in the correct format (no cents conversion needed)
            $amountToDeduct = $distribution->total_amount; // No division

            // Check balance before processing
            if ($paymentMethod->current_balance < $amountToDeduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance in selected payment method. Available: ' . 
                        $paymentMethod->formatted_current_balance . ', Required: UGX ' . 
                        number_format($amountToDeduct, 0)
                ], 400);
            }

            // Process payment using PaymentService
            $currency = $paymentMethod->currency;
            $transaction = $this->paymentService->withdraw([
                'payment_method_id' => $request->payment_method_id,
                'amount' => $amountToDeduct, // Already in UGX (no cents conversion)
                'currency_id' => $currency->id,
                'user_id' => $distribution->user_id,
                'department_id' => $distribution->department_id,
                'description' => 'Profit share distribution payment - ' . $distribution->reference,
                'reference_table' => 'profit_share_distributions',
                'reference_id' => $distribution->id,
                'external_reference' => $distribution->reference,
                'metadata' => [
                    'distribution_reference' => $distribution->reference,
                    'financial_year' => $distribution->departmentProfitShare?->financial_year,
                    'employee' => $distribution->user?->full_name ?? $distribution->user?->name,
                    'department' => $distribution->department?->name,
                ],
            ]);

            // Update distribution status
            $distribution->status = 'paid';
            $distribution->paid_by = auth()->id();
            $distribution->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Distribution marked as paid successfully. Amount deducted from account.',
                'transaction' => $transaction,
                'distribution' => $distribution,
                'new_balance' => $paymentMethod->fresh()->formatted_current_balance,
                'amount_deducted' => $amountToDeduct,
                'formatted_amount' => 'UGX ' . number_format($amountToDeduct, 0),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark distribution as paid: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get form data for mark as paid.
     */
    public function getMarkAsPaidData($id)
    {
        
        if (!auth()->user()->can('pay profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to pay profit shares.'
            ]);
        }

        try {
            $distribution = ProfitShareDistribution::with(['user', 'department'])
                ->findOrFail($id);

            $paymentMethods = PaymentMethod::with('currency')
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'distribution' => [
                    'id' => $distribution->id,
                    'reference' => $distribution->reference,
                    'total_amount' => $distribution->total_amount, // No division
                    'formatted_total' => 'UGX ' . number_format($distribution->total_amount, 0),
                    'user' => $distribution->user ? [
                        'id' => $distribution->user->id,
                        'name' => $distribution->user->full_name ?? $distribution->user->name,
                    ] : null,
                    'department' => $distribution->department?->name ?? 'N/A',
                ],
                'payment_methods' => $paymentMethods->map(function($pm) {
                    return [
                        'id' => $pm->id,
                        'name' => $pm->name,
                        'currency' => $pm->currency?->code ?? 'UGX',
                        'balance' => $pm->current_balance,
                        'formatted_balance' => $pm->currency?->formatAmount($pm->current_balance) ?? 'UGX ' . number_format($pm->current_balance, 0),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete distribution.
     */
    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete profit share')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete profit shares.'
            ]);
        }

        try {
            $distribution = ProfitShareDistribution::findOrFail($id);
            
            // Don't allow deletion if already paid
            if ($distribution->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid distribution.'
                ], 400);
            }
            
            $distribution->delete();

            return response()->json([
                'success' => true,
                'message' => 'Distribution deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete distribution: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'paid' => '<span class="badge badge-light-success">Paid</span>',
            'failed' => '<span class="badge badge-light-danger">Failed</span>',
        ];
        return $badges[$status] ?? '<span class="badge badge-light-secondary">' . $status . '</span>';
    }
}