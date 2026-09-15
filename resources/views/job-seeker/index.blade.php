@extends('layouts.admin')

@section('title', 'Job Seekers')
@section('page_title', 'Job Seekers')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Job Seekers</li>
@endsection

@section('content')
@can('view seekers')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search seekers..." />
            </div>
            <div>
                <select id="countryFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Countries</option>
                </select>
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Status</option>
                    <option value="has_cv">Has CV</option>
                    <option value="no_cv">No CV</option>
                    <option value="has_applied">Has Applied</option>
                </select>
            </div>
        </div>
        <div class="card-toolbar">
            <span class="badge badge-light-primary fs-6 py-3 px-5" id="totalBadge">0 Total</span>
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading job seekers...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-180px">Seeker</th>
                            <th class="min-w-150px">Professional Title</th>
                            <th class="min-w-100px">Country</th>
                            <th class="min-w-80px">Experience</th>
                            <th class="min-w-100px">CV</th>
                            <th class="min-w-80px">Applied</th>
                            <th class="min-w-80px">Saved</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="seekersTableBody"></tbody>
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
            <p class="text-muted">No job seekers found.</p>
        </div>
    </div>
</div>

<!-- View Seeker Modal -->
<div class="modal fade" id="kt_modal_view_seeker" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Seeker Details</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="seekerDetailsContainer">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Applications Modal -->
<div class="modal fade" id="kt_modal_view_applications" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Job Applications</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" id="applicationsContainer">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
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
let currentCountry = '';
let currentStatus = '';

document.addEventListener('DOMContentLoaded', function() {
    loadSeekers();
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
                loadSeekers();
            }, 500);
        });
    }

    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadSeekers();
    });

    document.getElementById('statusFilter')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadSeekers();
    });
}

function loadFilters() {
    fetch('/admin/seekers/filters')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const countrySelect = document.getElementById('countryFilter');
                if (countrySelect) {
                    countrySelect.innerHTML = '<option value="">All Countries</option>';
                    data.countries.forEach(country => {
                        const opt = document.createElement('option');
                        opt.value = country.code;
                        opt.textContent = country.flag + ' ' + country.name;
                        countrySelect.appendChild(opt);
                    });
                }
            }
        })
        .catch(err => console.error('Failed to load filters:', err));
}

function loadSeekers() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/seekers/data?page=${currentPage}&per_page=10`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentStatus) url += `&status=${encodeURIComponent(currentStatus)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderSeekersTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
                document.getElementById('totalBadge').textContent = data.total + ' Total';
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load seekers');
        });
}

function renderSeekersTable(seekers) {
    const tbody = document.getElementById('seekersTableBody');
    tbody.innerHTML = '';
    
    seekers.forEach(seeker => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${seeker.id}</span>`;
        row.insertCell(1).innerHTML = `
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px me-3">
                    <img src="${seeker.avatar}" alt="${seeker.full_name}" />
                </div>
                <div>
                    <div class="fw-bold text-gray-800">${escapeHtml(seeker.full_name)}</div>
                    <div class="text-muted fs-7">${escapeHtml(seeker.email)}</div>
                    ${seeker.phone ? `<div class="text-muted fs-7"><i class="bi bi-phone me-1"></i>${escapeHtml(seeker.phone)}</div>` : ''}
                </div>
            </div>
        `;
        row.insertCell(2).innerHTML = seeker.professional_title !== 'N/A' ? `<span class="fw-semibold">${escapeHtml(seeker.professional_title)}</span>` : '<span class="text-muted">-</span>';
        row.insertCell(3).innerHTML = `<span class="badge badge-light-info">${seeker.flag} ${seeker.country}</span>`;
        row.insertCell(4).innerHTML = seeker.years_of_experience > 0 ? `${seeker.years_of_experience} yrs` : '<span class="text-muted">-</span>';
        row.insertCell(5).innerHTML = seeker.cv_badge;
        row.insertCell(6).innerHTML = seeker.applied_count > 0 ? `<span class="badge badge-light-primary">${seeker.applied_count}</span>` : '<span class="text-muted">0</span>';
        row.insertCell(7).innerHTML = seeker.saved_count > 0 ? `<span class="badge badge-light-warning">${seeker.saved_count}</span>` : '<span class="text-muted">0</span>';
        row.insertCell(8).innerHTML = seeker.status_badge;
        row.insertCell(9).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="viewSeeker(${seeker.id})" title="View Details">
                    <i class="ki-duotone ki-eye fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="viewApplications(${seeker.id})" title="View Applications">
                    <i class="ki-duotone ki-briefcase fs-3">
                        <span class="path1"></span><span class="path2"></span>
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
    if (page !== currentPage && page > 0) { currentPage = page; loadSeekers(); }
};

window.viewSeeker = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_seeker'));
    const container = document.getElementById('seekerDetailsContainer');
    container.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    modal.show();
    
    // Fetch HTML content directly
    fetch(`/admin/seekers/${id}`)
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = `<div class="alert alert-danger">Failed to load seeker details: ${err.message}</div>`;
        });
};

function renderSeekerDetails(seeker) {
    return `
        <div class="d-flex align-items-center mb-5">
            <div class="symbol symbol-80px me-4">
                <img src="${seeker.user?.avatar || '/assets/media/avatars/blank.png'}" alt="${seeker.full_name}" />
            </div>
            <div>
                <h4 class="fw-bold mb-1">${escapeHtml(seeker.full_name)}</h4>
                <div class="text-muted">${escapeHtml(seeker.email)}</div>
                ${seeker.phone ? `<div class="text-muted"><i class="bi bi-phone me-1"></i>${escapeHtml(seeker.phone)}</div>` : ''}
                <span class="badge badge-light-${seeker.is_public ? 'success' : 'secondary'}">${seeker.is_public ? 'Public' : 'Private'}</span>
            </div>
        </div>
        <hr>
        <div class="row g-5">
            <div class="col-md-6">
                <div class="fw-bold text-muted fs-7">Professional Title</div>
                <div class="fw-semibold">${escapeHtml(seeker.professional_title || 'N/A')}</div>
            </div>
            <div class="col-md-6">
                <div class="fw-bold text-muted fs-7">Country</div>
                <div class="fw-semibold">${seeker.flag} ${escapeHtml(seeker.country || 'N/A')}</div>
            </div>
            <div class="col-md-6">
                <div class="fw-bold text-muted fs-7">Years of Experience</div>
                <div class="fw-semibold">${seeker.years_of_experience || '0'} years</div>
            </div>
            <div class="col-md-6">
                <div class="fw-bold text-muted fs-7">City</div>
                <div class="fw-semibold">${escapeHtml(seeker.city || 'N/A')}</div>
            </div>
            <div class="col-12">
                <div class="fw-bold text-muted fs-7">Skills</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    ${seeker.skills ? seeker.skills.split(',').map(s => `<span class="badge badge-light-primary">${escapeHtml(s.trim())}</span>`).join('') : '<span class="text-muted">No skills listed</span>'}
                </div>
            </div>
            <div class="col-12">
                <div class="fw-bold text-muted fs-7">Professional Summary</div>
                <div class="fw-semibold">${escapeHtml(seeker.professional_summary || 'N/A')}</div>
            </div>
            <div class="col-12">
                <div class="fw-bold text-muted fs-7">Languages</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    ${seeker.languages ? JSON.parse(seeker.languages).map(l => `<span class="badge badge-light-secondary">${escapeHtml(l)}</span>`).join('') : '<span class="text-muted">No languages listed</span>'}
                </div>
            </div>
            <div class="col-12">
                <div class="fw-bold text-muted fs-7">Links</div>
                <div class="d-flex gap-3 mt-2">
                    ${seeker.linkedin_url ? `<a href="${escapeHtml(seeker.linkedin_url)}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-linkedin me-1"></i>LinkedIn</a>` : ''}
                    ${seeker.github_url ? `<a href="${escapeHtml(seeker.github_url)}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-github me-1"></i>GitHub</a>` : ''}
                    ${seeker.portfolio_url ? `<a href="${escapeHtml(seeker.portfolio_url)}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-globe me-1"></i>Portfolio</a>` : ''}
                </div>
            </div>
        </div>
    `;
}

window.viewApplications = function(id) {
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_view_applications'));
    const container = document.getElementById('applicationsContainer');
    container.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    modal.show();
    
    fetch(`/admin/seekers/${id}/applications`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.data.forEach(app => {
                    const statusColors = {
                        'applied': 'primary',
                        'interviewing': 'warning',
                        'hired': 'success',
                        'rejected': 'danger'
                    };
                    const statusLabels = {
                        'applied': 'Applied',
                        'interviewing': 'Interviewing',
                        'hired': 'Hired 🎉',
                        'rejected': 'Rejected'
                    };
                    const color = statusColors[app.status] || 'secondary';
                    const label = statusLabels[app.status] || app.status;
                    
                    html += `
                        <tr>
                            <td class="fw-bold">${escapeHtml(app.job_title)}</td>
                            <td>${escapeHtml(app.company_name)}</td>
                            <td>${escapeHtml(app.location)}</td>
                            <td>${app.applied_at ? new Date(app.applied_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'}</td>
                            <td><span class="badge badge-light-${color}">${label}</span></td>
                        </tr>
                    `;
                });
                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-10">
                        <i class="ki-duotone ki-briefcase fs-3x text-muted d-block mb-3"></i>
                        <p class="text-muted">No applications found for this seeker.</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            container.innerHTML = `<div class="alert alert-danger">Failed to load applications.</div>`;
        });
};

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush