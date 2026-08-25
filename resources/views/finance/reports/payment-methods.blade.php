@extends('layouts.admin')

@section('title', 'Payment Methods Report')
@section('page_title', 'Payment Methods')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Settings</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Payment Methods</li>
@endsection

@section('content')
@can('view payment methods')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <label class="fw-semibold fs-6 mb-2">View in Currency</label>
                    <select id="baseCurrencySelect" class="form-select form-select-solid w-150px">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->code }}" {{ $baseCurrencyCode == $currency->code ? 'selected' : '' }}>
                                {{ $currency->code }} ({{ $currency->symbol }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Status</label>
                    <select id="statusFilter" class="form-select form-select-solid w-150px">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Filters</label><br>
                    <button id="refreshBtn" class="btn btn-primary">
                        <i class="ki-duotone ki-arrow-circle-right fs-2"></i> Apply
                    </button>
                </div>
            </div>
        </div>
        {{--
        <div class="card-toolbar">
            <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="ki-duotone ki-file-down fs-2"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="exportToExcel()">Export to Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="window.print()">Print Report</a></li>
                </ul>
            </div>
        </div>
        --}}
    </div>
    
    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-primary me-3">
                                <i class="ki-duotone ki-wallet fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Methods</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $stats['total_payment_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                                <span class="text-gray-600 fw-semibold">Total Balance</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $stats['total_balance_formatted'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-info me-3">
                                <i class="ki-duotone ki-arrow-up fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Active Methods</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $stats['active_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Simple Chart Row -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xl-6">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Balance Distribution ({{ $baseCurrencyCode }})</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="balanceDonutChart" style="height: 300px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Balance by Method ({{ $baseCurrencyCode }})</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="balanceBarChart" style="height: 300px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Methods Table -->
        <div class="card card-flush">
            <div class="card-header">
                <h3 class="card-title">Payment Methods List</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center position-relative">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search methods..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                <th class="min-w-200px">Payment Method</th>
                                <th class="min-w-100px">Type</th>
                                <th class="min-w-150px">Account Details</th>
                                <th class="min-w-120px text-end">Balance (Native)</th>
                                <th class="min-w-120px text-end">Balance ({{ $baseCurrencyCode }})</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px">Default</th>
                                <th class="min-w-120px">Last Activity</th>
                                <th class="min-w-100px text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="methodsTableBody">
                            @foreach($methodsData as $method)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-3">
                                            <span class="symbol-label bg-light-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                @switch($method['type'])
                                                    @case('cash')
                                                        <i class="ki-duotone ki-dollar fs-2x text-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('bank')
                                                        <i class="ki-duotone ki-building fs-2x text-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('mobile_money')
                                                        <i class="ki-duotone ki-phone fs-2x text-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('e_wallet')
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @default
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $method['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                @endswitch
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fs-6 fw-bold text-gray-800">{{ $method['name'] }}</span>
                                            <span class="fs-7 text-gray-500">{{ $method['code'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light-{{ $method['type'] === 'cash' ? 'warning' : ($method['type'] === 'bank' ? 'primary' : 'info') }}">
                                        {{ $method['type_label'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($method['type'] === 'bank')
                                        <span class="fs-7 text-gray-600">{{ $method['provider'] }}<br><small>****{{ substr($method['account_number'], -4) }}</small></span>
                                    @elseif($method['type'] === 'mobile_money')
                                        <span class="fs-7 text-gray-600">{{ $method['provider'] }}<br><small>{{ $method['phone_number'] }}</small></span>
                                    @elseif($method['type'] === 'e_wallet')
                                        <span class="fs-7 text-gray-600">{{ $method['provider'] }}<br><small>{{ $method['wallet_email'] }}</small></span>
                                    @elseif($method['type'] === 'cash')
                                        <span class="fs-7 text-gray-600">{{ $method['cash_location'] ?? 'Physical Cash' }}</span>
                                    @else
                                        <span class="fs-7 text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-gray-700">{{ $method['balance_formatted'] }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-success">{{ $method['balance_converted'] }}</span>
                                </td>
                                <td>
                                    @if($method['is_active'])
                                        <span class="badge badge-light-success">Active</span>
                                    @else
                                        <span class="badge badge-light-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($method['is_default'])
                                        <span class="badge badge-light-primary">Default</span>
                                    @else
                                        <span class="badge badge-light-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    @if($method['last_transaction_at'])
                                        <span class="fs-7 text-gray-500">{{ \Carbon\Carbon::parse($method['last_transaction_at'])->format('M d, Y') }}</span>
                                    @else
                                        <span class="fs-7 text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $method['id']]) }}" 
                                    class="btn btn-sm btn-icon btn-light" 
                                    target="_blank"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="View all transactions for {{ $method['name'] }} in new tab">
                                        <i class="ki-duotone ki-book fs-3"><span class="path1"></span><span class="path2"></span></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
let donutChart, barChart;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    document.getElementById('searchInput')?.addEventListener('keyup', filterTable);
    document.getElementById('statusFilter')?.addEventListener('change', filterTable);
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        const baseCurrency = document.getElementById('baseCurrencySelect').value;
        window.location.href = `{{ route('accounting.payment-methods.index') }}?base_currency=${baseCurrency}`;
    });
});

function initCharts() {
    const chartData = @json($chartData);
    const baseCurrency = '{{ $baseCurrencyCode }}';
    const names = chartData.map(item => item.name);
    const balances = chartData.map(item => item.balance);
    
    // Donut Chart
    const donutCtx = document.getElementById('balanceDonutChart').getContext('2d');
    donutChart = new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: names,
            datasets: [{
                data: balances,
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
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${baseCurrency} ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Bar Chart
    const barCtx = document.getElementById('balanceBarChart').getContext('2d');
    barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: names,
            datasets: [{
                label: `Balance (${baseCurrency})`,
                data: balances,
                backgroundColor: '#2BA58F',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            indexAxis: 'y',
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${baseCurrency} ${context.parsed.x.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: baseCurrency },
                    ticks: { callback: function(value) { return baseCurrency + ' ' + value.toLocaleString(); } }
                },
                y: { title: { display: true, text: 'Payment Method' } }
            }
        }
    });
}

function filterTable() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const rows = document.querySelectorAll('#methodsTableBody tr');
    
    rows.forEach(row => {
        let show = true;
        const text = row.textContent.toLowerCase();
        const statusCell = row.cells[5]?.textContent.toLowerCase() || '';
        
        if (searchTerm && !text.includes(searchTerm)) show = false;
        if (statusFilter === 'active' && !statusCell.includes('active')) show = false;
        if (statusFilter === 'inactive' && statusCell.includes('active')) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function exportToExcel() {
    const rows = [];
    const headers = ['Name', 'Type', 'Provider', 'Account Details', 'Native Balance', 'Converted Balance', 'Status', 'Last Activity'];
    rows.push(headers);
    
    document.querySelectorAll('#methodsTableBody tr').forEach(row => {
        if (row.style.display !== 'none') {
            rows.push([
                row.cells[0]?.textContent.trim() || '',
                row.cells[1]?.textContent.trim() || '',
                row.cells[2]?.innerText.split('\n')[0]?.trim() || '',
                row.cells[2]?.querySelector('small')?.innerText?.trim() || '',
                row.cells[3]?.textContent.trim() || '',
                row.cells[4]?.textContent.trim() || '',
                row.cells[5]?.textContent.trim() || '',
                row.cells[7]?.textContent.trim() || ''
            ]);
        }
    });
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'payment_methods_report.csv';
    link.click();
}
</script>
@endpush