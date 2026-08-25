@extends('layouts.admin')

@section('title', 'Expense Categories')
@section('page_title', 'Expense Categories')

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
    <li class="breadcrumb-item text-muted">Categories</li>
@endsection

@section('content')
@can('view expense categories')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search categories..." />
            </div>
        </div>
        @can('create expense categories')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_category">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Category
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading categories...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Name</th>
                            <th class="min-w-100px">Code</th>
                            <th class="min-w-200px">Description</th>
                            <th class="min-w-100px">Requires Approval</th>
                            <th class="min-w-100px">Requires Receipt</th>
                            <th class="min-w-120px">Monthly Budget</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody"></tbody>
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
            <p class="text-muted">No categories found.</p>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="kt_modal_add_category" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Expense Category</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addCategoryForm">
                    @csrf
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Category Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Office Supplies" required />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Code</label>
                        <input type="text" class="form-control form-control-solid" name="code" placeholder="e.g., OFS (auto-generated if left blank)" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Category description..."></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Monthly Budget</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="budget_monthly" placeholder="0.00" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Annual Budget</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="budget_annual" placeholder="0.00" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_approval" checked />
                                <span class="form-check-label fw-semibold">Requires Approval</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_receipt" checked />
                                <span class="form-check-label fw-semibold">Requires Receipt</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                        <input class="form-check-input" type="checkbox" name="is_active" checked />
                        <label class="form-check-label fw-semibold">Active</label>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addCategoryBtn">
                            <span class="indicator-label">Create Category</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="kt_modal_edit_category" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Expense Category</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editCategoryForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Category Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Code</label>
                        <input type="text" class="form-control form-control-solid" name="code" id="edit_code" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Monthly Budget</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="budget_monthly" id="edit_budget_monthly" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Annual Budget</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="budget_annual" id="edit_budget_annual" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_approval" id="edit_requires_approval" />
                                <span class="form-check-label fw-semibold">Requires Approval</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requires_receipt" id="edit_requires_receipt" />
                                <span class="form-check-label fw-semibold">Requires Receipt</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                        <label class="form-check-label fw-semibold">Active</label>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editCategoryBtn">
                            <span class="indicator-label">Update Category</span>
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
    loadCategories();
    
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadCategories();
            }, 500);
        });
    }
});

function loadCategories() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.expense-categories.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderCategoriesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load categories');
        });
}

function renderCategoriesTable(categories) {
    const tbody = document.getElementById('categoriesTableBody');
    tbody.innerHTML = '';
    
    categories.forEach(category => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${category.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(category.name)}</div>`;
        row.insertCell(2).innerHTML = `<span class="badge badge-light-primary">${category.code}</span>`;
        row.insertCell(3).innerHTML = category.description ? escapeHtml(category.description.substring(0, 50)) : '-';
        row.insertCell(4).innerHTML = category.requires_approval ? '<span class="badge badge-light-warning">Yes</span>' : '<span class="badge badge-light-secondary">No</span>';
        row.insertCell(5).innerHTML = category.requires_receipt ? '<span class="badge badge-light-info">Yes</span>' : '<span class="badge badge-light-secondary">No</span>';
        row.insertCell(6).innerHTML = category.budget_monthly ? '$' + Number(category.budget_monthly).toLocaleString() : '-';
        row.insertCell(7).innerHTML = category.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${category.id}, ${category.is_active})" title="${category.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${category.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editCategory(${category.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteCategory(${category.id}, '${escapeHtml(category.name)}')" title="Delete">
                    <i class="ki-duotone ki-trash fs-3 text-danger">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
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

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadCategories(); }
};

window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this category?`)) {
        fetch(`/admin/expense-categories/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadCategories(); }
            else window.showToast('error', data.message);
        });
    }
};

window.editCategory = function(id) {
    fetch(`/admin/expense-categories/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_category_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_code').value = data.code;
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_budget_monthly').value = data.budget_monthly || '';
            document.getElementById('edit_budget_annual').value = data.budget_annual || '';
            document.getElementById('edit_requires_approval').checked = data.requires_approval;
            document.getElementById('edit_requires_receipt').checked = data.requires_receipt;
            document.getElementById('edit_is_active').checked = data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_category')).show();
        });
};

window.deleteCategory = function(id, name) {
    if (confirm(`Are you sure you want to delete category "${name}"?`)) {
        fetch(`/admin/expense-categories/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadCategories(); }
            else window.showToast('error', data.message);
        });
    }
};

document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addCategoryBtn');
    window.showButtonSpinner(btn);
    
    fetch('{{ route("admin.expense-categories.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_category'))?.hide();
            this.reset();
            loadCategories();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to create category')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editCategoryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editCategoryBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_category_id').value;
    
    fetch(`/admin/expense-categories/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_category'))?.hide();
            loadCategories();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to update category')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush