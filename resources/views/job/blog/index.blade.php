@extends('layouts.admin')

@section('title', 'Blog Posts')
@section('page_title', 'Blog Posts')

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
    <li class="breadcrumb-item text-muted">Blog Posts</li>
@endsection

@section('content')
@can('view blogs')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search blogs..." />
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
            <div>
                <select id="categoryFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        @can('create blogs')
        <div class="card-toolbar">
            <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Blog
            </a>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading blogs...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-200px">Title</th>
                            <th class="min-w-120px">Category</th>
                            <th class="min-w-120px">Country</th>
                            <th class="min-w-120px">Status</th>
                            <th class="min-w-100px">Featured</th>
                            <th class="min-w-100px">Views</th>
                            <th class="min-w-100px">Published</th>
                            <th class="text-end min-w-200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blogsTableBody"></tbody>
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
            <p class="text-muted">No blog posts found.</p>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let currentCountry = '';
let currentCategory = '';
let currentStatus = '';

document.addEventListener('DOMContentLoaded', function() {
    loadBlogs();
    loadFilters();
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
                loadBlogs();
            }, 500);
        });
    }

    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadBlogs();
        loadFilters();
    });

    document.getElementById('categoryFilter')?.addEventListener('change', function() {
        currentCategory = this.value;
        currentPage = 1;
        loadBlogs();
    });

    document.getElementById('statusFilter')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadBlogs();
    });
}

function loadBlogs() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/blogs/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentCategory) url += `&category=${encodeURIComponent(currentCategory)}`;
    if (currentStatus) url += `&status=${encodeURIComponent(currentStatus)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderBlogsTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load blogs');
            }
        });
}

function loadFilters() {
    const country = document.getElementById('countryFilter')?.value || '';
    fetch(`/admin/blogs/filters?country=${country}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const categorySelect = document.getElementById('categoryFilter');
                if (categorySelect) {
                    categorySelect.innerHTML = '<option value="">All Categories</option>';
                    data.categories.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat;
                        opt.textContent = cat;
                        categorySelect.appendChild(opt);
                    });
                }
            }
        })
        .catch(err => console.error('Failed to load filters:', err));
}

function renderBlogsTable(blogs) {
    const tbody = document.getElementById('blogsTableBody');
    tbody.innerHTML = '';
    
    blogs.forEach(blog => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${blog.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div>
                <div class="fw-bold">${escapeHtml(blog.title)}</div>
                <div class="text-muted fs-7">${escapeHtml(blog.slug)}</div>
            </div>
        `;
        row.insertCell(2).innerHTML = blog.category ? `<span class="badge badge-light-info">${escapeHtml(blog.category)}</span>` : '<span class="text-muted">-</span>';
        row.insertCell(3).innerHTML = `<span class="badge badge-light-info">${blog.country_flag} ${blog.country_name}</span>`;
        row.insertCell(4).innerHTML = blog.status_badge;
        row.insertCell(5).innerHTML = blog.featured_badge;
        row.insertCell(6).innerHTML = blog.view_count || 0;
        row.insertCell(7).innerHTML = blog.published_at ? formatDate(blog.published_at) : '<span class="text-muted">-</span>';
        row.insertCell(8).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <!-- Publish/Unpublish Button -->
                ${blog.is_published ? `
                    <button class="btn btn-sm btn-icon btn-light-warning" onclick="togglePublish(${blog.id}, false)" title="Unpublish">
                        <i class="ki-duotone ki-eye-slash fs-3">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                ` : `
                    <button class="btn btn-sm btn-icon btn-light-success" onclick="togglePublish(${blog.id}, true)" title="Publish">
                        <i class="ki-duotone ki-eye fs-3">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </button>
                `}
                <!-- Active/Inactive Button -->
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${blog.id}, ${blog.is_active})" title="${blog.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${blog.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <!-- Featured Button -->
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleFeatured(${blog.id}, ${blog.is_featured})" title="${blog.is_featured ? 'Remove Featured' : 'Make Featured'}">
                    <i class="ki-duotone ki-star fs-3 text-${blog.is_featured ? 'warning' : 'muted'}">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <!-- Edit Button -->
                <a href="/admin/blogs/${blog.id}/edit" class="btn btn-sm btn-icon btn-light" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </a>
                <!-- Delete Button -->
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteBlog(${blog.id}, '${escapeHtml(blog.title)}')" title="Delete">
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

window.changePage = function(page) {
    if (page !== currentPage && page > 0) { currentPage = page; loadBlogs(); }
};

// Publish/Unpublish Toggle
window.togglePublish = function(id, publish) {
    const action = publish ? 'publish' : 'unpublish';
    const confirmMsg = publish ? 'Are you sure you want to publish this blog?' : 'Are you sure you want to unpublish this blog?';
    
    if (confirm(confirmMsg)) {
        const url = `/admin/blogs/${id}/${action}`;
        fetch(url, {
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
                if (typeof window.showToast === 'function') {
                    window.showToast('success', data.message);
                }
                loadBlogs(); // Reload the table
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', data.message);
                }
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to toggle publish status');
            }
        });
    }
};

// Toggle Active/Inactive Status
window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this blog?`)) {
        fetch(`/admin/blogs/${id}/toggle-status`, {
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
                if (typeof window.showToast === 'function') {
                    window.showToast('success', data.message);
                }
                loadBlogs();
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', data.message);
                }
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to toggle status');
            }
        });
    }
};

// Toggle Featured
window.toggleFeatured = function(id, current) {
    const action = current ? 'unfeature' : 'feature';
    if (confirm(`Are you sure you want to ${action} this blog?`)) {
        fetch(`/admin/blogs/${id}/toggle-featured`, {
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
                if (typeof window.showToast === 'function') {
                    window.showToast('success', data.message);
                }
                loadBlogs();
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', data.message);
                }
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to toggle featured');
            }
        });
    }
};

// Delete Blog
window.deleteBlog = function(id, title) {
    if (confirm(`Are you sure you want to delete blog "${title}"?`)) {
        fetch(`/admin/blogs/${id}`, {
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
                if (typeof window.showToast === 'function') {
                    window.showToast('success', data.message);
                }
                loadBlogs();
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast('error', data.message);
                }
            }
        })
        .catch(err => {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to delete blog');
            }
        });
    }
};

function formatDate(dateString) {
    if (!dateString) return '-';
    const d = new Date(dateString);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush