@extends('layouts.admin')

@section('title', 'Profit Share Distribution')
@section('page_title', 'Profit Share Distribution')

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
@can('view profit share')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Distributions..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
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
        <div class="card-toolbar">
            <div class="d-flex gap-3">
            @can('create profit share')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_distribution">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i> New Distribution
                </button>
            @endcan
            @can('distribute profit share')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kt_modal_bulk_distribution">
                    <i class="ki-duotone ki-copy fs-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i> Bulk Distribute
                </button>
            </div>
            @endcan
        </div>
    </div>

    <div class="card-body pt-0">
        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5">
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
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Distributions</span>
                                <span class="fw-bold text-gray-800" id="totalDistributions" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
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
                                <i class="ki-duotone ki-time fs-2 text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Pending</span>
                                <span class="fw-bold text-gray-800" id="pendingCount" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
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
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Paid</span>
                                <span class="fw-bold text-gray-800" id="paidCount" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
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
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Amount</span>
                                <div class="d-flex align-items-center flex-wrap">
                                    <span class="fw-bold text-gray-800" id="totalAmount" style="font-size: clamp(0.7rem, 1.8vw, 1.3rem);">0</span>
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
            <p class="mt-3 text-muted">Loading distributions...</p>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Reference</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-80px text-center">Units</th>
                            <th class="min-w-80px text-center">Vested</th>
                            <th class="min-w-120px text-end">Amount</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Date</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="distributionsTableBody"></tbody>
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
            <p class="text-muted">No profit share distributions found.</p>
        </div>
    </div>
</div>

<!-- Add Distribution Modal -->
<div class="modal fade" id="kt_modal_add_distribution" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Profit Share Distribution</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addDistributionForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Profit Share Period</label>
                            <select class="form-select form-select-solid" name="department_profit_share_id" id="add_profit_share_id" required>
                                <option value="">Select Period</option>
                            </select>
                            <div id="periodBalanceInfo" class="form-text text-muted mt-1"></div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Distribution Date</label>
                            <input type="date" class="form-control form-control-solid" name="distribution_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Units Held</label>
                            <input type="number" class="form-control form-control-solid" name="units_held" id="add_units_held" placeholder="0" required min="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Vested Units</label>
                            <input type="number" class="form-control form-control-solid" name="vested_units" id="add_vested_units" placeholder="0" required min="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Unit Value (UGX)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="unit_value" id="add_unit_value" placeholder="0.00" required min="0" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="add_status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <input type="text" class="form-control form-control-solid" id="add_total_amount" readonly style="background-color: #f5f5f5;" />
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center" id="balanceAlert">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <span class="fw-bold">Available Balance:</span>
                            <span id="availableBalance">UGX 0</span>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addDistributionBtn">
                            <span class="indicator-label">Create Distribution</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Distribution Modal -->
<div class="modal fade" id="kt_modal_bulk_distribution" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Bulk Profit Share Distribution</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="bulkDistributionForm">
                    @csrf
                    <div class="alert alert-warning d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            This will automatically distribute the remaining balance to all eligible employees in the selected department based on their vested units.
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Profit Share Period</label>
                            <select class="form-select form-select-solid" name="department_profit_share_id" id="bulk_profit_share_id" required>
                                <option value="">Select Period</option>
                            </select>
                            <div id="bulk_periodBalanceInfo" class="form-text text-muted mt-1"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Distribution Date</label>
                            <input type="date" class="form-control form-control-solid" name="distribution_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <div><strong>Available Balance:</strong> <span id="bulk_availableBalance">UGX 0</span></div>
                            <div class="mt-1"><strong>Estimated Employees:</strong> <span id="bulk_employeeCount">0</span></div>
                            <div class="mt-1"><strong>Estimated Unit Value:</strong> <span id="bulk_unitValue">UGX 0</span></div>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="bulkDistributionBtn">
                            <span class="indicator-label">Distribute to All</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Distribution Modal -->
<div class="modal fade" id="kt_modal_edit_distribution" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Distribution</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editDistributionForm">
                    @csrf
                    <input type="hidden" name="distribution_id" id="edit_distribution_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <input type="text" class="form-control form-control-solid" id="edit_employee_name" disabled />
                            <input type="hidden" name="user_id" id="edit_user_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <input type="text" class="form-control form-control-solid" id="edit_department_name" disabled />
                            <input type="hidden" name="department_id" id="edit_department_id" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Profit Share Period</label>
                            <input type="text" class="form-control form-control-solid" id="edit_profit_share_period" disabled />
                            <input type="hidden" name="department_profit_share_id" id="edit_profit_share_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Distribution Date</label>
                            <input type="date" class="form-control form-control-solid" name="distribution_date" id="edit_distribution_date" required />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Units Held</label>
                            <input type="number" class="form-control form-control-solid" name="units_held" id="edit_units_held" required min="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Vested Units</label>
                            <input type="number" class="form-control form-control-solid" name="vested_units" id="edit_vested_units" required min="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Unit Value (UGX)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="unit_value" id="edit_unit_value" required min="0" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="edit_status" required>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <input type="text" class="form-control form-control-solid" id="edit_total_amount" readonly style="background-color: #f5f5f5;" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" id="edit_notes" rows="3"></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editDistributionBtn">
                            <span class="indicator-label">Update Distribution</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Distribution Modal -->
<div class="modal fade" id="kt_modal_view_distribution" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Distribution Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewDistributionContent">
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

<!-- Mark as Paid Modal -->
<div class="modal fade" id="kt_modal_mark_paid" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Mark as Paid</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="markPaidForm">
                    @csrf
                    <input type="hidden" name="distribution_id" id="mark_paid_distribution_id">
                    
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Distribution: <span id="mark_paid_reference"></span></strong><br>
                            <span class="text-muted">Amount: <span id="mark_paid_amount"></span></span><br>
                            <span class="text-muted">Employee: <span id="mark_paid_employee"></span></span>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                        <select class="form-select form-select-solid" name="payment_method_id" id="mark_paid_payment_method_id" required>
                            <option value="">Select Payment Method</option>
                        </select>
                        <div class="form-text text-muted mt-2">
                            <span id="selected_payment_balance">Balance: --</span>
                        </div>
                    </div>

                    <div id="paymentMethodWarning" class="alert alert-warning d-none">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <span class="fw-bold">Insufficient Balance!</span><br>
                            <span>Please select a different payment method.</span>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="markPaidBtn">
                            <span class="indicator-label">Confirm Payment</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
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
let currentEmployee = '';
let markPaidId = null;

// Utility functions
function formatCurrency(amount) {
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
    fetch('{{ route("admin.profit-share.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Departments - Updated for distribution
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;

            // Profit Share Periods with balance info
            let psOptions = '<option value="">Select Period</option>';
            if (data.department_profit_shares && data.department_profit_shares.length > 0) {
                psOptions += data.department_profit_shares.map(p => 
                    `<option value="${p.id}" data-department="${p.department_id}" data-remaining="${p.remaining_amount}" data-total="${p.total_amount}" data-distributed="${p.distributed_amount}">
                        ${p.financial_year} - ${p.department_name} (Remaining: ${p.formatted_remaining})
                    </option>`
                ).join('');
            } else {
                psOptions += '<option value="" disabled>No periods available</option>';
            }
            document.getElementById('add_profit_share_id').innerHTML = psOptions;

            // Bulk distribution periods
            let bulkPsOptions = '<option value="">Select Period</option>';
            if (data.department_profit_shares && data.department_profit_shares.length > 0) {
                bulkPsOptions += data.department_profit_shares.map(p => 
                    `<option value="${p.id}" data-department="${p.department_id}" data-remaining="${p.remaining_amount}" data-total="${p.total_amount}" data-distributed="${p.distributed_amount}">
                        ${p.financial_year} - ${p.department_name} (Remaining: ${p.formatted_remaining})
                    </option>`
                ).join('');
            } else {
                bulkPsOptions += '<option value="" disabled>No periods available</option>';
            }
            document.getElementById('bulk_profit_share_id').innerHTML = bulkPsOptions;

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

// Load employees by department - IMPROVED
function loadDepartmentEmployees(departmentId) {
    const employeeSelect = document.getElementById('add_user_id');
    if (!departmentId) {
        employeeSelect.innerHTML = '<option value="">Select Employee</option>';
        return;
    }

    // Show loading state
    employeeSelect.innerHTML = '<option value="">Loading employees...</option>';

    fetch(`/admin/profit-share/department/${departmentId}/employees`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to load employees');
        }
        return res.json();
    })
    .then(data => {
        if (data.success && data.employees && data.employees.length > 0) {
            let options = '<option value="">Select Employee</option>';
            data.employees.forEach(e => {
                options += `<option value="${e.id}" 
                    data-salary-id="${e.employee_salary_id || ''}" 
                    data-units="${e.phantom_equity_units || 0}" 
                    data-vested="${e.vested_units || 0}">
                    ${e.name} (${e.email || ''}) - Units: ${e.phantom_equity_units || 0}, Vested: ${e.vested_units || 0}
                </option>`;
            });
            employeeSelect.innerHTML = options;
        } else {
            employeeSelect.innerHTML = '<option value="">No employees found in this department</option>';
        }
    })
    .catch(err => {
        console.error('Error loading employees:', err);
        employeeSelect.innerHTML = '<option value="">Error loading employees</option>';
        window.showToast('error', 'Failed to load employees for this department');
    });
}

// Also auto-select employee when department changes and only one employee exists
document.getElementById('add_department_id')?.addEventListener('change', function() {
    const departmentId = this.value;
    loadDepartmentEmployees(departmentId);
    
    // Also update period options based on department if needed
    if (departmentId) {
        // Filter profit share periods by department
        const periodSelect = document.getElementById('add_profit_share_id');
        const options = periodSelect.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value && opt.dataset.department) {
                if (opt.dataset.department == departmentId || opt.dataset.department == '') {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            }
        });
        // Reset period selection
        periodSelect.value = '';
        document.getElementById('periodBalanceInfo').innerHTML = '';
        document.getElementById('availableBalance').innerHTML = 'UGX 0';
    } else {
        // Show all periods
        const periodSelect = document.getElementById('add_profit_share_id');
        const options = periodSelect.querySelectorAll('option');
        options.forEach(opt => {
            opt.style.display = '';
        });
    }
});


// Load employees by department - IMPROVED
function loadDepartmentEmployees(departmentId) {
    const employeeSelect = document.getElementById('add_user_id');
    if (!departmentId) {
        employeeSelect.innerHTML = '<option value="">Select Employee</option>';
        return;
    }

    // Show loading state
    employeeSelect.innerHTML = '<option value="">Loading employees...</option>';

    fetch(`/admin/profit-share/department/${departmentId}/employees`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to load employees');
        }
        return res.json();
    })
    .then(data => {
        if (data.success && data.employees && data.employees.length > 0) {
            let options = '<option value="">Select Employee</option>';
            data.employees.forEach(e => {
                options += `<option value="${e.id}" 
                    data-salary-id="${e.employee_salary_id || ''}" 
                    data-units="${e.phantom_equity_units || 0}" 
                    data-vested="${e.vested_units || 0}">
                    ${e.name} (${e.email || ''}) - Units: ${e.phantom_equity_units || 0}, Vested: ${e.vested_units || 0}
                </option>`;
            });
            employeeSelect.innerHTML = options;
        } else {
            employeeSelect.innerHTML = '<option value="">No employees found in this department</option>';
        }
    })
    .catch(err => {
        console.error('Error loading employees:', err);
        employeeSelect.innerHTML = '<option value="">Error loading employees</option>';
        window.showToast('error', 'Failed to load employees for this department');
    });
}

// Also auto-select employee when department changes and only one employee exists
document.getElementById('add_department_id')?.addEventListener('change', function() {
    const departmentId = this.value;
    loadDepartmentEmployees(departmentId);
    
    // Also update period options based on department if needed
    if (departmentId) {
        // Filter profit share periods by department
        const periodSelect = document.getElementById('add_profit_share_id');
        const options = periodSelect.querySelectorAll('option');
        options.forEach(opt => {
            if (opt.value && opt.dataset.department) {
                if (opt.dataset.department == departmentId || opt.dataset.department == '') {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            }
        });
        // Reset period selection
        periodSelect.value = '';
        document.getElementById('periodBalanceInfo').innerHTML = '';
        document.getElementById('availableBalance').innerHTML = 'UGX 0';
    } else {
        // Show all periods
        const periodSelect = document.getElementById('add_profit_share_id');
        const options = periodSelect.querySelectorAll('option');
        options.forEach(opt => {
            opt.style.display = '';
        });
    }
});

// Load available balance for a period
function loadPeriodBalance(periodId, type = 'add') {
    if (!periodId) {
        if (type === 'add') {
            document.getElementById('availableBalance').innerHTML = 'UGX 0';
            document.getElementById('periodBalanceInfo').innerHTML = '';
        } else {
            document.getElementById('bulk_availableBalance').innerHTML = 'UGX 0';
            document.getElementById('bulk_periodBalanceInfo').innerHTML = '';
        }
        return;
    }

    fetch(`/admin/profit-share/period/${periodId}/balance`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const formattedRemaining = data.period.formatted_remaining;
            const formattedTotal = data.period.formatted_total;
            const formattedDistributed = data.period.formatted_distributed;
            
            if (type === 'add') {
                document.getElementById('availableBalance').innerHTML = formattedRemaining;
                document.getElementById('periodBalanceInfo').innerHTML = 
                    `Total: ${formattedTotal} | Distributed: ${formattedDistributed} | Remaining: ${formattedRemaining}`;
            } else {
                document.getElementById('bulk_availableBalance').innerHTML = formattedRemaining;
                document.getElementById('bulk_periodBalanceInfo').innerHTML = 
                    `Total: ${formattedTotal} | Distributed: ${formattedDistributed} | Remaining: ${formattedRemaining}`;
            }
        }
    })
    .catch(err => console.error('Error loading balance:', err));
}

// Load distributions
function loadDistributions() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');

    let url = `{{ route("admin.profit-share.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
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
                renderDistributionsTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load distributions');
        });
}

function updateSummary(summary) {
    document.getElementById('totalDistributions').innerHTML = summary.total_distributions || 0;
    document.getElementById('pendingCount').innerHTML = summary.pending_count || 0;
    document.getElementById('paidCount').innerHTML = summary.paid_count || 0;
    document.getElementById('totalAmount').innerHTML = formatCurrency(summary.total_amount || 0);
}

function renderDistributionsTable(distributions) {
    const tbody = document.getElementById('distributionsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    distributions.forEach(dist => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${dist.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${dist.reference}</span>`;
        row.insertCell(2).innerHTML = dist.user ? `<div class="fw-bold">${escapeHtml(dist.user.name)}</div>` : '-';
        row.insertCell(3).innerHTML = dist.department || '-';
        row.insertCell(4).innerHTML = `<span class="text-center d-block">${dist.units_held}</span>`;
        row.insertCell(5).innerHTML = `<span class="text-center d-block">${dist.vested_units}</span>`;
        row.insertCell(6).innerHTML = `<span class="fw-bold text-success text-end d-block">${dist.formatted_total}</span>`;
        row.insertCell(7).innerHTML = dist.status_badge;
        row.insertCell(8).innerHTML = formatDate(dist.distribution_date);
        row.insertCell(9).innerHTML = getActionButtons(dist);
    });
}

function getActionButtons(dist) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewDistribution(${dist.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
        <button class="btn btn-sm btn-icon btn-light" onclick="editDistribution(${dist.id})" title="Edit">
            <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
        </button>
    `;

    if (dist.status === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="markAsPaid(${dist.id})" title="Mark as Paid">
                <i class="ki-duotone ki-dollar fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }

    buttons += `
        <button class="btn btn-sm btn-icon btn-light" onclick="deleteDistribution(${dist.id})" title="Delete">
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
        loadDistributions();
    }
};

// ============================================
// CRUD OPERATIONS
// ============================================

