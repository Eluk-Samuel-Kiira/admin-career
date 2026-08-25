@extends('layouts.admin')

@section('title', 'Expenses')
@section('page_title', 'Expenses')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Expenses</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">All Expenses</li>
@endsection

@section('content')
@can('view expenses')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Search -->
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Expenses..." />
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
                
                <!-- Category Filter -->
                <div>
                    <select id="filterCategory" class="form-select form-select-solid w-180px">
                        <option value="">All Categories</option>
                    </select>
                </div>
            </div>
        </div>
        @can('create expenses')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_expense">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Expense
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
                                <span class="text-gray-600 fw-semibold">Total Expenses</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="totalExpenses">0</span>
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
                                    <span class="fs-2hx fw-bold text-gray-800" id="pendingExpenses">0</span>
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
                                    <span class="fs-2hx fw-bold text-gray-800" id="approvedExpenses">0</span>
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
                                <i class="ki-duotone ki-dollar fs-2x text-info">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-gray-600 fw-semibold">Paid</span>
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-gray-800" id="paidExpenses">0</span>
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
            <p class="mt-3 text-muted">Loading expenses...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Expense #</th>
                            <th class="min-w-120px">Date</th>
                            <th class="min-w-150px">Description</th>
                            <th class="min-w-120px">Category</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-120px">Vendor</th>
                            <th class="min-w-100px text-end">Amount</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTableBody"></tbody>
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
            <p class="text-muted">No expenses found.</p>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="kt_modal_add_expense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Expense</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addExpenseForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Expense Date</label>
                            <input type="date" class="form-control form-control-solid" name="date" id="add_date" value="{{ date('Y-m-d') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Category</label>
                            <select class="form-select form-select-solid" name="category_id" id="add_category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" placeholder="Brief description of expense" required />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="employee_id" id="add_employee_id">
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="add_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Name</label>
                            <input type="text" class="form-control form-control-solid" name="vendor_name" placeholder="Vendor name" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Contact</label>
                            <input type="text" class="form-control form-control-solid" name="vendor_contact" placeholder="Phone number" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Email</label>
                            <input type="email" class="form-control form-control-solid" name="vendor_email" placeholder="Email address" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Gross Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="add_currency_symbol">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="gross_amount" id="add_gross_amount" placeholder="0.00" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="tax_amount" id="add_tax_amount" placeholder="0.00" value="0" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Net Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control form-control-solid" name="net_amount_display" id="add_net_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control form-control-solid" name="total_amount_display" id="add_total_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Payment Status</label>
                            <select class="form-select form-select-solid" name="payment_status" id="add_payment_status">
                                <option value="pending" selected>Pending</option>
                                <!-- <option value="approved">Approved</option>
                                <option value="paid">Paid</option> -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Receipt Number</label>
                            <input type="text" class="form-control form-control-solid" name="receipt_number" placeholder="Receipt #" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_recurring" id="add_is_recurring" />
                                <span class="form-check-label fw-semibold">Recurring Expense</span>
                            </label>
                        </div>
                        <div class="col-md-6" id="recurringFields" style="display: none;">
                            <label class="fw-semibold fs-6 mb-2">Recurring Frequency</label>
                            <select class="form-select form-select-solid" name="recurring_frequency">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addExpenseBtn">
                            <span class="indicator-label">Create Expense</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="kt_modal_edit_expense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Expense</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editExpenseForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="expense_id" id="edit_expense_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Expense Date</label>
                            <input type="date" class="form-control form-control-solid" name="date" id="edit_date" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Category</label>
                            <select class="form-select form-select-solid" name="category_id" id="edit_category_id" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Description</label>
                        <input type="text" class="form-control form-control-solid" name="description" id="edit_description" required />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Employee</label>
                            <select class="form-select form-select-solid" name="employee_id" id="edit_employee_id">
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="edit_payment_method_id">
                                <option value="">Select Payment Method</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Name</label>
                            <input type="text" class="form-control form-control-solid" name="vendor_name" id="edit_vendor_name" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Contact</label>
                            <input type="text" class="form-control form-control-solid" name="vendor_contact" id="edit_vendor_contact" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Vendor Email</label>
                            <input type="email" class="form-control form-control-solid" name="vendor_email" id="edit_vendor_email" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-3">
                            <label class="required fw-semibold fs-6 mb-2">Gross Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="gross_amount" id="edit_gross_amount" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="tax_amount" id="edit_tax_amount" value="0" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Net Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control form-control-solid" name="net_amount_display" id="edit_net_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold fs-6 mb-2">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control form-control-solid" name="total_amount_display" id="edit_total_amount" readonly style="background-color: #f5f5f5;" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Payment Status</label>
                            <select class="form-select form-select-solid" name="payment_status" id="edit_payment_status">
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Receipt Number</label>
                            <input type="text" class="form-control form-control-solid" name="receipt_number" id="edit_receipt_number" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Notes</label>
                        <textarea class="form-control form-control-solid" name="notes" id="edit_notes" rows="3"></textarea>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editExpenseBtn">
                            <span class="indicator-label">Update Expense</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pay Expense Modal -->
