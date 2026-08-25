@extends('layouts.admin')

@section('title', 'Departments')
@section('page_title', 'Departments')

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
    <li class="breadcrumb-item text-muted">Departments</li>
@endsection

@section('content')
@can('view departments')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search departments..." />
            </div>
        </div>
        <div class="card-toolbar">
            @can('create departments')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_department">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Department
            </button>
            @endcan
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading departments...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">Department</th>
                            <th class="min-w-100px">Code</th>
                            <th class="min-w-100px">Head of Department</th>
                            <th class="min-w-100px">Users</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-100px">Sort Order</th>
                            <th class="min-w-100px">Contact</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departmentsTableBody"></tbody>
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
            <p class="text-muted">No departments found.</p>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="kt_modal_add_department" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Department</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addDepartmentForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Department Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., IT and Systems Administration" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" placeholder="e.g., IT" maxlength="10" required />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Icon</label>
                            <select class="form-select form-select-solid" name="icon">
                                <option value="ki-computer">Computer</option>
                                <option value="ki-users">Users</option>
                                <option value="ki-chart-line">Chart Line</option>
                                <option value="ki-chart-pie">Chart Pie</option>
                                <option value="ki-home">Home</option>
                                <option value="ki-briefcase">Briefcase</option>
                                <option value="ki-clock">Clock</option>
                                <option value="ki-people">People</option>
                                <option value="ki-dollar">Dollar</option>
                                <option value="ki-headphones">Headphones</option>
                                <option value="ki-user-search">User Search</option>
                                <option value="ki-document">Document</option>
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
                            <label class="fw-semibold fs-6 mb-2">Head of Department</label>
                            <select class="form-select form-select-solid" name="head_of_department_id" id="add_head_id">
                                <option value="">Select Head of Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" name="email" placeholder="department@lafab.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="text" class="form-control form-control-solid" name="phone" placeholder="+256 XXX XXX XXX" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Department description..."></textarea>
                    </div>
                    
                    <div class="form-check form-switch form-check-custom form-check-solid mb-7">
                        <input class="form-check-input" type="checkbox" name="is_active" checked />
                        <label class="form-check-label fw-semibold">Active</label>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addDepartmentBtn">
                            <span class="indicator-label">Create Department</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="kt_modal_edit_department" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Department</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editDepartmentForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="department_id" id="edit_department_id">
                    <div class="row mb-7">
                        <div class="col-md-8">
                            <label class="required fw-semibold fs-6 mb-2">Department Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                        </div>
                        <div class="col-md-4">
                            <label class="required fw-semibold fs-6 mb-2">Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" id="edit_code" maxlength="10" required />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Icon</label>
                            <select class="form-select form-select-solid" name="icon" id="edit_icon">
                                <option value="ki-computer">Computer</option>
                                <option value="ki-users">Users</option>
                                <option value="ki-chart-line">Chart Line</option>
                                <option value="ki-chart-pie">Chart Pie</option>
                                <option value="ki-home">Home</option>
                                <option value="ki-briefcase">Briefcase</option>
                                <option value="ki-clock">Clock</option>
                                <option value="ki-people">People</option>
                                <option value="ki-dollar">Dollar</option>
                                <option value="ki-headphones">Headphones</option>
                                <option value="ki-user-search">User Search</option>
                                <option value="ki-document">Document</option>
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
                            <label class="fw-semibold fs-6 mb-2">Head of Department</label>
                            <select class="form-select form-select-solid" name="head_of_department_id" id="edit_head_id">
                                <option value="">Select Head of Department</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Email</label>
                            <input type="email" class="form-control form-control-solid" name="email" id="edit_email" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="text" class="form-control form-control-solid" name="phone" id="edit_phone" />
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
                        <button type="submit" class="btn btn-primary" id="editDepartmentBtn">
                            <span class="indicator-label">Update Department</span>
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
                loadDepartments();
            }, 500);
        });
    }
});

function loadUsers() {
    fetch('{{ route("admin.departments.users") }}')
        .then(res => res.json())
        .then(data => {
            const options = '<option value="">Select Head of Department</option>' + 
                data.map(u => `<option value="${u.id}">${u.name} (${u.email})</option>`).join('');
            document.getElementById('add_head_id').innerHTML = options;
            document.getElementById('edit_head_id').innerHTML = options;
        });
}

function loadDepartments() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("admin.departments.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderDepartmentsTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load departments');
        });
}

function renderDepartmentsTable(departments) {
    const tbody = document.getElementById('departmentsTableBody');
    tbody.innerHTML = '';
    
    departments.forEach(dept => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${dept.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px me-3">
                    <span class="symbol-label bg-light-${dept.color}">
                        <i class="ki-duotone ${dept.icon || 'ki-building'} fs-2 text-${dept.color}">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold">${escapeHtml(dept.name)}</div>
                    <div class="text-muted fs-7">${dept.description ? escapeHtml(dept.description.substring(0, 50)) : ''}</div>
                </div>
            </div>
        `;
        row.insertCell(2).innerHTML = `<span class="badge badge-light-primary">${dept.code}</span>`;
        row.insertCell(3).innerHTML = dept.head_of_department ? `<div class="fw-bold">${escapeHtml(dept.head_of_department.name)}</div><div class="text-muted fs-7">${escapeHtml(dept.head_of_department.email)}</div>` : '<span class="text-muted">Not assigned</span>';
        row.insertCell(4).innerHTML = `<span class="badge badge-light-info">${dept.user_count || 0} Users</span>`;
        row.insertCell(5).innerHTML = dept.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(6).innerHTML = dept.sort_order;
        row.insertCell(7).innerHTML = `
            <div>${dept.email ? '<i class="ki-duotone ki-sms fs-6 me-1"></i> ' + escapeHtml(dept.email) : ''}</div>
            <div class="text-muted fs-7">${dept.phone ? escapeHtml(dept.phone) : ''}</div>
        `;
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${dept.id}, ${dept.is_active})" title="${dept.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${dept.is_active ? 'disconnect' : 'check'} fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editDepartment(${dept.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteDepartment(${dept.id}, '${escapeHtml(dept.name)}', ${dept.user_count || 0})" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadDepartments(); }
};

window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this department?`)) {
        fetch(`/admin/departments/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadDepartments(); }
            else window.showToast('error', data.message);
        });
    }
};

window.editDepartment = function(id) {
    fetch(`/admin/departments/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_department_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_code').value = data.code;
            document.getElementById('edit_icon').value = data.icon || '';
            document.getElementById('edit_color').value = data.color || 'primary';
            document.getElementById('edit_head_id').value = data.head_of_department_id || '';
            document.getElementById('edit_sort_order').value = data.sort_order;
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_phone').value = data.phone || '';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_is_active').checked = data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_department')).show();
        });
};

window.deleteDepartment = function(id, name, userCount) {
    if (userCount > 0) {
        window.showToast('warning', `Cannot delete "${name}". It has ${userCount} user(s) assigned.`);
        return;
    }
    
    if (confirm(`Are you sure you want to delete department "${name}"?`)) {
        fetch(`/admin/departments/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadDepartments(); }
            else window.showToast('error', data.message);
        });
    }
};

document.getElementById('addDepartmentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addDepartmentBtn');
    window.showButtonSpinner(btn);
    
    fetch('{{ route("admin.departments.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_department'))?.hide();
            this.reset();
            loadDepartments();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to create department')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editDepartmentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editDepartmentBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_department_id').value;
    
    fetch(`/admin/departments/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_department'))?.hide();
            loadDepartments();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to update department')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush