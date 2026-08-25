@extends('layouts.admin')

@section('title', 'Permissions List')
@section('page_title', 'Permissions List')

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
    <li class="breadcrumb-item text-muted">Permissions</li>
@endsection

@section('content')
@can('view permissions')
    <!--begin::Card-->
    <div class="card card-flush">
        <!--begin::Card header-->
        <div class="card-header mt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1 me-5">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" 
                           placeholder="Search Permissions" value="{{ request('search') }}" />
                </div>
                <!--end::Search-->
            </div>
            
            @can('create permissions')
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_permission">
                    <i class="ki-duotone ki-plus-square fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>Add Permission
                </button>
            </div>
            @endcan
        </div>
        <!--end::Card header-->
        
        <!--begin::Card body-->
        <div class="card-body pt-0">
            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading permissions...</p>
            </div>
            
            <!--begin::Table-->
            <div id="tableContainer">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="permissionsTable">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-125px">Name</th>
                            <th class="min-w-250px">Assigned to</th>
                            <th class="min-w-125px">Created Date</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="permissionsTableBody" class="fw-semibold text-gray-600">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
            <!--end::Table-->
            
            <!-- Pagination -->
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav>
                    <ul class="pagination m-0" id="pagination"></ul>
                </nav>
            </div>
            
            <!-- No Data Message -->
            <div id="noDataMessage" class="text-center py-10 d-none">
                <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <p class="text-muted">No permissions found.</p>
            </div>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Add permissions-->
    <div class="modal fade" id="kt_modal_add_permission" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Add a Permission</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="addPermissionForm">
                        @csrf
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">
                                <span class="required">Permission Name</span>
                                <span class="ms-2" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="Permission names is required to be unique.">
                                    <i class="ki-duotone ki-information fs-7">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                            </label>
                            <input class="form-control form-control-solid" placeholder="Enter a permission name (e.g., view_users)" name="permission_name" required />
                            <div class="form-text text-muted mt-2">Use underscore (_) instead of spaces. Example: manage_users, view_reports</div>
                        </div>
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="addPermissionBtn">
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

    <!--begin::Modal - Update permissions-->
    <div class="modal fade" id="kt_modal_update_permission" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Update Permission</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-9 p-6">
                        <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-stack flex-grow-1">
                            <div class="fw-semibold">
                                <div class="fs-6 text-gray-700">
                                    <strong class="me-1">Warning!</strong> By editing the permission name, you might break the system permissions functionality. Please ensure you're absolutely certain before proceeding.
                                </div>
                            </div>
                        </div>
                    </div>
                    <form id="updatePermissionForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="permission_id" id="update_permission_id">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mb-2">
                                <span class="required">Permission Name</span>
                            </label>
                            <input class="form-control form-control-solid" placeholder="Enter a permission name" name="permission_name" id="update_permission_name" required />
                            <div class="form-text text-muted mt-2">Use underscore (_) instead of spaces. Example: manage_users, view_reports</div>
                        </div>
                        <div class="text-center pt-15">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary" id="updatePermissionBtn">
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
@endcan
@endsection

@push('scripts')
<script>
    let currentPage = 1;
    let currentSearch = '';
    
    // Format permission name: replace underscores with spaces and capitalize
    function formatPermissionName(name) {
        if (!name) return '';
        return name
            .replace(/_/g, ' ')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }
    
    // Load permissions on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadPermissions();
        
        // Setup search functionality with debounce
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = this.value;
                    currentPage = 1;
                    loadPermissions();
                    // Update URL without reload
                    const url = new URL(window.location.href);
                    if (currentSearch) {
                        url.searchParams.set('search', currentSearch);
                    } else {
                        url.searchParams.delete('search');
                    }
                    window.history.pushState({}, '', url);
                }, 500);
            });
        }
        
        // Check URL for existing search param
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('search')) {
            currentSearch = urlParams.get('search');
            if (searchInput) searchInput.value = currentSearch;
            loadPermissions();
        }
    });
        
    
    // Load permissions from server with pagination
    function loadPermissions() {
        const loadingSpinner = document.getElementById('loadingSpinner');
        const tableContainer = document.getElementById('tableContainer');
        const noDataMessage = document.getElementById('noDataMessage');
        const paginationContainer = document.getElementById('paginationContainer');
        
        // Show loading spinner
        loadingSpinner.classList.remove('d-none');
        tableContainer.classList.add('d-none');
        noDataMessage.classList.add('d-none');
        paginationContainer.classList.add('d-none');
        
        let url = `{{ route("admin.permissions.data") }}?page=${currentPage}&per_page=20`;
        if (currentSearch) {
            url += `&search=${encodeURIComponent(currentSearch)}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                loadingSpinner.classList.add('d-none');
                
                if (data.data.length === 0) {
                    noDataMessage.classList.remove('d-none');
                    tableContainer.classList.add('d-none');
                    paginationContainer.classList.add('d-none');
                } else {
                    tableContainer.classList.remove('d-none');
                    renderPermissionsTable(data.data);
                    renderPagination(data);
                    paginationContainer.classList.remove('d-none');
                }
            })
            .catch(error => {
                loadingSpinner.classList.add('d-none');
                window.showToast('error', 'Failed to load permissions: ' + error.message);
                noDataMessage.classList.remove('d-none');
            });
    }
    
    // Render permissions table
    function renderPermissionsTable(permissions) {
        const tbody = document.getElementById('permissionsTableBody');
        tbody.innerHTML = '';
        
        permissions.forEach(permission => {
            const row = tbody.insertRow();
            
            // Permission name cell (formatted)
            const nameCell = row.insertCell(0);
            nameCell.innerHTML = `<span class="fw-bold">${escapeHtml(formatPermissionName(permission.name))}</span>`;
            
            // Assigned roles cell (formatted role names)
            const rolesCell = row.insertCell(1);
            if (permission.roles && permission.roles.length > 0) {
                const badges = permission.roles.map(role => 
                    `<span class="badge badge-light-primary fs-7 m-1">${escapeHtml(formatPermissionName(role))}</span>`
                ).join('');
                rolesCell.innerHTML = badges;
            } else {
                rolesCell.innerHTML = '<span class="text-muted">Not assigned</span>';
            }
            
            // Created date cell
            const dateCell = row.insertCell(2);
            dateCell.innerHTML = permission.created_at;
            
            // Actions cell
            const actionsCell = row.insertCell(3);
            actionsCell.className = 'text-end';
            actionsCell.innerHTML = `
                <button class="btn btn-icon btn-active-light-primary w-30px h-30px me-3" onclick="openEditModal(${permission.id}, '${escapeHtml(permission.name)}')">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-icon btn-active-light-primary w-30px h-30px" onclick="deletePermission(${permission.id}, '${escapeHtml(permission.name)}')">
                    <i class="ki-duotone ki-trash fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
            `;
        });
    }
    
    // Render pagination
    function renderPagination(data) {
        const paginationEl = document.getElementById('pagination');
        const paginationInfo = document.getElementById('paginationInfo');
        
        if (!paginationEl) return;
        
        paginationEl.innerHTML = '';
        
        // Show info
        paginationInfo.innerHTML = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${!data.prev_page_url ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${data.current_page - 1}); return false;">Previous</a>`;
        paginationEl.appendChild(prevLi);
        
        // Page numbers
        const startPage = Math.max(1, data.current_page - 2);
        const endPage = Math.min(data.last_page, data.current_page + 2);
        
        if (startPage > 1) {
            const firstLi = document.createElement('li');
            firstLi.className = 'page-item';
            firstLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(1); return false;">1</a>`;
            paginationEl.appendChild(firstLi);
            
            if (startPage > 2) {
                const dotsLi = document.createElement('li');
                dotsLi.className = 'page-item disabled';
                dotsLi.innerHTML = '<span class="page-link">...</span>';
                paginationEl.appendChild(dotsLi);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${i === data.current_page ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>`;
            paginationEl.appendChild(pageLi);
        }
        
        if (endPage < data.last_page) {
            if (endPage < data.last_page - 1) {
                const dotsLi = document.createElement('li');
                dotsLi.className = 'page-item disabled';
                dotsLi.innerHTML = '<span class="page-link">...</span>';
                paginationEl.appendChild(dotsLi);
            }
            
            const lastLi = document.createElement('li');
            lastLi.className = 'page-item';
            lastLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${data.last_page}); return false;">${data.last_page}</a>`;
            paginationEl.appendChild(lastLi);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${!data.next_page_url ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${data.current_page + 1}); return false;">Next</a>`;
        paginationEl.appendChild(nextLi);
    }
    
    // Change page
    window.changePage = function(page) {
        if (page === currentPage) return;
        currentPage = page;
        loadPermissions();
        // Scroll to top of table
        document.getElementById('permissionsTable')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    
    // Open edit modal
    window.openEditModal = function(id, name) {
        document.getElementById('update_permission_id').value = id;
        document.getElementById('update_permission_name').value = name;
        
        // Show formatted name in modal
        const modalTitle = document.querySelector('#kt_modal_update_permission .modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = `Update Permission: ${formatPermissionName(name)}`;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('kt_modal_update_permission'));
        modal.show();
    };
    
    // Add permission form submission
    document.getElementById('addPermissionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('addPermissionBtn');
        showButtonSpinner(submitBtn);
        
        const formData = new FormData(this);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('{{ route("admin.permissions.store") }}', {
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
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_permission'));
                modal.hide();
                document.getElementById('addPermissionForm').reset();
                currentPage = 1;
                loadPermissions();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(error => {
            window.showToast('error', 'Failed to add permission: ' + error.message);
        })
        .finally(() => {
            hideButtonSpinner(submitBtn);
        });
    });
    
    // Update permission form submission
    document.getElementById('updatePermissionForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('updatePermissionBtn');
        showButtonSpinner(submitBtn);
        
        const permissionId = document.getElementById('update_permission_id').value;
        const formData = new FormData(this);
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        
        fetch(`/admin/permissions/${permissionId}`, {
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
                const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_update_permission'));
                modal.hide();
                loadPermissions();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(error => {
            window.showToast('error', 'Failed to update permission: ' + error.message);
        })
        .finally(() => {
            hideButtonSpinner(submitBtn);
        });
    });
    
    // Delete permission
    window.deletePermission = function(id, name) {
        const formattedName = formatPermissionName(name);
        if (confirm(`Are you sure you want to delete permission "${formattedName}"? This action cannot be undone.`)) {
            window.showToast('info', `Deleting permission "${formattedName}"...`, 'Processing');
            
            fetch(`/admin/permissions/${id}`, {
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
                    loadPermissions();
                } else {
                    window.showToast('error', data.message);
                }
            })
            .catch(error => {
                window.showToast('error', 'Failed to delete permission: ' + error.message);
            });
        }
    };
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
@endpush