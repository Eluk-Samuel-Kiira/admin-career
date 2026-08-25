<div class="modal fade" id="kt_modal_receipts" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Deposit Receipts</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body p-7">
                <div class="alert alert-info d-flex align-items-center mb-7" id="depositInfo">
                    <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                    <div>
                        <strong id="depositRefInfo">Loading...</strong><br>
                        <span class="text-muted" id="depositAmountInfo"></span>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="card card-flush mb-7">
                    <div class="card-header">
                        <h4 class="card-title">Upload New Receipt</h4>
                    </div>
                    <div class="card-body">
                        <form id="uploadReceiptForm" enctype="multipart/form-data">
                            <input type="hidden" name="deposit_id" id="upload_deposit_id">
                            <div class="row mb-5">
                                <div class="col-md-8">
                                    <label class="required fw-semibold fs-6 mb-2">Receipt File</label>
                                    <input type="file" class="form-control form-control-solid" name="receipt" accept="image/*,.pdf" required />
                                    <div class="form-text text-muted">Accepted formats: JPG, PNG, PDF (Max 5MB)</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="fw-semibold fs-6 mb-2">Receipt Number</label>
                                    <input type="text" class="form-control form-control-solid" name="receipt_number" placeholder="Optional" />
                                </div>
                            </div>
                            <div class="row mb-5">
                                <div class="col-md-8">
                                    <label class="fw-semibold fs-6 mb-2">Description</label>
                                    <input type="text" class="form-control form-control-solid" name="description" placeholder="Brief description of this receipt" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-check form-switch form-check-custom form-check-solid mt-8">
                                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" />
                                        <span class="form-check-label fw-semibold">Set as Primary Receipt</span>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="uploadReceiptBtn">
                                    <span class="indicator-label">Upload Receipt</span>
                                    <span class="indicator-progress">Uploading... 
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Receipts List -->
                <div class="card card-flush">
                    <div class="card-header">
                        <h4 class="card-title">Uploaded Receipts</h4>
                    </div>
                    <div class="card-body pt-0">
                        <div id="receiptsList">
                            <div class="text-center py-5" id="receiptsLoading">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2">Loading receipts...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // View Receipts Modal
let currentDepositId = null;

window.viewReceipts = function(depositId, depositRef, depositAmount) {
    currentDepositId = depositId;
    document.getElementById('upload_deposit_id').value = depositId;
    document.getElementById('depositRefInfo').innerHTML = depositRef;
    document.getElementById('depositAmountInfo').innerHTML = `Amount: ${depositAmount}`;
    
    loadReceipts(depositId);
    new bootstrap.Modal(document.getElementById('kt_modal_receipts')).show();
};

function loadReceipts(depositId) {
    const receiptsList = document.getElementById('receiptsList');
    const loading = document.getElementById('receiptsLoading');
    
    // Check if loading element exists before manipulating
    if (loading) {
        loading.classList.remove('d-none');
    }
    if (receiptsList) {
        receiptsList.innerHTML = '';
    }
    
    fetch(`/admin/deposits/${depositId}/receipts`)
        .then(res => res.json())
        .then(data => {
            if (loading) loading.classList.add('d-none');
            renderReceipts(data);
        })
        .catch(err => {
            if (loading) loading.classList.add('d-none');
            if (receiptsList) {
                receiptsList.innerHTML = '<div class="alert alert-danger">Failed to load receipts</div>';
            }
            console.error('Error loading receipts:', err);
        });
}

function renderReceipts(receipts) {
    const container = document.getElementById('receiptsList');
    
    if (!container) return;
    
    if (!receipts || receipts.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-5">No receipts uploaded yet</div>';
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    receipts.forEach(receipt => {
        const isPrimary = receipt.is_primary === 1 || receipt.is_primary === true;
        const fileIcon = receipt.file_type?.includes('pdf') ? 'ki-file-pdf' : 
                        (receipt.file_type?.includes('image') ? 'ki-image' : 'ki-file');
        
        // Get the correct file URL
        const fileUrl = receipt.file_url || (receipt.file_path ? `/storage/${receipt.file_path}` : '#');
        
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-50px bg-light-${isPrimary ? 'success' : 'primary'}">
                        <i class="ki-duotone ${fileIcon} fs-2x text-${isPrimary ? 'success' : 'primary'}">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                    <div>
                        <div class="fw-bold">
                            ${escapeHtml(receipt.file_name)}
                            ${isPrimary ? '<span class="badge badge-light-success ms-2">Primary</span>' : ''}
                        </div>
                        <div class="text-muted fs-7">
                            ${receipt.receipt_number ? `#${escapeHtml(receipt.receipt_number)} • ` : ''}
                            ${formatFileSize(receipt.file_size)}
                            ${receipt.description ? ` • ${escapeHtml(receipt.description)}` : ''}
                        </div>
                        <div class="text-muted fs-8 mt-1">
                            Uploaded: ${new Date(receipt.created_at).toLocaleString()}
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="${fileUrl}" target="_blank" class="btn btn-sm btn-icon btn-light" title="View">
                        <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                    </a>
                    ${!isPrimary ? `
                        <button class="btn btn-sm btn-icon btn-light" onclick="setPrimaryReceipt(${receipt.id})" title="Set as Primary">
                            <i class="ki-duotone ki-star fs-3 text-warning"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-icon btn-light" onclick="deleteReceipt(${receipt.id})" title="Delete">
                        <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function formatFileSize(bytes) {
    if (!bytes) return '0 bytes';
    const sizes = ['bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
}

function setPrimaryReceipt(receiptId) {
    if (!currentDepositId) return;
    
    // Use global spinner function
    const btn = event?.target?.closest('.btn');
    if (btn) window.showButtonSpinner(btn);
    
    fetch(`/admin/deposits/${currentDepositId}/receipts/${receiptId}/primary`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            loadReceipts(currentDepositId);
            // Refresh the deposits table to show updated data if needed
            loadDeposits();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => {
        window.showToast('error', 'Failed to set primary receipt');
    }).finally(() => {
        if (btn) window.hideButtonSpinner(btn);
    });
}

function deleteReceipt(receiptId) {
    if (!confirm('Are you sure you want to delete this receipt?')) return;
    if (!currentDepositId) return;
    
    // Use global spinner function on the button
    const btn = event?.target?.closest('.btn');
    if (btn) window.showButtonSpinner(btn);
    
    fetch(`/admin/deposits/${currentDepositId}/receipts/${receiptId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            loadReceipts(currentDepositId);
            // Refresh the deposits table to show updated data
            loadDeposits();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => {
        window.showToast('error', 'Failed to delete receipt');
    }).finally(() => {
        if (btn) window.hideButtonSpinner(btn);
    });
}

// Upload receipt form submission
document.getElementById('uploadReceiptForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('uploadReceiptBtn');
    // Use global spinner function
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    fetch(`/admin/deposits/${currentDepositId}/receipts`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            this.reset();
            loadReceipts(currentDepositId);
            // Refresh the deposits table to show updated data
            loadDeposits();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => {
        window.showToast('error', 'Failed to upload receipt');
    }).finally(() => {
        window.hideButtonSpinner(btn);
    });
});
</script>