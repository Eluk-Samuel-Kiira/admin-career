@extends('layouts.admin')

@section('title', 'Income Statement')
@section('page_title', 'Income Statement')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Reports</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Income Statement</li>
@endsection

@section('content')
@can('view income statement')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <label class="fw-semibold fs-6 mb-2">Period</label>
                    <select id="periodSelect" class="form-select form-select-solid w-120px">
                        <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Quarterly</option>
                        <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
                <div id="monthDiv">
                    <label class="fw-semibold fs-6 mb-2">Month</label>
                    <select id="monthSelect" class="form-select form-select-solid w-140px">
                        @foreach($months as $key => $name)
                            <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="quarterDiv" style="display: none;">
                    <label class="fw-semibold fs-6 mb-2">Quarter</label>
                    <select id="quarterSelect" class="form-select form-select-solid w-140px">
                        @foreach($quarters as $key => $name)
                            <option value="{{ $key }}" {{ $quarter == $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Year</label>
                    <select id="yearSelect" class="form-select form-select-solid w-100px">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                    <select id="paymentMethodFilter" class="form-select form-select-solid w-180px">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ $paymentMethodId == $method->id ? 'selected' : '' }}>
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Source</label>
                    <select id="sourceFilter" class="form-select form-select-solid w-160px">
                        <option value="">All Sources</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" {{ $sourceId == $source->id ? 'selected' : '' }}>
                                {{ $source->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Purpose</label>
                    <select id="purposeFilter" class="form-select form-select-solid w-160px">
                        <option value="">All Purposes</option>
                        @foreach($purposes as $purpose)
                            <option value="{{ $purpose->id }}" {{ $purposeId == $purpose->id ? 'selected' : '' }}>
                                {{ $purpose->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Currency</label>
                    <select id="baseCurrency" class="form-select form-select-solid w-120px">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}" {{ $baseCurrencyCode == $currency->code ? 'selected' : '' }}>
                                {{ $currency->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Department</label>
                    <select id="departmentFilter" class="form-select form-select-solid w-160px">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Depositor</label>
                    <select id="depositorFilter" class="form-select form-select-solid w-180px">
                        <option value="">All Depositors</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $depositorId == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex gap-2">
                <button id="applyFiltersBtn" class="btn btn-primary">
                    <i class="ki-duotone ki-filter fs-2"></i> Apply
                </button>
                <button id="resetFiltersBtn" class="btn btn-light">
                    <i class="ki-duotone ki-arrows-circle fs-2"></i> Reset
                </button>
                <div class="dropdown">
                    <button class="btn btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="ki-duotone ki-file-down fs-2"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportToExcel()">Export to Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="window.print()">Print Report</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-success me-3">
                                <i class="ki-duotone ki-dollar fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Revenue</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_revenue'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Period: {{ $summary['start_date'] }} - {{ $summary['end_date'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-danger me-3">
                                <i class="ki-duotone ki-arrow-down fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Expenses</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_expenses'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Period: {{ $summary['start_date'] }} - {{ $summary['end_date'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-{{ $summary['net_income_color'] }} me-3">
                                <i class="ki-duotone ki-chart-line fs-2x text-{{ $summary['net_income_color'] }}">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Net Income</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-{{ $summary['net_income_color'] }}">{{ $summary['net_income'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Calculation: {{ $summary['total_revenue'] }} - {{ $summary['total_expenses'] }} = {{ $summary['net_income'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xl-6">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Revenue Breakdown</span>
                            <span class="text-muted fs-7 ms-2">By Source</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" style="height: 300px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Expense Breakdown</span>
                            <span class="text-muted fs-7 ms-2">By Category</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="expenseChart" style="height: 300px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue Breakdown Table -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Revenue Breakdown by Source</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Source</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueBreakdown as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end fw-bold text-success">{{ $item['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($item['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-success" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No revenue data available</td></tr>
                            @endforelse
                            @if(!empty($revenueBreakdown))
                            <tr class="fw-bold">
                                <td>Total Revenue</td>
                                <td class="text-end">{{ $summary['total_revenue'] }}</td>
                                <td class="text-end">100%</td>
                                <td></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Revenue by Department Breakdown Table - NEW -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Revenue Breakdown by Department</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Department</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueByDepartmentBreakdown as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end fw-bold text-success">{{ $item['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($item['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-success" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No revenue data available by department</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Revenue by Depositor Breakdown Table - NEW -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Revenue Breakdown by Depositor</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Depositor</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueByDepositorBreakdown as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end fw-bold text-success">{{ $item['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($item['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-success" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No revenue data available by depositor</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Expenses by Department Breakdown Table - NEW -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Expense Breakdown by Department</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Department</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expensesByDepartmentBreakdown as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end fw-bold text-danger">{{ $item['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($item['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-danger" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No expense data available by department</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Expense Breakdown Table -->
        <div class="card card-flush">
            <div class="card-header">
                <h3 class="card-title">Expense Breakdown by Category</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Category</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseBreakdown as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end fw-bold text-danger">{{ $item['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($item['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-danger" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No expense data available</td></tr>
                            @endforelse
                            @if(!empty($expenseBreakdown))
                            <tr class="fw-bold">
                                <td>Total Expenses</td>
                                <td class="text-end">{{ $summary['total_expenses'] }}</td>
                                <td class="text-end">100%</td>
                                <td></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Profitability Summary -->
        <div class="card card-flush mt-5">
            <div class="card-header">
                <h3 class="card-title">Profitability Analysis</h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4 text-center">
                        <div class="p-5 bg-light-success rounded">
                            <span class="text-muted">Total Revenue</span>
                            <div class="fs-2hx fw-bold text-success">{{ $summary['total_revenue'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="p-5 bg-light-danger rounded">
                            <span class="text-muted">Total Expenses</span>
                            <div class="fs-2hx fw-bold text-danger">{{ $summary['total_expenses'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="p-5 bg-light-{{ $summary['net_income_color'] }} rounded">
                            <span class="text-muted">Net Income</span>
                            <div class="fs-2hx fw-bold text-{{ $summary['net_income_color'] }}">{{ $summary['net_income'] }}</div>
                            <span class="text-muted fs-7">{{ $summary['total_revenue'] }} - {{ $summary['total_expenses'] }} = {{ $summary['net_income'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let revenueChart, expenseChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    // Period toggle
    document.getElementById('periodSelect')?.addEventListener('change', function() {
        if (this.value === 'month') {
            document.getElementById('monthDiv').style.display = 'block';
            document.getElementById('quarterDiv').style.display = 'none';
        } else if (this.value === 'quarter') {
            document.getElementById('monthDiv').style.display = 'none';
            document.getElementById('quarterDiv').style.display = 'block';
        } else {
            document.getElementById('monthDiv').style.display = 'none';
            document.getElementById('quarterDiv').style.display = 'none';
        }
    });
    
    // Trigger period change on load
    document.getElementById('periodSelect')?.dispatchEvent(new Event('change'));
    
    // Apply filters
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        applyFilters();
    });
    
    document.getElementById('resetFiltersBtn')?.addEventListener('click', function() {
        document.getElementById('periodSelect').value = 'month';
        document.getElementById('monthSelect').value = '{{ date("m") }}';
        document.getElementById('quarterSelect').value = '{{ ceil(date("m") / 3) }}';
        document.getElementById('yearSelect').value = '{{ date("Y") }}';
        document.getElementById('paymentMethodFilter').value = '';
        document.getElementById('sourceFilter').value = '';
        document.getElementById('purposeFilter').value = '';
        document.getElementById('departmentFilter').value = '';      // NEW
        document.getElementById('depositorFilter').value = '';        // NEW
        document.getElementById('baseCurrency').value = 'USD';
        document.getElementById('periodSelect')?.dispatchEvent(new Event('change'));
        applyFilters();
    });
});

function initCharts() {
    const revenueData = @json($revenueChartData);
    const expenseData = @json($expenseChartData);
    
    // Revenue Donut Chart
    const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
    if (revenueCtx && revenueData.length > 0) {
        revenueChart = new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: revenueData.map(item => item.name),
                datasets: [{
                    data: revenueData.map(item => item.value),
                    backgroundColor: ['#2BA58F', '#7239EA', '#F6C000', '#F1416C', '#50CD89', '#1E9FF2', '#FF9800', '#9C27B0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: {{ $baseCurrencyCode }} ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Expense Donut Chart
    const expenseCtx = document.getElementById('expenseChart')?.getContext('2d');
    if (expenseCtx && expenseData.length > 0) {
        expenseChart = new Chart(expenseCtx, {
            type: 'doughnut',
            data: {
                labels: expenseData.map(item => item.name),
                datasets: [{
                    data: expenseData.map(item => item.value),
                    backgroundColor: ['#F1416C', '#F6C000', '#7239EA', '#2BA58F', '#50CD89', '#1E9FF2'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: {{ $baseCurrencyCode }} ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

function applyFilters() {
    const params = new URLSearchParams();
    params.append('period', document.getElementById('periodSelect')?.value || 'month');
    params.append('month', document.getElementById('monthSelect')?.value || '');
    params.append('quarter', document.getElementById('quarterSelect')?.value || '');
    params.append('year', document.getElementById('yearSelect')?.value || '');
    params.append('payment_method_id', document.getElementById('paymentMethodFilter')?.value || '');
    params.append('source_id', document.getElementById('sourceFilter')?.value || '');
    params.append('purpose_id', document.getElementById('purposeFilter')?.value || '');
    params.append('department_id', document.getElementById('departmentFilter')?.value || '');      // NEW
    params.append('depositor_id', document.getElementById('depositorFilter')?.value || '');        // NEW
    params.append('base_currency', document.getElementById('baseCurrency')?.value || 'USD');
    
    window.location.href = `{{ route('accounting.income-statement') }}?${params.toString()}`;
}

function exportToExcel() {
    const rows = [];
    rows.push(['Income Statement Report']);
    rows.push(['Period:', document.getElementById('periodSelect')?.options[document.getElementById('periodSelect')?.selectedIndex]?.text || '']);
    rows.push(['Date Range:', '{{ $summary["start_date"] }} - {{ $summary["end_date"] }}']);
    rows.push([]);
    
    // Revenue by Source
    rows.push(['REVENUE BREAKDOWN BY SOURCE']);
    rows.push(['Source', 'Amount', 'Percentage']);
    @foreach($revenueBreakdown as $item)
    rows.push(['{{ $item["name"] }}', '{{ $item["amount_formatted"] }}', '{{ number_format($item["percentage"], 1) }}%']);
    @endforeach
    rows.push(['Total Revenue', '{{ $summary["total_revenue"] }}', '100%']);
    rows.push([]);
    
    // Revenue by Department - NEW
    rows.push(['REVENUE BREAKDOWN BY DEPARTMENT']);
    rows.push(['Department', 'Amount', 'Percentage']);
    @foreach($revenueByDepartmentBreakdown as $item)
    rows.push(['{{ $item["name"] }}', '{{ $item["amount_formatted"] }}', '{{ number_format($item["percentage"], 1) }}%']);
    @endforeach
    rows.push(['Total Revenue', '{{ $summary["total_revenue"] }}', '100%']);
    rows.push([]);
    
    // Revenue by Depositor - NEW
    rows.push(['REVENUE BREAKDOWN BY DEPOSITOR']);
    rows.push(['Depositor', 'Amount', 'Percentage']);
    @foreach($revenueByDepositorBreakdown as $item)
    rows.push(['{{ $item["name"] }}', '{{ $item["amount_formatted"] }}', '{{ number_format($item["percentage"], 1) }}%']);
    @endforeach
    rows.push(['Total Revenue', '{{ $summary["total_revenue"] }}', '100%']);
    rows.push([]);
    
    // Expense by Category
    rows.push(['EXPENSE BREAKDOWN BY CATEGORY']);
    rows.push(['Category', 'Amount', 'Percentage']);
    @foreach($expenseBreakdown as $item)
    rows.push(['{{ $item["name"] }}', '{{ $item["amount_formatted"] }}', '{{ number_format($item["percentage"], 1) }}%']);
    @endforeach
    rows.push(['Total Expenses', '{{ $summary["total_expenses"] }}', '100%']);
    rows.push([]);
    
    // Expense by Department - NEW
    rows.push(['EXPENSE BREAKDOWN BY DEPARTMENT']);
    rows.push(['Department', 'Amount', 'Percentage']);
    @foreach($expensesByDepartmentBreakdown as $item)
    rows.push(['{{ $item["name"] }}', '{{ $item["amount_formatted"] }}', '{{ number_format($item["percentage"], 1) }}%']);
    @endforeach
    rows.push(['Total Expenses', '{{ $summary["total_expenses"] }}', '100%']);
    rows.push([]);
    
    // Summary
    rows.push(['SUMMARY']);
    rows.push(['Total Revenue', '{{ $summary["total_revenue"] }}']);
    rows.push(['Total Expenses', '{{ $summary["total_expenses"] }}']);
    rows.push(['Net Income', '{{ $summary["net_income"] }}']);
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'income_statement.csv';
    link.click();
}
</script>
@endpush