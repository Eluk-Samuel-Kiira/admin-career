<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExpenseReportController extends Controller
{
    /**
     * Display expense reports dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Get base currency
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        // Get date range - last 12 months
        $startDate = Carbon::now()->subMonths(12)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Summary statistics
        $summary = $this->getSummaryStatistics($startDate, $endDate);
        
        // Monthly trends
        $monthlyTrends = $this->getMonthlyTrends($startDate, $endDate);
        
        // Top categories
        $topCategories = $this->getTopCategories($startDate, $endDate, 10);
        
        // Top vendors
        $topVendors = $this->getTopVendors($startDate, $endDate, 10);
        
        // Status breakdown
        $statusBreakdown = $this->getStatusBreakdown($startDate, $endDate);
        
        // Recent expenses with pagination
        $recentExpenses = Expense::with(['category', 'department', 'paymentMethod', 'employee'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(10);
        
        return view('reports.expenses.index', compact(
            'summary',
            'monthlyTrends',
            'topCategories',
            'topVendors',
            'statusBreakdown',
            'recentExpenses',
            'startDate',
            'endDate',
            'baseCurrency'
        ));
    }

    /**
     * Expense Summary Report
     */
    public function summary(Request $request)
    {
        $user = auth()->user();
        
        // Get filter parameters
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $departmentId = $request->get('department_id');
        $paymentMethodId = $request->get('payment_method_id');
        $paymentStatus = $request->get('payment_status');
        $employeeId = $request->get('employee_id');
        $minAmount = $request->get('min_amount');
        $maxAmount = $request->get('max_amount');
        $perPage = $request->get('per_page', 20);
        
        // Build query
        $query = Expense::with(['category', 'department', 'paymentMethod', 'employee', 'creator']);
        
        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }
        
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }
        
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        if ($minAmount) {
            $query->where('total_amount', '>=', $minAmount * 100);
        }
        
        if ($maxAmount) {
            $query->where('total_amount', '<=', $maxAmount * 100);
        }
        
        // Get paginated expenses
        $expenses = $query->orderBy('date', 'desc')->paginate($perPage);
        
        // Get all expenses for summary (without pagination)
        $allExpenses = clone $query;
        $allExpenses = $allExpenses->get();
        
        // Summary statistics
        $summary = [
            'total_expenses' => $allExpenses->count(),
            'total_amount' => $allExpenses->sum('total_amount'),
            'total_tax' => $allExpenses->sum('tax_amount'),
            'average_amount' => $allExpenses->count() > 0 ? $allExpenses->avg('total_amount') : 0,
            'max_amount' => $allExpenses->max('total_amount') ?? 0,
            'min_amount' => $allExpenses->min('total_amount') ?? 0,
        ];
        
        // Convert summary amounts to UGX display
        $summary['total_amount_display'] = 'UGX ' . number_format($summary['total_amount'] / 100, 0);
        $summary['total_tax_display'] = 'UGX ' . number_format($summary['total_tax'] / 100, 0);
        $summary['average_amount_display'] = 'UGX ' . number_format($summary['average_amount'] / 100, 0);
        $summary['max_amount_display'] = 'UGX ' . number_format($summary['max_amount'] / 100, 0);
        $summary['min_amount_display'] = 'UGX ' . number_format($summary['min_amount'] / 100, 0);
        
        // Daily breakdown
        $dailyBreakdown = $allExpenses->groupBy(function($expense) {
            return $expense->date->format('Y-m-d');
        })->map(function($items, $date) {
            $total = $items->sum('total_amount');
            return [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('M d, Y'),
                'count' => $items->count(),
                'total' => $total,
                'total_display' => 'UGX ' . number_format($total / 100, 0),
                'average' => $items->avg('total_amount'),
                'average_display' => 'UGX ' . number_format($items->avg('total_amount') / 100, 0),
            ];
        })->values()->sortBy('date');
        
        // Category breakdown - FIXED PERCENTAGE CALCULATION
        $categoryData = [];
        $grandTotal = 0;
        
        // First, group by category and calculate totals
        foreach ($allExpenses as $expense) {
            $categoryId = $expense->category_id;
            $categoryName = $expense->category?->name ?? 'Uncategorized';
            $categoryCode = $expense->category?->code ?? 'N/A';
            
            if (!isset($categoryData[$categoryId])) {
                $categoryData[$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'category_code' => $categoryCode,
                    'count' => 0,
                    'total' => 0,
                    'percentage' => 0,
                ];
            }
            
            $categoryData[$categoryId]['count']++;
            $categoryData[$categoryId]['total'] += $expense->total_amount;
            $grandTotal += $expense->total_amount;
        }
        
        // Now calculate percentages and format
        $categoryBreakdown = collect($categoryData)->map(function($item) use ($grandTotal) {
            $item['percentage'] = $grandTotal > 0 ? ($item['total'] / $grandTotal) * 100 : 0;
            $item['total_display'] = 'UGX ' . number_format($item['total'] / 100, 0);
            return $item;
        })->sortByDesc('total')->values();
        
        // Department breakdown
        $departmentData = [];
        foreach ($allExpenses as $expense) {
            $deptId = $expense->department_id;
            $deptName = $expense->department?->name ?? 'N/A';
            
            if (!isset($departmentData[$deptId])) {
                $departmentData[$deptId] = [
                    'department_id' => $deptId,
                    'department_name' => $deptName,
                    'count' => 0,
                    'total' => 0,
                ];
            }
            
            $departmentData[$deptId]['count']++;
            $departmentData[$deptId]['total'] += $expense->total_amount;
        }
        
        $departmentBreakdown = collect($departmentData)->map(function($item) {
            $item['total_display'] = 'UGX ' . number_format($item['total'] / 100, 0);
            return $item;
        })->sortByDesc('total')->values();
        
        // Status breakdown
        $statusData = [];
        foreach ($allExpenses as $expense) {
            $status = $expense->payment_status;
            
            if (!isset($statusData[$status])) {
                $statusData[$status] = [
                    'status' => $status,
                    'status_label' => ucfirst($status),
                    'count' => 0,
                    'total' => 0,
                ];
            }
            
            $statusData[$status]['count']++;
            $statusData[$status]['total'] += $expense->total_amount;
        }
        
        $statusBreakdown = collect($statusData)->map(function($item) {
            $item['total_display'] = 'UGX ' . number_format($item['total'] / 100, 0);
            return $item;
        })->values();
        
        // Get filter options
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        $employees = User::whereHas('employee', function($query) {
            $query->where('is_active', true);
        })->orderBy('name')->get();
        
        $statuses = ['pending', 'approved', 'paid', 'cancelled', 'rejected'];
        
        return view('reports.expenses.summary', compact(
            'expenses',
            'summary',
            'dailyBreakdown',
            'categoryBreakdown',
            'departmentBreakdown',
            'statusBreakdown',
            'categories',
            'departments',
            'paymentMethods',
            'employees',
            'statuses',
            'startDate',
            'endDate',
            'categoryId',
            'departmentId',
            'paymentMethodId',
            'paymentStatus',
            'employeeId',
            'minAmount',
            'maxAmount',
            'perPage'
        ));
    }

    /**
     * Expenses by Category Report
     */
    public function byCategory(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        // Get category breakdown with pagination
        $categoryBreakdownQuery = Expense::whereBetween('date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.id',
                'expense_categories.name as category_name',
                'expense_categories.code as category_code',
                'expense_categories.description',
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(expenses.total_amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('AVG(expenses.total_amount) as average_amount'),
                DB::raw('MAX(expenses.total_amount) as max_amount'),
                DB::raw('MIN(expenses.total_amount) as min_amount')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.code', 'expense_categories.description')
            ->orderBy('total_amount', 'desc');
        
        if ($categoryId) {
            $categoryBreakdownQuery->having('expense_categories.id', $categoryId);
        }
        
        $categoryBreakdown = $categoryBreakdownQuery->paginate($perPage);
        
        $totalExpenses = $categoryBreakdown->sum('total_amount');
        
        // Calculate percentages
        foreach ($categoryBreakdown as &$item) {
            $item['percentage'] = $totalExpenses > 0 ? ($item['total_amount'] / $totalExpenses) * 100 : 0;
            $item['total_display'] = $baseCurrency->formatAmount($item['total_amount']);
            $item['average_display'] = $baseCurrency->formatAmount($item['average_amount']);
            $item['max_display'] = $baseCurrency->formatAmount($item['max_amount']);
            $item['min_display'] = $baseCurrency->formatAmount($item['min_amount']);
        }
        
        // Monthly trend by category
        $monthlyTrend = Expense::whereBetween('date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                DB::raw('YEAR(expenses.date) as year'),
                DB::raw('MONTH(expenses.date) as month'),
                'expense_categories.name as category_name',
                DB::raw('SUM(expenses.total_amount) as monthly_total')
            )
            ->groupBy('year', 'month', 'expense_categories.name')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('category_name');
        
        // Categories list for filter
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.expenses.by-category', compact(
            'categoryBreakdown',
            'totalExpenses',
            'monthlyTrend',
            'categories',
            'startDate',
            'endDate',
            'categoryId',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Expenses by Vendor Report
     */
    public function byVendor(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $vendorName = $request->get('vendor_name');
        $categoryId = $request->get('category_id');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '');
        
        if ($vendorName) {
            $query->where('vendor_name', 'like', '%' . $vendorName . '%');
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        // Vendor breakdown with pagination
        $vendorBreakdown = $query->select(
                'vendor_name',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('AVG(total_amount) as average_transaction'),
                DB::raw('MAX(total_amount) as largest_transaction'),
                DB::raw('MIN(total_amount) as smallest_transaction'),
                DB::raw('COUNT(DISTINCT category_id) as categories_used')
            )
            ->groupBy('vendor_name')
            ->orderBy('total_amount', 'desc')
            ->paginate($perPage);
        
        // Format amounts
        foreach ($vendorBreakdown as &$item) {
            $item['total_display'] = $baseCurrency->formatAmount($item['total_amount']);
            $item['average_display'] = $baseCurrency->formatAmount($item['average_transaction']);
            $item['largest_display'] = $baseCurrency->formatAmount($item['largest_transaction']);
            $item['smallest_display'] = $baseCurrency->formatAmount($item['smallest_transaction']);
        }
        
        // Summary statistics
        $summary = [
            'total_vendors' => $vendorBreakdown->total(),
            'total_transactions' => $vendorBreakdown->sum('transaction_count'),
            'total_amount' => $vendorBreakdown->sum('total_amount'),
            'total_tax' => $vendorBreakdown->sum('total_tax'),
            'total_display' => $baseCurrency->formatAmount($vendorBreakdown->sum('total_amount')),
            'avg_transaction' => $vendorBreakdown->avg('average_transaction'),
        ];
        
        // Unique vendors for filter
        $uniqueVendors = Expense::whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->distinct('vendor_name')
            ->orderBy('vendor_name')
            ->pluck('vendor_name');
        
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.expenses.by-vendor', compact(
            'vendorBreakdown',
            'summary',
            'uniqueVendors',
            'categories',
            'startDate',
            'endDate',
            'vendorName',
            'categoryId',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Expenses by Employee Report
     */
    public function byEmployee(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $employeeId = $request->get('employee_id');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('employee_id');
        
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        // Employee breakdown with pagination
        $employeeBreakdown = $query->join('users', 'expenses.employee_id', '=', 'users.id')
            ->leftJoin('employees', 'users.id', '=', 'employees.user_id')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select(
                'users.id as user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'employees.job_title',
                'departments.name as department_name',
                DB::raw('CONCAT(users.first_name, " ", users.last_name) as employee_name'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(expenses.total_amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('AVG(expenses.total_amount) as average_expense'),
                DB::raw('MAX(expenses.total_amount) as max_expense'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "pending" THEN 1 END) as pending_count'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "approved" THEN 1 END) as approved_count'),
                DB::raw('COUNT(CASE WHEN expenses.payment_status = "paid" THEN 1 END) as paid_count')
            )
            ->groupBy(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'employees.job_title',
                'departments.name'
            )
            ->orderBy('total_amount', 'desc')
            ->paginate($perPage);
        
        // Format amounts
        foreach ($employeeBreakdown as &$item) {
            $item['total_display'] = $baseCurrency->formatAmount($item['total_amount']);
            $item['average_display'] = $baseCurrency->formatAmount($item['average_expense']);
            $item['max_display'] = $baseCurrency->formatAmount($item['max_expense']);
        }
        
        // Employees for filter
        $employees = User::whereHas('employee', function($query) {
            $query->where('is_active', true);
        })->orderBy('name')->get();
        
        return view('reports.expenses.by-employee', compact(
            'employeeBreakdown',
            'employees',
            'startDate',
            'endDate',
            'employeeId',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Expenses by Payment Method Report
     */
    public function byPaymentMethod(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $paymentMethodId = $request->get('payment_method_id');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::whereBetween('date', [$startDate, $endDate]);
        
        if ($paymentMethodId) {
            $query->where('payment_method_id', $paymentMethodId);
        }
        
        // Payment method breakdown with pagination
        $methodBreakdown = $query->join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->select(
                'payment_methods.id',
                'payment_methods.name as method_name',
                'payment_methods.type as method_type',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(expenses.total_amount) as total_amount'),
                DB::raw('SUM(expenses.tax_amount) as total_tax'),
                DB::raw('AVG(expenses.total_amount) as average_transaction'),
                DB::raw('MAX(expenses.total_amount) as max_transaction'),
                DB::raw('MIN(expenses.total_amount) as min_transaction')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.type')
            ->orderBy('total_amount', 'desc')
            ->paginate($perPage);
        
        // Format amounts
        foreach ($methodBreakdown as &$item) {
            $item['total_display'] = $baseCurrency->formatAmount($item['total_amount']);
            $item['average_display'] = $baseCurrency->formatAmount($item['average_transaction']);
            $item['max_display'] = $baseCurrency->formatAmount($item['max_transaction']);
            $item['min_display'] = $baseCurrency->formatAmount($item['min_transaction']);
        }
        
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.expenses.by-payment-method', compact(
            'methodBreakdown',
            'paymentMethods',
            'startDate',
            'endDate',
            'paymentMethodId',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Expense Trends Report
     */
    public function trends(Request $request)
    {
        $user = auth()->user();
        
        $period = $request->get('period', 'monthly');
        $year = $request->get('year', date('Y'));
        $categoryId = $request->get('category_id');
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        $trendData = [];
        $growthRates = [];
        
        if ($period === 'monthly') {
            // Monthly trend for the year
            for ($month = 1; $month <= 12; $month++) {
                $query = Expense::whereYear('date', $year)->whereMonth('date', $month);
                
                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }
                
                $total = $query->sum('total_amount');
                $count = $query->count();
                
                $trendData[$month] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('M'),
                    'total' => $total,
                    'total_display' => $baseCurrency->formatAmount($total),
                    'count' => $count,
                    'average' => $count > 0 ? $total / $count : 0,
                ];
            }
            
            // Calculate month-over-month growth
            $prevTotal = 0;
            foreach ($trendData as $month => &$data) {
                if ($prevTotal > 0) {
                    $growth = (($data['total'] - $prevTotal) / $prevTotal) * 100;
                    $data['growth'] = round($growth, 1);
                } else {
                    $data['growth'] = 0;
                }
                $prevTotal = $data['total'];
            }
            
        } elseif ($period === 'quarterly') {
            // Quarterly trend
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                
                $query = Expense::whereYear('date', $year)
                    ->whereMonth('date', '>=', $startMonth)
                    ->whereMonth('date', '<=', $endMonth);
                
                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }
                
                $total = $query->sum('total_amount');
                $count = $query->count();
                
                $trendData[$quarter] = [
                    'quarter' => $quarter,
                    'quarter_label' => "Q{$quarter}",
                    'total' => $total,
                    'total_display' => $baseCurrency->formatAmount($total),
                    'count' => $count,
                    'average' => $count > 0 ? $total / $count : 0,
                ];
            }
        } else {
            // Yearly trend (last 5 years)
            $years = range(date('Y') - 4, date('Y'));
            
            foreach ($years as $yr) {
                $query = Expense::whereYear('date', $yr);
                
                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }
                
                $total = $query->sum('total_amount');
                $count = $query->count();
                
                $trendData[$yr] = [
                    'year' => $yr,
                    'total' => $total,
                    'total_display' => $baseCurrency->formatAmount($total),
                    'count' => $count,
                    'average' => $count > 0 ? $total / $count : 0,
                ];
            }
        }
        
        // Get categories for filter
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $years = range(date('Y') - 5, date('Y'));
        
        return view('reports.expenses.trends', compact(
            'trendData',
            'period',
            'year',
            'categoryId',
            'categories',
            'years',
            'baseCurrency'
        ));
    }

    /**
     * Recurring Expenses Report
     */
    public function recurring(Request $request)
    {
        $user = auth()->user();
        
        $frequency = $request->get('frequency');
        $categoryId = $request->get('category_id');
        $status = $request->get('status', 'active');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::where('is_recurring', true)
            ->with(['category', 'paymentMethod', 'department']);
        
        if ($frequency) {
            $query->where('recurring_frequency', $frequency);
        }
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $today = Carbon::today();
        
        if ($status === 'active') {
            $query->where(function($q) use ($today) {
                $q->where('next_recurring_date', '>=', $today)
                    ->orWhereNull('next_recurring_date');
            });
        } elseif ($status === 'upcoming') {
            $nextWeek = $today->copy()->addWeek();
            $query->whereBetween('next_recurring_date', [$today, $nextWeek]);
        } elseif ($status === 'overdue') {
            $query->where('next_recurring_date', '<', $today);
        }
        
        $recurringExpenses = $query->orderBy('next_recurring_date', 'asc')->paginate($perPage);
        
        // Format amounts
        foreach ($recurringExpenses as &$expense) {
            $expense->formatted_amount = $baseCurrency->formatAmount($expense->total_amount);
        }
        
        // Group by frequency (all items, not just paginated)
        $allRecurring = clone $query;
        $allRecurring = $allRecurring->get();
        
        $byFrequency = $allRecurring->groupBy('recurring_frequency')->map(function($items) use ($baseCurrency) {
            return [
                'count' => $items->count(),
                'total_monthly' => $items->sum('total_amount'),
                'total_monthly_display' => $baseCurrency->formatAmount($items->sum('total_amount')),
                'total_annual' => $items->sum(function($item) {
                    $multiplier = match($item->recurring_frequency) {
                        'weekly' => 52,
                        'monthly' => 12,
                        'quarterly' => 4,
                        'yearly' => 1,
                        default => 12
                    };
                    return $item->total_amount * $multiplier;
                }),
                'total_annual_display' => $baseCurrency->formatAmount($items->sum(function($item) {
                    $multiplier = match($item->recurring_frequency) {
                        'weekly' => 52,
                        'monthly' => 12,
                        'quarterly' => 4,
                        'yearly' => 1,
                        default => 12
                    };
                    return $item->total_amount * $multiplier;
                })),
            ];
        });
        
        // Upcoming in next 30 days
        $upcomingNext30Days = $allRecurring->filter(function($expense) use ($today) {
            if (!$expense->next_recurring_date) return false;
            $nextDate = Carbon::parse($expense->next_recurring_date);
            return $nextDate->between($today, $today->copy()->addDays(30));
        })->sortBy('next_recurring_date');
        
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $frequencies = [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly'
        ];
        
        return view('reports.expenses.recurring', compact(
            'recurringExpenses',
            'byFrequency',
            'upcomingNext30Days',
            'categories',
            'frequencies',
            'frequency',
            'categoryId',
            'status',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Tax Report
     */
    public function taxReport(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        $taxType = $request->get('tax_type', 'all');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::with(['category'])
            ->whereBetween('date', [$startDate, $endDate]);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        if ($taxType === 'taxable') {
            $query->where('tax_amount', '>', 0);
        } elseif ($taxType === 'non_taxable') {
            $query->where('tax_amount', '=', 0);
        }
        
        // Get paginated expenses for the table
        $expenses = $query->orderBy('date', 'desc')->paginate($perPage);
        
        // Get all expenses for summary
        $allExpenses = clone $query;
        $allExpenses = $allExpenses->get();
        
        // Tax summary
        $taxSummary = [
            'total_expenses' => $allExpenses->count(),
            'total_amount' => $allExpenses->sum('total_amount'),
            'total_tax' => $allExpenses->sum('tax_amount'),
            'total_with_tax' => $allExpenses->sum('total_amount') + $allExpenses->sum('tax_amount'),
            'taxable_expenses' => $allExpenses->where('tax_amount', '>', 0)->count(),
            'non_taxable_expenses' => $allExpenses->where('tax_amount', '=', 0)->count(),
        ];
        
        $taxSummary['total_amount_display'] = $baseCurrency->formatAmount($taxSummary['total_amount']);
        $taxSummary['total_tax_display'] = $baseCurrency->formatAmount($taxSummary['total_tax']);
        $taxSummary['total_with_tax_display'] = $baseCurrency->formatAmount($taxSummary['total_with_tax']);
        
        // Tax by category
        $taxByCategory = $allExpenses->groupBy('category_id')->map(function($items) use ($baseCurrency) {
            $category = $items->first()->category;
            return [
                'category_name' => $category?->name ?? 'Uncategorized',
                'expense_count' => $items->count(),
                'subtotal' => $items->sum('total_amount'),
                'tax_total' => $items->sum('tax_amount'),
                'grand_total' => $items->sum('total_amount') + $items->sum('tax_amount'),
                'subtotal_display' => $baseCurrency->formatAmount($items->sum('total_amount')),
                'tax_display' => $baseCurrency->formatAmount($items->sum('tax_amount')),
                'grand_display' => $baseCurrency->formatAmount($items->sum('total_amount') + $items->sum('tax_amount')),
            ];
        })->values();
        
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        
        return view('reports.expenses.tax', compact(
            'taxSummary',
            'taxByCategory',
            'expenses',
            'categories',
            'startDate',
            'endDate',
            'categoryId',
            'taxType',
            'baseCurrency',
            'perPage'
        ));
    }

    /**
     * Budget vs Actual Report
     */
    public function budgetVsActual(Request $request)
    {
        $user = auth()->user();
        
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $categoryId = $request->get('category_id');
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        // Get budgeted categories
        $query = ExpenseCategory::where('is_active', true);
        
        if ($categoryId) {
            $query->where('id', $categoryId);
        }
        
        $budgetedCategories = $query->orderBy('name')->get();
        
        $budgetData = [];
        $totalBudgetMonthly = 0;
        $totalBudgetAnnual = 0;
        $totalActualMonthly = 0;
        $totalActualAnnual = 0;
        
        foreach ($budgetedCategories as $category) {
            // Get actual expenses
            $monthlyActual = Expense::where('category_id', $category->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_amount');
            
            $annualActual = Expense::where('category_id', $category->id)
                ->whereYear('date', $year)
                ->sum('total_amount');
            
            $budgetMonthly = $category->budget_monthly ?? 0;
            $budgetAnnual = $category->budget_annual ?? ($budgetMonthly * 12);
            
            $varianceMonthly = $budgetMonthly - $monthlyActual;
            $varianceAnnual = $budgetAnnual - $annualActual;
            
            $budgetData[] = [
                'category' => $category,
                'budget_monthly' => $budgetMonthly,
                'budget_monthly_display' => $baseCurrency->formatAmount($budgetMonthly),
                'actual_monthly' => $monthlyActual,
                'actual_monthly_display' => $baseCurrency->formatAmount($monthlyActual),
                'variance_monthly' => $varianceMonthly,
                'variance_monthly_display' => $baseCurrency->formatAmount($varianceMonthly),
                'variance_percentage_monthly' => $budgetMonthly > 0 ? ($varianceMonthly / $budgetMonthly) * 100 : 0,
                'budget_annual' => $budgetAnnual,
                'budget_annual_display' => $baseCurrency->formatAmount($budgetAnnual),
                'actual_annual' => $annualActual,
                'actual_annual_display' => $baseCurrency->formatAmount($annualActual),
                'variance_annual' => $varianceAnnual,
                'variance_annual_display' => $baseCurrency->formatAmount($varianceAnnual),
                'variance_percentage_annual' => $budgetAnnual > 0 ? ($varianceAnnual / $budgetAnnual) * 100 : 0,
            ];
            
            $totalBudgetMonthly += $budgetMonthly;
            $totalBudgetAnnual += $budgetAnnual;
            $totalActualMonthly += $monthlyActual;
            $totalActualAnnual += $annualActual;
        }
        
        // Summary
        $summary = [
            'total_budget_monthly' => $totalBudgetMonthly,
            'total_budget_monthly_display' => $baseCurrency->formatAmount($totalBudgetMonthly),
            'total_actual_monthly' => $totalActualMonthly,
            'total_actual_monthly_display' => $baseCurrency->formatAmount($totalActualMonthly),
            'total_variance_monthly' => $totalBudgetMonthly - $totalActualMonthly,
            'total_variance_monthly_display' => $baseCurrency->formatAmount($totalBudgetMonthly - $totalActualMonthly),
            'total_budget_annual' => $totalBudgetAnnual,
            'total_budget_annual_display' => $baseCurrency->formatAmount($totalBudgetAnnual),
            'total_actual_annual' => $totalActualAnnual,
            'total_actual_annual_display' => $baseCurrency->formatAmount($totalActualAnnual),
            'total_variance_annual' => $totalBudgetAnnual - $totalActualAnnual,
            'total_variance_annual_display' => $baseCurrency->formatAmount($totalBudgetAnnual - $totalActualAnnual),
            'under_budget_count' => collect($budgetData)->where('variance_monthly', '>', 0)->count(),
            'over_budget_count' => collect($budgetData)->where('variance_monthly', '<', 0)->count(),
            'on_budget_count' => collect($budgetData)->where('variance_monthly', '==', 0)->count(),
        ];
        
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $years = range(date('Y') - 5, date('Y'));
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return view('reports.expenses.budget-vs-actual', compact(
            'budgetData',
            'summary',
            'categories',
            'years',
            'months',
            'year',
            'month',
            'categoryId',
            'baseCurrency'
        ));
    }

    /**
     * Audit Report
     */
    public function audit(Request $request)
    {
        $user = auth()->user();
        
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $auditType = $request->get('audit_type', 'all');
        $employeeId = $request->get('employee_id');
        $perPage = $request->get('per_page', 20);
        
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::with(['category', 'paymentMethod', 'employee', 'approver', 'department'])
            ->whereBetween('date', [$startDate, $endDate]);
        
        // Apply audit type filters
        switch ($auditType) {
            case 'missing_receipts':
                $query->whereHas('category', function($q) {
                    $q->where('requires_receipt', true);
                })->where(function($q) {
                    $q->whereNull('receipt_url')
                        ->orWhere('receipt_url', '');
                });
                break;
                
            case 'unapproved':
                $query->whereHas('category', function($q) {
                    $q->where('requires_approval', true);
                })->whereNull('approved_at');
                break;
                
            case 'high_value':
                $threshold = $request->get('threshold', 1000000);
                $query->where('total_amount', '>=', $threshold);
                break;
                
            case 'late_submissions':
                $query->where(DB::raw('DATEDIFF(created_at, date)'), '>', 7);
                break;
                
            case 'policy_violations':
                $query->whereHas('category', function($q) {
                    $q->where('requires_receipt', true);
                })->where(function($q) {
                    $q->whereNull('receipt_url')
                        ->orWhere('receipt_url', '');
                });
                break;
        }
        
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        $auditItems = $query->orderBy('date', 'desc')->paginate($perPage);
        
        // Get all items for statistics
        $allItems = clone $query;
        $allItems = $allItems->get();
        
        // Format amounts
        foreach ($auditItems as &$item) {
            $item->formatted_amount = $baseCurrency->formatAmount($item->total_amount);
        }
        
        // Audit statistics
        $auditStats = [
            'total_items' => $allItems->count(),
            'total_amount' => $allItems->sum('total_amount'),
            'total_amount_display' => $baseCurrency->formatAmount($allItems->sum('total_amount')),
            'missing_receipts' => $allItems->filter(function($item) {
                return $item->category && $item->category->requires_receipt && empty($item->receipt_url);
            })->count(),
            'unapproved' => $allItems->filter(function($item) {
                return $item->category && $item->category->requires_approval && !$item->approved_at;
            })->count(),
            'high_value' => $allItems->filter(function($item) use ($request) {
                $threshold = $request->get('threshold', 1000000);
                return $item->total_amount >= $threshold;
            })->count(),
        ];
        
        // Group by category
        $byCategory = $allItems->groupBy(function($item) {
            return $item->category ? $item->category->name : 'Uncategorized';
        })->map(function($items, $category) use ($baseCurrency) {
            return [
                'category' => $category,
                'count' => $items->count(),
                'total_amount' => $items->sum('total_amount'),
                'total_display' => $baseCurrency->formatAmount($items->sum('total_amount')),
                'missing_receipts' => $items->filter(function($item) {
                    return $item->category && $item->category->requires_receipt && empty($item->receipt_url);
                })->count(),
                'unapproved' => $items->filter(function($item) {
                    return $item->category && $item->category->requires_approval && !$item->approved_at;
                })->count(),
            ];
        })->sortByDesc('count')->values();
        
        // Employees for filter
        $employees = User::whereHas('employee', function($query) {
            $query->where('is_active', true);
        })->orderBy('name')->get();
        
        $auditTypes = [
            'all' => 'All Items',
            'missing_receipts' => 'Missing Receipts',
            'unapproved' => 'Unapproved Expenses',
            'high_value' => 'High Value Expenses',
            'late_submissions' => 'Late Submissions',
            'policy_violations' => 'Policy Violations'
        ];
        
        return view('reports.expenses.audit', compact(
            'auditItems',
            'auditStats',
            'byCategory',
            'employees',
            'startDate',
            'endDate',
            'auditType',
            'employeeId',
            'auditTypes',
            'baseCurrency',
            'perPage'
        ));
    }



    /**
     * Export Report
     */
    public function export(Request $request, $type)
    {
        $user = auth()->user();
        $format = $request->get('format', 'csv');
        
        // Get data based on type
        $data = [];
        $filename = "expense-report-{$type}-" . date('Y-m-d');
        
        switch ($type) {
            case 'summary':
                $data = $this->getSummaryData($request);
                break;
            case 'category':
                $data = $this->getCategoryData($request);
                break;
            case 'vendor':
                $data = $this->getVendorData($request);
                break;
            case 'employee':
                $data = $this->getEmployeeData($request);
                break;
            default:
                return redirect()->back()->with('error', 'Invalid report type');
        }
        
        if ($format === 'csv') {
            return $this->exportCSV($data, $filename);
        } elseif ($format === 'excel') {
            // For Excel export, you can use Maatwebsite\Excel package
            // For now, we'll return CSV
            return $this->exportCSV($data, $filename);
        }
        
        return redirect()->back()->with('error', 'Unsupported export format');
    }

    /**
     * Export data as CSV
     */
    private function exportCSV($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];
        
        return response()->stream(function() use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            if (!empty($data)) {
                fputcsv($handle, array_keys((array)$data[0]));
            }
            
            // Add rows
            foreach ($data as $row) {
                fputcsv($handle, (array)$row);
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Get summary data for export
     */
    private function getSummaryData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $categoryId = $request->get('category_id');
        
        $query = Expense::with(['category', 'department'])
            ->whereBetween('date', [$startDate, $endDate]);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        return $query->get()->map(function($expense) {
            return [
                'Date' => $expense->date->format('Y-m-d'),
                'Expense #' => $expense->expense_number,
                'Description' => $expense->description,
                'Category' => $expense->category?->name ?? 'N/A',
                'Department' => $expense->department?->name ?? 'N/A',
                'Vendor' => $expense->vendor_name ?? 'N/A',
                'Amount' => $expense->total_amount,
                'Tax' => $expense->tax_amount,
                'Status' => ucfirst($expense->payment_status),
            ];
        })->toArray();
    }

    /**
     * Get category data for export
     */
    private function getCategoryData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as Category',
                DB::raw('COUNT(*) as Count'),
                DB::raw('SUM(expenses.total_amount) as Total'),
                DB::raw('AVG(expenses.total_amount) as Average'),
                DB::raw('MAX(expenses.total_amount) as Max'),
                DB::raw('MIN(expenses.total_amount) as Min')
            )
            ->groupBy('expense_categories.name')
            ->orderBy('Total', 'desc')
            ->get()
            ->toArray();
        
        return $data;
    }

    /**
     * Get vendor data for export
     */
    private function getVendorData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->select(
                'vendor_name as Vendor',
                DB::raw('COUNT(*) as Transactions'),
                DB::raw('SUM(total_amount) as Total'),
                DB::raw('AVG(total_amount) as Average'),
                DB::raw('MAX(total_amount) as Max'),
                DB::raw('MIN(total_amount) as Min')
            )
            ->groupBy('vendor_name')
            ->orderBy('Total', 'desc')
            ->get()
            ->toArray();
        
        return $data;
    }

    /**
     * Get employee data for export
     */
    private function getEmployeeData(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('employee_id')
            ->join('users', 'expenses.employee_id', '=', 'users.id')
            ->select(
                DB::raw('CONCAT(users.first_name, " ", users.last_name) as Employee'),
                DB::raw('COUNT(*) as Expenses'),
                DB::raw('SUM(expenses.total_amount) as Total'),
                DB::raw('AVG(expenses.total_amount) as Average'),
                DB::raw('MAX(expenses.total_amount) as Max'),
                DB::raw('MIN(expenses.total_amount) as Min')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderBy('Total', 'desc')
            ->get()
            ->toArray();
        
        return $data;
    }

    /**
     * Get summary statistics
     */
    private function getSummaryStatistics($startDate, $endDate)
    {
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $query = Expense::whereBetween('date', [$startDate, $endDate]);
        
        $total = $query->sum('total_amount');
        $count = $query->count();
        $tax = $query->sum('tax_amount');
        
        return [
            'total' => $total,
            'total_display' => $baseCurrency->formatAmount($total),
            'count' => $count,
            'tax' => $tax,
            'tax_display' => $baseCurrency->formatAmount($tax),
            'average' => $count > 0 ? $total / $count : 0,
            'average_display' => $baseCurrency->formatAmount($count > 0 ? $total / $count : 0),
        ];
    }

    /**
     * Get monthly trends
     */
    private function getMonthlyTrends($startDate, $endDate)
    {
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        $trends = [];
        
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $month = $currentDate->format('Y-m');
            $monthLabel = $currentDate->format('M Y');
            
            $query = Expense::whereYear('date', $currentDate->year)
                ->whereMonth('date', $currentDate->month);
            
            $total = $query->sum('total_amount');
            $count = $query->count();
            
            $trends[$month] = [
                'month' => $month,
                'month_label' => $monthLabel,
                'total' => $total,
                'total_display' => $baseCurrency->formatAmount($total),
                'count' => $count,
            ];
            
            $currentDate->addMonth();
        }
        
        return $trends;
    }

    /**
     * Get top categories
     */
    private function getTopCategories($startDate, $endDate, $limit = 10)
    {
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category_name',
                DB::raw('SUM(expenses.total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('expense_categories.name')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();
        
        foreach ($data as &$item) {
            $item['total_display'] = $baseCurrency->formatAmount($item['total']);
        }
        
        return $data;
    }

    /**
     * Get top vendors
     */
    private function getTopVendors($startDate, $endDate, $limit = 10)
    {
        $baseCurrency = Currency::where('is_default', true)->first() ?? Currency::where('code', 'UGX')->first();
        
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('vendor_name')
            ->where('vendor_name', '!=', '')
            ->select(
                'vendor_name',
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('vendor_name')
            ->orderBy('total', 'desc')
            ->limit($limit)
            ->get();
        
        foreach ($data as &$item) {
            $item['total_display'] = $baseCurrency->formatAmount($item['total']);
        }
        
        return $data;
    }

    /**
     * Get status breakdown
     */
    private function getStatusBreakdown($startDate, $endDate)
    {
        $data = Expense::whereBetween('date', [$startDate, $endDate])
            ->select(
                'payment_status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('payment_status')
            ->get();
        
        $result = [];
        foreach ($data as $item) {
            $result[$item->payment_status] = [
                'status' => $item->payment_status,
                'label' => ucfirst($item->payment_status),
                'count' => $item->count,
                'total' => $item->total,
            ];
        }
        
        return $result;
    }
}