<div class="modal fade" id="kt_modal_pay_expense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Pay Expense</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="payExpenseForm">
                    @csrf
                    <input type="hidden" name="expense_id" id="pay_expense_id">
                    <div class="alert alert-info d-flex align-items-center mb-7">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Expense: <span id="pay_expense_number"></span></strong><br>
                            <span class="text-muted">Amount: <span id="pay_expense_amount"></span></span>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                        <select class="form-select form-select-solid" name="payment_method_id" id="pay_payment_method_id" required>
                            <option value="">Select Payment Method</option>
                        </select>
                        <div class="form-text text-muted mt-2">This will deduct the amount from the selected payment method balance.</div>
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="payExpenseBtn">
                            <span class="indicator-label">Confirm Payment</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Expense Modal -->
<div class="modal fade" id="kt_modal_view_expense" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Expense Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7" id="viewExpenseContent">
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
let currentCategory = '';
let currentCurrency = '{{ $baseCurrencyCode }}';

// Format currency - now just displays the formatted amount from the API
function formatCurrency(amount, currencyCode) {
    // This is now just a fallback, the amount_formatted from API is used directly
    const symbols = { 'USD': '$', 'UGX': 'UGX', 'KES': 'KSh', 'EUR': '€', 'GBP': '£' };
    const symbol = symbols[currencyCode] || '$';
    return symbol + ' ' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
}

// Load form data
function loadFormData() {
    fetch('{{ route("admin.expenses.form-data") }}')
        .then(res => res.json())
        .then(data => {
            // Categories
            const catOptions = '<option value="">Select Category</option>' + 
                data.categories.map(c => `<option value="${c.id}">${c.name} (${c.code})</option>`).join('');
            document.getElementById('add_category_id').innerHTML = catOptions;
            document.getElementById('edit_category_id').innerHTML = catOptions;
            
            // Departments
            const deptOptions = '<option value="">Select Department</option>' + 
                data.departments.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
            document.getElementById('add_department_id').innerHTML = deptOptions;
            document.getElementById('edit_department_id').innerHTML = deptOptions;
            
            // Employees
            const empOptions = '<option value="">Select Employee</option>' + 
                data.employees.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
            document.getElementById('add_employee_id').innerHTML = empOptions;
            document.getElementById('edit_employee_id').innerHTML = empOptions;
            
            // Payment Methods with currency
            const pmOptions = '<option value="">Select Payment Method</option>' + 
                data.payment_methods.map(p => `<option value="${p.id}" data-currency="${p.currency?.code || 'USD'}">${p.name}</option>`).join('');
            document.getElementById('add_payment_method_id').innerHTML = pmOptions;
            document.getElementById('edit_payment_method_id').innerHTML = pmOptions;
            document.getElementById('pay_payment_method_id').innerHTML = pmOptions;
            
            // Filter categories
            const filterOptions = '<option value="">All Categories</option>' + 
                data.categories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            document.getElementById('filterCategory').innerHTML = filterOptions;
        })
        .catch(err => console.error('Error loading form data:', err));
}

// Update currency symbol based on payment method selection
document.getElementById('add_payment_method_id')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const currency = selected.getAttribute('data-currency') || 'USD';
    const symbols = { 'USD': '$', 'UGX': 'UGX', 'KES': 'KSh', 'EUR': '€', 'GBP': '£' };
    document.querySelectorAll('#kt_modal_add_expense .input-group-text').forEach(el => {
        el.innerHTML = symbols[currency] || '$';
    });
});

document.getElementById('edit_payment_method_id')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const currency = selected.getAttribute('data-currency') || 'USD';
    const symbols = { 'USD': '$', 'UGX': 'UGX', 'KES': 'KSh', 'EUR': '€', 'GBP': '£' };
    document.querySelectorAll('#kt_modal_edit_expense .input-group-text').forEach(el => {
        el.innerHTML = symbols[currency] || '$';
    });
});

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

document.addEventListener('DOMContentLoaded', function() {
    loadFormData();
    loadExpenses();
    
    // Recurring toggle
    document.getElementById('add_is_recurring')?.addEventListener('change', function() {
        document.getElementById('recurringFields').style.display = this.checked ? 'block' : 'none';
    });
    
    // Amount calculations
    document.getElementById('add_gross_amount')?.addEventListener('input', calculateAmounts);
    document.getElementById('add_tax_amount')?.addEventListener('input', calculateAmounts);
    document.getElementById('edit_gross_amount')?.addEventListener('input', calculateEditAmounts);
    document.getElementById('edit_tax_amount')?.addEventListener('input', calculateEditAmounts);
    
    // Currency selector
    document.getElementById('baseCurrency')?.addEventListener('change', function() {
        currentCurrency = this.value;
        loadExpenses();
        const symbols = { 'USD': '$', 'UGX': 'UGX', 'KES': 'KSh', 'EUR': '€', 'GBP': '£' };
        const symbol = symbols[currentCurrency] || '$';
        document.querySelectorAll('.input-group-text').forEach(el => {
            el.innerHTML = symbol;
        });
    });
    
    // Search and filters
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadExpenses();
            }, 500);
        });
    }
    
    document.getElementById('filterStatus')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadExpenses();
    });
    
    document.getElementById('filterCategory')?.addEventListener('change', function() {
        currentCategory = this.value;
        currentPage = 1;
        loadExpenses();
    });
});

function loadExpenses() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.expenses.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${currentStatus}`;
    if (currentCategory) url += `&category_id=${currentCategory}`;
    if (currentCurrency) url += `&currency=${currentCurrency}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderExpensesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
                // Update summary cards with counts
                updateSummary(data.summary);
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load expenses');
        });
}

function updateSummary(summary) {
    // Display counts, not monetary values
    document.getElementById('totalExpenses').innerHTML = summary.total_count || 0;
    document.getElementById('pendingExpenses').innerHTML = summary.pending_count || 0;
    document.getElementById('approvedExpenses').innerHTML = summary.approved_count || 0;
    document.getElementById('paidExpenses').innerHTML = summary.paid_count || 0;
}

function renderExpensesTable(expenses) {
    const tbody = document.getElementById('expensesTableBody');
    tbody.innerHTML = '';
    
    expenses.forEach(expense => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${expense.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${expense.expense_number}</span>`;
        row.insertCell(2).innerHTML = `<span class="fw-bold">${formatDate(expense.date)}</span>`;
        row.insertCell(3).innerHTML = `<span class="fw-bold">${escapeHtml(expense.description)}</span>`;
        row.insertCell(4).innerHTML = `<span class="badge badge-light-primary">${escapeHtml(expense.category?.name || 'N/A')}</span>`;
        row.insertCell(5).innerHTML = expense.department?.name || '-';
        row.insertCell(6).innerHTML = expense.vendor_name || '-';
        // Use the formatted amount from the API
        row.insertCell(7).innerHTML = `<span class="fw-bold text-success">${expense.amount_formatted || '0'}</span>`;
        row.insertCell(8).innerHTML = getStatusBadge(expense.payment_status);
        row.insertCell(9).innerHTML = getActionButtons(expense);
    });
}

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-light-warning">Pending</span>',
        'approved': '<span class="badge badge-light-info">Approved</span>',
        'paid': '<span class="badge badge-light-success">Paid</span>',
        'cancelled': '<span class="badge badge-light-secondary">Cancelled</span>',
        'rejected': '<span class="badge badge-light-danger">Rejected</span>'
    };
    return badges[status] || '<span class="badge badge-light-secondary">' + status + '</span>';
}

function getActionButtons(expense) {
    let buttons = `
        <button class="btn btn-sm btn-icon btn-light" onclick="viewExpense(${expense.id})" title="View">
            <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
        </button>
    `;
    
    // Show Pay button only when approved
    if (expense.payment_status === 'approved') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="payExpense(${expense.id})" title="Pay">
                <i class="ki-duotone ki-dollar fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Approve & Reject only for pending
    if (expense.payment_status === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="approveExpense(${expense.id})" title="Approve">
                <i class="ki-duotone ki-check-circle fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
            </button>
            <button class="btn btn-sm btn-icon btn-light" onclick="rejectExpense(${expense.id})" title="Reject">
                <i class="ki-duotone ki-cross-circle fs-3 text-danger"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Edit & Cancel for non-paid and non-cancelled
    if (expense.payment_status !== 'paid' && expense.payment_status !== 'cancelled') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="editExpense(${expense.id})" title="Edit">
                <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            </button>
            <button class="btn btn-sm btn-icon btn-light" onclick="cancelExpense(${expense.id})" title="Cancel">
                <i class="ki-duotone ki-cross-circle fs-3 text-warning"><span class="path1"></span><span class="path2"></span></i>
            </button>
        `;
    }
    
    // Show Delete for non-paid
    if (expense.payment_status !== 'paid') {
        buttons += `
            <button class="btn btn-sm btn-icon btn-light" onclick="deleteExpense(${expense.id})" title="Delete">
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
    info.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
    
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
    if (page !== currentPage && page > 0) { currentPage = page; loadExpenses(); }
};

// View Expense
window.viewExpense = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_expense'));
    const content = document.getElementById('viewExpenseContent');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch(`/admin/expenses/${id}`)
        .then(res => res.json())
        .then(data => {
            content.innerHTML = `
                <div class="mb-5">
                    <div class="d-flex justify-content-between">
                        <div><span class="text-muted">Expense #</span><div class="fw-bold">${data.expense_number}</div></div>
                        <div><span class="text-muted">Status</span><div>${getStatusBadge(data.payment_status)}</div></div>
                    </div>
                </div>
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Date</span><div class="fw-bold">${formatDate(data.date)}</div></div>
                    <div class="col-md-6"><span class="text-muted">Category</span><div class="fw-bold">${data.category?.name || 'N/A'}</div></div>
                </div>
                <div class="row mb-5">
                    <div class="col-md-12"><span class="text-muted">Description</span><div class="fw-bold">${data.description}</div></div>
                </div>
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-3"><span class="text-muted">Gross</span><div class="fw-bold">${formatCurrency(data.gross_amount / 100)}</div></div>
                    <div class="col-md-3"><span class="text-muted">Tax</span><div class="fw-bold">${formatCurrency(data.tax_amount / 100)}</div></div>
                    <div class="col-md-3"><span class="text-muted">Net</span><div class="fw-bold">${formatCurrency(data.net_amount / 100)}</div></div>
                    <div class="col-md-3"><span class="text-muted">Total</span><div class="fw-bold text-success">${formatCurrency(data.total_amount / 100)}</div></div>
                </div>
                <div class="separator my-5"></div>
                <div class="row mb-5">
                    <div class="col-md-6"><span class="text-muted">Vendor</span><div class="fw-bold">${data.vendor_name || 'N/A'}</div></div>
                    <div class="col-md-6"><span class="text-muted">Department</span><div class="fw-bold">${data.department?.name || 'N/A'}</div></div>
                </div>
                ${data.notes ? `<div class="row"><div class="col-md-12"><span class="text-muted">Notes</span><div class="fw-bold">${data.notes}</div></div></div>` : ''}
                ${data.approved_at ? `<div class="row mt-3"><div class="col-md-12"><span class="text-muted">Approved At</span><div class="fw-bold">${formatDate(data.approved_at)}</div></div></div>` : ''}
                ${data.paid_date ? `<div class="row"><div class="col-md-12"><span class="text-muted">Paid Date</span><div class="fw-bold">${formatDate(data.paid_date)}</div></div></div>` : ''}
            `;
        })
        .catch(err => {
            content.innerHTML = '<div class="text-center text-danger py-5">Failed to load expense details</div>';
        });
};

// Edit Expense
window.editExpense = function(id) {
    fetch(`/admin/expenses/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_expense_id').value = data.id;
            document.getElementById('edit_date').value = data.date;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_category_id').value = data.category_id;
            document.getElementById('edit_department_id').value = data.department_id || '';
            document.getElementById('edit_employee_id').value = data.employee_id || '';
            document.getElementById('edit_payment_method_id').value = data.payment_method_id || '';
            document.getElementById('edit_vendor_name').value = data.vendor_name || '';
            document.getElementById('edit_vendor_contact').value = data.vendor_contact || '';
            document.getElementById('edit_vendor_email').value = data.vendor_email || '';
            document.getElementById('edit_gross_amount').value = (data.gross_amount / 100).toFixed(2);
            document.getElementById('edit_tax_amount').value = (data.tax_amount / 100).toFixed(2);
            document.getElementById('edit_net_amount').value = (data.net_amount / 100).toFixed(2);
            document.getElementById('edit_total_amount').value = (data.total_amount / 100).toFixed(2);
            document.getElementById('edit_payment_status').value = data.payment_status;
            document.getElementById('edit_receipt_number').value = data.receipt_number || '';
            document.getElementById('edit_notes').value = data.notes || '';
            new bootstrap.Modal(document.getElementById('kt_modal_edit_expense')).show();
        });
};

// Pay Expense (Only for Approved expenses)
window.payExpense = function(id) {
    fetch(`/admin/expenses/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('pay_expense_id').value = data.id;
            document.getElementById('pay_expense_number').innerHTML = data.expense_number;
            document.getElementById('pay_expense_amount').innerHTML = formatCurrency(data.total_amount / 100);
            new bootstrap.Modal(document.getElementById('kt_modal_pay_expense')).show();
        });
};

// Approve Expense
window.approveExpense = function(id) {
    if (confirm('Are you sure you want to approve this expense?')) {
        fetch(`/admin/expenses/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadExpenses(); }
            else window.showToast('error', data.message);
        });
    }
};

// Reject Expense
window.rejectExpense = function(id) {
    const reason = prompt('Please enter the reason for rejection:');
    if (reason !== null) {
        fetch(`/admin/expenses/${id}/reject`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadExpenses(); }
            else window.showToast('error', data.message);
        });
    }
};

// Cancel Expense
window.cancelExpense = function(id) {
    const reason = prompt('Please enter the reason for cancellation:');
    if (reason !== null) {
        fetch(`/admin/expenses/${id}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadExpenses(); }
            else window.showToast('error', data.message);
        });
    }
};

// Delete Expense
window.deleteExpense = function(id) {
    if (confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
        fetch(`/admin/expenses/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadExpenses(); }
            else window.showToast('error', data.message);
        });
    }
};

// Submit Add Expense
document.getElementById('addExpenseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addExpenseBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    formData.delete('net_amount_display');
    formData.delete('total_amount_display');
    
    fetch('{{ route("admin.expenses.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_expense'))?.hide();
            this.reset();
            loadExpenses();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to create expense')).finally(() => window.hideButtonSpinner(btn));
});

// Submit Edit Expense
document.getElementById('editExpenseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editExpenseBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_expense_id').value;
    
    const formData = new FormData(this);
    formData.delete('net_amount_display');
    formData.delete('total_amount_display');
    
    fetch(`/admin/expenses/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_expense'))?.hide();
            loadExpenses();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to update expense')).finally(() => window.hideButtonSpinner(btn));
});

// Submit Pay Expense
document.getElementById('payExpenseForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('payExpenseBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('pay_expense_id').value;
    
    const formData = new FormData(this);
    
    fetch(`/admin/expenses/${id}/pay`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', 'Payment processed successfully. Amount deducted from account.');
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_pay_expense'))?.hide();
            loadExpenses();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to process payment')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush