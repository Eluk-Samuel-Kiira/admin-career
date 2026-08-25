@extends('layouts.admin')

@section('title', 'Currencies')
@section('page_title', 'Currencies')

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
    <li class="breadcrumb-item text-muted">Currencies</li>
@endsection

@section('content')
@can('view currencies')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Currencies" />
            </div>
        </div>
        <div class="card-toolbar">
            @can('create currencies')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_currency">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i> Add Currency
            </button>
            @endcan
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading currencies...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-100px">Code</th>
                            <th class="min-w-150px">Name</th>
                            <th class="min-w-80px">Symbol</th>
                            <th class="min-w-100px">Rate (USD)</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-100px">Default</th>
                            <th class="min-w-120px">Created</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="currenciesTableBody"></tbody>
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
            <p class="text-muted">No currencies found.</p>
        </div>
    </div>
</div>

<!-- Add Currency Modal -->
<div class="modal fade" id="kt_modal_add_currency" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Currency</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addCurrencyForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" placeholder="USD" maxlength="3" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Symbol</label>
                            <input type="text" class="form-control form-control-solid" name="symbol" placeholder="$" maxlength="5" required />
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Currency Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" placeholder="US Dollar" required />
                    </div>
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Decimal Places</label>
                            <select class="form-select form-select-solid" name="decimal_places">
                                <option value="0">0 (e.g., JPY)</option>
                                <option value="1">1 (e.g., Kuwaiti Dinar)</option>
                                <option value="2" selected>2 (e.g., USD, EUR)</option>
                                <option value="3">3 (e.g., Iraqi Dinar)</option>
                                <option value="4">4 (e.g., Bitcoin)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Exchange Rate (1 USD = ?)</label>
                            <input type="number" step="0.000001" class="form-control form-control-solid" name="exchange_rate_to_usd" value="1" required />
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
                        <button type="submit" class="btn btn-primary" id="addCurrencyBtn">
                            <span class="indicator-label">Create Currency</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Currency Modal -->
<div class="modal fade" id="kt_modal_edit_currency" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Currency</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editCurrencyForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="currency_id" id="edit_currency_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Currency Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" id="edit_code" maxlength="3" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Symbol</label>
                            <input type="text" class="form-control form-control-solid" name="symbol" id="edit_symbol" maxlength="5" required />
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Currency Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                    </div>
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Decimal Places</label>
                            <select class="form-select form-select-solid" name="decimal_places" id="edit_decimal_places">
                                <option value="0">0 (e.g., JPY)</option>
                                <option value="1">1 (e.g., Kuwaiti Dinar)</option>
                                <option value="2">2 (e.g., USD, EUR)</option>
                                <option value="3">3 (e.g., Iraqi Dinar)</option>
                                <option value="4">4 (e.g., Bitcoin)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Exchange Rate (1 USD = ?)</label>
                            <input type="number" step="0.000001" class="form-control form-control-solid" name="exchange_rate_to_usd" id="edit_exchange_rate" required />
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
                        <button type="submit" class="btn btn-primary" id="editCurrencyBtn">
                            <span class="indicator-label">Update Currency</span>
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

function formatDate(date) {
    if (!date) return 'N/A';
    return date;
}

document.addEventListener('DOMContentLoaded', function() {
    loadCurrencies();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCurrencies();
                const url = new URL(window.location.href);
                currentSearch ? url.searchParams.set('search', currentSearch) : url.searchParams.delete('search');
                window.history.pushState({}, '', url);
            }, 500);
        });
    }
});

function loadCurrencies() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.currencies.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url).then(res => res.json()).then(data => {
        spinner.classList.add('d-none');
        if (data.data.length === 0) {
            noData.classList.remove('d-none');
        } else {
            table.classList.remove('d-none');
            renderCurrenciesTable(data.data);
            renderPagination(data);
            pagination.classList.remove('d-none');
        }
    }).catch(err => {
        spinner.classList.add('d-none');
        window.showToast('error', 'Failed to load currencies');
    });
}

function renderCurrenciesTable(currencies) {
    const tbody = document.getElementById('currenciesTableBody');
    tbody.innerHTML = '';
    
    currencies.forEach(currency => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${currency.id}</span>`;
        row.insertCell(1).innerHTML = `<span class="badge badge-light-primary fs-7">${currency.code}</span>`;
        row.insertCell(2).innerHTML = currency.name;
        row.insertCell(3).innerHTML = `<span class="fw-bold fs-4">${currency.symbol}</span>`;
        row.insertCell(4).innerHTML = `1 USD = ${currency.exchange_rate_to_usd}`;
        row.insertCell(5).innerHTML = currency.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(6).innerHTML = currency.is_default ? '<span class="badge badge-light-warning">Default</span>' : '<span class="badge badge-light-secondary">No</span>';
        row.insertCell(7).innerHTML = currency.created_at;
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleCurrencyStatus(${currency.id}, ${currency.is_active})" title="${currency.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${currency.is_active ? 'disconnect' : 'check'} fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editCurrency(${currency.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
                ${!currency.is_default ? `
                    <button class="btn btn-sm btn-icon btn-light" onclick="deleteCurrency(${currency.id}, '${currency.code}')" title="Delete">
                        <i class="ki-duotone ki-trash fs-3 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </button>
                ` : '<span class="text-muted" title="Default currency cannot be deleted"><i class="ki-duotone ki-shield fs-3"></i></span>'}
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
    if (page !== currentPage && page > 0) { currentPage = page; loadCurrencies(); }
};

window.toggleCurrencyStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this currency?`)) {
        fetch(`/admin/currencies/${id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadCurrencies(); }
            else window.showToast('error', data.message);
        });
    }
};

window.editCurrency = function(id) {
    fetch(`/admin/currencies/${id}`).then(res => res.json()).then(data => {
        document.getElementById('edit_currency_id').value = data.id;
        document.getElementById('edit_code').value = data.code;
        document.getElementById('edit_symbol').value = data.symbol;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_decimal_places').value = data.decimal_places;
        document.getElementById('edit_exchange_rate').value = data.exchange_rate_to_usd;
        document.getElementById('edit_is_active').checked = data.is_active;
        document.getElementById('edit_is_default').checked = data.is_default;
        new bootstrap.Modal(document.getElementById('kt_modal_edit_currency')).show();
    });
};

window.deleteCurrency = function(id, code) {
    if (confirm(`Are you sure you want to delete currency "${code}"? This action cannot be undone.`)) {
        fetch(`/admin/currencies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadCurrencies(); }
            else window.showToast('error', data.message);
        });
    }
};

document.getElementById('addCurrencyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addCurrencyBtn');
    window.showButtonSpinner(btn);
    fetch('{{ route("admin.currencies.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_currency'))?.hide();
            this.reset();
            loadCurrencies();
        } else window.showToast('error', data.message);
    }).catch(err => window.showToast('error', 'Failed to create currency')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editCurrencyForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editCurrencyBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_currency_id').value;
    fetch(`/admin/currencies/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_currency'))?.hide();
            loadCurrencies();
        } else window.showToast('error', data.message);
    }).catch(err => window.showToast('error', 'Failed to update currency')).finally(() => window.hideButtonSpinner(btn));
});
</script>
@endpush