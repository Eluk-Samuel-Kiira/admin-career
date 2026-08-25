@extends('layouts.admin')

@section('title', 'Users List')
@section('page_title', 'Users List')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">User Management</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Users</li>
@endsection

@section('content')
@can('view users')

<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search Users" />
            </div>
        </div>
        @can('create users')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add User
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading users...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-150px">User</th>
                            <th class="min-w-150px">Contact Info</th>
                            <th class="min-w-120px">Department</th>
                            <th class="min-w-100px">Role</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-120px">Last Login</th>
                            <th class="min-w-100px">Created</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody"></tbody>
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
            <p class="text-muted">No users found.</p>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="kt_modal_add_user" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add User</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addUserForm">
                    @csrf
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">First Name</label>
                            <input type="text" class="form-control form-control-solid" name="first_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                            <input type="text" class="form-control form-control-solid" name="last_name" required />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" class="form-control form-control-solid" name="email" required />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Country Code</label>
                            <select class="form-select form-select-solid" name="country_code">
                                <option value="+1">+1 (USA)</option>
                                <option value="+44">+44 (UK)</option>
                                <option value="+256" selected>+256 (Uganda)</option>
                                <option value="+254">+254 (Kenya)</option>
                                <option value="+234">+234 (Nigeria)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="tel" class="form-control form-control-solid" name="phone" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="add_department_select">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6"></div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Password</label>
                            <input type="password" class="form-control form-control-solid" name="password" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                            <input type="password" class="form-control form-control-solid" name="password_confirmation" required />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Role</label>
                        <select class="form-select form-select-solid" name="role" id="add_role_select" required>
                            <option value="">Select Role</option>
                        </select>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addUserBtn">
                            <span class="indicator-label">Create User</span>
                            <span class="indicator-progress">Please wait... 
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="kt_modal_edit_user" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit User</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editUserForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">First Name</label>
                            <input type="text" class="form-control form-control-solid" name="first_name" id="edit_first_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Last Name</label>
                            <input type="text" class="form-control form-control-solid" name="last_name" id="edit_last_name" required />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Email</label>
                        <input type="email" class="form-control form-control-solid" name="email" id="edit_email" required />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Country Code</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_country_code">
                                <option value="+1">+1 (USA)</option>
                                <option value="+44">+44 (UK)</option>
                                <option value="+256">+256 (Uganda)</option>
                                <option value="+254">+254 (Kenya)</option>
                                <option value="+234">+234 (Nigeria)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Phone</label>
                            <input type="tel" class="form-control form-control-solid" name="phone" id="edit_phone" />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Department</label>
                            <select class="form-select form-select-solid" name="department_id" id="edit_department_select">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="col-md-6"></div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">New Password</label>
                            <input type="password" class="form-control form-control-solid" name="password" placeholder="Leave blank to keep current" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Confirm Password</label>
                            <input type="password" class="form-control form-control-solid" name="password_confirmation" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Role</label>
                        <select class="form-select form-select-solid" name="role" id="edit_role_select" required>
                            <option value="">Select Role</option>
                        </select>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editUserBtn">
                            <span class="indicator-label">Update User</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="kt_modal_user_permissions" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">User Permissions</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <div class="alert alert-info d-flex align-items-center p-5 mb-7">
                    <i class="ki-duotone ki-information-5 fs-2tx me-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div>
                        <strong id="permUserName"></strong><br>
                        <span class="text-muted" id="permUserRole"></span>
                    </div>
                </div>
                
                <div class="mb-7">
                    <h4 class="fw-bold mb-3">Role Permissions (Inherited)</h4>
                    <div id="rolePermissionsList" class="d-flex flex-wrap gap-2"></div>
                </div>
                
                <hr>
                
                <div class="mb-7">
                    <h4 class="fw-bold mb-3">Direct Permissions</h4>
                    <div id="directPermissionsList" class="d-flex flex-wrap gap-2 mb-3"></div>
                    <div class="mt-4">
                        <label class="fw-semibold fs-6 mb-2">Assign New Permission</label>
                        <div class="d-flex gap-2">
                            <select id="assignPermissionSelect" class="form-select form-select-solid flex-grow-1">
                                <option value="">Select Permission</option>
                            </select>
                            <button class="btn btn-primary" id="assignPermissionBtn">
                                <span class="indicator-label">Assign</span>
                                <span class="indicator-progress">... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
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

@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let allRoles = [];
let allDepartments = [];
let currentUserId = null;
let allPermissionsList = [];

function formatRoleName(name) {
    if (!name) return '';
    return name.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function loadRoles() {
    fetch('{{ route("users.roles") }}')
        .then(res => res.json())
        .then(data => {
            allRoles = data;
            const options = data.map(r => `<option value="${r.name}">${formatRoleName(r.name)}</option>`).join('');
            document.getElementById('add_role_select').innerHTML = '<option value="">Select Role</option>' + options;
            document.getElementById('edit_role_select').innerHTML = '<option value="">Select Role</option>' + options;
        })
        .catch(err => console.error('Error loading roles:', err));
}

function loadDepartments() {
    fetch('{{ route("users.departments") }}')
        .then(res => res.json())
        .then(data => {
            // Check if data is an array
            let departments = Array.isArray(data) ? data : (data.data || []);
            
            const options = '<option value="">Select Department</option>' + 
                departments.map(d => `<option value="${d.id}">${d.name} (${d.code})</option>`).join('');
            
            document.getElementById('add_department_select').innerHTML = options;
            document.getElementById('edit_department_select').innerHTML = options;
        })
        .catch(err => {
            console.error('Error loading departments:', err);
            // Set empty options on error
            const emptyOptions = '<option value="">No departments available</option>';
            document.getElementById('add_department_select').innerHTML = emptyOptions;
            document.getElementById('edit_department_select').innerHTML = emptyOptions;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    loadRoles();
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
                loadUsers();
            }, 500);
        });
    }
});

function loadUsers() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `{{ route("users.data") }}?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderUsersTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load users');
        });
}

function renderUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${user.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px symbol-circle me-3">
                    <img src="${user.avatar || '{{ asset('assets/media/avatars/blank.png') }}'}" alt="${user.name}">
                </div>
                <div>
                    <div class="fw-bold text-gray-800">${escapeHtml(user.name)}</div>
                    <div class="text-muted fs-7">${user.uuid?.substring(0, 8)}...</div>
                </div>
            </div>
        `;
        row.insertCell(2).innerHTML = `
            <div><i class="ki-duotone ki-sms fs-5 me-1"></i> ${escapeHtml(user.email)}</div>
            ${user.phone ? `<div class="text-muted fs-7 mt-1"><i class="ki-duotone ki-call fs-5 me-1"></i> ${user.country_code || ''} ${user.phone}</div>` : ''}
        `;
        row.insertCell(3).innerHTML = user.department_name ? `<span class="badge badge-light-primary">${escapeHtml(user.department_name)}</span>` : '<span class="text-muted">Not assigned</span>';
        row.insertCell(4).innerHTML = user.roles.map(r => `<span class="badge badge-light-primary fs-7 m-1">${formatRoleName(r)}</span>`).join('') || '<span class="badge badge-light-secondary">No Role</span>';
        row.insertCell(5).innerHTML = user.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(6).innerHTML = user.last_login_at || '<span class="text-muted">Never</span>';
        row.insertCell(7).innerHTML = user.created_at;
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="openPermissionsModal(${user.id}, '${escapeHtml(user.name)}', ${JSON.stringify(user.roles).replace(/"/g, '&quot;')})" title="Permissions">
                    <i class="ki-duotone ki-shield fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleUserStatus(${user.id}, ${user.is_active})" title="${user.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${user.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editUser(${user.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                ${!user.roles.includes('super_admin') ? `
                    <button class="btn btn-sm btn-icon btn-light" onclick="deleteUser(${user.id}, '${escapeHtml(user.name)}')" title="Delete">
                        <i class="ki-duotone ki-trash fs-3 text-danger">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                    </button>
                ` : `
                    <button class="btn btn-sm btn-icon btn-light" disabled title="Super Admin cannot be deleted">
                        <i class="ki-duotone ki-shield fs-3 text-muted">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                `}
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
    if (page !== currentPage && page > 0) {
        currentPage = page;
        loadUsers();
        document.getElementById('usersTable')?.scrollIntoView({ behavior: 'smooth' });
    }
};

window.editUser = function(id) {
    fetch(`/admin/users/${id}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                window.showToast('error', data.message);
                return;
            }
            document.getElementById('edit_user_id').value = data.id;
            document.getElementById('edit_first_name').value = data.first_name || '';
            document.getElementById('edit_last_name').value = data.last_name || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_phone').value = data.phone || '';
            document.getElementById('edit_country_code').value = data.country_code || '+256';
            if (document.getElementById('edit_department_select')) {
                document.getElementById('edit_department_select').value = data.department_id || '';
            }
            if (document.getElementById('edit_role_select')) {
                document.getElementById('edit_role_select').value = data.role || '';
            }
            new bootstrap.Modal(document.getElementById('kt_modal_edit_user')).show();
        })
        .catch(err => {
            console.error('Error:', err);
            window.showToast('error', 'Failed to load user details');
        });
};

window.toggleUserStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        fetch(`/admin/users/${id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadUsers(); }
            else window.showToast('error', data.message);
        });
    }
};

window.deleteUser = function(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/users/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.success) { window.showToast('success', data.message); loadUsers(); }
            else window.showToast('error', data.message);
        });
    }
};

window.openPermissionsModal = function(userId, userName, userRoles) {
    currentUserId = userId;
    document.getElementById('permUserName').innerHTML = userName;
    document.getElementById('permUserRole').innerHTML = `Roles: ${userRoles.map(r => formatRoleName(r)).join(', ') || 'No roles'}`;
    
    fetch(`/admin/users/${userId}/permissions`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return window.showToast('error', data.message);
            
            document.getElementById('rolePermissionsList').innerHTML = data.role_permissions.length ? data.role_permissions.map(p => `<span class="badge badge-light-info fs-7 m-1">${formatRoleName(p)}</span>`).join('') : '<span class="text-muted">No inherited permissions</span>';
            
            document.getElementById('directPermissionsList').innerHTML = data.direct_permissions.length ? data.direct_permissions.map(p => `<span class="badge badge-light-success fs-7 m-1">${formatRoleName(p)} <i class="ki-duotone ki-cross-circle ms-2 cursor-pointer" style="cursor:pointer" onclick="revokePermission('${p}')"></i></span>`).join('') : '<span class="text-muted">No direct permissions</span>';
            
            allPermissionsList = data.all_permissions;
            const select = document.getElementById('assignPermissionSelect');
            const existingPerms = [...data.direct_permissions, ...data.role_permissions];
            select.innerHTML = '<option value="">Select Permission</option>' + data.all_permissions.filter(p => !existingPerms.includes(p.name)).map(p => `<option value="${p.name}">${formatRoleName(p.name)}</option>`).join('');
            
            document.getElementById('assignPermissionBtn').onclick = () => assignPermission();
            
            new bootstrap.Modal(document.getElementById('kt_modal_user_permissions')).show();
        });
};

window.assignPermission = function() {
    const permission = document.getElementById('assignPermissionSelect').value;
    if (!permission) return window.showToast('warning', 'Please select a permission');
    
    const btn = document.getElementById('assignPermissionBtn');
    window.showButtonSpinner(btn);
    
    fetch(`/admin/users/${currentUserId}/assign-permission`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ permission })
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_user_permissions'))?.hide();
            loadUsers();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(() => window.showToast('error', 'Failed to assign permission')).finally(() => window.hideButtonSpinner(btn));
};

window.revokePermission = function(permission) {
    if (!confirm(`Revoke "${formatRoleName(permission)}" from this user?`)) return;
    
    fetch(`/admin/users/${currentUserId}/revoke-permission`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ permission })
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_user_permissions'))?.hide();
            loadUsers();
        } else {
            window.showToast('error', data.message);
        }
    });
};

document.getElementById('addUserForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addUserBtn');
    window.showButtonSpinner(btn);
    
    fetch('{{ route("users.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_user'))?.hide();
            this.reset();
            loadUsers();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to create user')).finally(() => window.hideButtonSpinner(btn));
});

document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editUserBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_user_id').value;
    
    fetch(`/admin/users/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: new FormData(this)
    }).then(res => res.json()).then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_user'))?.hide();
            loadUsers();
        } else {
            window.showToast('error', data.message);
        }
    }).catch(err => window.showToast('error', 'Failed to update user')).finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush