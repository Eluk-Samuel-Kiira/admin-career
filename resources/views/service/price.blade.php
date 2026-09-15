@extends('layouts.admin')

@section('title', 'Service Prices')
@section('page_title', 'Service Prices')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Services</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Prices</li>
@endsection

@section('content')
@can('view service prices')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search prices..." />
            </div>
            <div class="me-3">
                <select id="serviceFilter" class="form-select form-select-solid w-200px">
                    <option value="">All Services</option>
                </select>
            </div>
            <div class="me-3">
                <select id="countryFilter" class="form-select form-select-solid w-175px">
                    <option value="">All Countries</option>
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
        @can('create service prices')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_price">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Price
            </button>
        </div>
        @endcan
    </div>

    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading prices...</p>
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-180px">Service</th>
                            <th class="min-w-150px">Country</th>
                            <th class="min-w-120px">Price</th>
                            <th class="min-w-100px">Interval</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pricesTableBody"></tbody>
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
            <p class="text-muted">No service prices found.</p>
        </div>
    </div>
</div>

{{-- Add Price Modal --}}
<div class="modal fade" id="kt_modal_add_price" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Service Price</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPriceForm">
                    @csrf

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Service</label>
                        <select class="form-select form-select-solid" name="service_id" id="add_service_id" required>
                            <option value="">Select Service</option>
                        </select>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="add_country_code" required>
                                <option value="">Select Country</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="add_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="amount" id="add_amount" placeholder="19.99" required />
                            <div class="text-muted fs-7 mt-1">Decimal amount (e.g., 19.99). Stored as cents.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Interval</label>
                            <select class="form-select form-select-solid" name="interval" id="add_interval">
                                <option value="">None (One-Time / Free)</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </select>
                            <div class="text-muted fs-7 mt-1">Required for subscription services.</div>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked />
                            <label class="form-check-label fw-semibold">Active</label>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPriceBtn">
                            <span class="indicator-label">Create Price</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Edit Price Modal --}}
<div class="modal fade" id="kt_modal_edit_price" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Service Price</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPriceForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="price_id" id="edit_price_id">

                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Service</label>
                        <select class="form-select form-select-solid" name="service_id" id="edit_service_id" required>
                            <option value="">Select Service</option>
                        </select>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_country_code" required>
                                <option value="">Select Country</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency</label>
                            <select class="form-select form-select-solid" name="currency_id" id="edit_currency_id" required>
                                <option value="">Select Currency</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Amount</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="amount" id="edit_amount" required />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Interval</label>
                            <select class="form-select form-select-solid" name="interval" id="edit_interval">
                                <option value="">None (One-Time / Free)</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                            <label class="form-check-label fw-semibold">Active</label>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPriceBtn">
                            <span class="indicator-label">Update Price</span>
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
let currentServiceId = '';
let currentCountryCode = '';
let currentStatus = '';

let servicesList = [];
let countriesList = [];
let currenciesList = [];

document.addEventListener('DOMContentLoaded', async function () {
    await loadDropdownData();
    loadPrices();
    setupEventListeners();
});

async function loadDropdownData() {
    try {
        const [servicesRes, countriesRes, currenciesRes] = await Promise.all([
            fetch('/admin/service-prices/services', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch('/admin/service-prices/countries', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch('/admin/service-prices/currencies', { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
        ]);

        servicesList   = servicesRes.services || [];
        countriesList  = countriesRes.countries || [];
        currenciesList = currenciesRes.currencies || [];

        populateServiceSelects();
        populateCountrySelects();
        populateCurrencySelects();
    } catch (err) {
        console.error('Failed to load dropdown data', err);
        window.showToast('error', 'Failed to load dropdown data');
    }
}

function populateServiceSelects() {
    const options = servicesList.map(s =>
        `<option value="${s.id}">${escapeHtml(s.name)}</option>`
    ).join('');

    const filter = document.getElementById('serviceFilter');
    if (filter) filter.innerHTML = '<option value="">All Services</option>' + options;

    ['add_service_id', 'edit_service_id'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<option value="">Select Service</option>' + options;
    });
}

function populateCountrySelects() {
    const options = countriesList.map(c =>
        `<option value="${c.code}">${c.flag || '🌍'} ${escapeHtml(c.name)}</option>`
    ).join('');

    const filter = document.getElementById('countryFilter');
    if (filter) filter.innerHTML = '<option value="">All Countries</option>' + options;

    ['add_country_code', 'edit_country_code'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<option value="">Select Country</option>' + options;
    });
}

function populateCurrencySelects() {
    const options = currenciesList.map(c =>
        `<option value="${c.id}">${escapeHtml(c.code)} — ${escapeHtml(c.name)}</option>`
    ).join('');

    ['add_currency_id', 'edit_currency_id'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<option value="">Select Currency</option>' + options;
    });
}

function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadPrices();
            }, 500);
        });
    }

    document.getElementById('serviceFilter')?.addEventListener('change', function () {
        currentServiceId = this.value;
        currentPage = 1;
        loadPrices();
    });

    document.getElementById('countryFilter')?.addEventListener('change', function () {
        currentCountryCode = this.value;
        currentPage = 1;
        loadPrices();
    });

    document.getElementById('statusFilter')?.addEventListener('change', function () {
        currentStatus = this.value;
        currentPage = 1;
        loadPrices();
    });
}

function loadPrices() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');

    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');

    let url = `/admin/service-prices/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentServiceId) url += `&service_id=${encodeURIComponent(currentServiceId)}`;
    if (currentCountryCode) url += `&country_code=${encodeURIComponent(currentCountryCode)}`;
    if (currentStatus !== '') url += `&status=${encodeURIComponent(currentStatus)}`;

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (!data.data || data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderPricesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            console.error(err);
            window.showToast('error', 'Failed to load prices');
        });
}

function renderPricesTable(prices) {
    const tbody = document.getElementById('pricesTableBody');
    tbody.innerHTML = '';

    prices.forEach(price => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${price.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(price.service_name)}</div>`;
        row.insertCell(2).innerHTML = price.country_label;
        row.insertCell(3).innerHTML = `<span class="fw-bold text-primary">${escapeHtml(price.formatted_price)}</span>`;
        row.insertCell(4).innerHTML = `<span class="text-muted">${escapeHtml(price.interval_label)}</span>`;
        row.insertCell(5).innerHTML = price.status_badge;
        row.insertCell(6).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="togglePriceStatus(${price.id})" title="${price.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${price.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editPrice(${price.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" data-id="${price.id}" data-name="${escapeAttr(price.service_name)}" onclick="deletePrice(this)" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadPrices(); }
};

window.togglePriceStatus = function (id) {
    if (confirm('Are you sure you want to toggle this price status?')) {
        fetch(`/admin/service-prices/${id}/toggle-status`, {
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
                loadPrices();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to toggle status'));
    }
};

window.editPrice = function (id) {
    fetch(`/admin/service-prices/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_price_id').value         = data.id;
            document.getElementById('edit_service_id').value       = data.service_id;
            document.getElementById('edit_country_code').value     = data.country_code;
            document.getElementById('edit_currency_id').value      = data.currency_id;
            document.getElementById('edit_amount').value           = data.amount;
            document.getElementById('edit_interval').value         = data.interval || '';
            document.getElementById('edit_is_active').checked      = !!data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_price')).show();
        })
        .catch(() => window.showToast('error', 'Failed to load price details'));
};

window.deletePrice = function (btn) {
    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    if (confirm(`Are you sure you want to delete this price for "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/service-prices/${id}`, {
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
                loadPrices();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(() => window.showToast('error', 'Failed to delete price'));
    }
};

// Add Price Form
document.getElementById('addPriceForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('addPriceBtn');
    window.showButtonSpinner(btn);

    const formData = new FormData(this);
    const cb = document.querySelector('#addPriceForm input[name="is_active"]');
    if (cb) formData.set('is_active', cb.checked ? '1' : '0');

    fetch('/admin/service-prices', {
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
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_price'))?.hide();
            this.reset();
            loadPrices();
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Failed to create price');
            window.showToast('error', msg);
        }
    })
    .catch(err => {
        console.error(err);
        window.showToast('error', 'Failed to create price: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Edit Price Form
document.getElementById('editPriceForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('editPriceBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_price_id').value;

    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    const cb = document.querySelector('#editPriceForm input[name="is_active"]');
    if (cb) formData.set('is_active', cb.checked ? '1' : '0');

    fetch(`/admin/service-prices/${id}`, {
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
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_price'))?.hide();
            loadPrices();
        } else {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Failed to update price');
            window.showToast('error', msg);
        }
    })
    .catch(err => {
        console.error(err);
        window.showToast('error', 'Failed to update price: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Helpers
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

function applyCurrencyStep(currencyId, amountInputId) {
    const currency = currenciesList.find(c => String(c.id) === String(currencyId));
    const input = document.getElementById(amountInputId);
    if (!input || !currency) return;
    input.step = (currency.decimal_places === 0) ? '1' : '0.01';
    input.placeholder = (currency.decimal_places === 0) ? '95000' : '19.99';
}

document.getElementById('add_currency_id')?.addEventListener('change', function () {
    applyCurrencyStep(this.value, 'add_amount');
});
document.getElementById('edit_currency_id')?.addEventListener('change', function () {
    applyCurrencyStep(this.value, 'edit_amount');
});
</script>
@endpush