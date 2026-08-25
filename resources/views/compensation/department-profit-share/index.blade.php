@extends('layouts.admin')

@section('title', 'Department Profit Share')
@section('page_title', 'Department Profit Share')

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
    <li class="breadcrumb-item text-muted">Profit Share</li>
@endsection

@section('content')
 @can('view profit share periods')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Periods..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="calculated">Calculated</option>
                        <option value="distributed">Distributed</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <select id="filterDepartment" class="form-select form-select-solid w-180px">
                        <option value="">All Departments</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create profit share periods')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_calculate_profit_share">
                <i class="ki-duotone ki-calculator fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i> Calculate Profit Share
            </button>
        </div>
        @endcan
    </div>

    <div class="card-body pt-0">
        <!-- Summary Cards - Compact Version with Shrinking Values -->
        <div class="row g-3 g-xl-5 mb-5">
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle bg-light-primary me-2">
                                <i class="ki-duotone ki-dollar fs-2 text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Periods</span>
                                <span class="fw-bold text-gray-800" id="totalPeriods" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle bg-light-success me-2">
                                <i class="ki-duotone ki-check-circle fs-2 text-success">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Calculated</span>
                                <span class="fw-bold text-gray-800" id="calculatedCount" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle bg-light-info me-2">
                                <i class="ki-duotone ki-wallet fs-2 text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Profit</span>
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="fw-bold text-gray-800" id="totalProfit" style="font-size: clamp(0.7rem, 1.8vw, 1.3rem);">0</span>
                                    <span class="text-muted ms-1 fs-7 flex-shrink-0">UGX</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle bg-light-warning me-2">
                                <i class="ki-duotone ki-chart fs-2 text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Share</span>
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="fw-bold text-gray-800" id="totalShare" style="font-size: clamp(0.7rem, 1.8vw, 1.3rem);">0</span>
                                    <span class="text-muted ms-1 fs-7 flex-shrink-0">UGX</span>
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
            <p class="mt-3 text-muted">Loading profit share periods...</p>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Financial Year</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-120px text-end">Total Profit</th>
                            <th class="min-w-100px">Share %</th>
                            <th class="min-w-120px text-end">Share Amount</th>
                            <th class="min-w-80px text-center">Units</th>
                            <th class="min-w-120px text-end">Unit Value</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="periodsTableBody"></tbody>
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
            <p class="text-muted">No profit share periods found.</p>
        </div>
    </div>
</div>

<!-- Calculate Profit Share Modal -->
<div class="modal fade" id="kt_modal_calculate_profit_share" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Calculate Profit Share</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="calculateProfitShareForm">
                    @csrf
                    
                    <!-- Total Profit Summary -->
                    <div id="profitSummary" class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <div class="fw-bold">Total Profit from Payment Methods</div>
                            <div class="fs-1 fw-bold" id="totalProfitDisplay">UGX 0</div>
                            <div class="text-muted fs-7">Based on all active payment method balances converted to UGX</div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Financial Year</label>
                            <input type="text" class="form-control form-control-solid" name="financial_year" placeholder="e.g., 2024" required />
                            <div class="form-text text-muted">Year for this profit share period</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="calc_department_id">
                                <option value="">All Departments</option>
                            </select>
                            <div class="form-text text-muted">Leave blank for company-wide profit share</div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Profit Share Percentage (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="profit_share_percentage" placeholder="e.g., 10" required min="0" max="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Calculated Share Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" id="calculatedShareAmount" readonly style="background-color: #f5f5f5;" />
                            </div>
                            <div class="form-text text-muted">Auto-calculated based on percentage</div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-12">
                            <div class="alert alert-warning d-flex align-items-center">
                                <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                                <div class="fs-7">
                                    <strong>Note:</strong> This will calculate profit share based on total balances of all active payment methods.
                                    Currencies will be converted to UGX using the current exchange rates.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="calculateBtn">
                            <span class="indicator-label">Calculate Profit Share</span>
                            <span class="indicator-progress">Calculating... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profit Share Modal -->
<div class="modal fade" id="kt_modal_edit_profit_share" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Profit Share</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editProfitShareForm">
                    @csrf
                    <input type="hidden" name="period_id" id="edit_period_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Financial Year</label>
                            <input type="text" class="form-control form-control-solid" id="edit_financial_year" disabled />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <input type="text" class="form-control form-control-solid" id="edit_department_name" disabled />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Total Profit</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" id="edit_total_profit" disabled />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Profit Share Percentage (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="profit_share_percentage" id="edit_profit_share_percentage" required min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Share Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" id="edit_share_amount" disabled />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="edit_status" required>
                                <option value="pending">Pending</option>
                                <option value="calculated">Calculated</option>
                                <option value="distributed">Distributed</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="fw-semibold fs-6 mb-2">Distribution Date</label>
                            <input type="date" class="form-control form-control-solid" name="distribution_date" id="edit_distribution_date" />
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editProfitShareBtn">
                            <span class="indicator-label">Update</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Profit Share Modal -->
<div class="modal fade" id="kt_modal_view_profit_share" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Profit Share Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewProfitShareContent">
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
let currentStatus = '';
let currentDepartment = '';

// Utility functions
function formatCurrency(amount) {
    if (!amount && amount !== 0) return 'UGX 0';
    return 'UGX ' + Number(amount).toLocaleString(undefined, { 
        minimumFractionDigits: 0, 
        maximumFractionDigits: 0 
    });
}

function formatCurrencyShort(amount) {
    if (!amount && amount !== 0) return '0';
    return Number(amount).toLocaleString(undefined, { 
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
    fetch('{{ route("admin.department-profit-share.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Update total profit display
            document.getElementById('totalProfitDisplay').innerHTML = data.formatted_total_profit || 'UGX 0';
            
            // Departments for calculation
            const deptOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('calc_department_id').innerHTML = deptOptions;

            // Filter departments
            const filterDeptOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterDeptOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

// Load periods
function loadPeriods() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');

    let url = `{{ route("admin.department-profit-share.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentDepartment) url += `&department_id=${currentDepartment}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.classList.add('d-none');
            if (data.data.length === 0) {
                if (noData) noData.classList.remove('d-none');
            } else {
                if (table) table.classList.remove('d-none');
                renderPeriodsTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load profit share periods');
        });
}

function updateSummary(summary) {
    document.getElementById('totalPeriods').innerHTML = summary.total_periods || 0;
    document.getElementById('calculatedCount').innerHTML = summary.calculated_count || 0;
    document.getElementById('totalProfit').innerHTML = formatCurrencyShort(summary.total_profit || 0);
    document.getElementById('totalShare').innerHTML = formatCurrencyShort(summary.total_share || 0);
}

function renderPeriodsTable(periods) {
    const tbody = document.getElementById('periodsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    periods.forEach(period => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${period.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="fw-bold">${period.financial_year}</span>`;
        row.insertCell(2).innerHTML = period.department || 'All Departments';
        row.insertCell(3).innerHTML = `<span class="fw-bold text-success text-end d-block">${period.formatted_profit}</span>`;
        row.insertCell(4).innerHTML = `<span class="badge badge-light-primary">${period.profit_share_percentage}%</span>`;
        row.insertCell(5).innerHTML = `<span class="fw-bold text-info text-end d-block">${period.formatted_share}</span>`;
        row.insertCell(6).innerHTML = `<span class="text-center d-block">${period.total_units}</span>`;
        row.insertCell(7).innerHTML = `<span class="text-end d-block">${period.formatted_unit_value}</span>`;
        row.insertCell(8).innerHTML = period.status_badge;
        row.insertCell(9).innerHTML = getActionButtons(period);
    });
}

function getActionButtons(period) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewPeriod(${period.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editPeriod(${period.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="deletePeriod(${period.id})" title="Delete">
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
        loadPeriods();
    }
};

// Calculate share amount in real-time
document.querySelector('input[name="profit_share_percentage"]')?.addEventListener('input', function() {
    const totalProfitDisplay = document.getElementById('totalProfitDisplay').innerHTML;
    const totalProfit = parseInt(totalProfitDisplay.replace(/[^0-9]/g, '')) || 0;
    const percentage = parseFloat(this.value) || 0;
    const shareAmount = Math.round(totalProfit * (percentage / 100));
    document.getElementById('calculatedShareAmount').value = shareAmount.toLocaleString();
});

// Submit Calculate
document.getElementById('calculateProfitShareForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('calculateBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    fetch('{{ route("admin.department-profit-share.calculate") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_calculate_profit_share'));
            if (modal) modal.hide();
            this.reset();
            loadPeriods();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to calculate profit share');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to calculate profit share';
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

// View Period
window.viewPeriod = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_profit_share'));
    const content = document.getElementById('viewProfitShareContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`/admin/department-profit-share/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Financial Year</span><div class="fw-bold fs-4">${data.financial_year}</div></div>
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.status_badge}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department || 'All Departments'}</div></div>
                <div class="col-md-6"><span class="text-muted">Distribution Date</span><div class="fw-bold">${data.distribution_date ? formatDate(data.distribution_date) : 'Not set'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Total Profit</span><div class="fw-bold text-success fs-2">${data.formatted_profit}</div></div>
                <div class="col-md-6"><span class="text-muted">Profit Share %</span><div class="fw-bold">${data.profit_share_percentage}%</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Share Amount</span><div class="fw-bold text-info fs-2">${data.formatted_share}</div></div>
                <div class="col-md-6"><span class="text-muted">Total Units</span><div class="fw-bold">${data.total_units}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Unit Value</span><div class="fw-bold">${data.formatted_unit_value}</div></div>
                <div class="col-md-6"><span class="text-muted">Distributions</span><div class="fw-bold">${data.distributions_count || 0}</div></div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${formatDate(data.created_at)}</div></div>
            </div>
        `;

        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load period details</div>';
    });
};

// Edit Period
window.editPeriod = function(id) {
    fetch(`/admin/department-profit-share/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_period_id').value = data.id;
        document.getElementById('edit_financial_year').value = data.financial_year;
        document.getElementById('edit_department_name').value = data.department || 'All Departments';
        document.getElementById('edit_total_profit').value = formatCurrencyShort(data.total_profit);
        document.getElementById('edit_profit_share_percentage').value = data.profit_share_percentage;
        document.getElementById('edit_share_amount').value = formatCurrencyShort(data.profit_share_amount);
        document.getElementById('edit_status').value = data.status || 'pending';
        document.getElementById('edit_distribution_date').value = data.distribution_date || '';

        new bootstrap.Modal(document.getElementById('kt_modal_edit_profit_share')).show();
    })
    .catch(err => {
        console.error('Error loading period:', err);
        window.showToast('error', 'Failed to load period details');
    });
};

// Submit Edit
document.getElementById('editProfitShareForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('editProfitShareBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_period_id').value;

    const formData = new FormData(this);

    fetch(`/admin/department-profit-share/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_profit_share'));
            if (modal) modal.hide();
            loadPeriods();
        } else {
            window.showToast('error', data.message || 'Failed to update period');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update period';
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

// Delete Period
window.deletePeriod = function(id) {
    if (confirm('Are you sure you want to delete this profit share period? This action cannot be undone.')) {
        fetch(`/admin/department-profit-share/${id}`, {
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
                loadPeriods();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete period'));
    }
};

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadPeriods();

    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPeriods();
            }, 500);
        });
    }

    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadPeriods();
    });

    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadPeriods();
    });
});
</script>
<style>
.spinning {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
@endpush