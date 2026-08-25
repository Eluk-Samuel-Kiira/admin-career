@extends('layouts.admin')

@section('title', 'Phantom Equity')
@section('page_title', 'Phantom Equity')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Compensation</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Phantom Equity</li>
@endsection

@section('content')
@can('view phantom equity')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Transactions..." />
                </div>
                
                <!-- Transaction Type Filter -->
                <div>
                    <select id="filterType" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        <option value="allocation">Allocation</option>
                        <option value="award">Award</option>
                        <option value="vesting">Vesting</option>
                        <option value="forfeiture">Forfeiture</option>
                        <option value="payout">Payout</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <select id="filterDepartment" class="form-select form-select-solid w-180px">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <!-- Employee Filter -->
                <div>
                    <select id="filterEmployee" class="form-select form-select-solid w-200px">
                        <option value="">All Employees</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create phantom equity')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_transaction">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Transaction
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-primary me-3">
                                <i class="ki-duotone ki-cube fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Units</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalUnits">0</span>
                                    <span class="text-muted ms-2">Units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-success me-3">
                                <i class="ki-duotone ki-check-circle fs-2x text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Vested Units</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="vestedUnits">0</span>
                                    <span class="text-muted ms-2">Units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-warning me-3">
                                <i class="ki-duotone ki-wallet fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Payout</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalPayout">0</span>
                                    <span class="text-muted ms-2">UGX</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-50px symbol-circle bg-light-info me-3">
                                <i class="ki-duotone ki-users fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Participants</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalUsers">0</span>
                                    <span class="text-muted ms-2">Employees</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                
        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading transactions...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Reference</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-80px text-center">Units</th>
                            <th class="min-w-80px text-center">Vested</th>
                            <th class="min-w-120px text-end">Total Value</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Date</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody"></tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
        
        <div id="noDataMessage" class="text-center py-10 d-none">
            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <p class="text-muted">No phantom equity transactions found.</p>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="kt_modal_add_transaction" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Phantom Equity Transaction</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addTransactionForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Transaction Type</label>
                            <select class="form-select form-select-solid" name="transaction_type" id="add_transaction_type" required>
                                <option value="">Select Type</option>
                                <option value="allocation">Allocation</option>
                                <option value="award">Award</option>
                                <option value="vesting">Vesting</option>
                                <option value="forfeiture">Forfeiture</option>
                                <option value="payout">Payout</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Transaction Date</label>
                            <input type="date" class="form-control form-control-solid" name="transaction_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Units</label>
                            <input type="number" class="form-control form-control-solid" name="units" placeholder="0" required min="1" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Unit Value (UGX)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="unit_value" placeholder="0.00" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_vested" />
                                <span class="form-check-label fw-semibold">Vested</span>
                            </label>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_score" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Multiplier</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_multiplier" placeholder="1.0" value="1.0" min="0" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" placeholder="Transaction description" />
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addTransactionBtn">
                            <span class="indicator-label">Create Transaction</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="kt_modal_edit_transaction" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Transaction</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editTransactionForm">
                    @csrf
                    <input type="hidden" name="transaction_id" id="edit_transaction_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <input type="text" class="form-control form-control-solid" id="edit_employee_name" disabled />
                            <input type="hidden" name="user_id" id="edit_user_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Transaction Type</label>
                            <select class="form-select form-select-solid" name="transaction_type" id="edit_transaction_type" required>
                                <option value="allocation">Allocation</option>
                                <option value="award">Award</option>
                                <option value="vesting">Vesting</option>
                                <option value="forfeiture">Forfeiture</option>
                                <option value="payout">Payout</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Transaction Date</label>
                            <input type="date" class="form-control form-control-solid" name="transaction_date" id="edit_transaction_date" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Units</label>
                            <input type="number" class="form-control form-control-solid" name="units" id="edit_units" required min="1" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Unit Value (UGX)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="unit_value" id="edit_unit_value" placeholder="0.00" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_vested" id="edit_is_vested" />
                                <span class="form-check-label fw-semibold">Vested</span>
                            </label>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Score</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_score" id="edit_performance_score" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Multiplier</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_multiplier" id="edit_performance_multiplier" placeholder="1.0" min="0" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" id="edit_description" placeholder="Transaction description" />
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editTransactionBtn">
                            <span class="indicator-label">Update Transaction</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Transaction Modal -->
<div class="modal fade" id="kt_modal_view_transaction" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Transaction Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewTransactionContent">
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

@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let currentType = '';
let currentDepartment = '';
let currentEmployee = '';

// Utility functions
function formatCurrency(amount) {
    if (!amount) return 'UGX 0';
    return 'UGX ' + Number(amount).toLocaleString(undefined, { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
    });
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load form data
function loadFormData() {
    fetch('{{ route("admin.phantom-equity.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Employees
            const empOptions = '<option value="">Select Employee</option>' + 
                data.users.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
            document.getElementById('add_user_id').innerHTML = empOptions;
            
            // Departments
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;
            
            // Filter Departments
            const filterDeptOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterDeptOptions;
            
            // Filter Employees
            const filterEmpOptions = '<option value="">All Employees</option>' + 
                data.users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
            document.getElementById('filterEmployee').innerHTML = filterEmpOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

// Load transactions
function loadTransactions() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');
    
    let url = `{{ route("admin.phantom-equity.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentType) url += `&transaction_type=${currentType}`;
    if (currentDepartment) url += `&department_id=${currentDepartment}`;
    if (currentEmployee) url += `&user_id=${currentEmployee}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.classList.add('d-none');
            if (data.data.length === 0) {
                if (noData) noData.classList.remove('d-none');
            } else {
                if (table) table.classList.remove('d-none');
                renderTransactionsTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load transactions');
        });
}

function updateSummary(summary) {
    document.getElementById('totalUnits').innerHTML = summary.total_units || 0;
    document.getElementById('vestedUnits').innerHTML = summary.vested_units || 0;
    document.getElementById('totalPayout').innerHTML = formatCurrency(summary.total_payout || 0);
    document.getElementById('totalUsers').innerHTML = summary.total_users || 0;
}

function renderTransactionsTable(transactions) {
    const tbody = document.getElementById('transactionsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    transactions.forEach(tx => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${tx.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${tx.reference}</span>`;
        row.insertCell(2).innerHTML = `<span class="badge badge-light-primary">${tx.transaction_type_label}</span>`;
        row.insertCell(3).innerHTML = tx.user ? `<div class="fw-bold">${escapeHtml(tx.user.name)}</div>` : '-';
        row.insertCell(4).innerHTML = tx.department || '-';
        row.insertCell(5).innerHTML = `<span class="text-center d-block">${tx.units}</span>`;
        row.insertCell(6).innerHTML = `<span class="text-center d-block">${tx.vested_units}</span>`;
        row.insertCell(7).innerHTML = `<span class="fw-bold text-success text-end d-block">${tx.formatted_total_value}</span>`;
        row.insertCell(8).innerHTML = tx.vested_badge;
        row.insertCell(9).innerHTML = formatDate(tx.transaction_date);
        row.insertCell(10).innerHTML = getActionButtons(tx);
    });
}

function getActionButtons(tx) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewTransaction(${tx.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editTransaction(${tx.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="deleteTransaction(${tx.id})" title="Delete">
            <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
    `;
    return `<div class="d-flex justify-content-end gap-2">${buttons}</div>`;
}

function renderPagination(data) {
    const el = document.getElementById('pagination');
    const info = document.getElementById('paginationInfo');
    if (!el) return;
    
    el.innerHTML = '';
    if (info) {
        info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
    }
    
    const addPage = (page, text, isActive = false, isDisabled = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        if (!isDisabled) a.onclick = (e) => { e.preventDefault(); changePage(page); };
        li.appendChild(a);
        el.appendChild(li);
    };
    
    addPage(data.current_page - 1, 'Previous', false, !data.prev_page_url);
    let start = Math.max(1, data.current_page - 2);
    let end = Math.min(data.last_page, data.current_page + 2);
    if (start > 1) addPage(1, '1');
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { 
        currentPage = page; 
        loadTransactions(); 
    }
};

// View Transaction
window.viewTransaction = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_transaction'));
    const content = document.getElementById('viewTransactionContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/phantom-equity/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Reference</span><div class="fw-bold fs-4">${data.reference}</div></div>
                <div class="col-md-6"><span class="text-muted">Type</span><div class="fw-bold"><span class="badge badge-light-primary">${data.transaction_type_label}</span></div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Employee</span><div class="fw-bold">${data.user?.name || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Transaction Date</span><div class="fw-bold">${formatDate(data.transaction_date)}</div></div>
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.is_vested ? '<span class="badge badge-light-success">Vested</span>' : '<span class="badge badge-light-warning">Unvested</span>'}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Units</span><div class="fw-bold fs-3">${data.units}</div></div>
                <div class="col-md-4"><span class="text-muted">Vested Units</span><div class="fw-bold">${data.vested_units}</div></div>
                <div class="col-md-4"><span class="text-muted">Unit Value</span><div class="fw-bold">${data.formatted_unit_value}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-12"><span class="text-muted">Total Value</span><div class="fw-bold text-success fs-2">${data.formatted_total_value}</div></div>
            </div>
            ${data.performance_score ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Performance Score</span><div class="fw-bold">${data.performance_score}%</div></div>
                    <div class="col-md-6"><span class="text-muted">Performance Multiplier</span><div class="fw-bold">${data.performance_multiplier}x</div></div>
                </div>
            ` : ''}
            ${data.description ? `
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Description</span><div class="fw-bold">${escapeHtml(data.description)}</div></div>
                </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${formatDate(data.created_at)}</div></div>
            </div>
        `;
        
        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load transaction details</div>';
    });
};

// Edit Transaction
window.editTransaction = function(id) {
    fetch(`/admin/phantom-equity/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_transaction_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.user?.name || '';
        document.getElementById('edit_user_id').value = data.user?.id || '';
        document.getElementById('edit_transaction_type').value = data.transaction_type;
        document.getElementById('edit_transaction_date').value = data.transaction_date ? data.transaction_date.split('T')[0] : '';
        document.getElementById('edit_department_id').value = data.department?.id || '';
        document.getElementById('edit_units').value = data.units || 0;
        document.getElementById('edit_unit_value').value = data.unit_value || '';
        document.getElementById('edit_is_vested').checked = data.is_vested || false;
        document.getElementById('edit_performance_score').value = data.performance_score || '';
        document.getElementById('edit_performance_multiplier').value = data.performance_multiplier || 1.0;
        document.getElementById('edit_description').value = data.description || '';
        
        new bootstrap.Modal(document.getElementById('kt_modal_edit_transaction')).show();
    })
    .catch(err => {
        console.error('Error loading transaction:', err);
        window.showToast('error', 'Failed to load transaction details');
    });
};

// Delete Transaction
window.deleteTransaction = function(id) {
    if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
        fetch(`/admin/phantom-equity/${id}`, {
            method: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                window.showToast('success', data.message); 
                loadTransactions(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete transaction'));
    }
};

// Submit Add Transaction
document.getElementById('addTransactionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('addTransactionBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    // Fix checkbox - explicitly set boolean value
    const isVested = document.querySelector('input[name="is_vested"]');
    if (isVested) {
        formData.set('is_vested', isVested.checked ? '1' : '0');
    } else {
        formData.set('is_vested', '0');
    }
    
    // Ensure unit_value is set
    const unitValue = document.querySelector('input[name="unit_value"]');
    if (unitValue && !unitValue.value) {
        formData.set('unit_value', '0');
    }
    
    fetch('{{ route("admin.phantom-equity.store") }}', {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(data => { throw data; });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_transaction'));
            if (modal) modal.hide();
            this.reset();
            loadTransactions();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create transaction');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create transaction';
        if (err.errors) {
            errorMessage = Object.values(err.errors).flat().join('\n');
        } else if (err.message) {
            errorMessage = err.message;
        }
        window.showToast('error', errorMessage);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });
    
    return false;
});

// Submit Edit Transaction
document.getElementById('editTransactionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('editTransactionBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_transaction_id').value;
    
    const formData = new FormData(this);
    
    // Fix checkbox - explicitly set boolean value
    const isVested = document.querySelector('#edit_is_vested');
    if (isVested) {
        formData.set('is_vested', isVested.checked ? '1' : '0');
    } else {
        formData.set('is_vested', '0');
    }
    
    // Ensure unit_value is set
    const unitValue = document.querySelector('#edit_unit_value');
    if (unitValue && !unitValue.value) {
        formData.set('unit_value', '0');
    }
    
    fetch(`/admin/phantom-equity/${id}`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            return res.json().then(data => { throw data; });
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_transaction'));
            if (modal) modal.hide();
            loadTransactions();
        } else {
            window.showToast('error', data.message || 'Failed to update transaction');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update transaction';
        if (err.errors) {
            errorMessage = Object.values(err.errors).flat().join('\n');
        } else if (err.message) {
            errorMessage = err.message;
        }
        window.showToast('error', errorMessage);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });
    
    return false;
});

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadTransactions();
    
    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadTransactions();
            }, 500);
        });
    }
    
    // Filters
    document.getElementById('filterType')?.addEventListener('change', function() {
        currentType = this.value;
        currentPage = 1;
        loadTransactions();
    });
    
    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadTransactions();
    });
    
    document.getElementById('filterEmployee')?.addEventListener('change', function() {
        currentEmployee = this.value;
        currentPage = 1;
        loadTransactions();
    });
});
</script>
@endpush