@extends('layouts.admin')

@section('title', 'Employee Payments')
@section('page_title', 'Employee Payments')

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
    <li class="breadcrumb-item text-muted">Payments</li>
@endsection

@section('content')
@can('view salary payments')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Payments..." />
                </div>
                
                <!-- Status Filter -->
                <div>
                    <select id="filterStatus" class="form-select form-select-solid w-150px">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                
                <!-- Payment Type Filter -->
                <div>
                    <select id="filterType" class="form-select form-select-solid w-150px">
                        <option value="">All Types</option>
                        <option value="salary">Salary</option>
                        <option value="bonus">Bonus</option>
                        <option value="commission">Commission</option>
                        <option value="advance">Advance</option>
                        <option value="reimbursement">Reimbursement</option>
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
            @can('create salary payments')
            <button type="button" class="btn btn-primary me-3" data-bs-toggle="modal" data-bs-target="#kt_modal_add_payment">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Payment
            </button>
            @endcan
            @can('process salary payments')
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kt_modal_generate_salary">
                <i class="ki-duotone ki-document fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i> Generate Salary
            </button>
            @endcan
        </div>

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
                                <span class="text-gray-600 fw-semibold">Total Payments</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalPayments">0</span>
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
                            <div class="symbol symbol-50px symbol-circle bg-light-warning me-3">
                                <i class="ki-duotone ki-time fs-2x text-warning">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Pending</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="pendingPayments">0</span>
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
                                <span class="text-gray-600 fw-semibold">Approved</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="approvedPayments">0</span>
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
                                <span class="text-gray-600 fw-semibold">Paid</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="paidPayments">0</span>
                                    <span class="text-muted ms-2">Records</span>
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
            <p class="mt-3 text-muted">Loading payments...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Payment #</th>
                            <th class="min-w-120px">Date</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-150px">Employee</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-150px">Description</th>
                            <th class="min-w-120px text-end">Amount</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody"></tbody>
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
            <p class="text-muted">No employee payments found.</p>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="kt_modal_add_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Employee Payment</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPaymentForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="user_id" id="add_user_id" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Type</label>
                            <select class="form-select form-select-solid" name="payment_type" id="add_payment_type" required>
                                <option value="">Select Type</option>
                                <option value="salary">Salary</option>
                                <option value="bonus">Bonus</option>
                                <option value="commission">Commission</option>
                                <option value="advance">Advance</option>
                                <option value="reimbursement">Reimbursement</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Date</label>
                            <input type="date" class="form-control form-control-solid" name="payment_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" placeholder="Payment description" required />
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Gross Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="add_currency_symbol">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="gross_amount" id="add_gross_amount" placeholder="0.00" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="tax_amount" id="add_tax_amount" placeholder="0.00" value="0" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Net Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" name="net_amount_display" id="add_net_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" name="total_amount_display" id="add_total_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Pay Period Start</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_start" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Pay Period End</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_end" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="add_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                            <div class="form-text text-muted">Payment will be deducted from this account when paid</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Reference Number</label>
                            <input type="text" class="form-control form-control-solid" name="reference_number" placeholder="Reference #" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPaymentBtn">
                            <span class="indicator-label">Create Payment</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Generate Salary Modal -->
<div class="modal fade" id="kt_modal_generate_salary" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Generate Salary Payments</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="generateSalaryForm">
                    @csrf
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>This will generate salary payments for all active employees based on their salary structures.</div>
                    </div>
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Date</label>
                            <input type="date" class="form-control form-control-solid" name="payment_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="gen_department_id">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Pay Period Start</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_start" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Pay Period End</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_end" required />
                        </div>
                    </div>
                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="gen_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                            <div class="form-text text-muted">Payment will be deducted from this account when paid</div>
                        </div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="generateSalaryBtn">
                            <span class="indicator-label">Generate Salaries</span>
                            <span class="indicator-progress">Generating... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="kt_modal_edit_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Employee Payment</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPaymentForm">
                    @csrf
                    <input type="hidden" name="payment_id" id="edit_payment_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <input type="text" class="form-control form-control-solid" id="edit_employee_name" disabled />
                            <input type="hidden" name="user_id" id="edit_user_id" />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Type</label>
                            <select class="form-select form-select-solid" name="payment_type" id="edit_payment_type" required>
                                <option value="salary">Salary</option>
                                <option value="bonus">Bonus</option>
                                <option value="commission">Commission</option>
                                <option value="advance">Advance</option>
                                <option value="reimbursement">Reimbursement</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Date</label>
                            <input type="date" class="form-control form-control-solid" name="payment_date" id="edit_payment_date" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" id="edit_description" required />
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Gross Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="gross_amount" id="edit_gross_amount" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="tax_amount" id="edit_tax_amount" value="0" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Net Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" name="net_amount_display" id="edit_net_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="text" class="form-control form-control-solid" name="total_amount_display" id="edit_total_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Pay Period Start</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_start" id="edit_pay_period_start" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Pay Period End</label>
                            <input type="date" class="form-control form-control-solid" name="pay_period_end" id="edit_pay_period_end" />
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
                            <label class="fw-semibold fs-6 mb-2">Reference Number</label>
                            <input type="text" class="form-control form-control-solid" name="reference_number" id="edit_reference_number" />
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" id="edit_notes" rows="3"></textarea>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPaymentBtn">
                            <span class="indicator-label">Update Payment</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pay Payment Modal -->
<div class="modal fade" id="kt_modal_pay_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Pay Employee</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="payPaymentForm">
                    @csrf
                    <input type="hidden" name="payment_id" id="pay_payment_id">
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Payment: <span id="pay_payment_number"></span></strong><br>
                            <span class="text-muted">Amount: <span id="pay_payment_amount"></span></span><br>
                            <span class="text-muted">Employee: <span id="pay_employee_name"></span></span>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                        <select class="form-select form-select-solid" name="payment_method_id" id="pay_payment_method_id" required>
                            <option value="">Select Payment Method</option>
                        </select>
                        <div class="form-text text-muted">This will deduct the amount from the selected payment method balance.</div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="payPaymentBtn">
                            <span class="indicator-label">Confirm Payment</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="kt_modal_view_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Payment Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewPaymentContent">
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
let currentType = '';
let currentDepartment = '';
let currentEmployee = '';

// ============================================
// UTILITY FUNCTIONS
// ============================================

