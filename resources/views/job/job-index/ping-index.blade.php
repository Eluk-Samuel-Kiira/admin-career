@extends('layouts.admin')

@section('title', 'Sitemap & SEO Dashboard')
@section('page_title', 'Sitemap & SEO Dashboard')

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
    <li class="breadcrumb-item text-muted">Sitemap</li>
@endsection

@section('content')
<div class="row g-6 g-xl-9">
    <!-- Total Jobs -->
    <div class="col-xxl-3 col-md-6">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="d-flex flex-column flex-grow-1 me-2">
                        <span class="text-gray-600 fw-semibold fs-6">Total Jobs</span>
                        <span class="fw-bold fs-2x text-gray-900" id="totalJobs">-</span>
                    </div>
                    <div class="symbol symbol-45px symbol-circle bg-light-primary">
                        <i class="ki-duotone ki-briefcase fs-2x text-primary">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-muted fs-7" id="newJobsThisWeek">0 new this week</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pinged Jobs -->
    <div class="col-xxl-3 col-md-6">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="d-flex flex-column flex-grow-1 me-2">
                        <span class="text-gray-600 fw-semibold fs-6">Pinged Jobs</span>
                        <span class="fw-bold fs-2x text-gray-900" id="pingedJobs">-</span>
                    </div>
                    <div class="symbol symbol-45px symbol-circle bg-light-success">
                        <i class="ki-duotone ki-check-circle fs-2x text-success">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-success" id="pingProgress" style="width: 0%"></div>
                    </div>
                    <span class="text-muted fs-7" id="pingPercentage">0% pinged</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Indexed Jobs -->
    <div class="col-xxl-3 col-md-6">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="d-flex flex-column flex-grow-1 me-2">
                        <span class="text-gray-600 fw-semibold fs-6">Indexed Jobs</span>
                        <span class="fw-bold fs-2x text-gray-900" id="indexedJobs">-</span>
                    </div>
                    <div class="symbol symbol-45px symbol-circle bg-light-info">
                        <i class="ki-duotone ki-google fs-2x text-info">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-info" id="indexProgress" style="width: 0%"></div>
                    </div>
                    <span class="text-muted fs-7" id="indexPercentage">0% indexed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Unpinged Jobs -->
    <div class="col-xxl-3 col-md-6">
        <div class="card card-flush h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="d-flex flex-column flex-grow-1 me-2">
                        <span class="text-gray-600 fw-semibold fs-6">Unpinged Jobs</span>
                        <span class="fw-bold fs-2x text-gray-900" id="unpingedJobs">-</span>
                    </div>
                    <div class="symbol symbol-45px symbol-circle bg-light-danger">
                        <i class="ki-duotone ki-alarm fs-2x text-danger">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="progress h-6px">
                        <div class="progress-bar bg-danger" id="unpingedProgress" style="width: 0%"></div>
                    </div>
                    <span class="text-muted fs-7" id="unpingedPercentage">0% unpinged</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Per Country Statistics -->
<div class="card card-flush mt-6">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="fw-bold">Per Country Statistics</h3>
        </div>
        <div class="card-toolbar">
            <button class="btn btn-sm btn-primary" id="refreshStats">
                <i class="ki-duotone ki-arrows-circle fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Refresh
            </button>
            <button class="btn btn-sm btn-success ms-2" id="generateSitemapBtn">
                <i class="ki-duotone ki-file-up fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Generate Sitemap
            </button>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>Country</th>
                        <th>Total Jobs</th>
                        <th>Pinged</th>
                        <th>Unpinged</th>
                        <th>Indexed</th>
                        <th>Unindexed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="countryStatsBody">
                    <tr><td colspan="7" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Jobs List -->
<div class="card card-flush mt-6">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="fw-bold">Job Posts</h3>
        </div>
        <div class="card-toolbar">
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-150px" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="pinged">Pinged</option>
                    <option value="unpinged">Unpinged</option>
                    <option value="indexed">Indexed</option>
                    <option value="unindexed">Unindexed</option>
                </select>
                <select class="form-select form-select-sm w-120px" id="countryFilter">
                    <option value="all">All Countries</option>
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        <option value="{{ $country['code'] }}">
                            {{ $country['flag'] }} {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>
                <div class="position-relative">
                    <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4 top-50 translate-middle-y">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <input type="text" class="form-control form-control-sm w-200px ps-12" id="searchJobs" placeholder="Search jobs..." />
                </div>
            </div>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <button class="btn btn-sm btn-primary me-2" id="pingSelectedBtn" disabled>
                    <i class="ki-duotone ki-send fs-2 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Ping Selected
                </button>
                <button class="btn btn-sm btn-info" id="markIndexedSelectedBtn" disabled>
                    <i class="ki-duotone ki-check-circle fs-2 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Index Jobs
                </button>
            </div>
            <span class="text-muted fs-7" id="selectedCount">0 selected</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-20px">
                            <input type="checkbox" class="form-check-input" id="selectAll" />
                        </th>
                        <th class="min-w-150px">Job Title</th>
                        <th class="min-w-120px">Company</th>
                        <th class="min-w-80px">Country</th>
                        <th class="min-w-100px">Status</th>
                        <th class="min-w-120px">Last Pinged</th>
                        <th class="min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody id="jobsTableBody">
                    <tr><td colspan="7" class="text-center py-5">Loading jobs...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-5">
            <div id="paginationInfo" class="text-muted"></div>
            <nav><ul class="pagination m-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Hidden modals for confirmation -->
<div class="modal fade" id="confirmPingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Ping</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="pingConfirmMessage">Are you sure you want to ping selected jobs to search engines?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPingBtn">Yes, Ping</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentCountry = 'all';
let currentStatus = 'all';
let currentSearch = '';
let selectedJobs = new Set();

document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    loadCountryStats();
    loadJobs();
    setupEventListeners();
});

function setupEventListeners() {
    // Country filter
    document.getElementById('countryFilter').addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadJobs();
        loadCountryStats();
    });

    // Status filter
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadJobs();
    });

    // Search
    let timeout;
    document.getElementById('searchJobs').addEventListener('keyup', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadJobs();
        }, 500);
    });

    // Select all
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.job-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            if (this.checked) {
                selectedJobs.add(parseInt(cb.value));
            } else {
                selectedJobs.delete(parseInt(cb.value));
            }
        });
        updateSelectedCount();
    });

    // Ping selected
    document.getElementById('pingSelectedBtn').addEventListener('click', function() {
        if (selectedJobs.size === 0) return;
        document.getElementById('pingConfirmMessage').textContent = 
            `Are you sure you want to ping ${selectedJobs.size} job(s) to search engines?`;
        new bootstrap.Modal(document.getElementById('confirmPingModal')).show();
    });

    document.getElementById('confirmPingBtn').addEventListener('click', function() {
        pingJobs(Array.from(selectedJobs));
    });

    // Index Jobs selected
    document.getElementById('markIndexedSelectedBtn').addEventListener('click', function() {
        if (selectedJobs.size === 0) return;
        if (confirm(`Submit ${selectedJobs.size} job(s) for google indexing?`)) {
            markIndexed(Array.from(selectedJobs));
        }
    });

    // Refresh stats
    document.getElementById('refreshStats').addEventListener('click', function() {
        loadStatistics();
        loadCountryStats();
        loadJobs();
        window.showToast('success', 'Statistics refreshed');
    });

    // Generate sitemap
    document.getElementById('generateSitemapBtn').addEventListener('click', function() {
        generateSitemap();
    });
}

function loadStatistics() {
    fetch('/admin/sitemap/statistics')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                const total = stats.total_jobs || 0;
                const pinged = stats.pinged_jobs || 0;
                const unpinged = stats.unpinged_jobs || 0;
                const indexed = stats.indexed_jobs || 0;
                
                // Calculate percentages
                const pingPercent = total > 0 ? (pinged / total * 100) : 0;
                const unpingedPercent = total > 0 ? (unpinged / total * 100) : 0;
                const indexPercent = total > 0 ? (indexed / total * 100) : 0;
                
                // Update main stats
                document.getElementById('totalJobs').textContent = total;
                document.getElementById('pingedJobs').textContent = pinged;
                document.getElementById('unpingedJobs').textContent = unpinged;
                document.getElementById('indexedJobs').textContent = indexed;
                
                // Update ping progress (green)
                document.getElementById('pingProgress').style.width = pingPercent + '%';
                document.getElementById('pingPercentage').textContent = pingPercent.toFixed(1) + '% pinged';
                
                // Update unpinged progress (red)
                document.getElementById('unpingedProgress').style.width = unpingedPercent + '%';
                document.getElementById('unpingedPercentage').textContent = unpingedPercent.toFixed(1) + '% unpinged';
                
                // Update index progress (blue/info)
                document.getElementById('indexProgress').style.width = indexPercent + '%';
                document.getElementById('indexPercentage').textContent = indexPercent.toFixed(1) + '% indexed';
                
                // Update new jobs this week
                document.getElementById('newJobsThisWeek').textContent = 
                    `${stats.new_jobs_last_7_days || 0} new this week`;
            }
        })
        .catch(err => console.error('Error loading statistics:', err));
}

function loadCountryStats() {
    const url = `/admin/sitemap/statistics?country=all`;
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const countries = data.stats.countries;
                const tbody = document.getElementById('countryStatsBody');
                tbody.innerHTML = '';
                
                    const countryNames = {};
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        countryNames['{{ $country['code'] }}'] = '{{ $country['flag'] }} {{ $country['name'] }}';
                    @endforeach
                
                Object.keys(countries).forEach(code => {
                    const stat = countries[code];
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td><span class="fw-bold">${countryNames[code] || code}</span></td>
                        <td>${stat.total}</td>
                        <td><span class="badge badge-light-success">${stat.pinged}</span></td>
                        <td><span class="badge badge-light-danger">${stat.unpinged}</span></td>
                        <td><span class="badge badge-light-info">${stat.indexed}</span></td>
                        <td><span class="badge badge-light-warning">${stat.unindexed}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="pingCountry('${code}')">
                                <i class="ki-duotone ki-send fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Ping All
                            </button>
                            <button class="btn btn-sm btn-success ms-1" onclick="generateCountrySitemap('${code}')">
                                <i class="ki-duotone ki-file-up fs-2 me-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Sitemap
                            </button>
                        </td>
                    `;
                });
            }
        })
        .catch(err => console.error('Error loading country stats:', err));
}

function loadJobs() {
    let url = `/admin/sitemap/jobs?page=${currentPage}&per_page=20`;
    if (currentCountry !== 'all') url += `&country=${currentCountry}`;
    if (currentStatus !== 'all') url += `&status=${currentStatus}`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderJobsTable(data.data);
                renderPagination(data.pagination);
            }
        })
        .catch(err => console.error('Error loading jobs:', err));
}

function renderJobsTable(jobs) {
    const tbody = document.getElementById('jobsTableBody');
    tbody.innerHTML = '';
    
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No jobs found</td></tr>';
        return;
    }
    
    jobs.forEach(job => {
        const row = tbody.insertRow();
        const checked = selectedJobs.has(job.id) ? 'checked' : '';
        // Determine ping status from last_pinged_at
        const isPinged = job.last_pinged_at !== null;
        const isIndexed = job.is_indexed || false;
        const pingStatus = isPinged ? 'Pinged' : 'Unpinged';
        const indexStatus = isIndexed ? 'Indexed' : 'Unindexed';
        const pingBadge = isPinged ? 'success' : 'danger';
        const indexBadge = isIndexed ? 'info' : 'warning';
        const lastPinged = job.last_pinged_at ? new Date(job.last_pinged_at).toLocaleString() : 'Never';
        
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input job-checkbox" value="${job.id}" ${checked} />
            </td>
            <td>
                <div class="fw-bold">${escapeHtml(job.job_title)}</div>
                <div class="text-muted fs-7">${escapeHtml(job.slug || '')}</div>
            </td>
            <td>${job.company ? escapeHtml(job.company.name) : '-'}</td>
            <td><span class="badge badge-light-secondary">${job.country_code || '-'}</span></td>
            <td>
                <span class="badge badge-light-${pingBadge}">${pingStatus}</span>
                <span class="badge badge-light-${indexBadge} ms-1">${indexStatus}</span>
            </td>
            <td>${lastPinged}</td>
            <td>
                <button class="btn btn-sm btn-icon btn-light" onclick="pingSingleJob(${job.id})" title="Ping to Search Engines">
                    <i class="ki-duotone ki-send fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light ms-1" onclick="markIndexedSingle(${job.id})" title="Mark as Indexed">
                    <i class="ki-duotone ki-check-circle fs-2 text-info">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </td>
        `;
        
        // Add event listener to checkbox
        const checkbox = row.querySelector('.job-checkbox');
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedJobs.add(parseInt(this.value));
            } else {
                selectedJobs.delete(parseInt(this.value));
                document.getElementById('selectAll').checked = false;
            }
            updateSelectedCount();
        });
    });
    
    // Update select all
    const allCheckboxes = document.querySelectorAll('.job-checkbox');
    const allChecked = allCheckboxes.length > 0 && Array.from(allCheckboxes).every(cb => cb.checked);
    document.getElementById('selectAll').checked = allChecked;
}

function renderPagination(pagination) {
    const el = document.getElementById('pagination');
    const info = document.getElementById('paginationInfo');
    if (!el) return;
    
    el.innerHTML = '';
    info.innerHTML = `Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total} entries`;
    
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
    
    addPage(pagination.current_page - 1, 'Previous', false, !pagination.current_page > 1);
    
    let start = Math.max(1, pagination.current_page - 2);
    let end = Math.min(pagination.last_page, pagination.current_page + 2);
    
    if (start > 1) addPage(1, '1');
    if (start > 2) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    for (let i = start; i <= end; i++) addPage(i, i, i === pagination.current_page);
    if (end < pagination.last_page - 1) el.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
    if (end < pagination.last_page) addPage(pagination.last_page, pagination.last_page);
    addPage(pagination.current_page + 1, 'Next', false, pagination.current_page >= pagination.last_page);
}

function changePage(page) {
    if (page !== currentPage && page > 0) {
        currentPage = page;
        loadJobs();
    }
}

function updateSelectedCount() {
    document.getElementById('selectedCount').textContent = `${selectedJobs.size} selected`;
    document.getElementById('pingSelectedBtn').disabled = selectedJobs.size === 0;
    document.getElementById('markIndexedSelectedBtn').disabled = selectedJobs.size === 0;
}

function pingJobs(jobIds) {
    const btn = document.getElementById('confirmPingBtn');
    btn.disabled = true;
    btn.innerHTML = 'Pinging...';
    
    fetch('/admin/sitemap/ping', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            job_ids: jobIds,
            country: currentCountry !== 'all' ? currentCountry : null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            selectedJobs.clear();
            updateSelectedCount();
            loadJobs();
            loadStatistics();
            loadCountryStats();
            bootstrap.Modal.getInstance(document.getElementById('confirmPingModal')).hide();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(err => {
        window.showToast('error', 'Failed to ping jobs');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Yes, Ping';
    });
}

function pingSingleJob(jobId) {
    if (confirm('Ping this job to search engines?')) {
        pingJobs([jobId]);
    }
}

function markIndexed(jobIds) {
    fetch('/admin/sitemap/mark-indexed', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ job_ids: jobIds })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            selectedJobs.clear();
            updateSelectedCount();
            loadJobs();
            loadStatistics();
            loadCountryStats();
        } else {
            window.showToast('error', data.message);
        }
    })
    .catch(err => {
        window.showToast('error', 'Failed to mark jobs as indexed');
    });
}

function markIndexedSingle(jobId) {
    if (confirm('Mark this job as indexed?')) {
        markIndexed([jobId]);
    }
}

function pingCountry(countryCode) {
    if (confirm(`Ping all jobs in ${countryCode} to search engines?`)) {
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Pinging...';
        
        fetch('/admin/sitemap/ping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                job_ids: [],
                country: countryCode
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let message = data.message;
                if (data.ping_status === 'skipped') {
                    window.showToast('info', message);
                } else if (data.ping_status === 'failed') {
                    window.showToast('warning', message);
                } else {
                    window.showToast('success', message);
                }
                loadStatistics();
                loadCountryStats();
                loadJobs();
            } else {
                window.showToast('error', data.message || 'Failed to ping country');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            window.showToast('error', 'Failed to ping country: ' + err.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
}


function generateSitemap() {
    const btn = document.getElementById('generateSitemapBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="ki-duotone ki-loader fs-2 me-1"></i> Generating...';
    
    const country = currentCountry !== 'all' ? currentCountry : '';
    const url = `/admin/sitemap/generate?country=${country}&ping=true`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', 'Sitemap generated and submitted to search engines');
                loadStatistics();
                loadCountryStats();
            } else {
                window.showToast('error', data.message || 'Failed to generate sitemap');
            }
        })
        .catch(err => {
            window.showToast('error', 'Failed to generate sitemap');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function generateCountrySitemap(countryCode) {
    if (confirm(`Generate sitemap for ${countryCode}?`)) {
        fetch(`/admin/sitemap/generate?country=${countryCode}&ping=true`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showToast('success', `Sitemap generated for ${countryCode}`);
                } else {
                    window.showToast('error', data.message || 'Failed to generate sitemap');
                }
            })
            .catch(err => {
                window.showToast('error', 'Failed to generate sitemap');
            });
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush