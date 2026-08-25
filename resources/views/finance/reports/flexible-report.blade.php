@extends('layouts.admin')

@section('title', 'General Financial Report')
@section('page_title', 'General Financial Report')

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
    <li class="breadcrumb-item text-muted">General Financial Report</li>
@endsection

@section('content')
@can('view flexible reports')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-end gap-3 flex-wrap">
                <!-- Date Range -->
                <div>
                    <label class="fw-semibold fs-6 mb-2">Date Range</label>
                    <select id="rangeSelect" class="form-select form-select-solid w-160px">
                        <option value="today" {{ $range == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $range == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="last_7_days" {{ $range == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="last_30_days" {{ $range == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_week" {{ $range == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="last_week" {{ $range == 'last_week' ? 'selected' : '' }}>Last Week</option>
                        <option value="this_month" {{ $range == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $range == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ $range == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ $range == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="last_year" {{ $range == 'last_year' ? 'selected' : '' }}>Last Year</option>
                        <option value="custom" {{ $range == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>
                
                <!-- Custom Date Range -->
                <div id="customDateDiv" style="display: {{ $range == 'custom' ? 'block' : 'none' }};">
                    <label class="fw-semibold fs-6 mb-2">Custom Range</label>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="date" id="customStartDate" class="form-control form-control-solid w-150px" value="{{ $startDate }}">
                        <span class="text-muted">to</span>
                        <input type="date" id="customEndDate" class="form-control form-control-solid w-150px" value="{{ $endDate }}">
                    </div>
                </div>
                
                <!-- Month & Year Selection -->
                <div id="monthYearDiv" style="display: {{ $range == 'this_month' || $range == 'last_month' ? 'block' : 'none' }};">
                    <label class="fw-semibold fs-6 mb-2">Month & Year</label>
                    <div class="d-flex gap-2">
                        <select id="monthSelect" class="form-select form-select-solid w-140px">
                            @foreach($months as $key => $name)
                                <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select id="yearSelect" class="form-select form-select-solid w-100px">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Quarter & Year Selection -->
                <div id="quarterYearDiv" style="display: {{ $range == 'this_quarter' ? 'block' : 'none' }};">
                    <label class="fw-semibold fs-6 mb-2">Quarter & Year</label>
                    <div class="d-flex gap-2">
                        <select id="quarterSelect" class="form-select form-select-solid w-120px">
                            @foreach($quarters as $key => $name)
                                <option value="{{ $key }}" {{ $quarter == $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <select id="quarterYearSelect" class="form-select form-select-solid w-100px">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Currency -->
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
                
                <!-- Apply Button -->
                <div>
                    <button id="applyFiltersBtn" class="btn btn-primary">
                        <i class="ki-duotone ki-filter fs-2"></i> Apply
                    </button>
                </div>
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex gap-2">
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
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-primary me-3">
                                <i class="ki-duotone ki-brifecase-timer fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Transactions</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($summary['total_transactions']) }}</span>
                                    <span class="text-muted ms-2">Transactions</span>
                                </div>
                                <span class="text-muted fs-7">{{ $summary['range_label'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-success me-3">
                                <i class="ki-duotone ki-dollar fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Deposits</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-success">{{ $summary['total_deposits'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Money In</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-danger me-3">
                                <i class="ki-duotone ki-arrow-down fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Withdrawals</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-danger">{{ $summary['total_withdrawals'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Money Out</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-{{ $summary['net_cash_flow_color'] }} me-3">
                                <i class="ki-duotone ki-chart-line fs-2x text-{{ $summary['net_cash_flow_color'] }}">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Net Cash Flow</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-{{ $summary['net_cash_flow_color'] }}">{{ $summary['net_cash_flow'] }}</span>
                                </div>
                                <span class="text-muted fs-7">{{ $summary['total_deposits'] }} - {{ $summary['total_withdrawals'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Secondary Stats -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Days in Period</span>
                            <span class="fw-bold">{{ $summary['days_in_period'] }} days</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Average Daily Deposit</span>
                            <span class="fw-bold text-success">{{ $summary['average_daily_deposit'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Average Daily Withdrawal</span>
                            <span class="fw-bold text-danger">{{ $summary['average_daily_withdrawal'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Total Fees</span>
                            <span class="fw-bold">{{ $summary['total_fees'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Average Transaction</span>
                            <span class="fw-bold">{{ $summary['average_transaction'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Period</span>
                            <span class="fw-bold">{{ $summary['start_date'] }} - {{ $summary['end_date'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body">
                        <div class="progress h-10px mb-3">
                            @php
                                $totalFlow = $summary['total_deposits_raw'] ?? 0;
                                $depositPercent = $totalFlow > 0 ? ($summary['total_deposits_raw'] / $totalFlow) * 100 : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $depositPercent }}%"></div>
                            <div class="progress-bar bg-danger" style="width: {{ 100 - $depositPercent }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="ki-duotone ki-arrow-up text-success"></i> Deposits</span>
                            <span class="fw-bold">{{ $summary['total_deposits'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span><i class="ki-duotone ki-arrow-down text-danger"></i> Withdrawals</span>
                            <span class="fw-bold">{{ $summary['total_withdrawals'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Breakdown Chart -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xl-12">
                <div class="card card-flush">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Daily Breakdown</span>
                            <span class="text-muted fs-7 ms-2">{{ $baseCurrencyCode }}</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" style="height: 350px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Breakdown Table -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Daily Breakdown</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Date</th>
                                <th class="text-end">Deposits</th>
                                <th class="text-end">Withdrawals</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyBreakdown as $day)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $day['date_formatted'] }}</span>
                                    <span class="text-muted fs-7 ms-1">{{ $day['day_name'] }}</span>
                                </td>
                                <td class="text-end text-success fw-bold">{{ $day['deposits_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $day['withdrawals_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $day['net_flow_color'] }}">{{ $day['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $day['count'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No data available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Revenue by Source -->
        @if(count($sourceBreakdown) > 0)
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Revenue by Source</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Source</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Percentage</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sourceBreakdown as $source)
                            <tr>
                                <td>{{ $source['name'] }}</td>
                                <td class="text-end text-success fw-bold">{{ $source['amount_formatted'] }}</td>
                                <td class="text-end">{{ number_format($source['percentage'], 1) }}%</td>
                                <td class="w-200px">
                                    <div class="progress h-6px">
                                        <div class="progress-bar bg-success" style="width: {{ $source['percentage'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Payment Method Breakdown -->
        @if(count($methodBreakdown) > 0)
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Payment Method Breakdown</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Payment Method</th>
                                <th class="text-end">Deposits</th>
                                <th class="text-end">Withdrawals</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($methodBreakdown as $method)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-30px me-2">
                                            <span class="symbol-label bg-light-{{ $method['net_flow_color'] }}">
                                                <i class="ki-duotone ki-wallet fs-4"></i>
                                            </span>
                                        </div>
                                        {{ $method['name'] }}
                                    </div>
                                    <div class="text-muted fs-7">{{ ucfirst($method['type']) }}</div>
                                </td>
                                <td class="text-end text-success fw-bold">{{ $method['deposits_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $method['withdrawals_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $method['net_flow_color'] }}">{{ $method['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $method['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Department Breakdown - NEW -->
        @if(count($departmentBreakdown) > 0)
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Department Breakdown</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Department</th>
                                <th class="text-end">Deposits</th>
                                <th class="text-end">Withdrawals</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentBreakdown as $dept)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $dept['name'] }}</div>
                                    @if($dept['code'])
                                        <div class="text-muted fs-7">{{ $dept['code'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-bold">{{ $dept['deposits_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $dept['withdrawals_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $dept['net_flow_color'] }}">{{ $dept['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $dept['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Depositor Breakdown - NEW -->
        @if(count($depositorBreakdown) > 0)
        <div class="card card-flush">
            <div class="card-header">
                <h3 class="card-title">Depositor Breakdown</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Depositor</th>
                                <th class="text-end">Deposits</th>
                                <th class="text-end">Withdrawals</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($depositorBreakdown as $depositor)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $depositor['name'] }}</div>
                                    @if($depositor['email'])
                                        <div class="text-muted fs-7">{{ $depositor['email'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-bold">{{ $depositor['deposits_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $depositor['withdrawals_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $depositor['net_flow_color'] }}">{{ $depositor['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $depositor['count'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let dailyChart;

document.addEventListener('DOMContentLoaded', function() {
    initChart();
    
    // Range selector change handler
    document.getElementById('rangeSelect')?.addEventListener('change', function() {
        const customDiv = document.getElementById('customDateDiv');
        const monthYearDiv = document.getElementById('monthYearDiv');
        const quarterYearDiv = document.getElementById('quarterYearDiv');
        
        customDiv.style.display = this.value === 'custom' ? 'flex' : 'none';
        monthYearDiv.style.display = (this.value === 'this_month' || this.value === 'last_month') ? 'flex' : 'none';
        quarterYearDiv.style.display = this.value === 'this_quarter' ? 'flex' : 'none';
        
        if (this.value !== 'custom') {
            applyFilters();
        }
    });
    
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        applyFilters();
    });
});

function initChart() {
    const chartData = @json($chartData);
    const baseCurrency = '{{ $baseCurrencyCode }}';
    
    const ctx = document.getElementById('dailyChart').getContext('2d');
    dailyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.dates,
            datasets: [
                {
                    label: `Deposits (${baseCurrency})`,
                    data: chartData.deposits,
                    backgroundColor: '#50CD89',
                    borderRadius: 8
                },
                {
                    label: `Withdrawals (${baseCurrency})`,
                    data: chartData.withdrawals,
                    backgroundColor: '#F1416C',
                    borderRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${baseCurrency} ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    title: { display: true, text: baseCurrency },
                    ticks: {
                        callback: function(value) {
                            return baseCurrency + ' ' + value.toLocaleString();
                        }
                    }
                },
                x: { title: { display: true, text: 'Date' } }
            }
        }
    });
}

function applyFilters() {
    const params = new URLSearchParams();
    const range = document.getElementById('rangeSelect')?.value;
    params.append('range', range);
    
    if (range === 'custom') {
        params.append('start_date', document.getElementById('customStartDate')?.value || '');
        params.append('end_date', document.getElementById('customEndDate')?.value || '');
    } else if (range === 'this_month' || range === 'last_month') {
        params.append('month', document.getElementById('monthSelect')?.value || '');
        params.append('year', document.getElementById('yearSelect')?.value || '');
    } else if (range === 'this_quarter') {
        params.append('quarter', document.getElementById('quarterSelect')?.value || '');
        params.append('year', document.getElementById('quarterYearSelect')?.value || '');
    }
    
    params.append('department_id', document.getElementById('departmentFilter')?.value || '');      // NEW
    params.append('depositor_id', document.getElementById('depositorFilter')?.value || '');        // NEW
    params.append('base_currency', document.getElementById('baseCurrency')?.value || 'USD');
    
    window.location.href = `{{ route('accounting.flexible-report') }}?${params.toString()}`;
}

function exportToExcel() {
    const rows = [];
    rows.push(['General Financial Report']);
    rows.push(['Period:', '{{ $summary["range_label"] }}']);
    rows.push(['Date Range:', '{{ $summary["start_date"] }} - {{ $summary["end_date"] }}']);
    rows.push([]);
    rows.push(['SUMMARY']);
    rows.push(['Total Transactions', '{{ $summary["total_transactions"] }}']);
    rows.push(['Total Deposits', '{{ $summary["total_deposits"] }}']);
    rows.push(['Total Withdrawals', '{{ $summary["total_withdrawals"] }}']);
    rows.push(['Net Cash Flow', '{{ $summary["net_cash_flow"] }}']);
    rows.push(['Total Fees', '{{ $summary["total_fees"] }}']);
    rows.push([]);
    rows.push(['DAILY BREAKDOWN']);
    rows.push(['Date', 'Deposits', 'Withdrawals', 'Net Flow', 'Transactions']);
    
    @foreach($dailyBreakdown as $day)
    rows.push(['{{ $day["date_formatted"] }}', '{{ $day["deposits_formatted"] }}', '{{ $day["withdrawals_formatted"] }}', '{{ $day["net_flow_formatted"] }}', '{{ $day["count"] }}']);
    @endforeach
    
    @if(count($departmentBreakdown) > 0)
    rows.push([]);
    rows.push(['DEPARTMENT BREAKDOWN']);
    rows.push(['Department', 'Deposits', 'Withdrawals', 'Net Flow', 'Transactions']);
    @foreach($departmentBreakdown as $dept)
    rows.push(['{{ $dept["name"] }}', '{{ $dept["deposits_formatted"] }}', '{{ $dept["withdrawals_formatted"] }}', '{{ $dept["net_flow_formatted"] }}', '{{ $dept["count"] }}']);
    @endforeach
    @endif
    
    @if(count($depositorBreakdown) > 0)
    rows.push([]);
    rows.push(['DEPOSITOR BREAKDOWN']);
    rows.push(['Depositor', 'Deposits', 'Withdrawals', 'Net Flow', 'Transactions']);
    @foreach($depositorBreakdown as $depositor)
    rows.push(['{{ $depositor["name"] }}', '{{ $depositor["deposits_formatted"] }}', '{{ $depositor["withdrawals_formatted"] }}', '{{ $depositor["net_flow_formatted"] }}', '{{ $depositor["count"] }}']);
    @endforeach
    @endif
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'financial_report.csv';
    link.click();
}

</script>
@endpush