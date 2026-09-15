@extends('layouts.admin')

@section('title', 'Services')
@section('page_title', 'Services')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Services</li>
@endsection

@section('content')
@can('view services')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search services..." />
            </div>
            <div class="me-3">
                <select id="billingTypeFilter" class="form-select form-select-solid w-175px">
                    <option value="">All Billing Types</option>
                    <option value="one_time">One-Time</option>
                    <option value="subscription">Subscription</option>
                    <option value="free">Free</option>
                </select>
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        @can('create services')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_service">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Service
            </button>
        </div>
        @endcan
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading services...</p>
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-180px">Name</th>
                            <th class="min-w-150px">Key</th>
                            <th class="min-w-120px">Billing</th>
                            <th class="min-w-120px">Turnaround</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-80px">Order</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody"></tbody>
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
            <p class="text-muted">No services found.</p>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="kt_modal_add_service" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Service</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addServiceForm">
                    @csrf

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., CV Review, CV Rewrite, Premium Alerts" required />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Key</label>
                        <input type="text" class="form-control form-control-solid" name="key" placeholder="cv_review (auto-generated if blank)" />
                        <div class="text-muted fs-7 mt-1">Unique machine-readable identifier. Leave blank to auto-generate from name.</div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Brief description of this service (optional)"></textarea>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Billing Type</label>
                            <select class="form-select form-select-solid" name="billing_type" required>
                                <option value="">Select Billing Type</option>
                                <option value="one_time">One-Time</option>
                                <option value="subscription">Subscription</option>
                                <option value="free">Free</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Turnaround (hours)</label>
                            <input type="number" class="form-control form-control-solid" name="default_turnaround_hours" value="24" min="1" max="8760" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" value="0" min="0" />
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" checked />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addServiceBtn">
                            <span class="indicator-label">Create Service</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="kt_modal_edit_service" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Service</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editServiceForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="service_id" id="edit_service_id">

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Key</label>
                        <input type="text" class="form-control form-control-solid" name="key" id="edit_key" />
                        <div class="text-muted fs-7 mt-1">Leave blank to regenerate from name.</div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3"></textarea>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Billing Type</label>
                            <select class="form-select form-select-solid" name="billing_type" id="edit_billing_type" required>
                                <option value="">Select Billing Type</option>
                                <option value="one_time">One-Time</option>
                                <option value="subscription">Subscription</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Turnaround (hours)</label>
                            <input type="number" class="form-control form-control-solid" name="default_turnaround_hours" id="edit_default_turnaround_hours" min="1" max="8760" />
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" min="0" />
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editServiceBtn">
                            <span class="indicator-label">Update Service</span>
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
let currentBillingType = '';
let currentStatus = '';

document.addEventListener('DOMContentLoaded', function () {
    loadServices();
    setupEventListeners();
});

function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadServices();
            }, 500);
        });
    }

    document.getElementById('billingTypeFilter')?.addEventListener('change', function () {
        currentBillingType = this.value;
        currentPage = 1;
        loadServices();
    });

    document.getElementById('statusFilter')?.addEventListener('change', function () {
        currentStatus = this.value;
        currentPage = 1;
        loadServices();
    });
}

function loadServices() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');

    let url = `/admin/services/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentBillingType) url += `&billing_type=${encodeURIComponent(currentBillingType)}`;
    if (currentStatus !== '') url += `&status=${encodeURIComponent(currentStatus)}`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (!data.data || data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderServicesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            console.error(err);
            window.showToast('error', 'Failed to load services');
        });
}

function renderServicesTable(services) {
    const tbody = document.getElementById('servicesTableBody');
    tbody.innerHTML = '';

    services.forEach(service => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${service.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(service.name)}</div>`;
        row.insertCell(2).innerHTML = `<code class="text-muted">${escapeHtml(service.key)}</code>`;
        row.insertCell(3).innerHTML = service.billing_badge;
        row.insertCell(4).innerHTML = `<span class="text-gray-700">${escapeHtml(service.turnaround_label)}</span>`;
        row.insertCell(5).innerHTML = service.status_badge;
        row.insertCell(6).innerHTML = service.sort_order || 0;
        row.insertCell(7).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleServiceStatus(${service.id})" title="${service.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${service.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editService(${service.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" data-id="${service.id}" data-name="${escapeAttr(service.name)}" onclick="deleteService(this)" title="Delete">
                    <i class="ki-duotone ki-trash fs-3 text-danger">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
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

window.changePage = function (page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadServices(); }
};

window.toggleServiceStatus = function (id) {
    if (confirm('Are you sure you want to toggle this service status?')) {
        fetch(`/admin/services/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadServices();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to toggle status'));
    }
};

window.editService = function (id) {
    fetch(`/admin/services/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_service_id').value = data.id;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_key').value = data.key || '';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_billing_type').value = data.billing_type || '';
            document.getElementById('edit_default_turnaround_hours').value = data.default_turnaround_hours || 24;
            document.getElementById('edit_sort_order').value = data.sort_order || 0;
            document.getElementById('edit_is_active').checked = !!data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_service')).show();
        })
        .catch(() => window.showToast('error', 'Failed to load service details'));
};

window.deleteService = function (btn) {
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    if (confirm(`Are you sure you want to delete service "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/services/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadServices();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to delete service'));
    }
};

// ---------------------------------------------------------------
// Add Service Form
// ---------------------------------------------------------------
document.getElementById('addServiceForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('addServiceBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);

    const isActiveCheckbox = document.querySelector('#addServiceForm input[name="is_active"]');
    if (isActiveCheckbox) formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');

    fetch('/admin/services', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_service'))?.hide();
            this.reset();
            loadServices();
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Failed to create service');
            window.showToast('error', msg);
        }
    })
    .catch(err => {
        console.error(err);
        window.showToast('error', 'Failed to create service: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// ---------------------------------------------------------------
// Edit Service Form
// ---------------------------------------------------------------
document.getElementById('editServiceForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('editServiceBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_service_id').value;

    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    const isActiveCheckbox = document.querySelector('#editServiceForm input[name="is_active"]');
    if (isActiveCheckbox) formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');

    fetch(`/admin/services/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_service'))?.hide();
            loadServices();
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Failed to update service');
            window.showToast('error', msg);
        }
    })
    .catch(err => {
        console.error(err);
        window.showToast('error', 'Failed to update service: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// ---------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeAttr(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
</script>
@endpush