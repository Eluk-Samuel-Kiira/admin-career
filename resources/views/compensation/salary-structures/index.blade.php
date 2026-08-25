@extends('layouts.admin')

@section('title', 'Salary Structures')
@section('page_title', 'Salary Structures')

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
    <li class="breadcrumb-item text-muted">Salary Structures</li>
@endsection

@section('content')
@can('view salary structure')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Salary Structures..." />
                </div>
                <div>
                    <select id="filterDepartment" class="form-select form-select-solid w-180px">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="true">Active</option>
                        <option value="false">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create salary structure')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_salary_structure">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Salary Structure
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
                                <i class="ki-duotone ki-dollar fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Structures</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalStructures">0</span>
                                    <span class="text-muted ms-2">Records</span>
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
                                <span class="text-gray-600 fw-semibold">Active</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="activeStructures">0</span>
                                    <span class="text-muted ms-2">Records</span>
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
                            <div class="symbol symbol-50px symbol-circle bg-light-danger me-3">
                                <i class="ki-duotone ki-cross-circle fs-2x text-danger">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Inactive</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="inactiveStructures">0</span>
                                    <span class="text-muted ms-2">Records</span>
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
                                <i class="ki-duotone ki-wallet fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Total Budget (UGX)</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalBudget">0</span>
                                    <!-- <span class="text-muted ms-2">UGX</span> -->
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
            <p class="mt-3 text-muted">Loading salary structures...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Job Title</th>
                            <th class="min-w-100px">Role Code</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-120px">Salary Type</th>
                            <th class="min-w-120px text-end">Base Salary</th>
                            <th class="min-w-100px">Phantom Equity</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="structuresTableBody"></tbody>
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
            <p class="text-muted">No salary structures found.</p>
        </div>
    </div>
</div>

<!-- Add Salary Structure Modal -->
<div class="modal fade" id="kt_modal_add_salary_structure" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Salary Structure</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addSalaryStructureForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" name="job_title" placeholder="e.g., Operations Officer" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Role Code</label>
                            <input type="text" class="form-control form-control-solid" name="role_code" placeholder="e.g., OO" required />
                            <div class="form-text text-muted">Unique code for this role</div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="add_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h4 class="fw-bold mb-5">Salary Details</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Base Salary</label>
                            <div class="input-group">
                                <span class="input-group-text" id="add_currency_symbol">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="base_salary" placeholder="0.00" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary Type</label>
                            <select class="form-select form-select-solid" name="salary_type" required>
                                <option value="fixed">Fixed Salary</option>
                                <option value="hourly">Hourly Rate</option>
                                <option value="commission">Commission Based</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="min_salary" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Max Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="max_salary" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" checked />
                                <span class="form-check-label fw-semibold">Active</span>
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h4 class="fw-bold mb-5">Bonus & Incentives</h4>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Phantom Equity Units</label>
                            <input type="number" class="form-control form-control-solid" name="phantom_equity_units" placeholder="0" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Profit Share %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="profit_share_percentage" placeholder="0" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Commission Rate %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="commission_rate" placeholder="0" value="0" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Performance Bonus %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_bonus_percentage" placeholder="0" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Performance Bonus Max</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="performance_bonus_max" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Retention Bonus</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="retention_bonus" placeholder="0.00" />
                            </div>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Additional details about this salary structure..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addStructureBtn">
                            <span class="indicator-label">Create Structure</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Salary Structure Modal -->
<div class="modal fade" id="kt_modal_edit_salary_structure" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Salary Structure</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editSalaryStructureForm">
                    @csrf
                    <input type="hidden" name="structure_id" id="edit_structure_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Job Title</label>
                            <input type="text" class="form-control form-control-solid" name="job_title" id="edit_job_title" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Role Code</label>
                            <input type="text" class="form-control form-control-solid" name="role_code" id="edit_role_code" required />
                            <div class="form-text text-muted">Unique code for this role</div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="edit_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h4 class="fw-bold mb-5">Salary Details</h4>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Base Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="base_salary" id="edit_base_salary" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Salary Type</label>
                            <select class="form-select form-select-solid" name="salary_type" id="edit_salary_type" required>
                                <option value="fixed">Fixed Salary</option>
                                <option value="hourly">Hourly Rate</option>
                                <option value="commission">Commission Based</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="min_salary" id="edit_min_salary" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Max Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="max_salary" id="edit_max_salary" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                                <span class="form-check-label fw-semibold">Active</span>
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h4 class="fw-bold mb-5">Bonus & Incentives</h4>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Phantom Equity Units</label>
                            <input type="number" class="form-control form-control-solid" name="phantom_equity_units" id="edit_phantom_equity_units" placeholder="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Profit Share %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="profit_share_percentage" id="edit_profit_share_percentage" placeholder="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Commission Rate %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="commission_rate" id="edit_commission_rate" placeholder="0" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Performance Bonus %</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_bonus_percentage" id="edit_performance_bonus_percentage" placeholder="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Performance Bonus Max</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="performance_bonus_max" id="edit_performance_bonus_max" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Retention Bonus</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="retention_bonus" id="edit_retention_bonus" placeholder="0.00" />
                            </div>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3" placeholder="Additional details about this salary structure..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editStructureBtn">
                            <span class="indicator-label">Update Structure</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Salary Structure Modal -->
<div class="modal fade" id="kt_modal_view_salary_structure" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Salary Structure Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewStructureContent">
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
let currentDepartment = '';
let currentStatus = '';

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatCurrency(amount) {
    if (!amount && amount !== 0) return 'N/A';
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// LOAD DATA FUNCTIONS
// ============================================

function loadFormData() {
    fetch('{{ route("admin.salary-structures.form-data") }}')
        .then(res => res.json())
        .then(data => {
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;
            
            const filterOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterOptions;
            
            const currencyOptions = '<option value="">Select Currency</option>' + 
                data.currencies.map(c => `<option value="${c.id}">${c.code} - ${c.name} (${c.symbol})</option>`).join('');
            document.getElementById('add_currency_id').innerHTML = currencyOptions;
            document.getElementById('edit_currency_id').innerHTML = currencyOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

function loadSalaryStructures() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');
    
    let url = `{{ route("admin.salary-structures.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentDepartment) url += `&department_id=${currentDepartment}`;
    if (currentStatus !== '') url += `&is_active=${currentStatus}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (spinner) spinner.classList.add('d-none');
            if (data.data.length === 0) {
                if (noData) noData.classList.remove('d-none');
            } else {
                if (table) table.classList.remove('d-none');
                renderStructuresTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load salary structures');
        });
}

function updateSummary(summary) {
    document.getElementById('totalStructures').innerHTML = summary.total || 0;
    document.getElementById('activeStructures').innerHTML = summary.active || 0;
    document.getElementById('inactiveStructures').innerHTML = summary.inactive || 0;
    document.getElementById('totalBudget').innerHTML = formatCurrencyShort(summary.total_budget || 0);
}

function renderStructuresTable(structures) {
    const tbody = document.getElementById('structuresTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    structures.forEach(structure => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${structure.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(structure.job_title)}</div>`;
        row.insertCell(2).innerHTML = `<span class="badge badge-light-primary">${escapeHtml(structure.role_code)}</span>`;
        row.insertCell(3).innerHTML = structure.department || '-';
        row.insertCell(4).innerHTML = `<span class="badge badge-light-info">${structure.salary_type_label}</span>`;
        row.insertCell(5).innerHTML = `<span class="fw-bold text-success text-end d-block">${structure.formatted_salary}</span>`;
        row.insertCell(6).innerHTML = `<span class="badge badge-light-warning">${structure.phantom_equity_units} units</span>`;
        row.insertCell(7).innerHTML = structure.status_badge;
        row.insertCell(8).innerHTML = getActionButtons(structure);
    });
}

function getActionButtons(structure) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewStructure(${structure.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editStructure(${structure.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="toggleStructureStatus(${structure.id}, ${structure.is_active})" title="${structure.is_active ? 'Deactivate' : 'Activate'}">
            <i class="ki-duotone ki-${structure.is_active ? 'disconnect' : 'check'} fs-3 ${structure.is_active ? 'text-warning' : 'text-success'}"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="deleteStructure(${structure.id})" title="Delete">
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
        loadSalaryStructures(); 
    }
};

// ============================================
// CRUD OPERATIONS
// ============================================

// View Structure
window.viewStructure = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_salary_structure'));
    const content = document.getElementById('viewStructureContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/salary-structures/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        // Helper to format or show N/A
        const formatValue = (value) => {
            if (value === null || value === undefined || value === '') return 'N/A';
            return value;
        };

        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Job Title</span><div class="fw-bold fs-4">${escapeHtml(data.job_title)}</div></div>
                <div class="col-md-6"><span class="text-muted">Role Code</span><div class="fw-bold"><span class="badge badge-light-primary fs-3">${escapeHtml(data.role_code)}</span></div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${escapeHtml(data.department || 'N/A')}</div></div>
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Salary Type</span><div class="fw-bold">${escapeHtml(data.salary_type_label)}</div></div>
                <div class="col-md-6"><span class="text-muted">Base Salary</span><div class="fw-bold text-success fs-3">${data.formatted_salary}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Min Salary</span><div class="fw-bold">${data.min_salary !== null && data.min_salary !== '' ? formatCurrency(data.min_salary) : 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Max Salary</span><div class="fw-bold">${data.max_salary !== null && data.max_salary !== '' ? formatCurrency(data.max_salary) : 'N/A'}</div></div>
            </div>
            <div class="separator my-5"></div>
            <h5 class="fw-bold mb-3">Bonus & Incentives</h5>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Phantom Equity Units</span><div class="fw-bold">${data.phantom_equity_units || 0}</div></div>
                <div class="col-md-4"><span class="text-muted">Profit Share %</span><div class="fw-bold">${data.profit_share_percentage || 0}%</div></div>
                <div class="col-md-4"><span class="text-muted">Commission Rate</span><div class="fw-bold">${data.commission_rate || 0}%</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Performance Bonus</span><div class="fw-bold">${data.performance_bonus_percentage || 0}%</div></div>
                <div class="col-md-4"><span class="text-muted">Performance Bonus Max</span><div class="fw-bold">${data.performance_bonus_max !== null && data.performance_bonus_max !== '' ? formatCurrency(data.performance_bonus_max) : 'N/A'}</div></div>
                <div class="col-md-4"><span class="text-muted">Retention Bonus</span><div class="fw-bold">${data.retention_bonus !== null && data.retention_bonus !== '' ? formatCurrency(data.retention_bonus) : 'N/A'}</div></div>
            </div>
            ${data.description ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Description</span><div class="fw-bold">${escapeHtml(data.description)}</div></div>
                </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${data.created_at}</div></div>
            </div>
        `;
        
        content.innerHTML = html;
    })
    .catch(err => {
        console.error('Error:', err);
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load structure details</div>';
    });
};

// Edit Structure
window.editStructure = function(id) {
    fetch(`/admin/salary-structures/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_structure_id').value = data.id;
        document.getElementById('edit_job_title').value = data.job_title;
        document.getElementById('edit_role_code').value = data.role_code;
        document.getElementById('edit_department_id').value = data.department_id || '';
        document.getElementById('edit_currency_id').value = data.currency_id;
        
        // Handle null/undefined values properly
        document.getElementById('edit_base_salary').value = data.base_salary || 0;
        document.getElementById('edit_salary_type').value = data.salary_type || 'fixed';
        document.getElementById('edit_min_salary').value = data.min_salary || '';
        document.getElementById('edit_max_salary').value = data.max_salary || '';
        document.getElementById('edit_is_active').checked = data.is_active || false;
        
        document.getElementById('edit_phantom_equity_units').value = data.phantom_equity_units || 0;
        document.getElementById('edit_profit_share_percentage').value = data.profit_share_percentage || 0;
        document.getElementById('edit_commission_rate').value = data.commission_rate || 0;
        document.getElementById('edit_performance_bonus_percentage').value = data.performance_bonus_percentage || 0;
        document.getElementById('edit_performance_bonus_max').value = data.performance_bonus_max || '';
        document.getElementById('edit_retention_bonus').value = data.retention_bonus || '';
        document.getElementById('edit_description').value = data.description || '';
        
        new bootstrap.Modal(document.getElementById('kt_modal_edit_salary_structure')).show();
    })
    .catch(err => {
        console.error('Error loading structure:', err);
        window.showToast('error', 'Failed to load salary structure details');
    });
};

// Toggle Status
window.toggleStructureStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this salary structure?`)) {
        fetch(`/admin/salary-structures/${id}/toggle-status`, {
            method: 'POST',
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
                loadSalaryStructures(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle status'));
    }
};

// Delete Structure
window.deleteStructure = function(id) {
    if (confirm('⚠️ WARNING: This will permanently delete this salary structure.\n\nAre you sure you want to continue?')) {
        fetch(`/admin/salary-structures/${id}`, {
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
                loadSalaryStructures(); 
            } else {
                window.showToast('error', data.message || 'Failed to delete structure');
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete salary structure'));
    }
};

// ============================================
// FORM SUBMISSIONS
// ============================================

document.getElementById('addSalaryStructureForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('addStructureBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.salary-structures.store") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_salary_structure'));
            if (modal) modal.hide();
            this.reset();
            loadSalaryStructures();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create structure');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create salary structure';
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

document.getElementById('editSalaryStructureForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('editStructureBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_structure_id').value;
    
    const formData = new FormData(this);
    
    fetch(`/admin/salary-structures/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_salary_structure'));
            if (modal) modal.hide();
            loadSalaryStructures();
        } else {
            window.showToast('error', data.message || 'Failed to update structure');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update salary structure';
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

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadSalaryStructures();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadSalaryStructures();
            }, 500);
        });
    }
    
    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadSalaryStructures();
    });
    
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadSalaryStructures();
    });
});
</script>
@endpush