function formatCurrency(amount) {
    return 'UGX ' + Number(amount).toLocaleString(undefined, { 
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

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

// ============================================
// LOAD DATA FUNCTIONS
// ============================================

function loadFormData() {
    fetch('{{ route("admin.employee-payments.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Employees
            const empOptions = '<option value="">Select Employee</option>' + 
                data.employees.map(e => `<option value="${e.id}">${e.name} (${e.email})</option>`).join('');
            document.getElementById('add_user_id').innerHTML = empOptions;
            
            // Departments
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;
            document.getElementById('gen_department_id').innerHTML = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            
            // Payment Methods
            const pmOptions = '<option value="">Select Payment Method</option>' + 
                data.payment_methods.map(p => `<option value="${p.id}" data-currency="${p.currency?.code || 'UGX'}">${p.name}</option>`).join('');
            document.getElementById('add_payment_method_id').innerHTML = pmOptions;
            document.getElementById('edit_payment_method_id').innerHTML = pmOptions;
            document.getElementById('pay_payment_method_id').innerHTML = pmOptions;
            document.getElementById('gen_payment_method_id').innerHTML = pmOptions;
            
            // Filter Departments
            const filterDeptOptions = '<option value="">All Departments</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('filterDepartment').innerHTML = filterDeptOptions;
            
            // Filter Employees
            const filterEmpOptions = '<option value="">All Employees</option>' + 
                data.employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            document.getElementById('filterEmployee').innerHTML = filterEmpOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

function loadPayments() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    if (spinner) spinner.classList.remove('d-none');
    if (table) table.classList.add('d-none');
    if (noData) noData.classList.add('d-none');
    if (pagination) pagination.classList.add('d-none');
    
    let url = `{{ route("admin.employee-payments.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentType) url += `&payment_type=${currentType}`;
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
                renderPaymentsTable(data.data);
                renderPagination(data);
                if (pagination) pagination.classList.remove('d-none');
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            if (spinner) spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load payments');
        });
}

function updateSummary(summary) {
    document.getElementById('totalPayments').innerHTML = summary.total_count || 0;
    document.getElementById('pendingPayments').innerHTML = summary.pending_count || 0;
    document.getElementById('approvedPayments').innerHTML = summary.approved_count || 0;
    document.getElementById('paidPayments').innerHTML = summary.paid_count || 0;
}

function renderPaymentsTable(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    
    payments.forEach(payment => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${payment.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${payment.payment_number}</span>`;
        row.insertCell(2).innerHTML = `<span class="fw-bold">${formatDate(payment.payment_date)}</span>`;
        row.insertCell(3).innerHTML = `<span class="badge badge-light-primary">${payment.payment_type_label}</span>`;
        row.insertCell(4).innerHTML = payment.user ? `<div class="fw-bold">${escapeHtml(payment.user.name)}</div>` : '-';
        row.insertCell(5).innerHTML = payment.department || '-';
        row.insertCell(6).innerHTML = `<span class="fw-bold">${escapeHtml(payment.description)}</span>`;
        row.insertCell(7).innerHTML = `<span class="fw-bold text-success text-end d-block">${payment.amount_formatted}</span>`;
        row.insertCell(8).innerHTML = payment.status_badge;
        row.insertCell(9).innerHTML = getActionButtons(payment);
    });
}

function getActionButtons(payment) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewPayment(${payment.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
    `;
    
    // Show Pay button only when approved
    if (payment.payment_status === 'approved') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="payPayment(${payment.id})" title="Pay">
                <i class="ki-duotone ki-dollar fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Approve & Reject only for pending
    if (payment.payment_status === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="approvePayment(${payment.id})" title="Approve">
                <i class="ki-duotone ki-check-circle fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
            <button class="btn btn-sm btn-icon btn-light" onclick="rejectPayment(${payment.id})" title="Reject">
                <i class="ki-duotone ki-cross-circle fs-3 text-danger"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Edit & Cancel for non-paid and non-cancelled
    if (payment.payment_status !== 'paid' && payment.payment_status !== 'cancelled') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="editPayment(${payment.id})" title="Edit">
                <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            </button>
            <button class="btn btn-sm btn-icon btn-light" onclick="cancelPayment(${payment.id})" title="Cancel">
                <i class="ki-duotone ki-cross-circle fs-3 text-warning"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Delete for non-paid
    if (payment.payment_status !== 'paid') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="deletePayment(${payment.id})" title="Delete">
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
        loadPayments(); 
    }
};

// ============================================
// CRUD OPERATIONS
// ============================================

// View Payment
window.viewPayment = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_payment'));
    const content = document.getElementById('viewPaymentContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/employee-payments/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        let html = `
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Payment #</span><div class="fw-bold fs-4">${data.payment_number}</div></div>
                <div class="col-md-6"><span class="text-muted">Status</span><div>${data.payment_status_badge}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Employee</span><div class="fw-bold">${data.user?.full_name || data.user?.name || 'N/A'}</div></div>
                <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-6"><span class="text-muted">Payment Type</span><div class="fw-bold">${data.payment_type_label}</div></div>
                <div class="col-md-6"><span class="text-muted">Payment Date</span><div class="fw-bold">${formatDate(data.payment_date)}</div></div>
            </div>
            <div class="separator my-5"></div>
            <div class="row mb-5">
                <div class="col-md-12"><span class="text-muted">Description</span><div class="fw-bold">${escapeHtml(data.description)}</div></div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3"><span class="text-muted">Gross Amount</span><div class="fw-bold">${formatCurrency(data.gross_amount_display)}</div></div>
                <div class="col-md-3"><span class="text-muted">Tax Amount</span><div class="fw-bold">${formatCurrency(data.tax_amount_display)}</div></div>
                <div class="col-md-3"><span class="text-muted">Net Amount</span><div class="fw-bold">${formatCurrency(data.net_amount_display)}</div></div>
                <div class="col-md-3"><span class="text-muted">Total Amount</span><div class="fw-bold text-success">${formatCurrency(data.total_amount_display)}</div></div>
            </div>
            ${data.pay_period_start ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Pay Period</span><div class="fw-bold">${formatDate(data.pay_period_start)} - ${formatDate(data.pay_period_end)}</div></div>
                    ${data.hours_worked ? `<div class="col-md-6"><span class="text-muted">Hours Worked</span><div class="fw-bold">${data.hours_worked}</div></div>` : ''}
                </div>
            ` : ''}
            ${data.payment_method ? `
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Payment Method</span><div class="fw-bold">${data.payment_method.name}</div></div>
                    <div class="col-md-6"><span class="text-muted">Reference</span><div class="fw-bold">${data.reference_number || 'N/A'}</div></div>
                </div>
            ` : ''}
            ${data.approved_at ? `
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Approved At</span><div class="fw-bold">${formatDate(data.approved_at)}</div></div>
                    ${data.paid_date ? `<div class="col-md-6"><span class="text-muted">Paid Date</span><div class="fw-bold">${formatDate(data.paid_date)}</div></div>` : ''}
                </div>
            ` : ''}
            ${data.notes ? `
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Notes</span><div class="fw-bold">${escapeHtml(data.notes)}</div></div>
                </div>
            ` : ''}
        `;
        
        content.innerHTML = html;
    })
    .catch(err => {
        content.innerHTML = '<div class="text-center text-danger py-5">Failed to load payment details</div>';
    });
};

// Edit Payment
window.editPayment = function(id) {
    fetch(`/admin/employee-payments/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('edit_payment_id').value = data.id;
        document.getElementById('edit_employee_name').value = data.user?.full_name || data.user?.name || '';
        document.getElementById('edit_user_id').value = data.user_id;
        document.getElementById('edit_payment_type').value = data.payment_type || 'salary';
        
        // Fix dates - format as YYYY-MM-DD for date inputs
        if (data.payment_date) {
            document.getElementById('edit_payment_date').value = formatDateForInput(data.payment_date);
        }
        if (data.pay_period_start) {
            document.getElementById('edit_pay_period_start').value = formatDateForInput(data.pay_period_start);
        }
        if (data.pay_period_end) {
            document.getElementById('edit_pay_period_end').value = formatDateForInput(data.pay_period_end);
        }
        
        document.getElementById('edit_department_id').value = data.department_id || '';
        document.getElementById('edit_description').value = data.description || '';
        document.getElementById('edit_gross_amount').value = data.gross_amount_display || 0;
        document.getElementById('edit_tax_amount').value = data.tax_amount_display || 0;
        document.getElementById('edit_net_amount').value = data.net_amount_display || 0;
        document.getElementById('edit_total_amount').value = data.total_amount_display || 0;
        document.getElementById('edit_payment_method_id').value = data.payment_method_id || '';
        document.getElementById('edit_reference_number').value = data.reference_number || '';
        document.getElementById('edit_notes').value = data.notes || '';
        
        new bootstrap.Modal(document.getElementById('kt_modal_edit_payment')).show();
    })
    .catch(err => {
        console.error('Error loading payment:', err);
        window.showToast('error', 'Failed to load payment details');
    });
};

// Format date for input[type="date"] - YYYY-MM-DD
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

// Pay Payment
window.payPayment = function(id) {
    fetch(`/admin/employee-payments/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('pay_payment_id').value = data.id;
        document.getElementById('pay_payment_number').innerHTML = data.payment_number;
        document.getElementById('pay_payment_amount').innerHTML = formatCurrency(data.total_amount_display);
        document.getElementById('pay_employee_name').innerHTML = data.user?.full_name || data.user?.name || 'N/A';
        new bootstrap.Modal(document.getElementById('kt_modal_pay_payment')).show();
    })
    .catch(err => {
        console.error('Error loading payment:', err);
        window.showToast('error', 'Failed to load payment details');
    });
};

// Approve Payment
window.approvePayment = function(id) {
    if (confirm('Are you sure you want to approve this payment?')) {
        fetch(`/admin/employee-payments/${id}/approve`, {
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
                loadPayments(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to approve payment'));
    }
};

// Reject Payment
window.rejectPayment = function(id) {
    const reason = prompt('Please enter the reason for rejection:');
    if (reason !== null) {
        fetch(`/admin/employee-payments/${id}/reject`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                window.showToast('success', data.message); 
                loadPayments(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to reject payment'));
    }
};

// Cancel Payment
window.cancelPayment = function(id) {
    const reason = prompt('Please enter the reason for cancellation:');
    if (reason !== null) {
        fetch(`/admin/employee-payments/${id}/cancel`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) { 
                window.showToast('success', data.message); 
                loadPayments(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to cancel payment'));
    }
};

// Delete Payment
window.deletePayment = function(id) {
    if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
        fetch(`/admin/employee-payments/${id}`, {
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
                loadPayments(); 
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete payment'));
    }
};

// ============================================
// FORM SUBMISSIONS
// ============================================

// Calculate amounts
function calculateAmounts() {
    const gross = parseFloat(document.getElementById('add_gross_amount')?.value) || 0;
    const tax = parseFloat(document.getElementById('add_tax_amount')?.value) || 0;
    const net = gross - tax;
    const total = gross + tax;
    
    if (document.getElementById('add_net_amount')) {
        document.getElementById('add_net_amount').value = net.toFixed(2);
    }
    if (document.getElementById('add_total_amount')) {
        document.getElementById('add_total_amount').value = total.toFixed(2);
    }
}

function calculateEditAmounts() {
    const gross = parseFloat(document.getElementById('edit_gross_amount')?.value) || 0;
    const tax = parseFloat(document.getElementById('edit_tax_amount')?.value) || 0;
    const net = gross - tax;
    const total = gross + tax;
    
    if (document.getElementById('edit_net_amount')) {
        document.getElementById('edit_net_amount').value = net.toFixed(2);
    }
    if (document.getElementById('edit_total_amount')) {
        document.getElementById('edit_total_amount').value = total.toFixed(2);
    }
}

// Add Payment
document.getElementById('addPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('addPaymentBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    formData.delete('net_amount_display');
    formData.delete('total_amount_display');
    
    fetch('{{ route("admin.employee-payments.store") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_payment'));
            if (modal) modal.hide();
            this.reset();
            loadPayments();
            loadFormData();
        } else {
            window.showToast('error', data.message || 'Failed to create payment');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to create payment';
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

// Generate Salary
document.getElementById('generateSalaryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('generateSalaryBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.employee-payments.generate") }}', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_generate_salary'));
            if (modal) modal.hide();
            loadPayments();
        } else {
            window.showToast('error', data.message || 'Failed to generate salaries');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to generate salaries: ' + err.message);
    })
    .finally(() => {
        window.hideButtonSpinner(btn);
    });
    
    return false;
});

// Edit Payment
document.getElementById('editPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('editPaymentBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_payment_id').value;
    
    const formData = new FormData(this);
    formData.delete('net_amount_display');
    formData.delete('total_amount_display');
    
    fetch(`/admin/employee-payments/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_payment'));
            if (modal) modal.hide();
            loadPayments();
        } else {
            window.showToast('error', data.message || 'Failed to update payment');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        let errorMessage = 'Failed to update payment';
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

// Pay Payment
document.getElementById('payPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const btn = document.getElementById('payPaymentBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('pay_payment_id').value;
    
    const formData = new FormData(this);
    
    fetch(`/admin/employee-payments/${id}/pay`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_pay_payment'));
            if (modal) modal.hide();
            loadPayments();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to process payment: ' + err.message);
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
    loadPayments();
    
    // Amount calculations
    document.getElementById('add_gross_amount')?.addEventListener('input', calculateAmounts);
    document.getElementById('add_tax_amount')?.addEventListener('input', calculateAmounts);
    document.getElementById('edit_gross_amount')?.addEventListener('input', calculateEditAmounts);
    document.getElementById('edit_tax_amount')?.addEventListener('input', calculateEditAmounts);
    
    // Search
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPayments();
            }, 500);
        });
    }
    
    // Filters
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadPayments();
    });
    
    document.getElementById('filterType')?.addEventListener('change', function() {
        currentType = this.value;
        currentPage = 1;
        loadPayments();
    });
    
    document.getElementById('filterDepartment')?.addEventListener('change', function() {
        currentDepartment = this.value;
        currentPage = 1;
        loadPayments();
    });
    
    document.getElementById('filterEmployee')?.addEventListener('change', function() {
        currentEmployee = this.value;
        currentPage = 1;
        loadPayments();
    });
});
</script>
@endpush