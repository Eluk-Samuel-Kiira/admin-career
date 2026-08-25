@extends('layouts.admin')

@section('title', 'Deposits')
@section('page_title', 'Revenue - Deposits')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Revenue</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Deposits</li>
@endsection

@section('content')
@can('view deposits')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Deposits" />
            </div>
        </div>
        @can('view deposits')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_deposit">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> New Deposit
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading deposits...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-120px">Reference</th>
                            <th class="min-w-150px">Payment Method</th>
                            <th class="min-w-120px">Amount</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-120px">Depositor</th>
                            <th class="min-w-120px">Source</th>
                            <th class="min-w-150px">Purpose</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Date</th>
                            <th class="text-end min-w-120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="depositsTableBody"></tbody>
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
            <p class="text-muted">No deposits found.</p>
        </div>
    </div>
</div>

<!-- Add Deposit Modal -->
@include('finance.deposit.add-deposit-modal')

<!-- View Deposit Modal -->
@include('finance.deposit.view-deposit-modal')
@include('finance.deposit.receipts-modal')
@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let paymentMethods = [];

function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-light-warning">Pending</span>',
        'processing': '<span class="badge badge-light-info">Processing</span>',
        'completed': '<span class="badge badge-light-success">Completed</span>',
        'failed': '<span class="badge badge-light-danger">Failed</span>',
        'cancelled': '<span class="badge badge-light-secondary">Cancelled</span>'
    };
    return badges[status] || '<span class="badge badge-light-secondary">' + status + '</span>';
}

function getDepositMethodIcon(method) {
    const icons = {
        'cash': '<i class="ki-duotone ki-dollar fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'bank_transfer': '<i class="ki-duotone ki-building fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'mobile_money': '<i class="ki-duotone ki-phone fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'card': '<i class="ki-duotone ki-card fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'cheque': '<i class="ki-duotone ki-note fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'e_wallet': '<i class="ki-duotone ki-wallet fs-4"><span class="path1"></span><span class="path2"></span></i>',
        'crypto': '<i class="ki-duotone ki-bitcoin fs-4"><span class="path1"></span><span class="path2"></span></i>'
    };
    return icons[method] || '<i class="ki-duotone ki-coffee fs-4"></i>';
}

document.addEventListener('DOMContentLoaded', function() {
    loadDeposits();
    loadPaymentMethods();
    loadCurrencies();
    loadDepartments();
    loadUsers();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadDeposits();
            }, 500);
        });
    }
});

function loadPaymentMethods() {
    fetch('{{ route("admin.deposits.payment-methods") }}')
        .then(res => res.json())
        .then(data => {
            paymentMethods = data;
            const select = document.getElementById('add_payment_method_id');
            if (select) {
                select.innerHTML = '<option value="">Select Payment Method</option>' + 
                    data.map(m => `<option value="${m.id}" data-currency-id="${m.currency_id}" data-currency-symbol="${m.currency_symbol}">${m.name} (${m.currency_code})</option>`).join('');
            }
        })
        .catch(err => console.error('Error loading payment methods:', err));
}

function loadCurrencies() {
    fetch('{{ route("admin.deposits.currencies") }}')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('add_currency_id');
            if (select) {
                select.innerHTML = '<option value="">Select Currency</option>' + 
                    data.map(c => `<option value="${c.id}">${c.code} - ${c.name} (${c.symbol})</option>`).join('');
            }
        });
}

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

function loadDeposits() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.deposits.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url).then(res => res.json()).then(data => {
        spinner.classList.add('d-none');
        if (data.data.length === 0) {
            noData.classList.remove('d-none');
        } else {
            table.classList.remove('d-none');
            renderDepositsTable(data.data);
            renderPagination(data);
            pagination.classList.remove('d-none');
        }
    }).catch(err => {
        spinner.classList.add('d-none');
        window.showToast('error', 'Failed to load deposits');
    });
}

function renderDepositsTable(deposits) {
    const tbody = document.getElementById('depositsTableBody');
    tbody.innerHTML = '';
    
    deposits.forEach(deposit => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${deposit.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="text-muted fs-7">${deposit.deposit_ref?.substring(0, 13) || 'N/A'}...</span>`;
        row.insertCell(2).innerHTML = `<div><div class="fw-bold">${escapeHtml(deposit.payment_method_name || 'N/A')}</div><div class="text-muted fs-7">${getDepositMethodIcon(deposit.deposit_method)} ${(deposit.deposit_method || '').replace('_', ' ').toUpperCase()}</div></div>`;
        
        // Amount cell
        const feeDisplay = (deposit.fee && deposit.fee > 0) ? (deposit.formatted_fee || '0') : '0';
        row.insertCell(3).innerHTML = `<div class="fw-bold text-success">${deposit.formatted_amount || '0'}</div><div class="text-muted fs-7">Fee: ${feeDisplay}</div>`;
        
        // Department
        row.insertCell(4).innerHTML = deposit.department_name ? `<span class="badge badge-light-primary">${escapeHtml(deposit.department_name)}</span>` : '<span class="text-muted">-</span>';
        
        // Depositor (User)
        row.insertCell(5).innerHTML = deposit.depositor_name ? `<span class="badge badge-light-info">${escapeHtml(deposit.depositor_name)}</span>` : '<span class="text-muted">-</span>';
        
        // Source
        row.insertCell(6).innerHTML = `<div><span class="badge badge-light-info fs-7">Source</span><div class="fw-bold mt-1">${escapeHtml(deposit.source_name || 'N/A')}</div>${deposit.source_reference ? `<div class="text-muted fs-7 mt-1">Ref: ${escapeHtml(deposit.source_reference)}</div>` : ''}</div>`;
        
        // Purpose
        row.insertCell(7).innerHTML = `<div><span class="badge badge-light-primary fs-7">Purpose</span><div class="fw-bold mt-1">${escapeHtml(deposit.purpose_name || 'N/A')}</div>${deposit.purpose_description ? `<div class="text-muted fs-7 mt-1">${escapeHtml(deposit.purpose_description)}</div>` : ''}</div>`;
        
        // Status
        row.insertCell(8).innerHTML = getStatusBadge(deposit.status);
        
        // Date
        row.insertCell(9).innerHTML = deposit.deposit_date || 'N/A';
        
        // Actions
        row.insertCell(10).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="viewReceipts(${deposit.id}, '${escapeHtml(deposit.deposit_ref)}', '${deposit.formatted_amount}')" title="View Receipts">
                    <i class="ki-duotone ki-folder fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="viewDeposit(${deposit.id})" title="View">
                    <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                ${deposit.status === 'pending' ? `
                    <button class="btn btn-sm btn-icon btn-light" onclick="approveDeposit(${deposit.id})" title="Approve">
                        <i class="ki-duotone ki-check-circle fs-3 text-success"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                    <button class="btn btn-sm btn-icon btn-light" onclick="cancelDeposit(${deposit.id})" title="Cancel">
                        <i class="ki-duotone ki-cross-circle fs-3 text-warning"><span class="path1"></span><span class="path2"></span></i>
                    </button>
                ` : ''}
                ${deposit.status !== 'completed' && deposit.status !== 'pending' ? `
                    <button class="btn btn-sm btn-icon btn-light" onclick="deleteDeposit(${deposit.id})" title="Delete">
                        <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </button>
                ` : ''}
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
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadDeposits(); }
};

window.viewDeposit = function(id) {
    fetch(`/admin/deposits/${id}`)
        .then(res => res.json())
        .then(data => {
            // Set basic info
            document.getElementById('view_deposit_ref').innerHTML = data.deposit_ref || '-';
            document.getElementById('view_status').innerHTML = getStatusBadge(data.status);
            document.getElementById('view_amount').innerHTML = data.formatted_amount || (data.currency_symbol + ' ' + (data.amount || 0));
            document.getElementById('view_deposit_date').innerHTML = data.deposit_date || '-';
            
            // Department and Depositor
            document.getElementById('view_department').innerHTML = data.department_name || '<span class="text-muted">Not assigned</span>';
            document.getElementById('view_depositor').innerHTML = data.depositor_name || '<span class="text-muted">Not assigned</span>';
            
            // Source info
            document.getElementById('view_source').innerHTML = data.source_name || data.source_type || '-';
            document.getElementById('view_source_ref').innerHTML = data.source_reference || '-';
            
            // Purpose
            let purposeHtml = data.purpose || '-';
            if (data.purpose_description && data.purpose_description !== 'N/A') {
                purposeHtml += `<br><small class="text-muted">${data.purpose_description}</small>`;
            }
            document.getElementById('view_purpose').innerHTML = purposeHtml;
            
            // Customer / Invoice Details
            document.getElementById('view_customer_id').innerHTML = data.customer_id || '-';
            document.getElementById('view_invoice_number').innerHTML = data.invoice_number || '-';
            
            // Payment method & reference
            document.getElementById('view_payment_method').innerHTML = data.payment_method_name || '-';
            document.getElementById('view_reference').innerHTML = data.reference_number || '-';
            
            // Depositor physical details
            document.getElementById('view_depositor_name').innerHTML = data.depositor_name || '-';
            let contact = [];
            if (data.depositor_phone) contact.push(data.depositor_phone);
            if (data.depositor_email) contact.push(data.depositor_email);
            document.getElementById('view_depositor_contact').innerHTML = contact.join(' | ') || '-';
            
            // Description
            document.getElementById('view_description').innerHTML = data.description || '-';
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('kt_modal_view_deposit')).show();
        })
        .catch(err => {
            console.error(err);
            window.showToast('error', 'Failed to load deposit details');
        });
};

window.approveDeposit = function(id) {
    if (confirm('Approve this deposit? The payment method balance will be updated.')) {
        fetch(`/admin/deposits/${id}/approve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadDeposits(); }
            else window.showToast('error', data.message);
        });
    }
};

window.cancelDeposit = function(id) {
    if (confirm('Cancel this deposit?')) {
        fetch(`/admin/deposits/${id}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadDeposits(); }
            else window.showToast('error', data.message);
        });
    }
};

window.deleteDeposit = function(id) {
    if (confirm('Delete this deposit? This action cannot be undone.')) {
        fetch(`/admin/deposits/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadDeposits(); }
            else window.showToast('error', data.message);
        });
    }
};

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush