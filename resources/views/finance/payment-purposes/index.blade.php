@extends('layouts.admin')

@section('title', 'Payment Purposes')
@section('page_title', 'Payment Purposes')

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
    <li class="breadcrumb-item text-muted">Payment Purposes</li>
@endsection

@section('content')
@can('view payment purposes')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search purposes..." />
            </div>
        </div>
        <div class="card-toolbar">
            @can('create payment purposes')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_purpose">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Purpose
            </button>
            @endcan
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading purposes...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Name</th>
                            <th class="min-w-100px">Slug</th>
                            <th class="min-w-100px">Icon</th>
                            <th class="min-w-100px">Color</th>
                            <th class="min-w-100px">Category</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Deposits Count</th>
                            <th class="min-w-100px">Sort Order</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purposesTableBody"></tbody>
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
            <p class="text-muted">No payment purposes found.</p>
        </div>
    </div>
</div>

<!-- Add Purpose Modal -->
<div class="modal fade" id="kt_modal_add_purpose" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Payment Purpose</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPurposeForm">
                    @csrf
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Job Posting Fee" required />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Slug</label>
                        <input type="text" class="form-control form-control-solid" name="slug" placeholder="e.g., job_posting_fee" required />
                        <div class="form-text text-muted">Unique identifier, lowercase with underscores</div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Icon</label>
                            <select class="form-select form-select-solid" name="icon">
                                <option value="ki-briefcase">Briefcase</option>
                                <option value="ki-dollar">Dollar</option>
                                <option value="ki-building">Building</option>
                                <option value="ki-phone">Phone</option>
                                <option value="ki-wallet">Wallet</option>
                                <option value="ki-credit-cart">Credit Card</option>
                                <option value="ki-bitcoin">Bitcoin</option>
                                <option value="ki-abstract-26">Abstract</option>
                                <option value="ki-chart-line">Chart Line</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Color</label>
                            <select class="form-select form-select-solid" name="color">
                                <option value="primary">Primary (Blue)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="info">Info (Cyan)</option>
                                <option value="secondary">Secondary (Gray)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Category</label>
                            <select class="form-select form-select-solid" name="category" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="transfer">Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Description of this payment purpose"></textarea>
                    </div>
                    
                    <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                        <input class="form-check-input" type="checkbox" name="is_active" checked />
                        <label class="form-check-label fw-semibold">Active</label>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPurposeBtn">
                            <span class="indicator-label">Create Purpose</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Purpose Modal -->
<div class="modal fade" id="kt_modal_edit_purpose" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Payment Purpose</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPurposeForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="purpose_id" id="edit_purpose_id">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Slug</label>
                        <input type="text" class="form-control form-control-solid" name="slug" id="edit_slug" required />
                        <div class="form-text text-muted">Unique identifier, lowercase with underscores</div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Icon</label>
                            <select class="form-select form-select-solid" name="icon" id="edit_icon">
                                <option value="ki-briefcase">Briefcase</option>
                                <option value="ki-dollar">Dollar</option>
                                <option value="ki-building">Building</option>
                                <option value="ki-phone">Phone</option>
                                <option value="ki-wallet">Wallet</option>
                                <option value="ki-credit-cart">Credit Card</option>
                                <option value="ki-bitcoin">Bitcoin</option>
                                <option value="ki-abstract-26">Abstract</option>
                                <option value="ki-chart-line">Chart Line</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Color</label>
                            <select class="form-select form-select-solid" name="color" id="edit_color">
                                <option value="primary">Primary (Blue)</option>
                                <option value="success">Success (Green)</option>
                                <option value="danger">Danger (Red)</option>
                                <option value="warning">Warning (Yellow)</option>
                                <option value="info">Info (Cyan)</option>
                                <option value="secondary">Secondary (Gray)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Category</label>
                            <select class="form-select form-select-solid" name="category" id="edit_category" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="transfer">Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                        <label class="form-check-label fw-semibold">Active</label>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPurposeBtn">
                            <span class="indicator-label">Update Purpose</span>
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

document.addEventListener('DOMContentLoaded', function() {
    loadPurposes();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPurposes();
            }, 500);
        });
    }
});

function loadPurposes() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/payment-purposes/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderPurposesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load purposes');
        });
}

function renderPurposesTable(purposes) {
    const tbody = document.getElementById('purposesTableBody');
    tbody.innerHTML = '';
    
    purposes.forEach(purpose => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${purpose.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(purpose.name)}</div><div class="text-muted fs-7">${escapeHtml(purpose.slug)}</div>`;
        row.insertCell(2).innerHTML = `<span class="text-muted fs-7">${purpose.slug}</span>`;
        row.insertCell(3).innerHTML = `<i class="ki-duotone ${purpose.icon || 'ki-category'} fs-2x text-${purpose.color || 'primary'}"><span class="path1"></span><span class="path2"></span></i>`;
        row.insertCell(4).innerHTML = `<span class="badge badge-light-${purpose.color || 'secondary'}">${purpose.color || 'default'}</span>`;
        row.insertCell(5).innerHTML = `<span class="badge badge-light-${purpose.category === 'income' ? 'success' : (purpose.category === 'expense' ? 'danger' : 'info')}">${purpose.category}</span>`;
        row.insertCell(6).innerHTML = purpose.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(7).innerHTML = `<span class="fw-bold">${purpose.deposits_count}</span>`;
        row.insertCell(8).innerHTML = purpose.sort_order;
        row.insertCell(9).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="editPurpose(${purpose.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deletePurpose(${purpose.id}, '${escapeHtml(purpose.name)}', ${purpose.deposits_count})" title="Delete">
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
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === data.current_page);
    if (end < data.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < data.last_page) addPage(data.last_page, data.last_page);
    addPage(data.current_page + 1, 'Next', false, !data.next_page_url);
}

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadPurposes(); }
};

window.editPurpose = function(id) {
    fetch(`/admin/payment-purposes/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_purpose_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_slug').value = data.slug;
            document.getElementById('edit_icon').value = data.icon || '';
            document.getElementById('edit_color').value = data.color || 'primary';
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_sort_order').value = data.sort_order;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_is_active').checked = data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_purpose')).show();
        });
};

window.deletePurpose = function(id, name, depositsCount) {
    let message = `Are you sure you want to delete purpose "${name}"?`;
    if (depositsCount > 0) {
        message = `Cannot delete "${name}". It is used in ${depositsCount} deposit(s). Please reassign those deposits first.`;
        window.showToast('warning', message);
        return;
    }
    
    if (confirm(message)) {
        fetch(`/admin/payment-purposes/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadPurposes(); }
            else window.showToast('error', data.message);
        });
    }
};

document.getElementById('addPurposeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addPurposeBtn');
    window.showButtonSpinner(btn);
    
    fetch('{{ route("admin.payment-purposes.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_purpose'))?.hide();
            this.reset();
            loadPurposes();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to create purpose')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editPurposeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editPurposeBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_purpose_id').value;
    
    fetch(`/admin/payment-purposes/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_purpose'))?.hide();
            loadPurposes();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to update purpose')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush