@extends('layouts.admin')

@section('title', 'Transaction Ledger')
@section('page_title', 'Transaction Ledger')

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
    <li class="breadcrumb-item text-muted">Transaction Ledger</li>
@endsection

@section('content')
@can('view transaction ledger')
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
                    <label class="fw-semibold fs-6 mb-2">Transaction Type</label>
                    <select id="filterType" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" {{ $transactionType == $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                    <select id="filterPaymentMethod" class="form-select form-select-solid w-180px">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ $paymentMethodId == $method->id ? 'selected' : '' }}>
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Status</label>
                    <select id="filterStatus" class="form-select form-select-solid w-140px">
                        <option value="">All Status</option>
                        @foreach($statuses as $stat)
                            <option value="{{ $stat }}" {{ $status == $stat ? 'selected' : '' }}>
                                {{ ucfirst($stat) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">Source</label>
                    <select id="filterSource" class="form-select form-select-solid w-160px">
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
                    <select id="filterPurpose" class="form-select form-select-solid w-160px">
                        <option value="">All Purposes</option>
                        @foreach($purposes as $purpose)
                            <option value="{{ $purpose->id }}" {{ $purposeId == $purpose->id ? 'selected' : '' }}>
                                {{ $purpose->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="fw-semibold fs-6 mb-2">View Currency</label>
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
                    <select id="filterDepartment" class="form-select form-select-solid w-160px">
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
                    <select id="filterDepositor" class="form-select form-select-solid w-180px">
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
                    <i class="ki-duotone ki-filter fs-2"></i> Apply Filters
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
                                <span class="text-muted fs-7">Date Range: {{ \Carbon\Carbon::parse($summary['start_date'])->format('M d') }} - {{ \Carbon\Carbon::parse($summary['end_date'])->format('M d, Y') }}</span>
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
                                <span class="text-gray-600 fw-semibold">Total Amount</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_amount'] }}</span>
                                    <span class="text-muted ms-2">Total Value</span>
                                </div>
                                <div class="d-flex gap-3 mt-1">
                                    <span class="text-success fs-7">Deposits: {{ $summary['total_deposits'] ?? '0' }}</span>
                                    <span class="text-danger fs-7">Withdrawals: {{ $summary['total_withdrawals'] ?? '0' }}</span>
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
                                <i class="ki-duotone ki-chart-line fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Average Transaction</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['average_transaction'] }}</span>
                                    <span class="text-muted ms-2">Per Transaction</span>
                                </div>
                                <span class="text-muted fs-7">Total Transactions: {{ number_format($summary['total_transactions']) }}</span>
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
                                <i class="ki-duotone ki-calendar-8 fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                </i>
                            </div>
                            <div>
                                <span class="text-gray-600 fw-semibold">Date Range</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['days_range'] }}</span>
                                    <span class="text-muted ms-2">Days</span>
                                </div>
                                <span class="text-muted fs-7">{{ \Carbon\Carbon::parse($summary['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($summary['end_date'])->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="d-flex justify-content-end mb-5">
            <div class="d-flex align-items-center position-relative w-300px">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid ps-13" 
                       placeholder="Search by ref, description, counterparty..." value="{{ $search }}">
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                <thead>
                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                        <th class="min-w-50px">#</th>
                        <th class="min-w-150px">Reference</th>  <!-- Increased width -->
                        <th class="min-w-120px">Date</th>
                        <th class="min-w-100px">Type</th>
                        <th class="min-w-150px">Payment Method</th>
                        <th class="min-w-120px">Department</th>
                        <th class="min-w-150px">Depositor</th>
                        <th class="min-w-120px text-end">Amount</th>
                        <th class="min-w-100px">Status</th>
                        <th class="min-w-150px">Description</th>
                        <th class="min-w-100px text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    @foreach($transactionsData as $index => $transaction)
                    <tr>
                        <td><span class="fw-bold">{{ $transactions->firstItem() + $index }}</span></td>
                        <td><span class="text-muted fs-7" style="white-space: nowrap;">{{ substr($transaction['transaction_ref'], 0, 13) }}...</span></td>
                        <td>{{ $transaction['date'] }}</td>
                        <td>
                            <span class="badge badge-light-{{ $transaction['type'] === 'deposit' ? 'success' : ($transaction['type'] === 'withdrawal' ? 'danger' : 'info') }}">
                                {{ $transaction['type_label'] }}
                            </span>
                        </td>
                        <td>{{ $transaction['payment_method_name'] }}</td>
                        <td>
                            @if($transaction['department_name'])
                                <span class="badge badge-light-primary">{{ $transaction['department_name'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($transaction['depositor_name'])
                                <span class="badge badge-light-info">{{ $transaction['depositor_name'] }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-{{ $transaction['type'] === 'deposit' ? 'success' : 'danger' }}">
                                {{ $transaction['amount_formatted'] }}
                            </span>
                            <div class="text-muted fs-7">{{ $transaction['amount_display'] }}</div>
                        </td>
                        <td>
                            <span class="badge badge-light-{{ $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($transaction['status']) }}
                            </span>
                        </td>
                        <td>{{ Str::limit($transaction['description'], 30) }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-icon btn-light" onclick="viewTransaction({{ $transaction['id'] }})">
                                <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
                
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div class="text-muted">
                Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} entries
            </div>
            <nav>
                <ul class="pagination m-0">
                    <li class="page-item {{ $transactions->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="#" onclick="changePage({{ $transactions->currentPage() - 1 }})">Previous</a>
                    </li>
                    @for($i = 1; $i <= $transactions->lastPage(); $i++)
                        <li class="page-item {{ $transactions->currentPage() == $i ? 'active' : '' }}">
                            <a class="page-link" href="#" onclick="changePage({{ $i }})">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ !$transactions->hasMorePages() ? 'disabled' : '' }}">
                        <a class="page-link" href="#" onclick="changePage({{ $transactions->currentPage() + 1 }})">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Transaction Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="transactionDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Fix for card text wrapping */
    .card .fs-2hx {
        font-size: 1.75rem !important;
        line-height: 1.2 !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        max-width: 100%;
    }

    .card-body .d-flex {
        flex-wrap: wrap;
    }

    .card-body .fs-7 {
        font-size: 0.75rem !important;
        white-space: normal !important;
        word-break: break-word !important;
    }

    .symbol.symbol-50px {
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .card .fs-2hx {
            font-size: 1.25rem !important;
        }
    }
</style>
@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Apply filters on button click
    document.getElementById('applyFiltersBtn')?.addEventListener('click', function() {
        currentPage = 1;
        loadTransactions();
    });
    
    document.getElementById('resetFiltersBtn')?.addEventListener('click', function() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterPaymentMethod').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterSource').value = '';
        document.getElementById('filterPurpose').value = '';
        document.getElementById('filterDepartment').value = '';      // NEW
        document.getElementById('filterDepositor').value = '';        // NEW
        document.getElementById('searchInput').value = '';
        currentPage = 1;
        loadTransactions();
    });
    
    // Search on enter key
    document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadTransactions();
        }
    });
});

function loadTransactions() {
    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('start_date', document.getElementById('startDate')?.value || '');
    params.append('end_date', document.getElementById('endDate')?.value || '');
    params.append('transaction_type', document.getElementById('filterType')?.value || '');
    params.append('payment_method_id', document.getElementById('filterPaymentMethod')?.value || '');
    params.append('status', document.getElementById('filterStatus')?.value || '');
    params.append('source_id', document.getElementById('filterSource')?.value || '');
    params.append('purpose_id', document.getElementById('filterPurpose')?.value || '');
    params.append('department_id', document.getElementById('filterDepartment')?.value || '');      // NEW
    params.append('depositor_id', document.getElementById('filterDepositor')?.value || '');        // NEW
    params.append('search', document.getElementById('searchInput')?.value || '');
    params.append('base_currency', document.getElementById('baseCurrency')?.value || 'USD');
    
    window.location.href = `{{ route('accounting.transaction-ledger') }}?${params.toString()}`;
}

function changePage(page) {
    currentPage = page;
    loadTransactions();
}

function viewTransaction(id) {
    const modal = new bootstrap.Modal(document.getElementById('transactionDetailsModal'));
    const contentDiv = document.getElementById('transactionDetailsContent');
    
    contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/accounting/transaction-details/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                contentDiv.innerHTML = `
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">Reference</span>
                                <div class="fw-bold">${data.transaction_ref}</div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted">Status</span>
                                <div>${getStatusBadge(data.status)}</div>
                            </div>
                        </div>
                    </div>
                    <div class="separator my-5"></div>
                    
                    <!-- Amount Section -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <span class="text-muted">Amount</span>
                            <div class="fw-bold fs-2 text-${data.transaction_type === 'withdrawal' ? 'danger' : 'success'}">${data.amount_formatted}</div>
                            <small class="text-muted">${data.currency_code}</small>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Transaction Fee</span>
                            <div class="fw-bold">${data.fee_formatted}</div>
                        </div>
                    </div>
                    
                    <!-- Department & Depositor Section - NEW -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <span class="text-muted">Department</span>
                            <div class="fw-bold">
                                ${data.department_name 
                                    ? `<span class="badge badge-light-primary fs-6 p-2">${data.department_name}${data.department_code ? ' (' + data.department_code + ')' : ''}</span>` 
                                    : '<span class="text-muted">N/A</span>'}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Depositor</span>
                            <div class="fw-bold">
                                ${data.depositor_name 
                                    ? `<div class="d-flex flex-column">
                                        <span class="badge badge-light-info fs-6 p-2 mb-1">${data.depositor_name}</span>
                                        ${data.depositor_email ? `<small class="text-muted">${data.depositor_email}</small>` : ''}
                                        ${data.depositor_phone ? `<small class="text-muted">${data.depositor_phone}</small>` : ''}
                                       </div>` 
                                    : (data.user_name ? `<span class="badge badge-light-secondary">${data.user_name}</span>` : '<span class="text-muted">N/A</span>')}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date & Payment Method -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <span class="text-muted">Transaction Date</span>
                            <div class="fw-bold">${data.date}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Payment Method</span>
                            <div class="fw-bold">${data.payment_method}</div>
                            <small class="text-muted">${data.payment_method_type}</small>
                        </div>
                    </div>
                    
                    <!-- Balance Information -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <span class="text-muted">Balance Before</span>
                            <div class="fw-bold">${data.balance_before}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Balance After</span>
                            <div class="fw-bold">${data.balance_after}</div>
                        </div>
                    </div>
                    
                    <!-- Reference Information -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <span class="text-muted">External Reference</span>
                            <div class="fw-bold">${data.reference}</div>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted">Receipt Number</span>
                            <div class="fw-bold">${data.receipt_number}</div>
                        </div>
                    </div>
                    
                    <!-- Counterparty Information -->
                    ${data.counterparty !== 'N/A' ? `
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <span class="text-muted">Counterparty</span>
                            <div class="fw-bold">${data.counterparty}</div>
                            ${data.counterparty_account ? `<small class="text-muted">Account: ${data.counterparty_account}</small>` : ''}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Description -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <span class="text-muted">Description</span>
                            <div class="fw-bold">${data.description}</div>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    ${data.notes ? `
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <span class="text-muted">Notes</span>
                            <div class="fw-bold text-muted">${data.notes}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Metadata (if exists and is an object) -->
                    ${data.metadata && Object.keys(data.metadata).length > 0 ? `
                    <div class="separator my-5"></div>
                    <div class="row">
                        <div class="col-md-12">
                            <span class="text-muted fw-bold">Additional Metadata</span>
                            <div class="mt-2">
                                <pre class="bg-light p-3 rounded" style="font-size: 12px;">${JSON.stringify(data.metadata, null, 2)}</pre>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Created Information -->
                    <div class="separator my-5"></div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="text-muted">Created By</span>
                                    <div class="fw-bold">${data.created_by_name || 'System'}</div>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted">Created At</span>
                                    <div class="fw-bold">${data.created_at || data.date}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `<div class="text-center text-danger py-5">Failed to load transaction details</div>`;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            contentDiv.innerHTML = `<div class="text-center text-danger py-5">Failed to load transaction details</div>`;
        });
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-light-warning">Pending</span>',
        'processing': '<span class="badge badge-light-info">Processing</span>',
        'completed': '<span class="badge badge-light-success">Completed</span>',
        'failed': '<span class="badge badge-light-danger">Failed</span>',
        'cancelled': '<span class="badge badge-light-secondary">Cancelled</span>',
        'refunded': '<span class="badge badge-light-info">Refunded</span>'
    };
    return badges[status] || '<span class="badge badge-light-secondary">' + status + '</span>';
}

function exportToExcel() {
    const rows = [];
    const headers = ['#', 'Reference', 'Date', 'Type', 'Payment Method', 'Department', 'Depositor', 'Amount', 'Status', 'Description'];
    rows.push(headers);
    
    document.querySelectorAll('#transactionsTableBody tr').forEach((row, index) => {
        rows.push([
            index + 1,
            row.cells[1]?.textContent.trim() || '',
            row.cells[2]?.textContent.trim() || '',
            row.cells[3]?.textContent.trim() || '',
            row.cells[4]?.textContent.trim() || '',
            row.cells[5]?.textContent.trim() || '',  // Department
            row.cells[6]?.textContent.trim() || '',  // Depositor
            row.cells[7]?.querySelector('.fw-bold')?.textContent.trim() || '',
            row.cells[8]?.textContent.trim() || '',
            row.cells[9]?.textContent.trim() || ''
        ]);
    });
    
    const csvContent = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'transaction_ledger.csv';
    link.click();
}
</script>
@endpush