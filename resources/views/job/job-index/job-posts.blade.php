@extends('layouts.admin')

@section('title', 'Job Posts')
@section('page_title', 'Job Posts')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Jobs</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Job Posts</li>
@endsection

@section('content')

<style>
/* ===== SEARCHABLE SELECT STYLES ===== */
.searchable-select { position: relative; }
.searchable-select-dropdown {
    display: none;
    position: fixed;
    z-index: 2000;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #e4e6ef;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
    margin-top: 2px;
    min-width: 200px;
}
.searchable-select-dropdown.show { display: block; }
.searchable-select-option {
    padding: 8px 14px;
    cursor: pointer;
    font-size: 13px;
    color: #3f4254;
}
.searchable-select-option:hover,
.searchable-select-option.active { background: #f5f8fa; }
.searchable-select-empty {
    padding: 8px 14px;
    color: #a1a5b7;
    font-size: 13px;
}

/* ===== RICH EDITOR STYLES ===== */
.rich-editor-wrapper { background: #fff; }
.rich-editor-toolbar { background: #f8f9fa !important; border-bottom: 1px solid #e5e7eb; }
.re-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 28px; padding: 0; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; cursor: pointer; color: #495057;
    flex-shrink: 0; transition: background .1s, border-color .1s;
}
.re-btn:hover  { background: #e9ecef; border-color: #adb5bd; }
.re-btn:active { background: #dee2e6; }
.re-btn.active { background: #e9ecef; border-color: #adb5bd; }
.re-btn svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.re-btn-text { width: auto; padding: 0 8px; font-size: 12px; font-weight: 600; }
.re-btn-danger:hover { background: #fff5f5; color: #dc3545; border-color: #f5c2c7; }
.re-sep { width: 1px; height: 22px; background: #dee2e6; margin: 0 2px; flex-shrink: 0; }
.re-select {
    height: 28px; font-size: 12px; padding: 0 4px; border: 1px solid #dee2e6;
    border-radius: 4px; background: #fff; color: #495057; cursor: pointer;
}
.re-color-btn { cursor: pointer; overflow: hidden; position: relative; }
.re-color-input {
    position: absolute; opacity: 0; width: 100%; height: 100%;
    top: 0; left: 0; cursor: pointer; border: none; padding: 0;
}
.re-color-swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    margin-left: 2px;
}
.rich-editor-body:empty:before {
    content: attr(data-placeholder);
    color: #adb5bd; pointer-events: none; display: block;
}
.rich-editor-body ul { list-style-type: disc !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body ol { list-style-type: decimal !important; padding-left: 1.5em !important; margin: 0.5em 0; }
.rich-editor-body li { display: list-item !important; }
.rich-editor-body h1 { font-size: 2em; font-weight: 600; margin: 0.67em 0; }
.rich-editor-body h2 { font-size: 1.5em; font-weight: 600; margin: 0.75em 0; }
.rich-editor-body h3 { font-size: 1.17em; font-weight: 600; margin: 0.83em 0; }
.rich-editor-body h4 { font-size: 1em; font-weight: 600; margin: 1em 0; }
.rich-editor-body h5 { font-size: 0.83em; font-weight: 600; margin: 1.5em 0; }
.rich-editor-body h6 { font-size: 0.67em; font-weight: 600; margin: 1.67em 0; }
.rich-editor-statusbar { font-size: 11px; font-family: monospace; color: #9ca3af; background: #f9fafb; border-top: 1px solid #e5e7eb; min-height: 24px; }
</style>

@can('view jobs')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search job posts..." />
            </div>
            <div>
                <select id="countryFilter" class="form-select form-select-solid w-120px">
                    <option value="">All Countries</option>
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        <option value="{{ $country['code'] }}">
                            {{ $country['flag'] }} {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-140px">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="expired">Expired</option>
                    <option value="featured">Featured</option>
                    <option value="urgent">Urgent</option>
                    <option value="migrated">Migrated</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <select id="posterFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Posters</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="card-body pt-0">
        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading job posts...</p>
        </div>
        
        <!-- Table Container -->
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-200px">Job Title</th>
                            <th class="min-w-150px">Company</th>
                            <th class="min-w-100px">Poster</th>
                            <th class="min-w-100px">Category</th>
                            <th class="min-w-100px">Deadline</th>
                            <th class="min-w-120px">Status</th>
                            <th class="min-w-100px">Source</th>
                            <th class="text-end min-w-160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="jobPostsTableBody"></tbody>
                </table>
            </div>
            
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5 d-none">
                <div id="paginationInfo" class="text-muted"></div>
                <nav><ul class="pagination m-0" id="pagination"></ul></nav>
            </div>
        </div>
        
        <!-- No Data Message -->
        <div id="noDataMessage" class="text-center py-10 d-none">
            <i class="ki-duotone ki-information-5 fs-2tx text-muted mb-3 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <p class="text-muted">No job posts found.</p>
        </div>
    </div>
</div>

<!-- Feature Modal -->
<div class="modal fade" id="kt_modal_feature_job" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Feature Job Post</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="featureJobForm">
                    @csrf
                    <input type="hidden" name="job_id" id="feature_job_id">
                    
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Number of Days</label>
                        <input type="number" class="form-control form-control-solid" name="days" id="feature_days" min="1" max="365" value="7" required />
                        <div class="text-muted fs-7 mt-1">The job will be featured until {{ now()->addDays(7)->format('M d, Y') }}</div>
                    </div>
                    
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
                        <div>
                            <strong>Note:</strong> The job will be featured from today until the selected number of days.
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="featureJobBtn">
                            <span class="indicator-label">Feature Job</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Job Post Modal -->
<div class="modal fade" id="kt_modal_view_job" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold" id="viewJobTitle">Job Post Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="viewJobContent">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Edit Modal -->
@include('job.job-index.edit-job-modal')

@endcan
@endsection

@push('scripts')
<script>
// ================================================================
// GLOBALS
// ================================================================
let currentPage = 1;
let currentSearch = '';
let currentCountry = '';
let currentStatus = '';
let currentPoster = '';
const searchableSelectData = {};

// ================================================================
// DOM READY
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    loadJobPosts();
    loadPosters();
    setupEventListeners();
    // Load form data with default country AU
    loadFormData('AU');
});

// ================================================================
// EVENT LISTENERS
// ================================================================
function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    let timeout;
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                loadJobPosts();
            }, 500);
        });
    }

    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadJobPosts();
    });

    document.getElementById('statusFilter')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadJobPosts();
    });

    document.getElementById('posterFilter')?.addEventListener('change', function() {
        currentPoster = this.value;
        currentPage = 1;
        loadJobPosts();
    });

    // Feature days preview
    document.getElementById('feature_days')?.addEventListener('input', function() {
        const days = parseInt(this.value) || 0;
        const date = new Date();
        date.setDate(date.getDate() + days);
        const preview = document.querySelector('#kt_modal_feature_job .text-muted.fs-7');
        if (preview) {
            preview.textContent = `The job will be featured until ${date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}`;
        }
    });

    // Country change for edit modal - reload form data
    document.getElementById('edit_job_country_code')?.addEventListener('change', function() {
        const country = this.value;
        const jobId = document.getElementById('edit_job_post_id').value;
        if (jobId) {
            // Get current selections before reload
            const currentSelections = {
                company_id: getSearchableValue('edit_company'),
                job_category_id: getSearchableValue('edit_job_category'),
                industry_id: getSearchableValue('edit_industry'),
                job_location_id: getSearchableValue('edit_job_location'),
                job_type_id: getSearchableValue('edit_job_type'),
                experience_level_id: getSearchableValue('edit_experience_level'),
                education_level_id: getSearchableValue('edit_education_level'),
                salary_range_id: getSearchableValue('edit_salary_range')
            };
            loadFormData(country, currentSelections);
        } else {
            loadFormData(country);
        }
    });
}

// ================================================================
// LOAD POSTERS
// ================================================================
function loadPosters() {
    fetch('/admin/job-posts/posters')
        .then(res => res.json())
        .then(data => {
            const posterFilter = document.getElementById('posterFilter');
            if (!posterFilter) return;
            
            // Keep the default "All Posters" option
            posterFilter.innerHTML = '<option value="">All Posters</option>';
            
            if (data.success && data.data) {
                data.data.forEach(poster => {
                    const option = document.createElement('option');
                    option.value = poster.id;
                    option.textContent = poster.name;
                    posterFilter.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Error loading posters:', err));
}

// ================================================================
// LOAD JOB POSTS
// ================================================================
function loadJobPosts() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/job-posts/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentStatus) url += `&status=${encodeURIComponent(currentStatus)}`;
    if (currentPoster) url += `&poster=${encodeURIComponent(currentPoster)}`; // Add this line
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderJobPostsTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load job posts');
            }
        });
}

// ================================================================
// FORMAT JOB SOURCE - Remove underscores and capitalize words
// ================================================================
function formatJobSource(source) {
    if (!source) return 'N/A';
    
    // Replace underscores with spaces
    let formatted = source.replace(/_/g, ' ');
    
    // Capitalize first letter of each word
    formatted = formatted.split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
    
    return formatted;
}

function renderJobPostsTable(jobPosts) {
    const tbody = document.getElementById('jobPostsTableBody');
    tbody.innerHTML = '';
    
    jobPosts.forEach(job => {
        const row = tbody.insertRow();
        
        row.insertCell(0).innerHTML = `<span class="fw-bold">${job.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div class="fw-bold">${escapeHtml(job.job_title)}</div>
            <div class="text-muted fs-8">${escapeHtml(job.slug || '')}</div>
        `;
        row.insertCell(2).innerHTML = job.company ? `<span class="fw-semibold">${escapeHtml(job.company.name)}</span>` : '<span class="text-muted">N/A</span>';
        row.insertCell(3).innerHTML = job.poster ? `<span class="fw-semibold">${escapeHtml(job.poster.name || job.poster.email || 'N/A')}</span>` : '<span class="text-muted">N/A</span>';
        row.insertCell(4).innerHTML = job.job_category ? `<span class="badge badge-light-primary">${escapeHtml(job.job_category.name)}</span>` : '<span class="text-muted">-</span>';
        row.insertCell(5).innerHTML = `
            <div class="fw-bold">${formatDateOnly(job.deadline)}</div>
            <div class="text-muted fs-8">${job.days_remaining !== null ? (job.days_remaining >= 0 ? job.days_remaining + ' days left' : 'Expired') : ''}</div>
        `;
        row.insertCell(6).innerHTML = `
            ${job.status_badge}
            ${job.is_featured ? '<span class="badge badge-light-primary">Featured</span>' : ''}
            ${job.is_urgent ? '<span class="badge badge-light-danger">Urgent</span>' : ''}
        `;
        row.insertCell(7).innerHTML = job.job_source ? formatJobSource(job.job_source) : 'N/A';
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="viewJob(${job.id})" title="View">
                    <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span></i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${job.id}, ${job.is_active})" title="${job.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${job.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="openFeatureModal(${job.id})" title="Feature">
                    <i class="ki-duotone ki-star fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editJob(${job.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteJob(${job.id}, '${escapeHtml(job.job_title)}')" title="Delete">
                    <i class="ki-duotone ki-trash fs-3 text-danger">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
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
    if (page !== currentPage && page > 0) { currentPage = page; loadJobPosts(); }
};


// ================================================================
// FEATURE MODAL
// ================================================================
window.openFeatureModal = function(id) {
    document.getElementById('feature_job_id').value = id;
    document.getElementById('feature_days').value = 7;
    const preview = document.querySelector('#kt_modal_feature_job .text-muted.fs-7');
    if (preview) {
        const date = new Date();
        date.setDate(date.getDate() + 7);
        preview.textContent = `The job will be featured until ${date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}`;
    }
    new bootstrap.Modal(document.getElementById('kt_modal_feature_job')).show();
};

document.getElementById('featureJobForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('featureJobBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    const id = document.getElementById('feature_job_id').value;
    const days = document.getElementById('feature_days').value;
    
    fetch(`/admin/job-posts/${id}/feature`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ days: days })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_feature_job'));
            if (modal) modal.hide();
            loadJobPosts();
        } else {
            if (typeof window.showToast === 'function') window.showToast('error', data.message);
        }
    })
    .catch(err => {
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to feature job post');
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

// ================================================================
// TOGGLE FUNCTIONS
// ================================================================
window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this job post?`)) {
        fetch(`/admin/job-posts/${id}/toggle-status`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 
                'Content-Type': 'application/json' 
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadJobPosts();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to toggle status');
        });
    }
};

// ================================================================
// DELETE JOB
// ================================================================
window.deleteJob = function(id, name) {
    if (confirm(`Are you sure you want to delete job post "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/job-posts/${id}`, {
            method: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}', 
                'Content-Type': 'application/json' 
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadJobPosts();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to delete job post');
        });
    }
};

// ================================================================
// RICH EDITOR FUNCTIONS
// ================================================================
function initRichEditors() {
    const editors = [
        // Main form editors
        { id: 'f_job_description_editor', container: 'f_job_description_editor_container', hidden: 'f_job_description_hidden', placeholder: 'Enter job description...', height: 220 },
        { id: 'f_responsibilities_editor', container: 'f_responsibilities_editor_container', hidden: 'f_responsibilities_hidden', placeholder: 'Enter responsibilities...', height: 180 },
        { id: 'f_qualifications_editor', container: 'f_qualifications_editor_container', hidden: 'f_qualifications_hidden', placeholder: 'Enter qualifications...', height: 160 },
        { id: 'f_skills_editor', container: 'f_skills_editor_container', hidden: 'f_skills_hidden', placeholder: 'Enter skills...', height: 120 },
        { id: 'f_application_procedure_editor', container: 'f_application_procedure_editor_container', hidden: 'f_application_procedure_hidden', placeholder: 'Enter application procedure...', height: 100 },
        
        // Edit modal editors
        { id: 'edit_description_editor', container: 'edit_description_editor_container', hidden: 'edit_description_hidden', placeholder: 'Enter job description...', height: 200 },
        { id: 'edit_responsibilities_editor', container: 'edit_responsibilities_editor_container', hidden: 'edit_responsibilities_hidden', placeholder: 'Enter responsibilities...', height: 160 },
        { id: 'edit_skills_editor', container: 'edit_skills_editor_container', hidden: 'edit_skills_hidden', placeholder: 'Enter skills...', height: 120 },
        { id: 'edit_qualifications_editor', container: 'edit_qualifications_editor_container', hidden: 'edit_qualifications_hidden', placeholder: 'Enter qualifications...', height: 160 },
        { id: 'edit_application_editor', container: 'edit_application_editor_container', hidden: 'edit_application_hidden', placeholder: 'Enter application procedure...', height: 100 }
    ];
    
    editors.forEach(editor => {
        const container = document.getElementById(editor.container);
        if (container && !document.getElementById(editor.id)) {
            container.innerHTML = buildRichEditor(editor.id, editor.id, editor.placeholder, editor.height);
        }
    });
}

function buildRichEditor(id, name, placeholder, height = 160) {
    const s = `fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
    return `
    <div class="rich-editor-wrapper border rounded overflow-hidden" data-editor-id="${id}">
        <div class="rich-editor-toolbar d-flex flex-wrap align-items-center gap-1 px-2 py-1 border-bottom bg-light">
            <button type="button" class="re-btn" onclick="reFmt('${id}','undo')" title="Undo (Ctrl+Z)">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.7"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','redo')" title="Redo (Ctrl+Y)">
                <svg viewBox="0 0 24 24" ${s}><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.7"/></svg>
            </button>
            <div class="re-sep"></div>
            <button type="button" class="re-btn" id="${id}-bold" onclick="reFmt('${id}','bold')" title="Bold (Ctrl+B)">
                <svg viewBox="0 0 24 24" ${s}><path d="M6 4h8a4 4 0 0 1 0 8H6z"/><path d="M6 12h9a4 4 0 0 1 0 8H6z"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-italic" onclick="reFmt('${id}','italic')" title="Italic (Ctrl+I)">
                <svg viewBox="0 0 24 24" ${s}><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-underline" onclick="reFmt('${id}','underline')" title="Underline (Ctrl+U)">
                <svg viewBox="0 0 24 24" ${s}><path d="M6 3v7a6 6 0 0 0 12 0V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
            </button>
            <button type="button" class="re-btn" id="${id}-strikeThrough" onclick="reFmt('${id}','strikeThrough')" title="Strikethrough">
                <svg viewBox="0 0 24 24" ${s}><line x1="4" y1="12" x2="20" y2="12"/><path d="M17.5 6.5A4.5 4 0 0 0 12 5c-2.76 0-5 1.34-5 3.5 0 1.54 1.2 2.8 3 3.5"/><path d="M6.5 17.5A4.5 4 0 0 0 12 19c2.76 0 5-1.34 5-3.5 0-1-.37-1.9-1-2.6"/></svg>
            </button>
            <div class="re-sep"></div>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h2')" title="Heading 2">H2</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h3')" title="Heading 3">H3</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','p')"  title="Paragraph">P</button>
            <div class="re-sep"></div>
            <button type="button" class="re-btn" onclick="reInsertList('${id}', false)" title="Bullet list">
                <svg viewBox="0 0 24 24" ${s}><line x1="9" y1="6" x2="20" y2="6"/><line x1="9" y1="12" x2="20" y2="12"/><line x1="9" y1="18" x2="20" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reInsertList('${id}', true)" title="Numbered list">
                <svg viewBox="0 0 24 24" ${s}><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','outdent')" title="Outdent">
                <svg viewBox="0 0 24 24" ${s}><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M7 8l-4 4 4 4"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','indent')" title="Indent">
                <svg viewBox="0 0 24 24" ${s}><line x1="21" y1="6" x2="11" y2="6"/><line x1="21" y1="12" x2="11" y2="12"/><line x1="21" y1="18" x2="11" y2="18"/><path d="M3 8l4 4-4 4"/></svg>
            </button>
            <div class="re-sep"></div>
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyLeft')"   title="Align left">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyCenter')" title="Align center">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','justifyRight')"  title="Align right">
                <svg viewBox="0 0 24 24" ${s}><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="re-sep"></div>
            <button type="button" class="re-btn" onclick="reInsertLink('${id}')" title="Insert link (Ctrl+K)">
                <svg viewBox="0 0 24 24" ${s}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reUnlink('${id}')" title="Remove link">
                <svg viewBox="0 0 24 24" ${s}><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','formatBlock','blockquote')" title="Blockquote">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
            </button>
            <div class="re-sep"></div>
            <label class="re-btn re-color-btn position-relative" title="Text color">
                <svg viewBox="0 0 24 24" ${s}><path d="M9 3H5l7 14 7-14h-4l-3 6-3-6z"/><line x1="3" y1="21" x2="21" y2="21" stroke-width="3"/></svg>
                <span class="re-color-swatch" id="${id}-fgSwatch" style="background:#000"></span>
                <input type="color" class="re-color-input" id="${id}_fgColor" value="#000000"
                    oninput="updateSwatch('${id}_fgColor','${id}-fgSwatch')"
                    onchange="reFmt('${id}','foreColor',this.value)">
            </label>
            <label class="re-btn re-color-btn position-relative" title="Highlight color">
                <svg viewBox="0 0 24 24" ${s}><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5" fill="currentColor"/></svg>
                <span class="re-color-swatch" id="${id}-bgSwatch" style="background:#ffff00"></span>
                <input type="color" class="re-color-input" id="${id}_bgColor" value="#ffff00"
                    oninput="updateSwatch('${id}_bgColor','${id}-bgSwatch')"
                    onchange="reFmt('${id}','hiliteColor',this.value)">
            </label>
            <div class="re-sep"></div>
            <select class="re-select" onchange="reFmt('${id}','fontName',this.value)" title="Font" style="max-width:90px;">
                <option value="">Font</option>
                <option value="Arial">Arial</option>
                <option value="Georgia">Georgia</option>
                <option value="Verdana">Verdana</option>
                <option value="'Times New Roman'">Times NR</option>
                <option value="'Courier New'">Mono</option>
            </select>
            <select class="re-select" onchange="reFmt('${id}','fontSize',this.value)" title="Size" style="max-width:64px;">
                <option value="">Size</option>
                <option value="1">8pt</option>
                <option value="2">10pt</option>
                <option value="3">12pt</option>
                <option value="4">14pt</option>
                <option value="5">18pt</option>
                <option value="6">24pt</option>
                <option value="7">36pt</option>
            </select>
            <div class="re-sep"></div>
            <button type="button" class="re-btn" onclick="reFmt('${id}','removeFormat')" title="Clear formatting">
                <svg viewBox="0 0 24 24" ${s}><path d="M4 7l4-4 12 12-4 4"/><path d="M14.5 2.5l7 7"/><line x1="2" y1="22" x2="22" y2="22"/><path d="M3 17l4-4"/></svg>
            </button>
            <button type="button" class="re-btn re-btn-danger" onclick="richEditorClear('${id}')" title="Clear all content">
                <svg viewBox="0 0 24 24" ${s}><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
        <div id="${id}" contenteditable="true" class="rich-editor-body p-3"
            style="min-height:${height}px;max-height:${height * 2}px;overflow-y:auto;outline:none;font-size:14px;line-height:1.7"
            data-placeholder="${placeholder}" oninput="richEditorSync('${id}'); updateStats('${id}')">
        </div>
        <div class="rich-editor-statusbar d-flex justify-content-between align-items-center px-3">
            <span></span>
            <div class="d-flex gap-3">
                <span id="${id}-words">0 words</span>
                <span id="${id}-chars">0 chars</span>
            </div>
        </div>
    </div>`;
}

function reFmt(id, cmd, val) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, val);
    richEditorSync(id);
}

function reInsertList(id, ordered) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
    richEditorSync(id);
}

function reInsertLink(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const url = prompt('Enter URL:', 'https://');
    if (!url) return;
    el.focus();
    document.execCommand('insertHTML', false, `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
    richEditorSync(id);
}

function richEditorSync(id) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id.replace('_editor', '') + '_hidden');
    if (el && hidden) hidden.value = el.innerHTML;
}

function richEditorSet(id, html) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id.replace('_editor', '') + '_hidden');
    if (el) el.innerHTML = html ?? '';
    if (hidden) hidden.value = html ?? '';
}

function richEditorClear(id) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id.replace('_editor', '') + '_hidden');
    if (el) el.innerHTML = '';
    if (hidden) hidden.value = '';
    el?.focus();
}

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return '-';
    }
}

function formatDateOnly(dateString) {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    } catch (e) {
        return '-';
    }
}

function getStatusBadge(isActive) {
    if (isActive) {
        return '<span class="badge badge-light-success">Active</span>';
    }
    return '<span class="badge badge-light-danger">Inactive</span>';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<!-- Include Edit Modal Scripts -->
@include('job.job-index.edit-job-scripts')
@include('job.job-index.view-job-modal')
@endpush