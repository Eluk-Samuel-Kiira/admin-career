@extends('layouts.admin')

@section('title', 'Pages')
@section('page_title', 'Pages')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Content</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Pages</li>
@endsection

@section('content')

<style>
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

@can('view pages')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search pages..." />
            </div>
            <div>
                <select id="countryFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Countries</option>
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        <option value="{{ $country['code'] }}">
                            {{ $country['flag'] }} {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        @can('create pages')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_page">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Page
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading pages...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-180px">Title</th>
                            <th class="min-w-150px">Slug</th>
                            <th class="min-w-100px">Country</th>
                            <th class="min-w-120px">Template</th>
                            <th class="min-w-120px">Status</th>
                            <th class="min-w-100px">Order</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="pagesTableBody"></tbody>
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
            <p class="text-muted">No pages found.</p>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- ADD PAGE MODAL -->
<!-- ================================================================ -->
<div class="modal fade" id="kt_modal_add_page" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Page</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPageForm">
                    @csrf
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Title</label>
                            <input type="text" class="form-control form-control-solid" name="title" id="add_title" placeholder="Page Title" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Slug</label>
                            <input type="text" class="form-control form-control-solid" name="slug" id="add_slug" placeholder="page-slug" required />
                            <div class="text-muted fs-7 mt-1">URL-friendly name (e.g., about-us)</div>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="add_country" required>
                                <option value="">Select Country</option>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Template</label>
                            <select class="form-select form-select-solid" name="template" id="add_template">
                                <option value="default">Default</option>
                                <option value="contact">Contact</option>
                                <option value="legal">Legal</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Content</label>
                        <div id="add_content_editor_container"></div>
                        <input type="hidden" name="content" id="add_content_hidden">
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                            <input type="text" class="form-control form-control-solid" name="meta_title" id="add_meta_title" placeholder="SEO Title" />
                            <div class="text-muted fs-7 mt-1">Recommended: 50-60 characters</div>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="add_sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" id="add_meta_description" rows="2" placeholder="SEO Description"></textarea>
                        <div class="text-muted fs-7 mt-1">Recommended: 150-160 characters</div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Featured Image</label>
                        <input type="text" class="form-control form-control-solid" name="featured_image" id="add_featured_image" placeholder="Image URL or path" />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="add_is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="published_at" id="add_published_at" />
                                <label class="form-check-label fw-semibold">Published Now</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPageBtn">
                            <span class="indicator-label">Create Page</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- EDIT PAGE MODAL -->
<!-- ================================================================ -->
<div class="modal fade" id="kt_modal_edit_page" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Page</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPageForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="page_id" id="edit_page_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Title</label>
                            <input type="text" class="form-control form-control-solid" name="title" id="edit_title" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Slug</label>
                            <input type="text" class="form-control form-control-solid" name="slug" id="edit_slug" required />
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_country" required>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Template</label>
                            <select class="form-select form-select-solid" name="template" id="edit_template">
                                <option value="default">Default</option>
                                <option value="contact">Contact</option>
                                <option value="legal">Legal</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Content</label>
                        <div id="edit_content_editor_container"></div>
                        <input type="hidden" name="content" id="edit_content_hidden">
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                            <input type="text" class="form-control form-control-solid" name="meta_title" id="edit_meta_title" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" value="0" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" id="edit_meta_description" rows="2"></textarea>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Featured Image</label>
                        <input type="text" class="form-control form-control-solid" name="featured_image" id="edit_featured_image" />
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" />
                                <label class="form-check-label fw-semibold">Active</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="published_at" id="edit_published_at" />
                                <label class="form-check-label fw-semibold">Published Now</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPageBtn">
                            <span class="indicator-label">Update Page</span>
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
// ================================================================
// GLOBALS
// ================================================================
let currentPage = 1;
let currentSearch = '';
let currentCountry = '';

// ================================================================
// DOM READY
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    loadPages();
    setupEventListeners();
    initRichEditors();
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
                loadPages();
            }, 500);
        });
    }

    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadPages();
    });

    // Reset add form on modal close
    document.getElementById('kt_modal_add_page')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addPageForm')?.reset();
        richEditorClear('add_content_editor');
    });
}

// ================================================================
// LOAD PAGES TABLE
// ================================================================
function loadPages() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/pages/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderPagesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load pages');
            }
        });
}

function renderPagesTable(pages) {
    const tbody = document.getElementById('pagesTableBody');
    tbody.innerHTML = '';
    
    pages.forEach(page => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${page.id}</span>`;
        row.insertCell(1).innerHTML = `<div class="fw-bold">${escapeHtml(page.title)}</div>`;
        row.insertCell(2).innerHTML = `<code class="small">/${escapeHtml(page.slug)}</code>`;
        row.insertCell(3).innerHTML = `<span class="badge badge-light-info">${page.country_flag} ${page.country_name}</span>`;
        row.insertCell(4).innerHTML = page.template_badge;
        row.insertCell(5).innerHTML = page.status_badge;
        row.insertCell(6).innerHTML = page.sort_order || 0;
        row.insertCell(7).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${page.id}, ${page.is_active})" title="${page.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${page.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editPage(${page.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deletePage(${page.id}, '${escapeHtml(page.title)}')" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadPages(); }
};

// ================================================================
// TOGGLE FUNCTIONS
// ================================================================
window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this page?`)) {
        fetch(`/admin/pages/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadPages();
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
// EDIT PAGE - FIXED
// ================================================================
window.editPage = function(id) {
    fetch(`/admin/pages/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            // Populate form fields
            document.getElementById('edit_page_id').value = data.id;
            document.getElementById('edit_title').value = data.title || '';
            document.getElementById('edit_slug').value = data.slug || '';
            document.getElementById('edit_country').value = data.country_code || '';
            document.getElementById('edit_template').value = data.template || 'default';
            document.getElementById('edit_meta_title').value = data.meta_title || '';
            document.getElementById('edit_meta_description').value = data.meta_description || '';
            document.getElementById('edit_sort_order').value = data.sort_order || 0;
            document.getElementById('edit_featured_image').value = data.featured_image || '';
            document.getElementById('edit_is_active').checked = data.is_active || false;
            document.getElementById('edit_is_featured').checked = data.is_featured || false;
            document.getElementById('edit_published_at').checked = data.published_at !== null;
            
            // CRITICAL: Set rich editor content
            const content = data.content || '';
            const editorEl = document.getElementById('edit_content_editor');
            const hiddenEl = document.getElementById('edit_content_hidden');
            
            if (editorEl) {
                editorEl.innerHTML = content;
            }
            if (hiddenEl) {
                hiddenEl.value = content;
            }
            updateStats('edit_content_editor');
            
            // Open modal
            new bootstrap.Modal(document.getElementById('kt_modal_edit_page')).show();
        })
        .catch(err => {
            console.error('Error loading page:', err);
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load page details');
            }
        });
};

// ================================================================
// DELETE PAGE
// ================================================================
window.deletePage = function(id, title) {
    if (confirm(`Are you sure you want to delete page "${title}"? This action cannot be undone.`)) {
        fetch(`/admin/pages/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (typeof window.showToast === 'function') window.showToast('success', data.message);
                loadPages();
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message);
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') window.showToast('error', 'Failed to delete page');
        });
    }
};

// ================================================================
// RICH EDITOR FUNCTIONS
// ================================================================
function initRichEditors() {
    // Initialize Add Rich Editor
    const addContainer = document.getElementById('add_content_editor_container');
    if (addContainer) {
        addContainer.innerHTML = buildRichEditor('add_content_editor', 'content', 'Enter page content...', 300);
    }
    
    // Initialize Edit Rich Editor
    const editContainer = document.getElementById('edit_content_editor_container');
    if (editContainer) {
        editContainer.innerHTML = buildRichEditor('edit_content_editor', 'content', 'Enter page content...', 300);
    }
}

function buildRichEditor(id, name, placeholder, height = 160) {
    const s = `fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
    return `
    <div class="rich-editor-wrapper border rounded overflow-hidden" data-editor-id="${id}">

        <div class="rich-editor-toolbar d-flex flex-wrap align-items-center gap-1 px-2 py-1 border-bottom bg-light">

            <!-- History -->
            <button type="button" class="re-btn" onclick="reFmt('${id}','undo')" title="Undo">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.7"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','redo')" title="Redo">
                <svg viewBox="0 0 24 24" ${s}><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.7"/></svg>
            </button>

            <div class="re-sep"></div>

            <!-- Text styles -->
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

            <!-- Block formats -->
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h2')" title="Heading 2">H2</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h3')" title="Heading 3">H3</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','p')"  title="Paragraph">P</button>

            <div class="re-sep"></div>

            <!-- Lists -->
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

            <!-- Alignment -->
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

            <!-- Link -->
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

            <!-- Colors -->
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

            <!-- Font -->
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

            <!-- Clear -->
            <button type="button" class="re-btn" onclick="reFmt('${id}','removeFormat')" title="Clear formatting">
                <svg viewBox="0 0 24 24" ${s}><path d="M4 7l4-4 12 12-4 4"/><path d="M14.5 2.5l7 7"/><line x1="2" y1="22" x2="22" y2="22"/><path d="M3 17l4-4"/></svg>
            </button>
            <button type="button" class="re-btn re-btn-danger" onclick="richEditorClear('${id}')" title="Clear all content">
                <svg viewBox="0 0 24 24" ${s}><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>

        </div>

        <!-- Editable area -->
        <div id="${id}"
            contenteditable="true"
            class="rich-editor-body p-3"
            style="min-height:${height}px;max-height:${height * 2}px;overflow-y:auto;outline:none;font-size:14px;line-height:1.7"
            data-placeholder="${placeholder}"
            oninput="richEditorSync('${id}'); updateStats('${id}')">
        </div>

        <!-- Status bar -->
        <div class="rich-editor-statusbar d-flex justify-content-between align-items-center px-3">
            <span></span>
            <div class="d-flex gap-3">
                <span id="${id}-words">0 words</span>
                <span id="${id}-chars">0 chars</span>
            </div>
        </div>

    </div>`;
}

function reFmt(id, cmd, val = null) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, val);
    richEditorSync(id);
    updateActiveStates(id);
}

function reInsertList(id, ordered) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    const listTag = ordered ? 'OL' : 'UL';
    const sel = window.getSelection();
    if (sel && sel.rangeCount) {
        const anc = sel.getRangeAt(0).commonAncestorContainer;
        let node = anc.nodeType === 3 ? anc.parentNode : anc;
        while (node && node !== el) {
            if (node.tagName === listTag) {
                document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
                richEditorSync(id);
                return;
            }
            node = node.parentNode;
        }
    }
    document.execCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList', false, null);
    richEditorSync(id);
}

function reInsertLink(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const sel = window.getSelection();
    const txt = sel && sel.toString().trim();
    const url = prompt('Enter URL:', 'https://');
    if (!url) return;
    el.focus();
    if (txt) {
        document.execCommand('createLink', false, url);
    } else {
        document.execCommand('insertHTML', false,
            `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
    }
    richEditorSync(id);
}

function reUnlink(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.focus();
    document.execCommand('unlink', false, null);
    richEditorSync(id);
}

function richEditorSync(id) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el && hidden) {
        const content = el.innerHTML;
        hidden.value = content;
        return content;
    }
    return '';
}

function richEditorGet(id) {
    const el = document.getElementById(id);
    return el ? el.innerHTML : '';
}

function richEditorSet(id, html) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el) {
        el.innerHTML = html || '';
    }
    if (hidden) {
        hidden.value = html || '';
    }
    updateStats(id);
}

function richEditorClear(id) {
    richEditorSet(id, '');
    document.getElementById(id)?.focus();
}

function updateStats(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const text = el.innerText || '';
    const words = text.trim() ? text.trim().split(/\s+/).length : 0;
    const chars = text.length;
    const wEl = document.getElementById(id + '-words');
    const cEl = document.getElementById(id + '-chars');
    if (wEl) wEl.textContent = words + (words === 1 ? ' word' : ' words');
    if (cEl) cEl.textContent = chars + (chars === 1 ? ' char' : ' chars');
}

function updateActiveStates(id) {
    ['bold','italic','underline','strikeThrough'].forEach(cmd => {
        const btn = document.getElementById(`${id}-${cmd}`);
        if (btn) btn.classList.toggle('active', document.queryCommandState(cmd));
    });
}

function updateSwatch(inputId, swatchId) {
    const input = document.getElementById(inputId);
    const swatch = document.getElementById(swatchId);
    if (input && swatch) swatch.style.background = input.value;
}

// Keyboard shortcuts for rich editor
document.addEventListener('keydown', e => {
    const active = document.activeElement;
    if (!active || active.getAttribute('contenteditable') !== 'true') return;
    const id = active.id;
    if (!id) return;
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 'b') { e.preventDefault(); reFmt(id, 'bold'); }
        else if (e.key === 'i') { e.preventDefault(); reFmt(id, 'italic'); }
        else if (e.key === 'u') { e.preventDefault(); reFmt(id, 'underline'); }
        else if (e.key === 'z') { e.preventDefault(); reFmt(id, 'undo'); }
        else if (e.key === 'y') { e.preventDefault(); reFmt(id, 'redo'); }
        else if (e.key === 'k') { e.preventDefault(); reInsertLink(id); }
    }
});

document.addEventListener('selectionchange', () => {
    const active = document.activeElement;
    if (!active || active.getAttribute('contenteditable') !== 'true') return;
    if (active.id) updateActiveStates(active.id);
});

document.addEventListener('submit', () => {
    document.querySelectorAll('[contenteditable="true"][id]').forEach(el => {
        richEditorSync(el.id);
    });
});

// ================================================================
// ADD PAGE FORM - FIXED
// ================================================================
document.getElementById('addPageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addPageBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    
    // CRITICAL: Get content directly from editor and set hidden input
    const editorEl = document.getElementById('add_content_editor');
    const hiddenEl = document.getElementById('add_content_hidden');
    
    if (editorEl && hiddenEl) {
        hiddenEl.value = editorEl.innerHTML;
    }
    
    const formData = new FormData(this);

    // Handle checkboxes
    const isActiveCheckbox = document.querySelector('#addPageForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    const isFeaturedCheckbox = document.querySelector('#addPageForm input[name="is_featured"]');
    if (isFeaturedCheckbox) {
        formData.set('is_featured', isFeaturedCheckbox.checked ? '1' : '0');
    }
    
    const publishedCheckbox = document.querySelector('#addPageForm input[name="published_at"]');
    if (publishedCheckbox && publishedCheckbox.checked) {
        formData.set('published_at', new Date().toISOString());
    } else {
        formData.delete('published_at');
    }

    fetch('/admin/pages', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_page'));
            if (modal) modal.hide();
            this.reset();
            richEditorClear('add_content_editor');
            loadPages();
        } else {
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                if (typeof window.showToast === 'function') window.showToast('error', errorMessages);
            } else {
                if (typeof window.showToast === 'function') window.showToast('error', data.message || 'Failed to create page');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to create page: ' + err.message);
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

// ================================================================
// EDIT PAGE FORM - FIXED
// ================================================================
document.getElementById('editPageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editPageBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    const id = document.getElementById('edit_page_id').value;

    // CRITICAL: Get content directly from editor and set hidden input
    const editorEl = document.getElementById('edit_content_editor');
    const hiddenEl = document.getElementById('edit_content_hidden');
    
    if (editorEl && hiddenEl) {
        hiddenEl.value = editorEl.innerHTML;
    }

    const formData = new FormData(this);
    formData.append('_method', 'PUT');

    // Handle checkboxes
    const isActiveCheckbox = document.querySelector('#editPageForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    const isFeaturedCheckbox = document.querySelector('#editPageForm input[name="is_featured"]');
    if (isFeaturedCheckbox) {
        formData.set('is_featured', isFeaturedCheckbox.checked ? '1' : '0');
    }
    
    const publishedCheckbox = document.querySelector('#editPageForm input[name="published_at"]');
    if (publishedCheckbox && publishedCheckbox.checked) {
        formData.set('published_at', new Date().toISOString());
    } else {
        formData.delete('published_at');
    }

    fetch(`/admin/pages/${id}`, {
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
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_page'));
            if (modal) modal.hide();
            loadPages();
        } else {
            let errorMsg = data.message;
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('\n');
            }
            if (typeof window.showToast === 'function') window.showToast('error', errorMsg);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to update page: ' + err.message);
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush