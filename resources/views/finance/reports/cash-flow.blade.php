@extends('layouts.admin')

@section('title', 'Cash Flow Report')
@section('page_title', 'Cash Flow')

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
    <li class="breadcrumb-item text-muted">Cash Flow</li>
@endsection

@section('content')
@can('view cash flow')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                    <label class="fw-semibold fs-6 mb-2">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" id="startDate" class="form-control form-control-solid w-150px" value="{{ $startDate }}">
                        <span class="align-self-center">to</span>
                        <input type="date" id="endDate" class="form-control form-control-solid w-150px" value="{{ $endDate }}">
                    </div>
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
                    <label class="fw-semibold fs-6 mb-2">Transaction Type</label>
                    <select id="typeFilter" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" {{ $transactionType == $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
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
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-success me-3">
                                <i class="ki-duotone ki-arrow-down fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Cash In</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-success">{{ $summary['total_cash_in'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Money Received</span>
                                <span class="text-muted fs-7 d-block">Period {{ $summary['start_date'] }} - {{ $summary['end_date'] }}</span>
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
                                <i class="ki-duotone ki-arrow-up fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Cash Out</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-danger">{{ $summary['total_cash_out'] }}</span>
                                </div>
                                <span class="text-muted fs-7">Money Paid</span>
                                <span class="text-muted fs-7 d-block">Period {{ $summary['start_date'] }} - {{ $summary['end_date'] }}</span>
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
                                <span class="text-muted fs-7">Net Movement</span>
                                <span class="text-muted fs-7 d-block">Transactions: {{ $summary['total_transactions'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-info me-3">
                                <i class="ki-duotone ki-calendar-8 fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Average Daily</span>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <span class="text-success fw-bold">{{ $summary['average_daily_in'] }}</span>
                                        <span class="text-muted fs-7">/in</span>
                                    </div>
                                    <div>
                                        <span class="text-danger fw-bold">{{ $summary['average_daily_out'] }}</span>
                                        <span class="text-muted fs-7">/out</span>
                                    </div>
                                </div>
                                <span class="text-muted fs-7">{{ $summary['days_range'] }} days period</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cash Flow Chart -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xl-12">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Cash Flow Trend</span>
                            <span class="text-muted fs-7 ms-2">{{ $baseCurrencyCode }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <div class="btn-group" data-kt-buttons="true">
                                <button type="button" class="btn btn-sm btn-light" onclick="toggleChartSeries('cash_in')">Cash In</button>
                                <button type="button" class="btn btn-sm btn-light" onclick="toggleChartSeries('cash_out')">Cash Out</button>
                                <button type="button" class="btn btn-sm btn-light active" onclick="toggleChartSeries('net_flow')">Net Flow</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="cashFlowChart" style="height: 350px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Cash Flow Table -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Daily Cash Flow</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Date</th>
                                <th class="text-end">Cash In</th>
                                <th class="text-end">Cash Out</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">Trend</th>
                                <th class="text-end">Daily Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalIn = 0; $totalOut = 0; @endphp
                            @foreach($dailyCashFlow as $day)
                            @php 
                                $totalIn += $day['cash_in']; 
                                $totalOut += $day['cash_out'];
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $day['date_formatted'] }}</span>
                                    <span class="text-muted fs-7 ms-1">{{ $day['day_name'] }}</span>
                                </td>
                                <td class="text-end text-success fw-bold">{{ $day['cash_in_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $day['cash_out_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $day['net_flow_color'] }}">{{ $day['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $day['transaction_count'] }}</td>
                                <td class="text-end">
                                    <span class="badge badge-light-{{ $day['trend_color'] }}">
                                        <i class="ki-duotone ki-{{ $day['trend_icon'] }} fs-5"></i>
                                        {{ abs($day['trend']) }}%
                                    </span>
                                </td>
                                <td class="text-end fw-bold">{{ $day['running_balance_formatted'] }}</td>
                            </tr>
                            @endforeach
                            @if(!empty($dailyCashFlow))
                            <tr class="fw-bold fs-6 bg-light">
                                <td>Total</td>
                                <td class="text-end text-success">{{ $summary['total_cash_in'] }}</td>
                                <td class="text-end text-danger">{{ $summary['total_cash_out'] }}</td>
                                <td class="text-end text-{{ $summary['net_cash_flow_color'] }}">{{ $summary['net_cash_flow'] }}</td>
                                <td class="text-end">{{ $summary['total_transactions'] }}</td>
                                <td></td>
                                <td class="text-end fw-bold">{{ $summary['net_cash_flow'] }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Cash Flow by Payment Method -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Cash Flow by Payment Method</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Payment Method</th>
                                <th class="text-end">Cash In</th>
                                <th class="text-end">Cash Out</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">In/Out Ratio</th>
                                <th class="text-end">Average Transaction</th>
                             </tr>
                        </thead>
                        <tbody>
                            @forelse($cashFlowByMethod as $method)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-30px me-2">
                                            <span class="symbol-label bg-light-{{ $method['net_flow_color'] }}">
                                                <i class="ki-duotone ki-{{ $method['type'] === 'cash' ? 'dollar' : ($method['type'] === 'bank' ? 'building' : 'wallet') }} fs-4">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </span>
                                        </div>
                                        {{ $method['name'] }}
                                    </div>
                                    <div class="text-muted fs-7">{{ $method['type_label'] }}</div>
                                 </td>
                                <td class="text-end text-success fw-bold">{{ $method['cash_in_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $method['cash_out_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $method['net_flow_color'] }}">{{ $method['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $method['transaction_count'] }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center">
                                        <span class="me-2">{{ $method['in_out_ratio'] }}%</span>
                                        <div class="progress h-6px w-50px">
                                            <div class="progress-bar bg-{{ $method['net_flow_color'] }}" style="width: {{ $method['in_out_ratio'] }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">{{ $method['avg_transaction'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">No cash flow data available</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cash Flow by Department - NEW -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <h3 class="card-title">Cash Flow by Department</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Department</th>
                                <th class="text-end">Cash In</th>
                                <th class="text-end">Cash Out</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashFlowByDepartment as $dept)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $dept['name'] }}</div>
                                    @if($dept['code'])
                                        <div class="text-muted fs-7">{{ $dept['code'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-bold">{{ $dept['cash_in_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $dept['cash_out_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $dept['net_flow_color'] }}">{{ $dept['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $dept['transaction_count'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No cash flow data available by department</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cash Flow by Depositor - NEW -->
        <div class="card card-flush">
            <div class="card-header">
                <h3 class="card-title">Cash Flow by Depositor</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Depositor</th>
                                <th class="text-end">Cash In</th>
                                <th class="text-end">Cash Out</th>
                                <th class="text-end">Net Flow</th>
                                <th class="text-end">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashFlowByDepositor as $depositor)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $depositor['name'] }}</div>
                                    @if($depositor['email'])
                                        <div class="text-muted fs-7">{{ $depositor['email'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-bold">{{ $depositor['cash_in_formatted'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $depositor['cash_out_formatted'] }}</td>
                                <td class="text-end fw-bold text-{{ $depositor['net_flow_color'] }}">{{ $depositor['net_flow_formatted'] }}</td>
                                <td class="text-end">{{ $depositor['transaction_count'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No cash flow data available by depositor</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
                
        <!-- Key Insights -->
        <div class="card card-flush mt-5">
            <div class="card-header">
                <h3 class="card-title">Key Insights</h3>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-4">
                        <div class="p-4 bg-light-primary rounded">
                            <i class="ki-duotone ki-arrow-up fs-2x text-primary"></i>
                            <div class="mt-2">
                                <span class="text-muted">Highest Cash In Day</span>
                                <div class="fw-bold fs-4">{{ $summary['max_cash_in_day'] ?? 'N/A' }}</div>
                                <div class="text-success">{{ $summary['max_cash_in'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 bg-light-danger rounded">
                            <i class="ki-duotone ki-arrow-down fs-2x text-danger"></i>
                            <div class="mt-2">
                                <span class="text-muted">Highest Cash Out Day</span>
                                <div class="fw-bold fs-4">{{ $summary['max_cash_out_day'] ?? 'N/A' }}</div>
                                <div class="text-danger">{{ $summary['max_cash_out'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 bg-light-{{ $summary['net_cash_flow_color'] }} rounded">
                            <i class="ki-duotone ki-chart-line fs-2x text-{{ $summary['net_cash_flow_color'] }}"></i>
                            <div class="mt-2">
                                <span class="text-muted">Overall Cash Position</span>
                                <div class="fw-bold fs-4 text-{{ $summary['net_cash_flow_color'] }}">{{ $summary['net_cash_flow'] }}</div>
                                <div class="text-muted">Over {{ $summary['days_range'] }} days</div>
                            </div>
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
let cashFlowChart;
let currentSeries = ['net_flow'];

document.addEventListener('DOMContentLoaded', function() {
    initChart();
    
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        applyFilters();
    });
    
    document.getElementById('resetFiltersBtn')?.addEventListener('click', function() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('paymentMethodFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('departmentFilter').value = '';      // NEW
        document.getElementById('depositorFilter').value = '';        // NEW
        document.getElementById('baseCurrency').value = 'USD';
        applyFilters();
    });
});

function initChart() {
    const chartData = @json($chartData);
    const baseCurrency = '{{ $baseCurrencyCode }}';
    
    const ctx = document.getElementById('cashFlowChart').getContext('2d');
    cashFlowChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.dates,
            datasets: [
                {
                    label: `Cash In (${baseCurrency})`,
                    data: chartData.cash_in,
                    borderColor: '#50CD89',
                    backgroundColor: 'rgba(80, 205, 137, 0.1)',
                    fill: true,
                    tension: 0.4,
                    hidden: true
                },
                {
                    label: `Cash Out (${baseCurrency})`,
                    data: chartData.cash_out,
                    borderColor: '#F1416C',
                    backgroundColor: 'rgba(241, 65, 108, 0.1)',
                    fill: true,
                    tension: 0.4,
                    hidden: true
                },
                {
                    label: `Net Flow (${baseCurrency})`,
                    data: chartData.net_flow,
                    borderColor: '#7239EA',
                    backgroundColor: 'rgba(114, 57, 234, 0.1)',
                    fill: true,
                    tension: 0.4,
                    hidden: false
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

function toggleChartSeries(seriesName) {
    const datasetIndex = {
        'cash_in': 0,
        'cash_out': 1,
        'net_flow': 2
    };
    
    const index = datasetIndex[seriesName];
    if (cashFlowChart) {
        cashFlowChart.data.datasets[index].hidden = !cashFlowChart.data.datasets[index].hidden;
        cashFlowChart.update();
    }
}

function applyFilters() {
    const params = new URLSearchParams();
    params.append('start_date', document.getElementById('startDate')?.value || '');
    params.append('end_date', document.getElementById('endDate')?.value || '');
    params.append('payment_method_id', document.getElementById('paymentMethodFilter')?.value || '');
    params.append('transaction_type', document.getElementById('typeFilter')?.value || '');
    params.append('department_id', document.getElementById('departmentFilter')?.value || '');      // NEW
    params.append('depositor_id', document.getElementById('depositorFilter')?.value || '');        // NEW
    params.append('base_currency', document.getElementById('baseCurrency')?.value || 'USD');
    
    window.location.href = `{{ route('accounting.cash-flow') }}?${params.toString()}`;
}

function exportToExcel() {
    const rows = [];
    rows.push(['Cash Flow Report']);
    rows.push(['Period:', document.getElementById('startDate')?.value || '{{ $startDate }}', 'to', document.getElementById('endDate')?.value || '{{ $endDate }}']);
    rows.push([]);
    rows.push(['SUMMARY']);
    rows.push(['Total Cash In', '{{ $summary["total_cash_in"] }}']);
    rows.push(['Total Cash Out', '{{ $summary["total_cash_out"] }}']);
    rows.push(['Net Cash Flow', '{{ $summary["net_cash_flow"] }}']);
    rows.push(['Total Transactions', '{{ $summary["total_transactions"] }}']);
    rows.push(['Highest Cash In Day', '{{ $summary["max_cash_in_day"] ?? "N/A" }}', '{{ $summary["max_cash_in"] ?? "N/A" }}']);
    rows.push(['Highest Cash Out Day', '{{ $summary["max_cash_out_day"] ?? "N/A" }}', '{{ $summary["max_cash_out"] ?? "N/A" }}']);
    rows.push([]);
    rows.push(['DAILY CASH FLOW']);
    rows.push(['Date', 'Cash In', 'Cash Out', 'Net Flow', 'Transactions', 'Daily Balance']);
    
    @foreach($dailyCashFlow as $day)
    rows.push(['{{ $day["date_formatted"] }}', '{{ $day["cash_in_formatted"] }}', '{{ $day["cash_out_formatted"] }}', '{{ $day["net_flow_formatted"] }}', '{{ $day["transaction_count"] }}', '{{ $day["running_balance_formatted"] }}']);
    @endforeach
    
    rows.push([]);
    rows.push(['CASH FLOW BY PAYMENT METHOD']);
    rows.push(['Payment Method', 'Cash In', 'Cash Out', 'Net Flow', 'Transactions']);
    
    @foreach($cashFlowByMethod as $method)
    rows.push(['{{ $method["name"] }}', '{{ $method["cash_in_formatted"] }}', '{{ $method["cash_out_formatted"] }}', '{{ $method["net_flow_formatted"] }}', '{{ $method["transaction_count"] }}']);
    @endforeach
    
    rows.push([]);
    rows.push(['CASH FLOW BY DEPARTMENT']);
    rows.push(['Department', 'Cash In', 'Cash Out', 'Net Flow', 'Transactions']);
    
    @foreach($cashFlowByDepartment as $dept)
    rows.push(['{{ $dept["name"] }}', '{{ $dept["cash_in_formatted"] }}', '{{ $dept["cash_out_formatted"] }}', '{{ $dept["net_flow_formatted"] }}', '{{ $dept["transaction_count"] }}']);
    @endforeach
    
    rows.push([]);
    rows.push(['CASH FLOW BY DEPOSITOR']);
    rows.push(['Depositor', 'Cash In', 'Cash Out', 'Net Flow', 'Transactions']);
    
    @foreach($cashFlowByDepositor as $depositor)
    rows.push(['{{ $depositor["name"] }}', '{{ $depositor["cash_in_formatted"] }}', '{{ $depositor["cash_out_formatted"] }}', '{{ $depositor["net_flow_formatted"] }}', '{{ $depositor["transaction_count"] }}']);
    @endforeach
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'cash_flow_report.csv';
    link.click();
}
</script>
@endpush