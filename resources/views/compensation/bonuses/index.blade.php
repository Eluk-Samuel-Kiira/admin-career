@extends('layouts.admin')

@section('title', 'Bonuses')
@section('page_title', 'Bonuses')

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
    <li class="breadcrumb-item text-muted">Bonuses</li>
@endsection

@section('content')
@can('view bonuses')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Bonuses..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Bonus Type Filter -->
                <div>
                    <select id="filterType" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        <option value="performance">Performance</option>
                        <option value="retention">Retention</option>
                        <option value="commission">Commission</option>
                        <option value="extraordinary">Extraordinary</option>
                        <option value="referral">Referral</option>
                        <option value="signing">Signing</option>
                        <option value="holiday">Holiday</option>
                        <option value="project">Project</option>
                        <option value="team">Team</option>
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
        @can('create bonuses')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_bonus">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Bonus
            </button>
        </div>
        @endcan
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
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Total Bonuses</span>
                                <span class="fw-bold text-gray-800" id="totalBonuses" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
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
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">Approved</span>
                                <span class="fw-bold text-gray-800" id="approvedCount" style="font-size: clamp(0.8rem, 2vw, 1.5rem);">0</span>
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
            <p class="mt-3 text-muted">Loading bonuses...</p>
        </div>

        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Bonus #</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-100px">Category</th>
                            <th class="min-w-120px text-end">Amount</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Date</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bonusesTableBody"></tbody>
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
            <p class="text-muted">No bonuses found.</p>
        </div>
    </div>
</div>

<!-- Add Bonus Modal -->
<div class="modal fade" id="kt_modal_add_bonus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Bonus</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addBonusForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Bonus Type</label>
                            <select class="form-select form-select-solid" name="bonus_type" id="add_bonus_type" required>
                                <option value="">Select Type</option>
                                <option value="performance">Performance</option>
                                <option value="retention">Retention</option>
                                <option value="commission">Commission</option>
                                <option value="extraordinary">Extraordinary</option>
                                <option value="referral">Referral</option>
                                <option value="signing">Signing</option>
                                <option value="holiday">Holiday</option>
                                <option value="project">Project</option>
                                <option value="team">Team</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Bonus Category</label>
                            <select class="form-select form-select-solid" name="bonus_category" id="add_bonus_category" required>
                                <option value="">Select Category</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                                <option value="one_time">One Time</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Bonus Date</label>
                            <input type="date" class="form-control form-control-solid" name="bonus_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="amount" placeholder="0.00" required />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Score (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_score" placeholder="0-100" min="0" max="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Target Achieved (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="target_achieved" placeholder="0-100" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="add_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="add_status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <!-- <option value="paid">Paid</option> -->
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" placeholder="Bonus description" />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Reference</label>
                        <input type="text" class="form-control form-control-solid" name="reference" placeholder="Reference number" />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addBonusBtn">
                            <span class="indicator-label">Create Bonus</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Bonus Modal -->
<div class="modal fade" id="kt_modal_edit_bonus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Bonus</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editBonusForm">
                    @csrf
                    <input type="hidden" name="bonus_id" id="edit_bonus_id">
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
                            <label class="required fw-semibold fs-6 mb-2">Bonus Type</label>
                            <select class="form-select form-select-solid" name="bonus_type" id="edit_bonus_type" required>
                                <option value="performance">Performance</option>
                                <option value="retention">Retention</option>
                                <option value="commission">Commission</option>
                                <option value="extraordinary">Extraordinary</option>
                                <option value="referral">Referral</option>
                                <option value="signing">Signing</option>
                                <option value="holiday">Holiday</option>
                                <option value="project">Project</option>
                                <option value="team">Team</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Bonus Category</label>
                            <select class="form-select form-select-solid" name="bonus_category" id="edit_bonus_category" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="annual">Annual</option>
                                <option value="one_time">One Time</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Bonus Date</label>
                            <input type="date" class="form-control form-control-solid" name="bonus_date" id="edit_bonus_date" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="amount" id="edit_amount" required />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Performance Score (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="performance_score" id="edit_performance_score" min="0" max="100" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Target Achieved (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="target_achieved" id="edit_target_achieved" min="0" max="100" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="edit_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Status</label>
                            <select class="form-select form-select-solid" name="status" id="edit_status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" id="edit_description" />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Reference</label>
                        <input type="text" class="form-control form-control-solid" name="reference" id="edit_reference" />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" id="edit_notes" rows="3"></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editBonusBtn">
                            <span class="indicator-label">Update Bonus</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Bonus Modal -->
<div class="modal fade" id="kt_modal_view_bonus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Bonus Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewBonusContent">
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

<!-- Approve Bonus Modal -->
<div class="modal fade" id="kt_modal_approve_bonus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Approve Bonus</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="approveBonusForm">
                    @csrf
                    <input type="hidden" name="bonus_id" id="approve_bonus_id">
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Bonus: <span id="approve_bonus_number"></span></strong><br>
                            <span class="text-muted">Amount: <span id="approve_bonus_amount"></span></span><br>
                            <span class="text-muted">Employee: <span id="approve_bonus_employee"></span></span>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Approval Notes</label>
                        <textarea class="form-control form-control-solid" name="approval_notes" rows="3" placeholder="Approval notes..."></textarea>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="approveBonusBtn">
                            <span class="indicator-label">Approve Bonus</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pay Bonus Modal -->
<div class="modal fade" id="kt_modal_pay_bonus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Pay Bonus</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="payBonusForm">
                    @csrf
                    <input type="hidden" name="bonus_id" id="pay_bonus_id">
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Bonus: <span id="pay_bonus_number"></span></strong><br>
                            <span class="text-muted">Amount: <span id="pay_bonus_amount"></span></span><br>
                            <span class="text-muted">Employee: <span id="pay_bonus_employee"></span></span>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                        <select class="form-select form-select-solid" name="payment_method_id" id="pay_payment_method_id" required>
                            <option value="">Select Payment Method</option>
                        </select>
                        <div class="form-text text-muted mt-2">
                            <span id="pay_payment_balance">Balance: --</span>
                        </div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="payBonusBtn">
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
let currentType = '';
let currentDepartment = '';
let currentEmployee = '';

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

function formatDateForInput(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    } catch (e) {
        return '';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load form data
function loadFormData() {
    fetch('{{ route("admin.bonuses.form-data") }}')
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

            // Payment Methods
            const pmOptions = '<option value="">Select Payment Method</option>' + 
                data.payment_methods.map(p => `<option value="${p.id}" data-balance="${p.current_balance}">${p.name}</option>`).join('');
            document.getElementById('add_payment_method_id').innerHTML = pmOptions;
            document.getElementById('edit_payment_method_id').innerHTML = pmOptions;
            document.getElementById('pay_payment_method_id').innerHTML = pmOptions;

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

// Load bonuses
function loadBonuses() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');

    let url = `{{ route("admin.bonuses.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentType) url += `&bonus_type=${currentType}`;
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
                renderBonusesTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load bonuses');
        });
}

function updateSummary(summary) {
    document.getElementById('totalBonuses').innerHTML = summary.total_bonuses || 0;
    document.getElementById('pendingCount').innerHTML = summary.pending_count || 0;
    document.getElementById('approvedCount').innerHTML = summary.approved_count || 0;
    document.getElementById('totalAmount').innerHTML = formatCurrency(summary.total_amount || 0);
}

function renderBonusesTable(bonuses) {
    const tbody = document.getElementById('bonusesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    bonuses.forEach(bonus => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${bonus.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${bonus.bonus_number}</span>`;
        row.insertCell(2).innerHTML = bonus.user ? `<div class="fw-bold">${escapeHtml(bonus.user.name)}</div>` : '-';
        row.insertCell(3).innerHTML = bonus.department || '-';
        row.insertCell(4).innerHTML = `<span class="badge badge-light-primary">${bonus.bonus_type_label}</span>`;
        row.insertCell(5).innerHTML = `<span class="badge badge-light-info">${bonus.bonus_category_label}</span>`;
        row.insertCell(6).innerHTML = `<span class="fw-bold text-success text-end d-block">${bonus.formatted_amount}</span>`;
        row.insertCell(7).innerHTML = bonus.status_badge;
        row.insertCell(8).innerHTML = formatDate(bonus.bonus_date);
        row.insertCell(9).innerHTML = getActionButtons(bonus);
    });
}

function getActionButtons(bonus) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewBonus(${bonus.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
    `;

    // PENDING status: Show Approve button only
    if (bonus.status === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="approveBonus(${bonus.id})" title="Approve">
                <i class="ki-duotone ki-check-circle fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // APPROVED status: Show Pay button only
    else if (bonus.status === 'approved') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="payBonus(${bonus.id})" title="Pay">
                <i class="ki-duotone ki-dollar fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // PAID, REJECTED, CANCELLED: No action buttons (only View)

    // Show Edit button only for non-paid, non-cancelled, non-rejected
    if (bonus.status !== 'paid' && bonus.status !== 'cancelled' && bonus.status !== 'rejected') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="editBonus(${bonus.id})" title="Edit">
                <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            </button>
        `;
    }

    // Show Delete button only for non-paid, non-rejected, non-cancelled
    if (bonus.status !== 'paid' && bonus.status !== 'rejected' && bonus.status !== 'cancelled') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="deleteBonus(${bonus.id})" title="Delete">
                <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            </button>
        `;
    }

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
        loadBonuses();
    }
};

// View Bonus
window.viewBonus = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_bonus'));
    const content = document.getElementById('viewBonusContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    fetch(`/admin/bonuses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Bonus #</span><div class="fw-bold fs-4">${data.bonus_number}</div></div>
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.status_badge}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Employee</span><div class="fw-bold">${data.user?.name || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-4"><span class="text-muted">Type</span><div class="fw-bold"><span class="badge badge-light-primary">${data.bonus_type_label}</span></div></div>
                <div class="col-md-4"><span class="text-muted">Category</span><div class="fw-bold"><span class="badge badge-light-info">${data.bonus_category_label}</span></div></div>
                <div class="col-md-4"><span class="text-muted">Date</span><div class="fw-bold">${formatDate(data.bonus_date)}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Amount</span><div class="fw-bold text-success fs-2">${data.formatted_amount}</div></div>
                <div class="col-md-6"><span class="text-muted">Payment Method</span><div class="fw-bold">${data.payment_method?.name || 'N/A'}</div></div>
            </div>
            ${data.performance_score ? `
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Performance Score</span><div class="fw-bold">${data.performance_score}%</div></div>
                    <div class="col-md-6"><span class="text-muted">Target Achieved</span><div class="fw-bold">${data.target_achieved || 'N/A'}%</div></div>
                </div>
            ` : ''}
            ${data.description ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Description</span><div class="fw-bold">${escapeHtml(data.description)}</div></div>
                </div>
            ` : ''}
            ${data.reference ? `
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Reference</span><div class="fw-bold">${data.reference}</div></div>
                </div>
            ` : ''}
            ${data.approved_at ? `
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Approved At</span><div class="fw-bold">${formatDate(data.approved_at)}</div></div>
                    <div class="col-md-6"><span class="text-muted">Approved By</span><div class="fw-bold">${data.approved_by?.name || 'N/A'}</div></div>
                </div>
            ` : ''}
            ${data.paid_date ? `
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Paid Date</span><div class="fw-bold">${formatDate(data.paid_date)}</div></div>
                </div>
            ` : ''}
            ${data.notes ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Notes</span><div class="fw-bold">${escapeHtml(data.notes)}</div></div>
                </div>
            ` : ''}
            <div class="row mt-3">
                <div class="col-md-12"><span class="text-muted">Created At</span><div class="fw-bold">${formatDate(data.created_at)}</div></div>
            </div>
        `;

        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load bonus details</div>';
    });
};

// Edit Bonus
window.editBonus = function(id) {
    fetch(`/admin/bonuses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_bonus_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.user?.name || '';
        document.getElementById('edit_user_id').value = data.user?.id || '';
        document.getElementById('edit_department_name').value = data.department?.name || '';
        document.getElementById('edit_department_id').value = data.department?.id || '';
        document.getElementById('edit_bonus_type').value = data.bonus_type || 'performance';
        document.getElementById('edit_bonus_category').value = data.bonus_category || 'one_time';
        document.getElementById('edit_bonus_date').value = formatDateForInput(data.bonus_date);
        document.getElementById('edit_amount').value = data.amount || 0;
        document.getElementById('edit_performance_score').value = data.performance_score || '';
        document.getElementById('edit_target_achieved').value = data.target_achieved || '';
        document.getElementById('edit_payment_method_id').value = data.payment_method_id || '';
        document.getElementById('edit_status').value = data.status || 'pending';
        document.getElementById('edit_description').value = data.description || '';
        document.getElementById('edit_reference').value = data.reference || '';
        document.getElementById('edit_notes').value = data.notes || '';

        new bootstrap.Modal(document.getElementById('kt_modal_edit_bonus')).show();
    })
    .catch(err => {
        console.error('Error loading bonus:', err);
        window.showToast('error', 'Failed to load bonus details');
    });
};

// Approve Bonus
window.approveBonus = function(id) {
    fetch(`/admin/bonuses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('approve_bonus_id').value = data.id;
        document.getElementById('approve_bonus_number').innerHTML = data.bonus_number;
        document.getElementById('approve_bonus_amount').innerHTML = data.formatted_amount;
        document.getElementById('approve_bonus_employee').innerHTML = data.user?.name || 'N/A';
        new bootstrap.Modal(document.getElementById('kt_modal_approve_bonus')).show();
    })
    .catch(err => {
        console.error('Error loading bonus:', err);
        window.showToast('error', 'Failed to load bonus details');
    });
};

// Pay Bonus
window.payBonus = function(id) {
    fetch(`/admin/bonuses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('pay_bonus_id').value = data.id;
        document.getElementById('pay_bonus_number').innerHTML = data.bonus_number;
        document.getElementById('pay_bonus_amount').innerHTML = data.formatted_amount;
        document.getElementById('pay_bonus_employee').innerHTML = data.user?.name || 'N/A';
        new bootstrap.Modal(document.getElementById('kt_modal_pay_bonus')).show();
    })
    .catch(err => {
        console.error('Error loading bonus:', err);
        window.showToast('error', 'Failed to load bonus details');
    });
};

// Delete Bonus
window.deleteBonus = function(id) {
    if (confirm('Are you sure you want to delete this bonus? This action cannot be undone.')) {
        fetch(`/admin/bonuses/${id}`, {
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
                loadBonuses();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete bonus'));
    }
};

// Submit Add Bonus
document.getElementById('addBonusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('addBonusBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    fetch('{{ route("admin.bonuses.store") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_bonus'));
            if (modal) modal.hide();
            this.reset();
            loadBonuses();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create bonus');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create bonus';
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

// Submit Edit Bonus
document.getElementById('editBonusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('editBonusBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_bonus_id').value;

    const formData = new FormData(this);

    fetch(`/admin/bonuses/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_bonus'));
            if (modal) modal.hide();
            loadBonuses();
        } else {
            window.showToast('error', data.message || 'Failed to update bonus');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update bonus';
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

// Submit Approve Bonus
document.getElementById('approveBonusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('approveBonusBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('approve_bonus_id').value;

    const formData = new FormData(this);

    fetch(`/admin/bonuses/${id}/approve`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_approve_bonus'));
            if (modal) modal.hide();
            loadBonuses();
        } else {
            window.showToast('error', data.message || 'Failed to approve bonus');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to approve bonus: ' + err.message);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });

    return false;
});

// Submit Pay Bonus
document.getElementById('payBonusForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = document.getElementById('payBonusBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('pay_bonus_id').value;

    const formData = new FormData(this);

    fetch(`/admin/bonuses/${id}/pay`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_pay_bonus'));
            if (modal) modal.hide();
            loadBonuses();
        } else {
            window.showToast('error', data.message || 'Failed to pay bonus');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to pay bonus: ' + err.message);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });

    return false;
});

// Payment method balance check for pay modal
document.getElementById('pay_payment_method_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const balance = selectedOption.dataset.balance || 0;
        document.getElementById('pay_payment_balance').innerHTML = `Balance: ${formatCurrency(balance)}`;
    } else {
        document.getElementById('pay_payment_balance').innerHTML = 'Balance: --';
    }
});

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadBonuses();

    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadBonuses();
            }, 500);
        });
    }

    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadBonuses();
    });

    document.getElementById('filterType')?.addEventListener('change', function() {
        currentType = this.value;
        currentPage = 1;
        loadBonuses();
    });

    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadBonuses();
    });

    document.getElementById('filterEmployee')?.addEventListener('change', function() {
        currentEmployee = this.value;
        currentPage = 1;
        loadBonuses();
    });
});
</script>
@endpush