// View Distribution
window.viewDistribution = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_distribution'));
    const content = document.getElementById('viewDistributionContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`/admin/profit-share/${id}`, {
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
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.status_badge}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Employee</span><div class="fw-bold">${data.user?.name || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Financial Year</span><div class="fw-bold">${data.department_profit_share?.financial_year || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Distribution Date</span><div class="fw-bold">${formatDate(data.distribution_date)}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Units Held</span><div class="fw-bold fs-3">${data.units_held}</div></div>
                <div class="col-md-4"><span class="text-muted">Vested Units</span><div class="fw-bold">${data.vested_units}</div></div>
                <div class="col-md-4"><span class="text-muted">Unit Value</span><div class="fw-bold">${formatCurrency(data.unit_value)}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-12"><span class="text-muted">Total Amount</span><div class="fw-bold text-success fs-2">${data.formatted_total}</div></div>
            </div>
            ${data.notes ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Notes</span><div class="fw-bold">${escapeHtml(data.notes)}</div></div>
                </div>
            ` : ''}
            ${data.paid_by ? `
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Paid By</span><div class="fw-bold">${data.paid_by.name}</div></div>
                </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${formatDate(data.created_at)}</div></div>
            </div>
        `;

        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load distribution details</div>';
    });
};

// Edit Distribution - Fixed date handling
window.editDistribution = function(id) {
    fetch(`/admin/profit-share/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to load distribution');
        }
        return res.json();
    })
    .then(data => {
        document.getElementById('edit_distribution_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.user?.name || '';
        document.getElementById('edit_user_id').value = data.user?.id || '';
        document.getElementById('edit_department_name').value = data.department?.name || '';
        document.getElementById('edit_department_id').value = data.department?.id || '';
        document.getElementById('edit_profit_share_period').value = data.department_profit_share?.financial_year || '';
        document.getElementById('edit_profit_share_id').value = data.department_profit_share?.id || '';
        
        // Fix date format - convert to YYYY-MM-DD for date input
        if (data.distribution_date) {
            const date = new Date(data.distribution_date);
            if (!isNaN(date.getTime())) {
                document.getElementById('edit_distribution_date').value = date.toISOString().split('T')[0];
            } else {
                // Try to parse as string
                const dateStr = data.distribution_date;
                if (dateStr.includes('-')) {
                    const parts = dateStr.split(' ');
                    document.getElementById('edit_distribution_date').value = parts[0];
                } else {
                    document.getElementById('edit_distribution_date').value = '';
                }
            }
        } else {
            document.getElementById('edit_distribution_date').value = '';
        }
        
        document.getElementById('edit_units_held').value = data.units_held || 0;
        document.getElementById('edit_vested_units').value = data.vested_units || 0;
        document.getElementById('edit_unit_value').value = data.unit_value || 0;
        document.getElementById('edit_status').value = data.status || 'pending';
        document.getElementById('edit_notes').value = data.notes || '';
        document.getElementById('edit_total_amount').value = data.formatted_total || 'UGX 0';

        new bootstrap.Modal(document.getElementById('kt_modal_edit_distribution')).show();
    })
    .catch(err => {
        console.error('Error loading distribution:', err);
        window.showToast('error', 'Failed to load distribution details');
    });
};



// Mark as Paid - Load payment methods
window.markAsPaid = function(id) {
    fetch(`/admin/profit-share/${id}/payment-data`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            window.showToast('error', data.message || 'Failed to load payment data');
            return;
        }

        // Set distribution data
        document.getElementById('mark_paid_distribution_id').value = data.distribution.id;
        document.getElementById('mark_paid_reference').innerHTML = data.distribution.reference;
        document.getElementById('mark_paid_amount').innerHTML = data.distribution.formatted_total;
        document.getElementById('mark_paid_employee').innerHTML = data.distribution.user?.name || 'N/A';

        // Load payment methods
        const pmOptions = '<option value="">Select Payment Method</option>' + 
            data.payment_methods.map(pm => 
                `<option value="${pm.id}" data-balance="${pm.balance}" data-currency="${pm.currency}" data-formatted-balance="${pm.formatted_balance}">
                    ${pm.name} (${pm.currency}) - ${pm.formatted_balance}
                </option>`
            ).join('');
        document.getElementById('mark_paid_payment_method_id').innerHTML = pmOptions;

        // Reset warning
        document.getElementById('paymentMethodWarning').classList.add('d-none');

        new bootstrap.Modal(document.getElementById('kt_modal_mark_paid')).show();
    })
    .catch(err => {
        console.error('Error loading payment data:', err);
        window.showToast('error', 'Failed to load payment data');
    });
};

// Payment method selection - check balance
document.getElementById('mark_paid_payment_method_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        document.getElementById('selected_payment_balance').innerHTML = 'Balance: --';
        document.getElementById('paymentMethodWarning').classList.add('d-none');
        return;
    }

    const balance = parseInt(selectedOption.dataset.balance) || 0;
    const formattedBalance = selectedOption.dataset.formattedBalance || 'UGX 0';
    const currency = selectedOption.dataset.currency || 'UGX';
    
    document.getElementById('selected_payment_balance').innerHTML = `Balance: ${formattedBalance}`;

    // Get the amount to be paid
    const amountText = document.getElementById('mark_paid_amount').innerHTML;
    const amount = parseInt(amountText.replace(/[^0-9]/g, '')) || 0;
    const amountInCents = amount; // Already in cents

    // Check if balance is sufficient
    if (balance < amountInCents) {
        document.getElementById('paymentMethodWarning').classList.remove('d-none');
        document.getElementById('paymentMethodWarning').querySelector('span:last-child').innerHTML = 
            `Available balance (${formattedBalance}) is less than required amount (${amountText}).`;
    } else {
        document.getElementById('paymentMethodWarning').classList.add('d-none');
    }
});

// Submit Mark as Paid
document.getElementById('markPaidForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('markPaidBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);
    const id = document.getElementById('mark_paid_distribution_id').value;

    fetch(`/admin/profit-share/${id}/mark-paid`, {
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
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_mark_paid'))?.hide();
            loadDistributions();
            // Update payment methods balance if needed
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to process payment');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to process payment';
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




// Delete Distribution
window.deleteDistribution = function(id) {
    if (confirm('Are you sure you want to delete this distribution? This action cannot be undone.')) {
        fetch(`/admin/profit-share/${id}`, {
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
                loadDistributions();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete distribution'));
    }
};

// Calculate total amount
function calculateTotal() {
    const vestedUnits = parseFloat(document.getElementById('add_vested_units')?.value) || 0;
    const unitValue = parseFloat(document.getElementById('add_unit_value')?.value) || 0;
    const total = vestedUnits * unitValue;
    document.getElementById('add_total_amount').value = 'UGX ' + total.toLocaleString();
}

function calculateEditTotal() {
    const vestedUnits = parseFloat(document.getElementById('edit_vested_units')?.value) || 0;
    const unitValue = parseFloat(document.getElementById('edit_unit_value')?.value) || 0;
    const total = vestedUnits * unitValue;
    document.getElementById('edit_total_amount').value = 'UGX ' + total.toLocaleString();
}

// ============================================
// FORM SUBMISSIONS
// ============================================

// Submit Add Distribution
document.getElementById('addDistributionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('addDistributionBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    fetch('{{ route("admin.profit-share.store") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_distribution'));
            if (modal) modal.hide();
            this.reset();
            loadDistributions();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create distribution');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create distribution';
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

// Submit Bulk Distribution
document.getElementById('bulkDistributionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (!confirm('This will distribute the remaining balance to all eligible employees in the department. Continue?')) {
        return false;
    }

    const btn = document.getElementById('bulkDistributionBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    fetch('{{ route("admin.profit-share.bulk") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_bulk_distribution'));
            if (modal) modal.hide();
            loadDistributions();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to distribute');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to distribute';
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

// Submit Edit Distribution
document.getElementById('editDistributionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('editDistributionBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_distribution_id').value;

    const formData = new FormData(this);

    fetch(`/admin/profit-share/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_distribution'));
            if (modal) modal.hide();
            loadDistributions();
        } else {
            window.showToast('error', data.message || 'Failed to update distribution');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update distribution';
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
    loadDistributions();

    // Department change - load employees
    document.getElementById('add_department_id')?.addEventListener('change', function() {
        loadDepartmentEmployees(this.value);
    });

    // Period change - load balance
    document.getElementById('add_profit_share_id')?.addEventListener('change', function() {
        loadPeriodBalance(this.value, 'add');
    });

    document.getElementById('bulk_profit_share_id')?.addEventListener('change', function() {
        loadPeriodBalance(this.value, 'bulk');
        // Also load employee count estimate
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const departmentId = selectedOption.dataset.department;
            if (departmentId) {
                fetch(`/admin/profit-share/department/${departmentId}/employees`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('bulk_employeeCount').innerHTML = data.employees.length;
                    }
                })
                .catch(err => console.error('Error loading employees:', err));
            }
        }
    });

    // Amount calculations
    document.getElementById('add_vested_units')?.addEventListener('input', calculateTotal);
    document.getElementById('add_unit_value')?.addEventListener('input', calculateTotal);
    document.getElementById('edit_vested_units')?.addEventListener('input', calculateEditTotal);
    document.getElementById('edit_unit_value')?.addEventListener('input', calculateEditTotal);

    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadDistributions();
            }, 500);
        });
    }

    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadDistributions();
    });

    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadDistributions();
    });

    document.getElementById('filterEmployee')?.addEventListener('change', function() {
        currentEmployee = this.value;
        currentPage = 1;
        loadDistributions();
    });
});
</script>
@endpush