<div class="modal fade" id="kt_modal_view_deposit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Deposit Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7">
                <!-- Reference and Status -->
                <div class="d-flex justify-content-between mb-7">
                    <div>
                        <span class="text-muted">Reference</span>
                        <div class="fw-bold fs-5" id="view_deposit_ref">-</div>
                    </div>
                    <div>
                        <span class="text-muted">Status</span>
                        <div id="view_status">-</div>
                    </div>
                </div>
                
                <div class="separator my-5"></div>
                
                <!-- Amount and Date -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Amount</span>
                        <div class="fw-bold fs-2 text-success" id="view_amount">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Deposit Date</span>
                        <div class="fw-bold" id="view_deposit_date">-</div>
                    </div>
                </div>
                
                <div class="separator my-5"></div>
                
                <!-- Department and Depositor (NEW) -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Department</span>
                        <div class="fw-bold" id="view_department">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Depositor (User)</span>
                        <div class="fw-bold" id="view_depositor">-</div>
                    </div>
                </div>
                
                <div class="separator my-5"></div>
                
                <!-- Source Information -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Source</span>
                        <div class="fw-bold" id="view_source">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Source Reference</span>
                        <div class="fw-bold" id="view_source_ref">-</div>
                    </div>
                </div>
                
                <!-- Purpose -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <span class="text-muted">Purpose</span>
                        <div class="fw-bold" id="view_purpose">-</div>
                    </div>
                </div>
                
                <div class="separator my-5"></div>
                
                <!-- Customer / Invoice Details -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Customer ID</span>
                        <div class="fw-bold" id="view_customer_id">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Invoice Number</span>
                        <div class="fw-bold" id="view_invoice_number">-</div>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Payment Method</span>
                        <div class="fw-bold" id="view_payment_method">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Reference Number</span>
                        <div class="fw-bold" id="view_reference">-</div>
                    </div>
                </div>
                
                <!-- Depositor Physical Details -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <span class="text-muted">Depositor Name</span>
                        <div class="fw-bold" id="view_depositor_name">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">Depositor Contact</span>
                        <div class="fw-bold" id="view_depositor_contact">-</div>
                    </div>
                </div>
                
                <!-- Description -->
                <div class="row">
                    <div class="col-md-12">
                        <span class="text-muted">Description</span>
                        <div class="fw-bold" id="view_description">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>