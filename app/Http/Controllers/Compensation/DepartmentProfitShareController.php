<?php

namespace App\Http\Controllers\Compensation;

use App\Http\Controllers\Controller;
use App\Models\DepartmentProfitShare;
use App\Models\Department;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepartmentProfitShareController extends Controller
{
    /**
     * Display profit share periods list.
     */
    public function index()
    {
        return view('compensation.department-profit-share.index');
    }

    /**
     * Get profit share periods data for datatable.
     */
    public function getPeriods(Request $request)
    {
        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 20;
        $status = $request->get('status', '');
        $departmentId = $request->get('department_id', '');

        $query = DepartmentProfitShare::with(['department', 'creator']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('financial_year', 'like', '%' . $search . '%')
                  ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($departmentId)) {
            $query->where('department_id', $departmentId);
        }

        $periods = $query->orderBy('financial_year', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get summary statistics
        $summary = [
            'total_periods' => DepartmentProfitShare::count(),
            'pending_count' => DepartmentProfitShare::where('status', 'pending')->count(),
            'calculated_count' => DepartmentProfitShare::where('status', 'calculated')->count(),
            'distributed_count' => DepartmentProfitShare::where('status', 'distributed')->count(),
            'total_profit' => DepartmentProfitShare::sum('total_profit'),
            'total_share' => DepartmentProfitShare::sum('profit_share_amount'),
        ];

        $data = [
            'current_page' => $periods->currentPage(),
            'data' => collect($periods->items())->map(function($period) {
                return [
                    'id' => $period->id,
                    'financial_year' => $period->financial_year,
                    'department' => $period->department?->name ?? 'All Departments',
                    'department_id' => $period->department_id,
                    'total_profit' => $period->total_profit,
                    'formatted_profit' => 'UGX ' . number_format($period->total_profit, 0),
                    'profit_share_percentage' => $period->profit_share_percentage,
                    'profit_share_amount' => $period->profit_share_amount,
                    'formatted_share' => 'UGX ' . number_format($period->profit_share_amount, 0),
                    'total_units' => $period->total_units,
                    'unit_value' => $period->unit_value,
                    'formatted_unit_value' => 'UGX ' . number_format($period->unit_value, 0),
                    'status' => $period->status,
                    'status_badge' => $this->getStatusBadge($period->status),
                    'distribution_date' => $period->distribution_date,
                    'created_at' => $period->created_at,
                ];
            })->toArray(),
            'first_page_url' => $periods->url(1),
            'from' => $periods->firstItem(),
            'last_page' => $periods->lastPage(),
            'last_page_url' => $periods->url($periods->lastPage()),
            'next_page_url' => $periods->nextPageUrl(),
            'prev_page_url' => $periods->previousPageUrl(),
            'to' => $periods->lastItem(),
            'total' => $periods->total(),
            'per_page' => $perPage,
            'summary' => $summary,
        ];

        return response()->json($data);
    }

    /**
     * Get form data.
     */
    public function getFormData()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        
        // Get active payment methods with their currencies
        $paymentMethods = PaymentMethod::with('currency')
            ->where('is_active', true)
            ->get();

        // Get UGX currency for conversion
        $ugxCurrency = Currency::where('code', 'UGX')->first();

        // Calculate total profit from all active payment methods
        $totalProfit = $this->calculateTotalProfit($paymentMethods, $ugxCurrency);

        return response()->json([
            'departments' => $departments,
            'total_profit' => $totalProfit,
            'formatted_total_profit' => 'UGX ' . number_format($totalProfit, 0),
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
    }

    /**
     * Calculate total profit from all payment methods converted to UGX.
     */
    private function calculateTotalProfit($paymentMethods, $ugxCurrency)
    {
        $total = 0;

        foreach ($paymentMethods as $paymentMethod) {
            $currency = $paymentMethod->currency;
            $balance = $paymentMethod->current_balance ?? 0;

            if (!$currency) {
                // If no currency, assume UGX
                $total += $balance;
                continue;
            }

            if ($currency->code === 'UGX') {
                // Already in UGX
                $total += $balance;
            } else {
                // Convert to UGX using exchange rate
                if ($currency->exchange_rate_to_usd && $ugxCurrency && $ugxCurrency->exchange_rate_to_usd) {
                    // Convert to USD first, then to UGX
                    $amountInUSD = $balance / $currency->exchange_rate_to_usd;
                    $amountInUGX = $amountInUSD * $ugxCurrency->exchange_rate_to_usd;
                    $total += (int) round($amountInUGX);
                } else {
                    // If no exchange rate, assume 1:1 (should not happen)
                    Log::warning('No exchange rate available for currency: ' . $currency->code);
                    $total += $balance;
                }
            }
        }

        return $total;
    }

    /**
     * Calculate and store profit share for a financial year.
     */
    public function calculate(Request $request)
    {
        if (!auth()->user()->can('create profit share periods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create profit share periods.'
            ]);
        }
        $request->validate([
            'financial_year' => 'required|string|max:10',
            'department_id' => 'nullable|exists:departments,id',
            'profit_share_percentage' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Check if already exists
            $existing = DepartmentProfitShare::where('financial_year', $request->financial_year)
                ->when($request->department_id, function($query) use ($request) {
                    return $query->where('department_id', $request->department_id);
                })
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profit share for this financial year already exists.'
                ], 400);
            }

            // Get all active payment methods
            $paymentMethods = PaymentMethod::with('currency')
                ->where('is_active', true)
                ->get();

            // Get UGX currency
            $ugxCurrency = Currency::where('code', 'UGX')->first();

            // Calculate total profit
            $totalProfit = $this->calculateTotalProfit($paymentMethods, $ugxCurrency);

            // Calculate profit share amount
            $profitShareAmount = (int) round($totalProfit * ($request->profit_share_percentage / 100));

            // Get total units from employee salaries
            $totalUnits = 0;
            $unitValue = 0;

            if ($request->department_id) {
                // For specific department
                $totalUnits = \App\Models\EmployeeSalary::where('department_id', $request->department_id)
                    ->where('is_active', true)
                    ->sum('phantom_equity_units');
            } else {
                // For all departments
                $totalUnits = \App\Models\EmployeeSalary::where('is_active', true)
                    ->sum('phantom_equity_units');
            }

            if ($totalUnits > 0) {
                $unitValue = (int) round($profitShareAmount / $totalUnits);
            }

            // Create profit share record
            $profitShare = DepartmentProfitShare::create([
                'department_id' => $request->department_id,
                'financial_year' => $request->financial_year,
                'total_profit' => $totalProfit,
                'profit_share_percentage' => $request->profit_share_percentage,
                'profit_share_amount' => $profitShareAmount,
                'total_units' => $totalUnits,
                'unit_value' => $unitValue,
                'status' => 'calculated',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profit share calculated successfully',
                'profit_share' => $profitShare,
                'total_profit' => $totalProfit,
                'formatted_total_profit' => 'UGX ' . number_format($totalProfit, 0),
                'profit_share_amount' => $profitShareAmount,
                'formatted_profit_share' => 'UGX ' . number_format($profitShareAmount, 0),
                'total_units' => $totalUnits,
                'unit_value' => $unitValue,
                'formatted_unit_value' => 'UGX ' . number_format($unitValue, 0),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profit share calculation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate profit share: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show profit share period details.
     */
    public function show($id)
    {
        try {
            $period = DepartmentProfitShare::with(['department', 'creator', 'distributions'])
                ->findOrFail($id);

            return response()->json([
                'id' => $period->id,
                'financial_year' => $period->financial_year,
                'department' => $period->department?->name ?? 'All Departments',
                'department_id' => $period->department_id,
                'total_profit' => $period->total_profit,
                'formatted_profit' => 'UGX ' . number_format($period->total_profit, 0),
                'profit_share_percentage' => $period->profit_share_percentage,
                'profit_share_amount' => $period->profit_share_amount,
                'formatted_share' => 'UGX ' . number_format($period->profit_share_amount, 0),
                'total_units' => $period->total_units,
                'unit_value' => $period->unit_value,
                'formatted_unit_value' => 'UGX ' . number_format($period->unit_value, 0),
                'status' => $period->status,
                'status_badge' => $this->getStatusBadge($period->status),
                'distribution_date' => $period->distribution_date,
                'distributions_count' => $period->distributions->count(),
                'created_at' => $period->created_at,
                'created_by' => $period->creator?->name ?? 'N/A',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Period not found'
            ], 404);
        }
    }

    /**
     * Update profit share period.
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit profit share periods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit profit share periods.'
            ]);
        }

        $request->validate([
            'profit_share_percentage' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:pending,calculated,distributed,closed',
            'distribution_date' => 'nullable|date',
        ]);

        try {
            $period = DepartmentProfitShare::findOrFail($id);

            // Calculate new amounts if percentage changed
            if ($request->profit_share_percentage != $period->profit_share_percentage) {
                $profitShareAmount = (int) round($period->total_profit * ($request->profit_share_percentage / 100));
                $unitValue = $period->total_units > 0 ? (int) round($profitShareAmount / $period->total_units) : 0;
                
                $period->profit_share_percentage = $request->profit_share_percentage;
                $period->profit_share_amount = $profitShareAmount;
                $period->unit_value = $unitValue;
            }

            $period->status = $request->status;
            if ($request->distribution_date) {
                $period->distribution_date = $request->distribution_date;
            }
            $period->save();

            return response()->json([
                'success' => true,
                'message' => 'Profit share period updated successfully',
                'period' => $period
            ]);

        } catch (\Exception $e) {
            Log::error('Profit share update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profit share period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete profit share period.
     */
    public function destroy($id)
    {
        
        if (!auth()->user()->can('delete profit share periods')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete profit share periods.'
            ]);
        }
        try {
            $period = DepartmentProfitShare::findOrFail($id);
            
            // Check if has distributions
            if ($period->distributions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete period with existing distributions.'
                ], 400);
            }
            
            $period->delete();

            return response()->json([
                'success' => true,
                'message' => 'Profit share period deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profit share period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status badge.
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'calculated' => '<span class="badge badge-light-info">Calculated</span>',
            'distributed' => '<span class="badge badge-light-success">Distributed</span>',
            'closed' => '<span class="badge badge-light-secondary">Closed</span>',
        ];
        return $badges[$status] ?? '<span class="badge badge-light-secondary">' . $status . '</span>';
    }
}