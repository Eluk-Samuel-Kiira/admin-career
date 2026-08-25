<div class="modal fade" id="kt_modal_add_deposit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">New Deposit</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addDepositForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Payment Method</label>
                            <select class="form-select form-select-solid" name="payment_method_id" id="add_payment_method_id" required>
                                <option value="">Select Payment Method</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Deposit Method</label>
                            <select class="form-select form-select-solid" name="deposit_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="crypto">Cryptocurrency</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" id="currency_symbol">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="amount" id="add_amount" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Fee</label>
                            <div class="input-group">
                                <span class="input-group-text currency_symbol_fee">$</span>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="fee" id="add_fee" value="0" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Deposit Date</label>
                            <input type="datetime-local" class="form-control form-control-solid" name="deposit_date" value="{{ now()->format('Y-m-d\TH:i') }}" required />
                        </div>
                    </div>
                    
                    <!-- Hidden currency_id field -->
                    <input type="hidden" name="currency_id" id="add_currency_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_id">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Depositor (User)</label>
                            <select class="form-select form-select-solid" name="depositor_id" id="add_depositor_id">
                                <option value="">Select Depositor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Source of Money</label>
                            <select class="form-select form-select-solid" name="source_id" id="add_source_id" required>
                                <option value="">Select Source</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Purpose Category</label>
                            <select class="form-select form-select-solid" name="purpose_id" id="add_purpose_id" required>
                                <option value="">Select Purpose</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Source Reference (ID/Name)</label>
                            <input type="text" class="form-control form-control-solid" name="source_reference" placeholder="e.g., Job ID, Client ID, Invoice #" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Reference Number</label>
                            <input type="text" class="form-control form-control-solid" name="reference_number" placeholder="Transaction reference" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Customer ID</label>
                            <input type="text" class="form-control form-control-solid" name="customer_id" placeholder="Customer ID" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Invoice Number</label>
                            <input type="text" class="form-control form-control-solid" name="invoice_number" placeholder="Invoice #" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="fw-semibold fs-6 mb-2">Purpose Description</label>
                            <textarea class="form-control form-control-solid" name="purpose_description" rows="2" placeholder="Describe the purpose of this deposit"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-12">
                            <label class="fw-semibold fs-6 mb-2">Notes</label>
                            <textarea class="form-control form-control-solid" name="notes" rows="2" placeholder="Additional notes"></textarea>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addDepositBtn">
                            <span class="indicator-label">Create Deposit</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Load departments and users when modal opens
document.getElementById('kt_modal_add_deposit')?.addEventListener('show.bs.modal', function() {
    loadSources();
    loadPurposes();
    loadDepartments();
    loadUsers();
});

function loadDepartments() {
    fetch('{{ route("admin.deposits.departments") }}')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('add_department_id');
            if (select) {
                select.innerHTML = '<option value="">Select Department</option>' + 
                    data.map(d => `<option value="${d.id}">${d.name} (${d.code})</option>`).join('');
            }
        })
        .catch(err => console.error('Error loading departments:', err));
}

function loadUsers() {
    fetch('{{ route("admin.deposits.users") }}')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('add_depositor_id');
            if (select) {
                select.innerHTML = '<option value="">Select Depositor</option>' + 
                    data.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
            }
        })
        .catch(err => console.error('Error loading users:', err));
}

function loadSources() {
    fetch('{{ route("admin.deposits.sources") }}')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('add_source_id');
            if (select) {
                select.innerHTML = '<option value="">Select Source</option>' + 
                    data.map(s => `<option value="${s.id}" data-icon="${s.icon}" data-color="${s.color}">${s.name}</option>`).join('');
            }
        })
        .catch(err => console.error('Error loading sources:', err));
}

function loadPurposes() {
    fetch('{{ route("admin.deposits.purposes") }}')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('add_purpose_id');
            if (select) {
                select.innerHTML = '<option value="">Select Purpose</option>' + 
                    data.map(p => `<option value="${p.id}" data-icon="${p.icon}" data-color="${p.color}">${p.name}</option>`).join('');
            }
        })
        .catch(err => console.error('Error loading purposes:', err));
}

// Update currency symbol when payment method changes
document.getElementById('add_payment_method_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const currencyId = selectedOption.getAttribute('data-currency-id');
    const currencySymbol = selectedOption.getAttribute('data-currency-symbol') || '$';
    
    if (currencyId) {
        document.getElementById('add_currency_id').value = currencyId;
        document.getElementById('currency_symbol').innerHTML = currencySymbol;
        document.querySelectorAll('.currency_symbol_fee').forEach(el => {
            el.innerHTML = currencySymbol;
        });
    } else {
        document.getElementById('add_currency_id').value = '';
        document.getElementById('currency_symbol').innerHTML = '$';
        document.querySelectorAll('.currency_symbol_fee').forEach(el => {
            el.innerHTML = '$';
        });
    }
});

document.getElementById('addDepositForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addDepositBtn');
    window.showButtonSpinner(btn);
    
    const currencyId = document.getElementById('add_currency_id').value;
    if (!currencyId) {
        window.showToast('error', 'Please select a payment method first');
        window.hideButtonSpinner(btn);
        return;
    }
    
    const sourceId = document.getElementById('add_source_id').value;
    if (!sourceId) {
        window.showToast('error', 'Please select a source of money');
        window.hideButtonSpinner(btn);
        return;
    }
    
    const purposeId = document.getElementById('add_purpose_id').value;
    if (!purposeId) {
        window.showToast('error', 'Please select a purpose category');
        window.hideButtonSpinner(btn);
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.deposits.store") }}', {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_deposit'))?.hide();
            this.reset();
            loadDeposits();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => {
        console.error(err);
        window.showToast('error', 'Failed to create deposit: ' + (err.message || 'Unknown error'));
    }).finally(() => window.hideButtonSpinner(btn));
});
</script>