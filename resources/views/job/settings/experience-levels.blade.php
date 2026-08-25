@extends('layouts.admin')

@section('title', 'Experience Levels')
@section('page_title', 'Experience Levels')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Job Settings</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Experience Levels</li>
@endsection

@section('content')
@can('view experience levels')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search experience levels..." />
            </div>
        </div>
        @can('create experience levels')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_experience_level">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Experience Level
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading experience levels...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-180px">Name</th>
                            <th class="min-w-150px">Years Range</th>
                            <th class="min-w-250px">Description</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-80px">Order</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="experienceLevelsTableBody"></tbody>
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
            <p class="text-muted">No experience levels found.</p>
        </div>
    </div>
</div>

<!-- Add Experience Level Modal -->
<div class="modal fade" id="kt_modal_add_experience_level" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Experience Level</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addExperienceLevelForm">
                    @csrf
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Level Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Entry Level, Mid Level, Senior" required />
                        <div class="text-muted fs-7 mt-1">Example: Entry Level, Mid Level, Senior, Executive</div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Minimum Years</label>
                            <input type="number" class="form-control form-control-solid" name="min_years" placeholder="0" min="0" />
                            <div class="text-muted fs-7 mt-1">Leave empty for no minimum</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Maximum Years</label>
                            <input type="number" class="form-control form-control-solid" name="max_years" placeholder="10" min="0" />
                            <div class="text-muted fs-7 mt-1">Leave empty for no maximum</div>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Brief description of this experience level (optional)"></textarea>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                        <input type="text" class="form-control form-control-solid" name="meta_title" placeholder="SEO Title (auto-generated if left blank)" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" rows="2" placeholder="SEO Description (auto-generated if left blank)"></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" value="0" placeholder="0" />
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
                        <button type="submit" class="btn btn-primary" id="addExperienceLevelBtn">
                            <span class="indicator-label">Create Experience Level</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Experience Level Modal -->
<div class="modal fade" id="kt_modal_edit_experience_level" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Experience Level</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editExperienceLevelForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="experience_level_id" id="edit_experience_level_id">
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Level Name</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Minimum Years</label>
                            <input type="number" class="form-control form-control-solid" name="min_years" id="edit_min_years" placeholder="0" min="0" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Maximum Years</label>
                            <input type="number" class="form-control form-control-solid" name="max_years" id="edit_max_years" placeholder="10" min="0" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                        <input type="text" class="form-control form-control-solid" name="meta_title" id="edit_meta_title" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" id="edit_meta_description" rows="2"></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" value="0" />
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
                        <button type="submit" class="btn btn-primary" id="editExperienceLevelBtn">
                            <span class="indicator-label">Update Experience Level</span>
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
    loadExperienceLevels();
    setupEventListeners();
});

function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadExperienceLevels();
            }, 500);
        });
    }
}

function loadExperienceLevels() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/experience-levels/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderExperienceLevelsTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load experience levels');
        });
}

function renderExperienceLevelsTable(levels) {
    const tbody = document.getElementById('experienceLevelsTableBody');
    tbody.innerHTML = '';
    
    levels.forEach(level => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${level.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(level.name)}</div>`;
        row.insertCell(2).innerHTML = level.years_range ? `<span class="badge badge-light-primary">${level.years_range}</span>` : '<span class="text-muted">-</span>';
        row.insertCell(3).innerHTML = level.description ? escapeHtml(level.description.substring(0, 100)) : '<span class="text-muted">-</span>';
        row.insertCell(4).innerHTML = level.is_active ? '<span class="badge badge-light-success">Active</span>' : '<span class="badge badge-light-danger">Inactive</span>';
        row.insertCell(5).innerHTML = level.sort_order || 0;
        row.insertCell(6).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${level.id}, ${level.is_active})" title="${level.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${level.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editExperienceLevel(${level.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteExperienceLevel(${level.id}, '${escapeHtml(level.name)}')" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadExperienceLevels(); }
};

window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this experience level?`)) {
        fetch(`/admin/experience-levels/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadExperienceLevels();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle status'));
    }
};

window.editExperienceLevel = function(id) {
    fetch(`/admin/experience-levels/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('edit_experience_level_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_min_years').value = data.min_years || '';
            document.getElementById('edit_max_years').value = data.max_years || '';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_meta_title').value = data.meta_title || '';
            document.getElementById('edit_meta_description').value = data.meta_description || '';
            document.getElementById('edit_sort_order').value = data.sort_order || 0;
            document.getElementById('edit_is_active').checked = data.is_active;
            new bootstrap.Modal(document.getElementById('kt_modal_edit_experience_level')).show();
        })
        .catch(err => window.showToast('error', 'Failed to load experience level details'));
};

window.deleteExperienceLevel = function(id, name) {
    if (confirm(`Are you sure you want to delete experience level "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/experience-levels/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadExperienceLevels();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete experience level'));
    }
};

// Add Experience Level Form
document.getElementById('addExperienceLevelForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addExperienceLevelBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    // Fix is_active checkbox
    const isActiveCheckbox = document.querySelector('#addExperienceLevelForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    fetch('/admin/experience-levels', {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_experience_level'));
            if (modal) modal.hide();
            this.reset();
            loadExperienceLevels();
        } else {
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                window.showToast('error', errorMessages);
            } else {
                window.showToast('error', data.message || 'Failed to create experience level');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to create experience level: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Edit Experience Level Form
document.getElementById('editExperienceLevelForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editExperienceLevelBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_experience_level_id').value;
    
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    const isActiveCheckbox = document.querySelector('#editExperienceLevelForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    fetch(`/admin/experience-levels/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_experience_level'));
            if (modal) modal.hide();
            loadExperienceLevels();
        } else {
            let errorMsg = data.message;
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('\n');
            }
            window.showToast('error', errorMsg);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to update experience level: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush