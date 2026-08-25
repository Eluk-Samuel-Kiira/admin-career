@extends('layouts.admin')

@section('title', 'Account Balances Report')
@section('page_title', 'Account Balances')

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
    <li class="breadcrumb-item text-muted">Account Balances</li>
@endsection

@section('content')
@can('view account balances')
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
                    <label class="fw-semibold fs-6 mb-2">&nbsp;</label><br>
                    <button id="refreshBtn" class="btn btn-primary">
                        <i class="ki-duotone ki-arrow-circle-right fs-2"></i> Apply
                    </button>
                </div>
            </div>
        </div>
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
    </div>
    
    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-primary me-3">
                                <i class="ki-duotone ki-wallet fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Total Accounts</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['accounts_count'] }}</span>
                                </div>
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
                                <span class="text-gray-600 fw-semibold">Total Current Balance</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_current'] }}</span>
                                </div>
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
                                <i class="ki-duotone ki-calendar fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Available Balance</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_available'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-warning me-3">
                                <i class="ki-duotone ki-time fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Pending Balance</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_pending'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Balance Comparison Chart -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xl-12">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-5">
                        <h3 class="card-title">
                            <span class="card-label fw-bold fs-3">Balance Comparison ({{ $baseCurrencyCode }})</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="balanceComparisonChart" style="height: 400px; width: 100%"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Balances Table -->
        <div class="card card-flush">
            <div class="card-header">
                <h3 class="card-title">Account Balances List</h3>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center position-relative">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search accounts..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                <th class="min-w-200px">Account</th>
                                <th class="min-w-100px">Type</th>
                                <th class="min-w-150px">Provider/Bank</th>
                                <th class="min-w-120px text-end">Current Balance</th>
                                <th class="min-w-120px text-end">Available</th>
                                <th class="min-w-120px text-end">Pending</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-120px">Last Activity</th>
                                <th class="min-w-100px text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accountsTableBody">
                            @foreach($accountsData as $account)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-3">
                                            <span class="symbol-label bg-light-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                @switch($account['type'])
                                                    @case('cash')
                                                        <i class="ki-duotone ki-dollar fs-2x text-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('bank')
                                                        <i class="ki-duotone ki-building fs-2x text-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('mobile_money')
                                                        <i class="ki-duotone ki-phone fs-2x text-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @case('e_wallet')
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                        @break
                                                    @default
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $account['is_active'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span><span class="path2"></span>
                                                        </i>
                                                @endswitch
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fs-6 fw-bold text-gray-800">{{ $account['name'] }}</span>
                                            <span class="fs-7 text-gray-500">{{ $account['code'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-light-{{ $account['type'] === 'cash' ? 'warning' : ($account['type'] === 'bank' ? 'primary' : 'info') }}">
                                        {{ $account['type_label'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fs-7 text-gray-600">{{ $account['provider'] ?? 'N/A' }}<br><small>{{ $account['account_number'] ? '****'.substr($account['account_number'], -4) : '' }}</small></span>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">{{ $account['current_balance_converted'] }}</div>
                                    <div class="text-muted fs-7">{{ $account['current_balance_native'] }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-gray-700">{{ $account['available_balance_converted'] }}</div>
                                    <div class="text-muted fs-7">{{ $account['available_balance_native'] }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-warning">{{ $account['pending_balance_converted'] }}</div>
                                    <div class="text-muted fs-7">{{ $account['pending_balance_native'] }}</div>
                                </td>
                                <td>
                                    @if($account['is_active'])
                                        <span class="badge badge-light-success">Active</span>
                                    @else
                                        <span class="badge badge-light-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account['last_transaction_at'])
                                        <span class="fs-7 text-gray-500">{{ \Carbon\Carbon::parse($account['last_transaction_at'])->format('M d, Y') }}</span>
                                    @else
                                        <span class="fs-7 text-gray-400">Never</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.transaction-ledger', ['payment_method_id' => $account['id']]) }}" 
                                    class="btn btn-sm btn-icon btn-light" 
                                    target="_blank"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="View all transactions for {{ $account['name'] }} in new tab">
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
        
        <!-- Recent Transactions -->
        <div class="card card-flush mt-5">
            <div class="card-header">
                <h3 class="card-title">Recent Transactions</h3>
                <div class="card-toolbar">
                    <a href="{{ route('accounting.transaction-ledger') }}" class="btn btn-sm btn-light">
                        View All Transactions
                    </a>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered gy-4 align-middle gs-0">
                        <thead>
                            <tr class="fs-7 fw-bold text-gray-500">
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Payment Method</th>
                                <th>Department</th>
                                <th>Depositor/User</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                            <tr>
                                <td><span class="text-muted fs-7">{{ substr($transaction['transaction_ref'], 0, 13) }}...</span></td>
                                <td>{{ $transaction['date'] }}</td>
                                <td><span class="badge badge-light-{{ $transaction['type'] === 'deposit' ? 'success' : ($transaction['type'] === 'withdrawal' ? 'danger' : 'info') }}">{{ $transaction['type_label'] }}</span></td>
                                <td>{{ $transaction['payment_method'] }}</td>
                                <td>
                                    @if($transaction['department_name'] !== 'N/A')
                                        <span class="badge badge-light-primary">{{ $transaction['department_name'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction['depositor_name'] !== 'N/A')
                                        <span class="badge badge-light-info">{{ $transaction['depositor_name'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end"><span class="fw-bold">{{ $transaction['amount_formatted'] }}</span></td>
                                <td>{!! $transaction['status_badge'] !!}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No recent transactions found</td>
                            </tr>
                            @endforelse
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
let balanceChart;

document.addEventListener('DOMContentLoaded', function() {
    initChart();
    
    document.getElementById('searchInput')?.addEventListener('keyup', filterTable);
    document.getElementById('statusFilter')?.addEventListener('change', filterTable);
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        const baseCurrency = document.getElementById('baseCurrencySelect').value;
        window.location.href = `{{ route('accounting.account-balances') }}?base_currency=${baseCurrency}`;
    });
});

function initChart() {
    const chartData = @json($chartData);
    const baseCurrency = '{{ $baseCurrencyCode }}';
    const names = chartData.map(item => item.name);
    const currentBalances = chartData.map(item => item.current_balance);
    const availableBalances = chartData.map(item => item.available_balance);
    
    const ctx = document.getElementById('balanceComparisonChart').getContext('2d');
    balanceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: names,
            datasets: [
                {
                    label: `Current Balance (${baseCurrency})`,
                    data: currentBalances,
                    backgroundColor: '#2BA58F',
                    borderRadius: 8
                },
                {
                    label: `Available Balance (${baseCurrency})`,
                    data: availableBalances,
                    backgroundColor: '#7239EA',
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
                x: { title: { display: true, text: 'Account' } }
            }
        }
    });
}

function filterTable() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const rows = document.querySelectorAll('#accountsTableBody tr');
    
    rows.forEach(row => {
        let show = true;
        const text = row.textContent.toLowerCase();
        const statusCell = row.cells[6]?.textContent.toLowerCase() || '';
        
        if (searchTerm && !text.includes(searchTerm)) show = false;
        if (statusFilter === 'active' && !statusCell.includes('active')) show = false;
        if (statusFilter === 'inactive' && statusCell.includes('active')) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function exportToExcel() {
    const rows = [];
    const headers = ['Account Name', 'Type', 'Provider', 'Current Balance', 'Available Balance', 'Pending Balance', 'Status', 'Last Activity'];
    rows.push(headers);
    
    document.querySelectorAll('#accountsTableBody tr').forEach(row => {
        if (row.style.display !== 'none') {
            rows.push([
                row.cells[0]?.textContent.trim() || '',
                row.cells[1]?.textContent.trim() || '',
                row.cells[2]?.innerText.split('\n')[0]?.trim() || '',
                row.cells[3]?.querySelector('.fw-bold')?.textContent.trim() || '',
                row.cells[4]?.querySelector('.fw-bold')?.textContent.trim() || '',
                row.cells[5]?.querySelector('.fw-bold')?.textContent.trim() || '',
                row.cells[6]?.textContent.trim() || '',
                row.cells[7]?.textContent.trim() || ''
            ]);
        }
    });
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'account_balances_report.csv';
    link.click();
}
</script>
@endpush