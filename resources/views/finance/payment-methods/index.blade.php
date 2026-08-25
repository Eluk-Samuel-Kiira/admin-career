@extends('layouts.admin')

@section('title', 'Payment Methods')
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
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Payment Methods" />
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex gap-3">
                @can('create payment methods')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_payment_method">
                    <i class="ki-duotone ki-plus-square fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i> Add Payment Method
                </button>
                @endcan
                @can('transfer payment methods')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#kt_modal_transfer_payment">
                    <i class="ki-duotone ki-arrows-circle fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i> Transfer
                </button>
                @endcan
            </div>
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading payment methods...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Name</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-100px">Code</th>
                            <th class="min-w-150px">Provider/Bank</th>
                            <th class="min-w-100px">Currency</th>
                            <th class="min-w-120px">Balance</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-100px">Actions</th>
                        <tr>
                    </thead>
                    <tbody id="paymentMethodsTableBody"></tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
        
        <div id="noDataMessage" class="text-center py-10 d-none">
            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <p class="text-muted">No payment methods found.</p>
        </div>
    </div>
</div>

<!-- Add Payment Method Modal -->
<div class="modal fade" id="kt_modal_add_payment_method" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Payment Method</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPaymentMethodForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Method Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Stanbic Bank USD Account" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" placeholder="e.g., STANBIC_USD" required />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Type</label>
                            <select class="form-select form-select-solid" name="type" id="add_type" required>
                                <option value="bank">Bank Account</option>
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="crypto">Cryptocurrency</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="add_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="bankFields" class="payment-type-fields">
                        <div class="row mb-7">
                            <div class="col-md-12">
                                <label class="fw-semibold fs-6 mb-2">Bank/Provider Name</label>
                                <input type="text" class="form-control form-control-solid" name="provider" placeholder="e.g., Stanbic Bank, MTN, PayPal" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Account Name</label>
                                <input type="text" class="form-control form-control-solid" name="account_name" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Account Number</label>
                                <input type="text" class="form-control form-control-solid" name="account_number" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">IBAN</label>
                                <input type="text" class="form-control form-control-solid" name="iban" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">SWIFT/BIC</label>
                                <input type="text" class="form-control form-control-solid" name="swift_bic" />
                            </div>
                        </div>
                    </div>
                    
                    <div id="mobileFields" class="payment-type-fields" style="display:none">
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Phone Number</label>
                                <input type="text" class="form-control form-control-solid" name="phone_number" placeholder="+2567XXXXXXXX" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Wallet ID</label>
                                <input type="text" class="form-control form-control-solid" name="wallet_id" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-12">
                                <label class="fw-semibold fs-6 mb-2">Provider</label>
                                <input type="text" class="form-control form-control-solid" name="provider" placeholder="e.g., MTN, Airtel, Vodafone" />
                            </div>
                        </div>
                    </div>
                    
                    <div id="cardFields" class="payment-type-fields" style="display:none">
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Card Last 4 Digits</label>
                                <input type="text" class="form-control form-control-solid" name="card_last_four" maxlength="4" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Card Type</label>
                                <select class="form-select form-select-solid" name="card_type">
                                    <option value="Visa">Visa</option>
                                    <option value="Mastercard">Mastercard</option>
                                    <option value="American Express">American Express</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-12">
                                <label class="fw-semibold fs-6 mb-2">Expiry Date</label>
                                <input type="month" class="form-control form-control-solid" name="card_expiry_date" />
                            </div>
                        </div>
                    </div>
                    
                    <div id="eWalletFields" class="payment-type-fields" style="display:none">
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Wallet Email</label>
                                <input type="email" class="form-control form-control-solid" name="wallet_email" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Provider</label>
                                <input type="text" class="form-control form-control-solid" name="provider" placeholder="PayPal, Skrill, etc." />
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h4 class="fw-bold mb-5">Balance & Limits</h4>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Initial Balance</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="current_balance_display" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Transaction Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="min_transaction_amount_display" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Max Transaction Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="max_transaction_amount_display" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Daily Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="daily_limit_display" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Monthly Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="monthly_limit_display" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Balance Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="min_balance_limit" value="0" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Transaction Fee (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="transaction_fee_percentage" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Transaction Fee (Fixed)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="transaction_fee_fixed_display" value="0" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="allow_negative_balance" />
                                <span class="form-check-label fw-semibold">Allow Negative Balance</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" checked />
                                <span class="form-check-label fw-semibold">Active</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" />
                                <span class="form-check-label fw-semibold">Set as Default</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPaymentMethodBtn">
                            <span class="indicator-label">Create Payment Method</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Payment Method Modal -->
<div class="modal fade" id="kt_modal_transfer_payment" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Transfer Between Accounts</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="transferPaymentForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">From Account</label>
                            <select class="form-select form-select-solid" name="from_payment_method_id" id="transfer_from" required>
                                <option value="">Select Source Account</option>
                            </select>
                            <div id="from_balance_info" class="text-muted fs-7 mt-1"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">To Account</label>
                            <select class="form-select form-select-solid" name="to_payment_method_id" id="transfer_to" required>
                                <option value="">Select Destination Account</option>
                            </select>
                            <div id="to_balance_info" class="text-muted fs-7 mt-1"></div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="transfer_currency_symbol">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="amount" id="transfer_amount" placeholder="0.00" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Description (Optional)</label>
                            <input type="text" class="form-control form-control-solid" name="description" placeholder="Transfer description" />
                        </div>
                    </div>
                    
                    <!-- Transfer Preview -->
                    <div id="transferPreview" class="transfer-preview d-none">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="fw-bold text-muted fs-7">You Send</div>
                                <div class="fs-2 fw-bold amount-sent" id="preview_send_amount">$0.00</div>
                                <div class="text-muted fs-7" id="preview_from_account">From: -</div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="fw-bold text-muted fs-7">Recipient Gets</div>
                                <div class="fs-2 fw-bold amount-received" id="preview_receive_amount">$0.00</div>
                                <div class="text-muted fs-7" id="preview_to_account">To: -</div>
                            </div>
                        </div>
                        <div class="rate-info mt-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <span class="text-muted">Exchange Rate:</span>
                                    <span class="fw-bold" id="preview_exchange_rate">1.0000</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted">Fee:</span>
                                    <span class="fw-bold" id="preview_fee">$0.00</span>
                                </div>
                                <div class="col-md-4">
                                    <span class="text-muted">Total Debit:</span>
                                    <span class="fw-bold" id="preview_total_debit">$0.00</span>
                                </div>
                            </div>
                        </div>
                        <div id="currencyWarning" class="transfer-currency-warning d-none">
                            <i class="ki-duotone ki-information-5 fs-3 me-2"></i>
                            <span>Currency conversion will be applied as the accounts have different currencies.</span>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="transferPaymentBtn">
                            <span class="indicator-label">Complete Transfer</span>
                            <span class="indicator-progress">Processing... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Edit Payment Method Modal -->
<div class="modal fade" id="kt_modal_edit_payment_method" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Payment Method</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPaymentMethodForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="payment_method_id" id="edit_payment_method_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Method Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" id="edit_code" required />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Type</label>
                            <select class="form-select form-select-solid" name="type" id="edit_type" required>
                                <option value="bank">Bank Account</option>
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="crypto">Cryptocurrency</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="edit_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="editBankFields">
                        <div class="row mb-7">
                            <div class="col-md-12">
                                <label class="fw-semibold fs-6 mb-2">Bank/Provider Name</label>
                                <input type="text" class="form-control form-control-solid" name="provider" id="edit_provider" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Account Name</label>
                                <input type="text" class="form-control form-control-solid" name="account_name" id="edit_account_name" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">Account Number</label>
                                <input type="text" class="form-control form-control-solid" name="account_number" id="edit_account_number" />
                            </div>
                        </div>
                        <div class="row mb-7">
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">IBAN</label>
                                <input type="text" class="form-control form-control-solid" name="iban" id="edit_iban" />
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold fs-6 mb-2">SWIFT/BIC</label>
                                <input type="text" class="form-control form-control-solid" name="swift_bic" id="edit_swift_bic" />
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h4 class="fw-bold mb-5">Balance & Limits</h4>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Current Balance</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="current_balance_display" id="edit_current_balance" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Transaction Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="min_transaction_amount_display" id="edit_min_transaction" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Max Transaction Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="max_transaction_amount_display" id="edit_max_transaction" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Daily Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="daily_limit_display" id="edit_daily_limit" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Monthly Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="monthly_limit_display" id="edit_monthly_limit" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Min Balance Limit</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="min_balance_limit" id="edit_min_balance" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Transaction Fee (%)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="transaction_fee_percentage" id="edit_fee_percentage" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Transaction Fee (Fixed)</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="transaction_fee_fixed_display" id="edit_fee_fixed" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="allow_negative_balance" id="edit_allow_negative" />
                                <span class="form-check-label fw-semibold">Allow Negative Balance</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                                <span class="form-check-label fw-semibold">Active</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" id="edit_is_default" />
                                <span class="form-check-label fw-semibold">Set as Default</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPaymentMethodBtn">
                            <span class="indicator-label">Update Payment Method</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
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

function getTypeIcon(type) {
    const icons = {
        'bank': '<i class="ki-duotone ki-building fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'cash': '<i class="ki-duotone ki-dollar fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'card': '<i class="ki-duotone ki-card fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'mobile_money': '<i class="ki-duotone ki-phone fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'e_wallet': '<i class="ki-duotone ki-wallet fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'crypto': '<i class="ki-duotone ki-bitcoin fs-3"><span class="path1"></span><span class="path2"></span></i>',
        'cheque': '<i class="ki-duotone ki-note fs-3"><span class="path1"></span><span class="path2"></span></i>'
    };
    return icons[type] || '<i class="ki-duotone ki-category fs-3"></i>';
}

function formatTypeName(type) {
    const names = {
        'bank': 'Bank',
        'cash': 'Cash',
        'card': 'Card',
        'mobile_money': 'Mobile Money',
        'e_wallet': 'E-Wallet',
        'crypto': 'Crypto',
        'cheque': 'Cheque'
    };
    return names[type] || type;
}

document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    loadPaymentMethods();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPaymentMethods();
            }, 500);
        });
    }
    
    // Type field toggle
    const typeSelect = document.getElementById('add_type');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            document.querySelectorAll('.payment-type-fields').forEach(el => el.style.display = 'none');
            if (this.value === 'bank') document.getElementById('bankFields').style.display = 'block';
            else if (this.value === 'mobile_money') document.getElementById('mobileFields').style.display = 'block';
            else if (this.value === 'card') document.getElementById('cardFields').style.display = 'block';
            else if (this.value === 'e_wallet') document.getElementById('eWalletFields').style.display = 'block';
        });
    }
});

function loadCurrencies() {
    fetch('{{ route("admin.payment-methods.currencies") }}')
        .then(res => res.json())
        .then(data => {
            const options = data.map(c => `<option value="${c.id}">${c.code} - ${c.name} (${c.symbol})</option>`).join('');
            document.getElementById('add_currency_id').innerHTML = '<option value="">Select Currency</option>' + options;
            document.getElementById('edit_currency_id').innerHTML = '<option value="">Select Currency</option>' + options;
        });
}

function loadPaymentMethods() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.payment-methods.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url).then(res => res.json()).then(data => {
        spinner.classList.add('d-none');
        if (data.data.length === 0) {
            noData.classList.remove('d-none');
        } else {
            table.classList.remove('d-none');
            renderPaymentMethodsTable(data.data);
            renderPagination(data);
            pagination.classList.remove('d-none');
        }
    }).catch(err => {
        spinner.classList.add('d-none');
        window.showToast('error', 'Failed to load payment methods');
    });
}

function renderPaymentMethodsTable(methods) {
    const tbody = document.getElementById('paymentMethodsTableBody');
    tbody.innerHTML = '';
    
    methods.forEach(method => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${method.id}</span>`;
        row.insertCell(1).innerHTML = `<div><div class="fw-bold">${escapeHtml(method.name)}</div><div class="text-muted fs-7">${escapeHtml(method.code)}</div></div>`;
        row.insertCell(2).innerHTML = `<span class="badge badge-light-info fs-7">${getTypeIcon(method.type)} ${formatTypeName(method.type)}</span>`;
        row.insertCell(3).innerHTML = `<span class="text-muted">${escapeHtml(method.code)}</span>`;
        row.insertCell(4).innerHTML = method.provider ? escapeHtml(method.provider) : '<span class="text-muted">N/A</span>';
        row.insertCell(5).innerHTML = `<span class="badge badge-light-primary">${method.currency || 'N/A'}</span>`;
        row.insertCell(6).innerHTML = `<div class="fw-bold text-success">${method.formatted_balance}</div>`;
        row.insertCell(7).innerHTML = method.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="togglePaymentMethodStatus(${method.id}, ${method.is_active})" title="${method.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${method.is_active ? 'disconnect' : 'check'} fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editPaymentMethod(${method.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deletePaymentMethod(${method.id}, '${escapeHtml(method.name)}')" title="Delete">
                    <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
            </div>
        `;
    });
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
    if (start > 2) el.appendChild('<li class="page-item disabled"><span class="page-link">...</span></li>');
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.appendChild('<li class="page-item disabled"><span class="page-link">...</span></li>');
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadPaymentMethods(); }
};

window.togglePaymentMethodStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this payment method?`)) {
        fetch(`/admin/payment-methods/${id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadPaymentMethods(); }
            else window.showToast('error', data.message);
        });
    }
};

window.editPaymentMethod = function(id) {
    fetch(`/admin/payment-methods/${id}`).then(res => res.json()).then(data => {
        document.getElementById('edit_payment_method_id').value = data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_code').value = data.code;
        document.getElementById('edit_type').value = data.type;
        document.getElementById('edit_currency_id').value = data.currency_id;
        document.getElementById('edit_provider').value = data.provider || '';
        document.getElementById('edit_account_name').value = data.account_name || '';
        document.getElementById('edit_account_number').value = data.account_number || '';
        document.getElementById('edit_iban').value = data.iban || '';
        document.getElementById('edit_swift_bic').value = data.swift_bic || '';
        document.getElementById('edit_current_balance').value = data.current_balance_display || 0;
        document.getElementById('edit_min_transaction').value = data.min_transaction_amount_display || 0;
        document.getElementById('edit_max_transaction').value = data.max_transaction_amount_display || '';
        document.getElementById('edit_daily_limit').value = data.daily_limit_display || '';
        document.getElementById('edit_monthly_limit').value = data.monthly_limit_display || '';
        document.getElementById('edit_min_balance').value = data.min_balance_limit || 0;
        document.getElementById('edit_fee_percentage').value = data.transaction_fee_percentage || 0;
        document.getElementById('edit_fee_fixed').value = data.transaction_fee_fixed_display || 0;
        document.getElementById('edit_allow_negative').checked = data.allow_negative_balance;
        document.getElementById('edit_is_active').checked = data.is_active;
        document.getElementById('edit_is_default').checked = data.is_default;
        new bootstrap.Modal(document.getElementById('kt_modal_edit_payment_method')).show();
    });
};

window.deletePaymentMethod = function(id, name) {
    if (confirm(`Are you sure you want to delete payment method "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/payment-methods/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadPaymentMethods(); }
            else window.showToast('error', data.message);
        });
    }
};

document.getElementById('addPaymentMethodForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addPaymentMethodBtn');
    window.showButtonSpinner(btn);
    fetch('{{ route("admin.payment-methods.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_payment_method'))?.hide();
            this.reset();
            loadPaymentMethods();
        } else window.showToast('error', data.message);
    }).catch(err => window.showToast('error', 'Failed to create payment method')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editPaymentMethodForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editPaymentMethodBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_payment_method_id').value;
    fetch(`/admin/payment-methods/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_payment_method'))?.hide();
            loadPaymentMethods();
        } else window.showToast('error', data.message);
    }).catch(err => window.showToast('error', 'Failed to update payment method')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<script>
    // Load payment methods for transfer dropdowns
function loadTransferPaymentMethods() {
    fetch('{{ route("admin.payment-methods.data") }}?per_page=100')
        .then(res => res.json())
        .then(data => {
            const fromSelect = document.getElementById('transfer_from');
            const toSelect = document.getElementById('transfer_to');
            
            // Filter active payment methods
            const methods = data.data.filter(m => m.is_active);
            
            const options = methods.map(m => `
                <option value="${m.id}" data-currency="${m.currency}" data-balance="${m.current_balance}" data-currency-symbol="${m.currency_symbol}">
                    ${m.name} (${m.currency}) - ${m.formatted_balance}
                </option>
            `).join('');
            
            fromSelect.innerHTML = '<option value="">Select Source Account</option>' + options;
            toSelect.innerHTML = '<option value="">Select Destination Account</option>' + options;
        });
}

// Calculate and preview transfer
function previewTransfer() {
    const fromId = document.getElementById('transfer_from').value;
    const toId = document.getElementById('transfer_to').value;
    const amount = parseFloat(document.getElementById('transfer_amount').value);
    const preview = document.getElementById('transferPreview');
    const warning = document.getElementById('currencyWarning');
    
    if (!fromId || !toId || !amount || amount <= 0) {
        preview.classList.add('d-none');
        return;
    }
    
    if (fromId === toId) {
        window.showToast('warning', 'Source and destination accounts must be different');
        preview.classList.add('d-none');
        return;
    }
    
    // Get selected options
    const fromSelect = document.getElementById('transfer_from');
    const toSelect = document.getElementById('transfer_to');
    const fromOption = fromSelect.options[fromSelect.selectedIndex];
    const toOption = toSelect.options[toSelect.selectedIndex];
    
    const fromCurrency = fromOption.dataset.currency;
    const toCurrency = toOption.dataset.currency;
    const fromSymbol = fromOption.dataset.currencySymbol || '$';
    const toSymbol = toOption.dataset.currencySymbol || '$';
    
    // Check if currencies differ
    const currenciesDiffer = fromCurrency !== toCurrency;
    warning.classList.toggle('d-none', !currenciesDiffer);
    
    // For now, assume 1:1 conversion if same currency, or use exchange rate if different
    // In production, fetch exchange rate from API
    let exchangeRate = 1;
    let convertedAmount = amount;
    
    if (currenciesDiffer) {
        // You can implement exchange rate API here
        // For demo, using a simple conversion (you should fetch from a real API)
        exchangeRate = getExchangeRateFallback(fromCurrency, toCurrency);
        convertedAmount = amount * exchangeRate;
    }
    
    // Format amounts
    const formattedSend = fromSymbol + ' ' + amount.toFixed(2);
    const formattedReceive = toSymbol + ' ' + convertedAmount.toFixed(2);
    
    // Calculate fee (assume 0.5% for demo, you can implement actual fee calculation)
    const fee = amount * 0.005;
    const totalDebit = amount + fee;
    
    document.getElementById('preview_send_amount').textContent = formattedSend;
    document.getElementById('preview_receive_amount').textContent = formattedReceive;
    document.getElementById('preview_from_account').textContent = 'From: ' + fromOption.text.split('(')[0].trim();
    document.getElementById('preview_to_account').textContent = 'To: ' + toOption.text.split('(')[0].trim();
    document.getElementById('preview_exchange_rate').textContent = exchangeRate.toFixed(4);
    document.getElementById('preview_fee').textContent = fromSymbol + ' ' + fee.toFixed(2);
    document.getElementById('preview_total_debit').textContent = fromSymbol + ' ' + totalDebit.toFixed(2);
    
    preview.classList.remove('d-none');
}

// Fallback exchange rate function (replace with real API)
function getExchangeRateFallback(fromCurrency, toCurrency) {
    // This is a demo - replace with real exchange rate API call
    const rates = {
        'USD': 1,
        'UGX': 3700,
        'EUR': 0.92,
        'GBP': 0.79,
        'KES': 130,
        'TZS': 2500,
        'RWF': 1300,
    };
    
    if (fromCurrency === toCurrency) return 1;
    
    const fromRate = rates[fromCurrency] || 1;
    const toRate = rates[toCurrency] || 1;
    
    // If both rates exist, calculate cross rate
    if (fromRate && toRate) {
        return toRate / fromRate;
    }
    
    return 1; // Fallback
}

// Handle transfer form submission
document.getElementById('transferPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('transferPaymentBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.payment-methods.transfer") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_transfer_payment'))?.hide();
            this.reset();
            document.getElementById('transferPreview').classList.add('d-none');
            loadPaymentMethods(); // Refresh table
            loadTransferPaymentMethods(); // Refresh dropdowns
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(err => {
        window.showToast('error', 'Transfer failed: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Event listeners for transfer preview
document.getElementById('transfer_from')?.addEventListener('change', function() {
    const toSelect = document.getElementById('transfer_to');
    const selectedValue = this.value;
    
    // Remove the selected option from "to" dropdown to prevent same account
    Array.from(toSelect.options).forEach(option => {
        option.disabled = option.value === selectedValue && option.value !== '';
    });
    
    // Show balance info
    const option = this.options[this.selectedIndex];
    if (option && option.value) {
        document.getElementById('from_balance_info').textContent = 'Balance: ' + option.text.split('(')[1].replace(')', '').trim();
    } else {
        document.getElementById('from_balance_info').textContent = '';
    }
    
    previewTransfer();
});

document.getElementById('transfer_to')?.addEventListener('change', function() {
    const fromSelect = document.getElementById('transfer_from');
    const selectedValue = this.value;
    
    // Remove the selected option from "from" dropdown to prevent same account
    Array.from(fromSelect.options).forEach(option => {
        option.disabled = option.value === selectedValue && option.value !== '';
    });
    
    // Show balance info
    const option = this.options[this.selectedIndex];
    if (option && option.value) {
        document.getElementById('to_balance_info').textContent = 'Balance: ' + option.text.split('(')[1].replace(')', '').trim();
    } else {
        document.getElementById('to_balance_info').textContent = '';
    }
    
    previewTransfer();
});

document.getElementById('transfer_amount')?.addEventListener('input', previewTransfer);

// Update the DOMContentLoaded event listener to include transfer loading
document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    loadPaymentMethods();
    loadTransferPaymentMethods();
    
    // ... rest of existing code ...
});
</script>
@endpush