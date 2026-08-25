<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\PhantomEquityTransaction;
use App\Models\EmployeeSalary;
use App\Models\User;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PhantomEquityController extends Controller
{
    /**
     * Display phantom equity list.
     */
    public function index()
    {
        return view('compensation.phantom-equity.index');
    }

    /**
     * Get phantom equity data for datatable.
     */
    public function getTransactions(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $transactionType = $request->get('transaction_type', '');
        $userId = $request->get('user_id', '');
        $departmentId = $request->get('department_id', '');

        $query = PhantomEquityTransaction::with(['user', 'department', 'employeeSalary', 'creator']);

        // Apply search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Apply filters
        if (!empty($transactionType)) {
            $query->where('transaction_type', $transactionType);
        }

        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total_units' => PhantomEquityTransaction::sum('units'),
            'vested_units' => PhantomEquityTransaction::where('is_vested', true)->sum('vested_units'),
            'total_payout' => PhantomEquityTransaction::where('transaction_type', 'payout')->sum('total_value'),
            'total_users' => PhantomEquityTransaction::distinct('user_id')->count(),
        ];

        $data = [
            'current_page' => $transactions->currentPage(),
            'data' => collect($transactions->items())->map(function($transaction) {
                // Calculate display values (divide by 100 for cents)
                $unitValue = $transaction->unit_value / 100;
                $totalValue = $transaction->total_value / 100;
                
                return [
                    'id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'transaction_type' => $transaction->transaction_type,
                    'transaction_type_label' => $this->getTransactionTypeLabel($transaction->transaction_type),
                    'user' => $transaction->user ? [
                        'id' => $transaction->user->id,
                        'name' => $transaction->user->full_name ?? $transaction->user->name,
                    ] : null,
                    'department' => $transaction->department?->name ?? 'N/A',
                    'units' => $transaction->units,
                    'vested_units' => $transaction->vested_units,
                    'unit_value' => $unitValue,
                    'total_value' => $totalValue,
                    'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
                    'formatted_total_value' => 'UGX ' . number_format($totalValue, 0),
                    'is_vested' => (bool) $transaction->is_vested,
                    'vested_badge' => $transaction->is_vested ? 
                        '<span class="badge badge-light-success">Vested</span>' : 
                        '<span class="badge badge-light-warning">Unvested</span>',
                    'performance_score' => $transaction->performance_score,
                    'performance_multiplier' => $transaction->performance_multiplier,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date,
                    'created_at' => $transaction->created_at,
                ];
            })->toArray(),

            'first_page_url' => $transactions->url(1),
            'from' => $transactions->firstItem(),
            'last_page' => $transactions->lastPage(),
            'last_page_url' => $transactions->url($transactions->lastPage()),
            'next_page_url' => $transactions->nextPageUrl(),
            'prev_page_url' => $transactions->previousPageUrl(),
            'to' => $transactions->lastItem(),
            'total' => $transactions->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data for creating/editing phantom equity.
     */
    public function getFormData()
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

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'employee_salaries' => $employeeSalaries,
            'transaction_types' => [
                ['value' => 'allocation', 'label' => 'Allocation'],
                ['value' => 'award', 'label' => 'Award'],
                ['value' => 'vesting', 'label' => 'Vesting'],
                ['value' => 'forfeiture', 'label' => 'Forfeiture'],
                ['value' => 'payout', 'label' => 'Payout'],
            ],
        ]);
    }

    /**
     * Store a new phantom equity transaction.
     */
    public function store(Request $request)
    {
        
        if (!auth()->user()->can('create phantom equity')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create phantom equity.'
            ]);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'employee_salary_id' => 'nullable|exists:employee_salaries,id',
            'department_id' => 'nullable|exists:departments,id',
            'transaction_type' => 'required|in:allocation,award,vesting,forfeiture,payout',
            'units' => 'required|integer|min:1',
            'unit_value' => 'nullable|numeric|min:0',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'performance_multiplier' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
            'is_vested' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Get department if not provided
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

            // Calculate total value
            $unitValue = $request->unit_value ?? 0;
            $totalValue = $unitValue * $request->units;

            $transaction = PhantomEquityTransaction::create([
                'employee_salary_id' => $employeeSalaryId,
                'user_id' => $request->user_id,
                'department_id' => $departmentId,
                'transaction_type' => $request->transaction_type,
                'units' => $request->units,
                'vested_units' => $request->is_vested ? $request->units : 0,
                'unit_value' => (int) round($unitValue * 100),
                'total_value' => (int) round($totalValue * 100),
                'performance_score' => $request->performance_score,
                'performance_multiplier' => $request->performance_multiplier ?? 1.0,
                'description' => $request->description,
                'reference' => 'PE-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'transaction_date' => $request->transaction_date,
                'is_vested' => $request->has('is_vested'),
                'created_by' => auth()->id(),
            ]);

            // Update employee salary phantom equity units
            if ($employeeSalaryId) {
                $employeeSalary = EmployeeSalary::find($employeeSalaryId);
                if ($employeeSalary) {
                    if ($request->transaction_type === 'allocation' || $request->transaction_type === 'award') {
                        $employeeSalary->phantom_equity_units += $request->units;
                        if ($request->is_vested) {
                            $employeeSalary->vested_units += $request->units;
                        }
                        $employeeSalary->save();
                    } elseif ($request->transaction_type === 'forfeiture') {
                        $employeeSalary->phantom_equity_units -= $request->units;
                        $employeeSalary->vested_units -= $request->units;
                        $employeeSalary->save();
                    } elseif ($request->transaction_type === 'vesting') {
                        $employeeSalary->vested_units += $request->units;
                        $employeeSalary->save();
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Phantom equity transaction created successfully',
                'transaction' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Phantom equity creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create phantom equity transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show phantom equity transaction details.
     */
    public function show($id)
    {
        try {
            $transaction = PhantomEquityTransaction::with(['user', 'department', 'employeeSalary', 'creator'])
                ->findOrFail($id);

            // Divide by 100 to get display values
            $unitValue = $transaction->unit_value / 100;
            $totalValue = $transaction->total_value / 100;

            return response()->json([
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'transaction_type' => $transaction->transaction_type,
                'transaction_type_label' => $this->getTransactionTypeLabel($transaction->transaction_type),
                'user' => $transaction->user ? [
                    'id' => $transaction->user->id,
                    'name' => $transaction->user->full_name ?? $transaction->user->name,
                    'email' => $transaction->user->email,
                ] : null,
                'department' => $transaction->department ? [
                    'id' => $transaction->department->id,
                    'name' => $transaction->department->name,
                ] : null,
                'employee_salary' => $transaction->employeeSalary ? [
                    'id' => $transaction->employeeSalary->id,
                    'base_salary' => $transaction->employeeSalary->base_salary,
                    'phantom_equity_units' => $transaction->employeeSalary->phantom_equity_units,
                    'vested_units' => $transaction->employeeSalary->vested_units,
                ] : null,
                'units' => $transaction->units,
                'vested_units' => $transaction->vested_units,
                'unit_value' => $unitValue,
                'total_value' => $totalValue,
                'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
                'formatted_total_value' => 'UGX ' . number_format($totalValue, 0),
                'is_vested' => (bool) $transaction->is_vested,
                'performance_score' => $transaction->performance_score,
                'performance_multiplier' => $transaction->performance_multiplier,
                'description' => $transaction->description,
                'transaction_date' => $transaction->transaction_date,
                'created_at' => $transaction->created_at,
                'created_by' => $transaction->creator?->name ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
    }

    /**
     * Update phantom equity transaction.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit phantom equity')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit phantom equity.'
            ]);
        }

        $request->validate([
            'transaction_type' => 'required|in:allocation,award,vesting,forfeiture,payout',
            'units' => 'required|integer|min:1',
            'unit_value' => 'nullable|numeric|min:0',
            'performance_score' => 'nullable|numeric|min:0|max:100',
            'performance_multiplier' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
            'is_vested' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $transaction = PhantomEquityTransaction::findOrFail($id);

            // Calculate total value
            $unitValue = $request->unit_value ?? 0;
            $totalValue = $unitValue * $request->units;

            $transaction->update([
                'transaction_type' => $request->transaction_type,
                'units' => $request->units,
                'vested_units' => $request->is_vested ? $request->units : 0,
                'unit_value' => (int) round($unitValue * 100),
                'total_value' => (int) round($totalValue * 100),
                'performance_score' => $request->performance_score,
                'performance_multiplier' => $request->performance_multiplier ?? 1.0,
                'description' => $request->description,
                'transaction_date' => $request->transaction_date,
                'is_vested' => $request->has('is_vested'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Phantom equity transaction updated successfully',
                'transaction' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Phantom equity update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update phantom equity transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete phantom equity transaction.
     */
    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete phantom equity')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit phantom equity.'
            ]);
        }

        try {
            $transaction = PhantomEquityTransaction::findOrFail($id);
            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Phantom equity transaction deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete phantom equity transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction type label.
     */
    private function getTransactionTypeLabel($type)
    {
        $labels = [
            'allocation' => 'Allocation',
            'award' => 'Award',
            'vesting' => 'Vesting',
            'forfeiture' => 'Forfeiture',
            'payout' => 'Payout',
        ];
        return $labels[$type] ?? $type;
    }

    /**
     * Get employee's phantom equity summary.
     */
    public function getUserSummary($userId)
    {
        try {
            $employeeSalary = EmployeeSalary::where('user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$employeeSalary) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee salary record not found'
                ], 404);
            }

            $transactions = PhantomEquityTransaction::where('user_id', $userId)
                ->orderBy('transaction_date', 'desc')
                ->get();

            $totalAllocated = $transactions->where('transaction_type', 'allocation')->sum('units');
            $totalAwarded = $transactions->where('transaction_type', 'award')->sum('units');
            $totalVested = $transactions->where('is_vested', true)->sum('vested_units');
            $totalForfeited = $transactions->where('transaction_type', 'forfeiture')->sum('units');
            $totalPayout = $transactions->where('transaction_type', 'payout')->sum('total_value');

            return response()->json([
                'success' => true,
                'data' => [
                    'employee_salary' => $employeeSalary,
                    'total_allocated' => $totalAllocated,
                    'total_awarded' => $totalAwarded,
                    'total_vested' => $totalVested,
                    'total_forfeited' => $totalForfeited,
                    'total_payout' => $totalPayout / 100,
                    'formatted_total_payout' => 'UGX ' . number_format($totalPayout / 100, 0),
                    'current_units' => $employeeSalary->phantom_equity_units,
                    'current_vested_units' => $employeeSalary->vested_units,
                    'transactions' => $transactions,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user summary: ' . $e->getMessage()
            ], 500);
        }
    }
}