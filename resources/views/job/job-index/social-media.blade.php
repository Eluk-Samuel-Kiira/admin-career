@extends('layouts.admin')

@section('title', 'Social Media Platforms')
@section('page_title', 'Social Media Platforms')

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
    <li class="breadcrumb-item text-muted">Social Media</li>
@endsection

@section('content')
@can('view social media platforms')

{{-- Statistics Dashboard --}}
<div class="row g-5 g-xl-8 mb-5">
    <div class="col-xl-3">
        <div class="card card-flush py-4">
            <div class="card-body text-center pt-0">
                <div class="mb-2">
                    <span class="fs-2hx fw-bold text-gray-900" id="totalFollowers">0</span>
                </div>
                <span class="text-gray-500 fw-semibold fs-6">Total Followers</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush py-4">
            <div class="card-body text-center pt-0">
                <div class="mb-2">
                    <span class="fs-2hx fw-bold text-gray-900" id="totalGrowth">0</span>
                </div>
                <span class="text-gray-500 fw-semibold fs-6">Total Growth</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush py-4">
            <div class="card-body text-center pt-0">
                <div class="mb-2">
                    <span class="fs-2hx fw-bold text-gray-900" id="avgGrowth">0%</span>
                </div>
                <span class="text-gray-500 fw-semibold fs-6">Average Growth</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-flush py-4">
            <div class="card-body text-center pt-0">
                <div class="mb-2">
                    <span class="fs-2hx fw-bold text-gray-900" id="totalPlatforms">0</span>
                </div>
                <span class="text-gray-500 fw-semibold fs-6">Active Platforms</span>
            </div>
        </div>
    </div>
</div>

{{-- Chart Section --}}
<div class="card card-flush mb-5">
    <div class="card-header pt-5">
        <div class="card-title">
            <h3 class="fw-bold">Growth Overview</h3>
        </div>
        <div class="card-toolbar">
            <select id="periodFilter" class="form-select form-select-sm w-150px">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div style="height: 300px;">
            <canvas id="growthChart"></canvas>
        </div>
    </div>
</div>

{{-- Main Table --}}
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search platforms..." />
            </div>
            <div>
                <select id="countryFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Countries</option>
                    @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get() as $country)
                        <option value="{{ $country->code }}">
                            {{ $country->flag_emoji }} {{ $country->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="platformFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Platforms</option>
                    <option value="facebook">Facebook</option>
                    <option value="twitter">Twitter / X</option>
                    <option value="instagram">Instagram</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="youtube">YouTube</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="tiktok">TikTok</option>
                    <option value="telegram">Telegram</option>
                </select>
            </div>
        </div>
        <div class="card-toolbar">
            @can('create social media platforms')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_platform">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Platform
            </button>
            @endcan
        </div>
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading platforms...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-200px">Platform</th>
                            <th class="min-w-120px">Country</th>
                            <th class="min-w-150px">Handle</th>
                            <th class="min-w-100px">Followers</th>
                            <th class="min-w-120px">Growth</th>
                            <th class="min-w-120px">Status</th>
                            <th class="min-w-100px">Verified</th>
                            <th class="min-w-100px">Featured</th>
                            <th class="min-w-100px">Order</th>
                            <th class="text-end min-w-140px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="platformsTableBody"></tbody>
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
            <p class="text-muted">No social media platforms found.</p>
        </div>
    </div>
</div>

<!-- Add Platform Modal -->
<div class="modal fade" id="kt_modal_add_platform" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Social Media Platform</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addPlatformForm">
                    @csrf
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="add_name" placeholder="e.g., Great Jobs Facebook" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Platform</label>
                            <select class="form-select form-select-solid" name="platform" id="add_platform" required>
                                <option value="">Select Platform</option>
                                <option value="facebook">Facebook</option>
                                <option value="twitter">Twitter / X</option>
                                <option value="instagram">Instagram</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="youtube">YouTube</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="tiktok">TikTok</option>
                                <option value="telegram">Telegram</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="add_country" required>
                                <option value="">Select Country</option>
                                @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get() as $country)
                                    <option value="{{ $country->code }}">
                                        {{ $country->flag_emoji }} {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Handle</label>
                            <input type="text" class="form-control form-control-solid" name="handle" id="add_handle" placeholder="@greatjobs" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">URL</label>
                        <input type="url" class="form-control form-control-solid" name="url" id="add_url" placeholder="https://www.facebook.com/greatjobs" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="add_description" rows="3" placeholder="Platform description..."></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Followers Count</label>
                            <input type="number" class="form-control form-control-solid" name="followers_count" id="add_followers" value="0" min="0" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="add_sort_order" value="0" min="0" />
                        </div>
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
                                <input class="form-check-input" type="checkbox" name="is_verified" id="add_is_verified" />
                                <label class="form-check-label fw-semibold">Verified</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="add_is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addPlatformBtn">
                            <span class="indicator-label">Create Platform</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Edit Platform Modal -->
<div class="modal fade" id="kt_modal_edit_platform" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Social Media Platform</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editPlatformForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="platform_id" id="edit_platform_id">
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Platform</label>
                            <select class="form-select form-select-solid" name="platform" id="edit_platform" required>
                                <option value="facebook">Facebook</option>
                                <option value="twitter">Twitter / X</option>
                                <option value="instagram">Instagram</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="youtube">YouTube</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="tiktok">TikTok</option>
                                <option value="telegram">Telegram</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid" name="country_code" id="edit_country" required>
                                @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get() as $country)
                                    <option value="{{ $country->code }}">
                                        {{ $country->flag_emoji }} {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Handle</label>
                            <input type="text" class="form-control form-control-solid" name="handle" id="edit_handle" />
                        </div>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">URL</label>
                        <input type="url" class="form-control form-control-solid" name="url" id="edit_url" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Description</label>
                        <textarea class="form-control form-control-solid" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Followers Count</label>
                            <input type="number" class="form-control form-control-solid" name="followers_count" id="edit_followers" min="0" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Sort Order</label>
                            <input type="number" class="form-control form-control-solid" name="sort_order" id="edit_sort_order" min="0" />
                        </div>
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
                                <input class="form-check-input" type="checkbox" name="is_verified" id="edit_is_verified" />
                                <label class="form-check-label fw-semibold">Verified</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="edit_is_featured" />
                                <label class="form-check-label fw-semibold">Featured</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editPlatformBtn">
                            <span class="indicator-label">Update Platform</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Followers Modal -->
<div class="modal fade" id="kt_modal_update_followers" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Update Followers</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="updateFollowersForm">
                    @csrf
                    <input type="hidden" name="platform_id" id="update_platform_id">
                    <div class="fv-row mb-7">
                        <label class="required fw-semibold fs-6 mb-2">Followers Count</label>
                        <input type="number" class="form-control form-control-solid" name="followers_count" id="update_followers_count" min="0" required />
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Note</label>
                        <input type="text" class="form-control form-control-solid" name="note" placeholder="e.g., Manual update" />
                    </div>
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateFollowersBtn">
                            <span class="indicator-label">Update</span>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentPage = 1;
let currentSearch = '';
let currentCountry = '';
let currentPlatform = '';
let growthChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadPlatforms();
    loadStats();
    setupEventListeners();
    setupChart();
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
                loadPlatforms();
            }, 500);
        });
    }

    document.getElementById('countryFilter')?.addEventListener('change', function() {
        currentCountry = this.value;
        currentPage = 1;
        loadPlatforms();
        loadStats();
    });

    document.getElementById('platformFilter')?.addEventListener('change', function() {
        currentPlatform = this.value;
        currentPage = 1;
        loadPlatforms();
        loadStats();
    });

    document.getElementById('periodFilter')?.addEventListener('change', function() {
        loadStats();
    });

    document.getElementById('kt_modal_add_platform')?.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addPlatformForm')?.reset();
    });
}

function loadStats() {
    const period = document.getElementById('periodFilter')?.value || 30;
    let url = `/admin/social-media/stats?period=${period}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentPlatform) url += `&platform=${encodeURIComponent(currentPlatform)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const data = response.data;
                document.getElementById('totalFollowers').textContent = numberFormat(data.total_followers);
                document.getElementById('totalGrowth').textContent = numberFormat(data.total_growth);
                document.getElementById('avgGrowth').textContent = data.average_growth + '%';
                document.getElementById('totalPlatforms').textContent = data.platforms_count;
                updateChart(data.platforms);
            }
        })
        .catch(err => console.error('Error loading stats:', err));
}

function setupChart() {
    const ctx = document.getElementById('growthChart').getContext('2d');
    growthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                }
            }
        }
    });
}

function updateChart(platforms) {
    if (!growthChart) return;

    const allDates = new Set();
    platforms.forEach(p => {
        p.history.forEach(h => allDates.add(h.date));
    });
    const sortedDates = Array.from(allDates).sort();

    const datasets = platforms.map(p => {
        const color = p.color || '#03A588';
        return {
            label: p.name,
            data: sortedDates.map(date => {
                const record = p.history.find(h => h.date === date);
                return record ? record.followers : null;
            }),
            borderColor: color,
            backgroundColor: color + '33',
            borderWidth: 2,
            tension: 0.3,
            fill: false,
            pointRadius: 3,
            pointHoverRadius: 5,
        };
    });

    growthChart.data.labels = sortedDates;
    growthChart.data.datasets = datasets;
    growthChart.update();
}

function loadPlatforms() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/social-media/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentCountry) url += `&country=${encodeURIComponent(currentCountry)}`;
    if (currentPlatform) url += `&platform=${encodeURIComponent(currentPlatform)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderPlatformsTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load platforms');
        });
}

function renderPlatformsTable(platforms) {
    const tbody = document.getElementById('platformsTableBody');
    tbody.innerHTML = '';
    
    platforms.forEach(platform => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${platform.id}</span>`;
        
        row.insertCell(1).innerHTML = `
            <div class="d-flex align-items-center">
                <div class="symbol symbol-40px me-3">
                    <span class="symbol-label bg-light-${platform.is_featured ? 'warning' : 'primary'}">
                        <i class="ki-duotone ${platform.platform_icon} fs-2 text-${platform.is_featured ? 'warning' : 'primary'}">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </span>
                </div>
                <div>
                    <div class="fw-bold">${escapeHtml(platform.name)}</div>
                    <div class="text-muted fs-7">${escapeHtml(platform.platform)}</div>
                </div>
            </div>
        `;
        
        row.insertCell(2).innerHTML = `<span class="badge badge-light-info">${platform.country_flag} ${platform.country_name}</span>`;
        row.insertCell(3).innerHTML = platform.handle ? `<code class="small">${escapeHtml(platform.handle)}</code>` : '<span class="text-muted">-</span>';
        row.insertCell(4).innerHTML = `<span class="fw-bold">${numberFormat(platform.current_followers || 0)}</span>`;
        
        const growthIcon = platform.growth_icon || 'ki-arrow-right';
        const growthClass = platform.growth_class || 'text-muted';
        const growthDisplay = platform.followers_change !== undefined ? platform.followers_change : 0;
        const sign = growthDisplay > 0 ? '+' : '';
        row.insertCell(5).innerHTML = `
            <span class="${growthClass}">
                <i class="ki-duotone ${growthIcon} fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                ${sign}${growthDisplay} (${platform.followers_percentage_change || 0}%)
            </span>
        `;
        
        row.insertCell(6).innerHTML = platform.status_badge;
        row.insertCell(7).innerHTML = platform.verified_badge;
        row.insertCell(8).innerHTML = platform.is_featured 
            ? '<span class="badge badge-light-warning">⭐ Featured</span>' 
            : '<span class="badge badge-light-secondary">-</span>';
        row.insertCell(9).innerHTML = platform.sort_order || 0;
        
        row.insertCell(10).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleFeatured(${platform.id}, ${platform.is_featured})" title="${platform.is_featured ? 'Remove Featured' : 'Make Featured'}">
                    <i class="ki-duotone ki-star fs-3 text-${platform.is_featured ? 'warning' : 'muted'}">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${platform.id}, ${platform.is_active})" title="${platform.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${platform.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleVerified(${platform.id}, ${platform.is_verified})" title="${platform.is_verified ? 'Unverify' : 'Verify'}">
                    <i class="ki-duotone ki-shield fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="updateFollowers(${platform.id}, ${platform.current_followers || 0})" title="Update Followers">
                    <i class="ki-duotone ki-people fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editPlatform(${platform.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span><span class="path2"></span>
                        <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deletePlatform(${platform.id}, '${escapeHtml(platform.name)}')" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadPlatforms(); }
};

// ================================================================
// TOGGLE FUNCTIONS - FIXED
// ================================================================
window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this platform?`)) {
        fetch(`/admin/social-media/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadPlatforms();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle status'));
    }
};

window.toggleVerified = function(id, current) {
    const action = current ? 'unverify' : 'verify';
    if (confirm(`Are you sure you want to ${action} this platform?`)) {
        fetch(`/admin/social-media/${id}/toggle-verified`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadPlatforms();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle verification'));
    }
};

window.toggleFeatured = function(id, current) {
    const action = current ? 'unfeature' : 'feature';
    if (confirm(`Are you sure you want to ${action} this platform?`)) {
        fetch(`/admin/social-media/${id}/toggle-featured`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadPlatforms();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle featured'));
    }
};

// ================================================================
// EDIT PLATFORM
// ================================================================
window.editPlatform = function(id) {
    // Show loading state
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_edit_platform'));
    modal.show();
    
    // Show loading in form fields
    document.getElementById('edit_name').value = 'Loading...';
    
    fetch(`/admin/social-media/${id}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            return res.json();
        })
        .then(data => {
            // Check if all elements exist before setting values
            const elements = {
                'edit_platform_id': data.id,
                'edit_name': data.name || '',
                'edit_platform': data.platform || 'facebook',
                'edit_country': data.country_code || '',
                'edit_handle': data.handle || '',
                'edit_url': data.url || '',
                'edit_description': data.description || '',
                'edit_followers': data.followers_count || 0,
                'edit_sort_order': data.sort_order || 0,
                'edit_is_active': data.is_active || false,
                'edit_is_verified': data.is_verified || false,
                'edit_is_featured': data.is_featured || false,
            };
            
            Object.entries(elements).forEach(([id, value]) => {
                const el = document.getElementById(id);
                if (el) {
                    if (el.type === 'checkbox') {
                        el.checked = value;
                    } else {
                        el.value = value;
                    }
                } else {
                    console.warn(`Element with id "${id}" not found`);
                }
            });
        })
        .catch(err => {
            console.error('Error loading platform:', err);
            window.showToast('error', 'Failed to load platform details: ' + err.message);
        });
};

// ================================================================
// DELETE PLATFORM
// ================================================================
window.deletePlatform = function(id, name) {
    if (confirm(`Are you sure you want to delete platform "${name}"?`)) {
        fetch(`/admin/social-media/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadPlatforms();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete platform'));
    }
};

// ================================================================
// UPDATE FOLLOWERS
// ================================================================
window.updateFollowers = function(id, current) {
    document.getElementById('update_platform_id').value = id;
    document.getElementById('update_followers_count').value = current || 0;
    new bootstrap.Modal(document.getElementById('kt_modal_update_followers')).show();
};

// ================================================================
// ADD PLATFORM FORM
// ================================================================
document.getElementById('addPlatformForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addPlatformBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    const isActiveCheckbox = document.querySelector('#addPlatformForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    const isVerifiedCheckbox = document.querySelector('#addPlatformForm input[name="is_verified"]');
    if (isVerifiedCheckbox) {
        formData.set('is_verified', isVerifiedCheckbox.checked ? '1' : '0');
    }
    const isFeaturedCheckbox = document.querySelector('#addPlatformForm input[name="is_featured"]');
    if (isFeaturedCheckbox) {
        formData.set('is_featured', isFeaturedCheckbox.checked ? '1' : '0');
    }
    
    fetch('/admin/social-media', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_platform'))?.hide();
            this.reset();
            loadPlatforms();
        } else {
            window.showToast('error', data.message || 'Failed to create platform');
        }
    })
    .catch(err => window.showToast('error', 'Failed to create platform: ' + err.message))
    .finally(() => window.hideButtonSpinner(btn));
});

// ================================================================
// EDIT PLATFORM FORM
// ================================================================
document.getElementById('editPlatformForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editPlatformBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_platform_id').value;
    
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    const isActiveCheckbox = document.querySelector('#editPlatformForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    const isVerifiedCheckbox = document.querySelector('#editPlatformForm input[name="is_verified"]');
    if (isVerifiedCheckbox) {
        formData.set('is_verified', isVerifiedCheckbox.checked ? '1' : '0');
    }
    const isFeaturedCheckbox = document.querySelector('#editPlatformForm input[name="is_featured"]');
    if (isFeaturedCheckbox) {
        formData.set('is_featured', isFeaturedCheckbox.checked ? '1' : '0');
    }
    
    fetch(`/admin/social-media/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_platform'))?.hide();
            loadPlatforms();
        } else {
            window.showToast('error', data.message || 'Failed to update platform');
        }
    })
    .catch(err => window.showToast('error', 'Failed to update platform: ' + err.message))
    .finally(() => window.hideButtonSpinner(btn));
});

// ================================================================
// UPDATE FOLLOWERS FORM
// ================================================================
document.getElementById('updateFollowersForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('updateFollowersBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('update_platform_id').value;
    
    const formData = new FormData(this);
    
    fetch(`/admin/social-media/${id}/update-followers`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.showToast('success', data.message);
            bootstrap.Modal.getInstance(document.getElementById('kt_modal_update_followers'))?.hide();
            loadPlatforms();
            loadStats();
        } else {
            window.showToast('error', data.message || 'Failed to update followers');
        }
    })
    .catch(err => window.showToast('error', 'Failed to update followers: ' + err.message))
    .finally(() => window.hideButtonSpinner(btn));
});

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function numberFormat(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush