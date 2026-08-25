@extends('layouts.admin')

@section('title', 'Roles List')
@section('page_title', 'Roles List')

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
    <li class="breadcrumb-item text-muted">Roles</li>
@endsection

@section('content')
@can('view roles')
    <!--begin::Toolbar-->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <!--begin::Search-->
        <div class="d-flex align-items-center position-relative">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" 
                   placeholder="Search Roles" />
        </div>
        <!--end::Search-->
        
        @can('create roles')
        <!--begin::Add button-->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_role">
            <i class="ki-duotone ki-plus-square fs-2"></i> Add Role
        </button>
        <!--end::Add button-->
        @endcan
    </div>
    <!--end::Toolbar-->
    
    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="text-center py-10 d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">Loading roles...</p>
    </div>
    
    <!--begin::Row-->
    <div id="rolesContainer" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
        <!-- Roles will be loaded here dynamically -->
    </div>
    <!--end::Row-->
    
    <!-- No Data Message -->
    <div id="noDataMessage" class="text-center py-10 d-none">
        <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        <p class="text-muted">No roles found.</p>
    </div>

    <!--begin::Modal - Add role-->
    <div class="modal fade" id="kt_modal_add_role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add a Role</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-lg-5 my-7">
                    <form id="addRoleForm">
                        @csrf
                        <div class="fv-row mb-10">
                            <label class="fs-5 fw-bold form-label mb-2">
                                <span class="required">Role name</span>
                            </label>
                            <input class="form-control form-control-solid" placeholder="Enter a role name" name="role_name" required />
                        </div>
                        
                        <div class="fv-row">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                                <label class="fs-5 fw-bold form-label mb-0">Role Permissions</label>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="d-flex align-items-center position-relative">
                                        <i class="ki-duotone ki-magnifier fs-5 position-absolute ms-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <input type="text" class="form-control form-control-sm form-control-solid ps-10 permission-search" data-target="permissionsTableAdd" placeholder="Search permissions" style="width: 200px;" />
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary select-all-permissions" data-target="permissionsTableAdd">Select All</button>
                                    <button type="button" class="btn btn-sm btn-light-danger deselect-all-permissions" data-target="permissionsTableAdd">Deselect All</button>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <tbody class="text-gray-600 fw-semibold" id="permissionsTableAdd">
                                        <!-- Permissions will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center text-muted py-3 d-none" id="noPermissionsAdd">No permissions match your search.</div>
                        </div>
                        
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addRoleBtn">
                                <span class="indicator-label">Submit</span>
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
    <!--end::Modal-->

    <!--begin::Modal - Update role-->
    <div class="modal fade" id="kt_modal_update_role" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Update Role</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 my-7">
                    <form id="updateRoleForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="role_id" id="update_role_id">
                        <div class="fv-row mb-10">
                            <label class="fs-5 fw-bold form-label mb-2">
                                <span class="required">Role name</span>
                            </label>
                            <input class="form-control form-control-solid" placeholder="Enter a role name" name="role_name" id="update_role_name" required />
                        </div>
                        
                        <div class="fv-row">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
                                <label class="fs-5 fw-bold form-label mb-0">Role Permissions</label>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="d-flex align-items-center position-relative">
                                        <i class="ki-duotone ki-magnifier fs-5 position-absolute ms-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <input type="text" class="form-control form-control-sm form-control-solid ps-10 permission-search" data-target="permissionsTableEdit" placeholder="Search permissions" style="width: 200px;" />
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary select-all-permissions" data-target="permissionsTableEdit">Select All</button>
                                    <button type="button" class="btn btn-sm btn-light-danger deselect-all-permissions" data-target="permissionsTableEdit">Deselect All</button>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <tbody class="text-gray-600 fw-semibold" id="permissionsTableEdit">
                                        <!-- Permissions will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center text-muted py-3 d-none" id="noPermissionsEdit">No permissions match your search.</div>
                        </div>
                        
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="updateRoleBtn">
                                <span class="indicator-label">Update</span>
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
    <!--end::Modal-->
    
    <!--begin::Modal - View Users-->
    <div class="modal fade" id="kt_modal_view_users" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Users with Role: <span id="modalRoleName"></span></h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="usersListContainer">
                        <div class="text-center py-5 d-none" id="usersLoading">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        <div id="usersList" class="list-group"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal-->
@endcan
@endsection

@push('scripts')
<script>
    let allRoles = [];
    let allPermissions = [];
    let currentSearch = '';
    
    // Format permission name
    function formatPermissionName(name) {
        if (!name) return '';
        return name
            .replace(/_/g, ' ')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }
    
    // Load roles on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPermissions();
        loadRoles();
        
        // Setup search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = this.value;
                    filterRoles();
                }, 300);
            });
        }
        
        // Setup permission search inputs (Add & Edit modals)
        document.querySelectorAll('.permission-search').forEach(input => {
            let permSearchTimeout;
            input.addEventListener('keyup', function() {
                clearTimeout(permSearchTimeout);
                const targetId = this.getAttribute('data-target');
                permSearchTimeout = setTimeout(() => {
                    filterPermissionsTable(targetId, this.value);
                }, 200);
            });
        });
        
        // Setup select all buttons (Add & Edit modals)
        document.querySelectorAll('.select-all-permissions').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                document.querySelectorAll(`#${targetId} .permission-checkbox`).forEach(cb => {
                    const label = cb.closest('label');
                    if (!label || !label.classList.contains('d-none')) {
                        cb.checked = true;
                    }
                });
            });
        });
        
        // Setup deselect all buttons (Add & Edit modals)
        document.querySelectorAll('.deselect-all-permissions').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                document.querySelectorAll(`#${targetId} .permission-checkbox`).forEach(cb => {
                    const label = cb.closest('label');
                    if (!label || !label.classList.contains('d-none')) {
                        cb.checked = false;
                    }
                });
            });
        });
        
        // Reset permission search/filter whenever a modal is opened
        const addModalEl = document.getElementById('kt_modal_add_role');
        if (addModalEl) {
            addModalEl.addEventListener('show.bs.modal', function() {
                resetPermissionSearch('permissionsTableAdd');
            });
        }
        const editModalEl = document.getElementById('kt_modal_update_role');
        if (editModalEl) {
            editModalEl.addEventListener('show.bs.modal', function() {
                resetPermissionSearch('permissionsTableEdit');
            });
        }
    });
    
    // Filter a permissions table (Add or Edit) by search term.
    // Matches against the permission name or the category name; hides
    // non-matching checkboxes and hides a whole row if nothing in it matches.
    function filterPermissionsTable(tableId, searchTerm) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const term = searchTerm.toLowerCase().trim();
        const rows = table.querySelectorAll('tr');
        let anyVisible = false;
        
        rows.forEach(row => {
            const categoryCell = row.querySelector('td.text-gray-800');
            const categoryMatches = !!term && categoryCell && categoryCell.textContent.toLowerCase().includes(term);
            
            let rowHasMatch = false;
            row.querySelectorAll('label.form-check').forEach(label => {
                const checkbox = label.querySelector('.permission-checkbox');
                const permName = checkbox ? (checkbox.getAttribute('data-permission-name') || '') : '';
                const labelText = label.textContent || '';
                const matches = !term || categoryMatches || permName.toLowerCase().includes(term) || labelText.toLowerCase().includes(term);
                
                label.classList.toggle('d-none', !matches);
                if (matches) rowHasMatch = true;
            });
            
            row.classList.toggle('d-none', !rowHasMatch);
            if (rowHasMatch) anyVisible = true;
        });
        
        const noResultsId = tableId === 'permissionsTableAdd' ? 'noPermissionsAdd' : 'noPermissionsEdit';
        const noResultsEl = document.getElementById(noResultsId);
        if (noResultsEl) {
            noResultsEl.classList.toggle('d-none', anyVisible || !term);
        }
    }
    
    // Clear the search box and reset visibility for a permissions table
    function resetPermissionSearch(tableId) {
        const searchInput = document.querySelector(`.permission-search[data-target="${tableId}"]`);
        if (searchInput) searchInput.value = '';
        filterPermissionsTable(tableId, '');
    }
    
    // Load all permissions
    function loadPermissions() {
        fetch('{{ route("admin.roles.permissions") }}')
            .then(response => response.json())
            .then(data => {
                allPermissions = data;
                renderPermissionsTables(data);
            })
            .catch(error => {
                console.error('Error loading permissions:', error);
            });
    }
    
    // Render permissions in both modals
    function renderPermissionsTables(permissions) {
        // Group permissions by category
        const grouped = {};
        permissions.forEach(perm => {
            const category = perm.name.split('_')[0];
            if (!grouped[category]) grouped[category] = [];
            grouped[category].push(perm);
        });
        
        let html = '';
        for (const [category, perms] of Object.entries(grouped)) {
            html += `
                <tr>
                    <td class="text-gray-800 fw-bold">${formatPermissionName(category)}</td>
                    <td>
                        <div class="d-flex flex-wrap">
                            ${perms.map(perm => `
                                <label class="form-check form-check-sm form-check-custom form-check-solid me-5 mb-2">
                                    <input class="form-check-input permission-checkbox" type="checkbox" value="${perm.id}" name="permissions[]" data-permission-name="${perm.name}">
                                    <span class="form-check-label">${formatPermissionName(perm.name)}</span>
                                </label>
                            `).join('')}
                        </div>
                    </td>
                </tr>
            `;
        }
        
        document.getElementById('permissionsTableAdd').innerHTML = html;
        document.getElementById('permissionsTableEdit').innerHTML = html;
    }
    
    // Load roles from server
    function loadRoles() {
        const loadingSpinner = document.getElementById('loadingSpinner');
        const rolesContainer = document.getElementById('rolesContainer');
        const noDataMessage = document.getElementById('noDataMessage');
        
        loadingSpinner.classList.remove('d-none');
        rolesContainer.classList.add('d-none');
        noDataMessage.classList.add('d-none');
        
        fetch('{{ route("admin.roles.data") }}')
            .then(response => response.json())
            .then(data => {
                loadingSpinner.classList.add('d-none');
                allRoles = data;
                renderRoles(data);
                
                if (data.length === 0) {
                    noDataMessage.classList.remove('d-none');
                } else {
                    rolesContainer.classList.remove('d-none');
                }
            })
            .catch(error => {
                loadingSpinner.classList.add('d-none');
                window.showToast('error', 'Failed to load roles: ' + error.message);
            });
    }
    
    // Render roles cards
    function renderRoles(roles) {
        const container = document.getElementById('rolesContainer');
        container.innerHTML = '';
        
        roles.forEach(role => {
            const displayPermissions = role.permissions.slice(0, 5);
            const remainingCount = role.permissions.length - 5;
            
            const card = document.createElement('div');
            card.className = 'col-md-4';
            card.setAttribute('data-role-name', role.name.toLowerCase());
            card.innerHTML = `
                <div class="card card-flush h-md-100">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>${formatPermissionName(role.name)}</h2>
                        </div>
                    </div>
                    <div class="card-body pt-1">
                        <div class="fw-bold text-gray-600 mb-5">
                            Total users with this role: <span class="text-primary fw-bold">${role.users_count}</span>
                            <button class="btn btn-sm btn-link p-0 ms-2" onclick="viewRoleUsers(${role.id}, '${escapeHtml(formatPermissionName(role.name))}')">
                                <i class="ki-duotone ki-eye fs-6"></i> View
                            </button>
                        </div>
                        <div class="d-flex flex-column text-gray-600">
                            ${displayPermissions.map(perm => `
                                <div class="d-flex align-items-center py-2">
                                    <span class="bullet bg-primary me-3"></span>
                                    ${formatPermissionName(perm)}
                                </div>
                            `).join('')}
                            ${remainingCount > 0 ? `
                                <div class="d-flex align-items-center py-2">
                                    <span class="bullet bg-primary me-3"></span>
                                    <em>and ${remainingCount} more...</em>
                                </div>
                            ` : ''}
                            ${role.permissions.length === 0 ? `
                                <div class="d-flex align-items-center py-2">
                                    <span class="bullet bg-secondary me-3"></span>
                                    <span class="text-muted">No permissions assigned</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-footer flex-wrap pt-0">
                        <button class="btn btn-light btn-active-primary my-1 me-2" onclick="viewRoleUsers(${role.id}, '${escapeHtml(formatPermissionName(role.name))}')">
                            <i class="ki-duotone ki-users fs-5"></i> View Users
                        </button>
                        ${role.name !== 'super_admin' ? `
                            <button class="btn btn-light btn-active-light-primary my-1" onclick="editRole(${role.id})">
                                <i class="ki-duotone ki-setting-3 fs-5"></i> Edit Role
                            </button>
                            <button class="btn btn-light btn-active-danger my-1 ms-2" onclick="deleteRole(${role.id}, '${escapeHtml(role.name)}')">
                                <i class="ki-duotone ki-trash fs-5"></i> Delete
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            container.appendChild(card);
        });
    }
    
    // Filter roles by search
    function filterRoles() {
        if (!currentSearch) {
            renderRoles(allRoles);
            return;
        }
        
        const filtered = allRoles.filter(role => 
            role.name.toLowerCase().includes(currentSearch.toLowerCase())
        );
        renderRoles(filtered);
        
        const noDataMessage = document.getElementById('noDataMessage');
        if (filtered.length === 0) {
            noDataMessage.classList.remove('d-none');
        } else {
            noDataMessage.classList.add('d-none');
        }
    }
    
    // View users with a specific role
    window.viewRoleUsers = function(roleId, roleName) {
        document.getElementById('modalRoleName').innerHTML = roleName;
        const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_users'));
        modal.show();
        
        const usersList = document.getElementById('usersList');
        const usersLoading = document.getElementById('usersLoading');
        
        usersList.innerHTML = '';
        usersLoading.classList.remove('d-none');
        
        fetch(`/admin/roles/${roleId}/users`)
            .then(response => response.json())
            .then(data => {
                usersLoading.classList.add('d-none');
                
                if (data.users.length === 0) {
                    usersList.innerHTML = '<div class="text-center text-muted py-3">No users assigned to this role</div>';
                } else {
                    data.users.forEach(user => {
                        usersList.innerHTML += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${escapeHtml(user.name)}</strong><br>
                                        <small class="text-muted">${escapeHtml(user.email)}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
            })
            .catch(error => {
                usersLoading.classList.add('d-none');
                usersList.innerHTML = '<div class="text-center text-danger py-3">Failed to load users</div>';
            });
    };
    
    // Edit role
    window.editRole = function(roleId) {
        fetch(`/admin/roles/${roleId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('update_role_id').value = data.id;
                document.getElementById('update_role_name').value = data.name;
                
                // Check permissions
                document.querySelectorAll('#permissionsTableEdit .permission-checkbox').forEach(checkbox => {
                    checkbox.checked = data.permissions.includes(parseInt(checkbox.value));
                });
                
                const modal = new bootstrap.Modal(document.getElementById('kt_modal_update_role'));
                modal.show();
            })
            .catch(error => {
                window.showToast('error', 'Failed to load role details');
            });
    };
    
    // Delete role
    window.deleteRole = function(roleId, roleName) {
        if (confirm(`Are you sure you want to delete role "${formatPermissionName(roleName)}"? This action cannot be undone.`)) {
            window.showToast('info', `Deleting role "${formatPermissionName(roleName)}"...`, 'Processing');
            
            fetch(`/admin/roles/${roleId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showToast('success', data.message);
                    loadRoles();
                } else {
                    window.showToast('error', data.message);
                }
            })
            .catch(error => {
                window.showToast('error', 'Failed to delete role: ' + error.message);
            });
        }
    };
    
    // Add role form submission
    document.getElementById('addRoleForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('addRoleBtn');
        showButtonSpinner(submitBtn);
        
        const permissions = [];
        document.querySelectorAll('#permissionsTableAdd .permission-checkbox:checked').forEach(cb => {
            permissions.push(cb.value);
        });
        
        const formData = new FormData(this);
        formData.append('permissions', JSON.stringify(permissions));
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('{{ route("admin.roles.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_role'));
                modal.hide();
                document.getElementById('addRoleForm').reset();
                loadRoles();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(error => {
            window.showToast('error', 'Failed to add role: ' + error.message);
        })
        .finally(() => {
            hideButtonSpinner(submitBtn);
        });
    });
    
    // Update role form submission - FIXED
    document.getElementById('updateRoleForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('updateRoleBtn');
        showButtonSpinner(submitBtn);
        
        const roleId = document.getElementById('update_role_id').value;
        const roleName = document.getElementById('update_role_name').value;
        const permissions = [];
        document.querySelectorAll('#permissionsTableEdit .permission-checkbox:checked').forEach(cb => {
            permissions.push(cb.value);
        });
        
        // Build data object
        const data = {
            role_name: roleName,
            permissions: permissions,
            _token: '{{ csrf_token() }}'
        };
        
        fetch(`/admin/roles/${roleId}`, {
            method: 'PUT', // Use PUT directly
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw data;
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_update_role'));
                modal.hide();
                loadRoles();
            } else {
                window.showToast('error', data.message || 'Failed to update role');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMessage = 'Failed to update role';
            if (error.errors) {
                errorMessage = Object.values(error.errors).flat().join('\n');
            } else if (error.message) {
                errorMessage = error.message;
            }
            window.showToast('error', errorMessage);
        })
        .finally(() => {
            hideButtonSpinner(submitBtn);
        });
    });
        
    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endpush