@extends('layouts.admin')

@section('title', 'Countries')
@section('page_title', 'Countries')

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
    <li class="breadcrumb-item text-muted">Countries</li>
@endsection

@section('content')
@can('view countries')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1 me-5">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput" class="form-control form-control-solid w-250px ps-13" placeholder="Search countries..." />
            </div>
            <div>
                <select id="statusFilter" class="form-select form-select-solid w-150px">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        @can('create countries')
        <div class="card-toolbar">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_country">
                <i class="ki-duotone ki-plus-square fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i> Add Country
            </button>
        </div>
        @endcan
    </div>
    
    <div class="card-body pt-0">
        <div id="loadingSpinner" class="text-center py-10 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading countries...</p>
        </div>
        
        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-100px">Flag</th>
                            <th class="min-w-150px">Country</th>
                            <th class="min-w-80px">Code</th>
                            <th class="min-w-120px">Capital</th>
                            <th class="min-w-100px">Region</th>
                            <th class="min-w-100px">Currency</th>
                            <th class="min-w-100px">Phone Code</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-80px">Order</th>
                            <th class="text-end min-w-160px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="countriesTableBody"></tbody>
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
            <p class="text-muted">No countries found.</p>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD COUNTRY MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="kt_modal_add_country" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Add Country</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="addCountryForm">
                    @csrf
                    
                    <!-- Basic Info -->
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" placeholder="e.g., AU, UG, KE" maxlength="2" required />
                            <div class="text-muted fs-7 mt-1">ISO 3166-1 alpha-2 country code (2 letters)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" placeholder="e.g., Australia, Uganda" required />
                        </div>
                    </div>
                    
                    <!-- Location Info -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Region</label>
                            <input type="text" class="form-control form-control-solid" name="region" placeholder="e.g., Oceania, East Africa" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Time Zone</label>
                            <input type="text" class="form-control form-control-solid" name="timezone" placeholder="e.g., Australia/Sydney" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Phone Code</label>
                            <input type="text" class="form-control form-control-solid" name="phone_code" placeholder="e.g., +61, +256" />
                        </div>
                    </div>
                    
                    <!-- Currency & Flag -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid" name="currency" placeholder="e.g., AUD, UGX" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Currency Symbol</label>
                            <input type="text" class="form-control form-control-solid" name="currency_symbol" placeholder="e.g., $, UGX" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Flag Emoji</label>
                            <input type="text" class="form-control form-control-solid" name="flag" placeholder="e.g., 🇦🇺, 🇺🇬" />
                        </div>
                    </div>
                    
                    <!-- Capital & Coordinates -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital</label>
                            <input type="text" class="form-control form-control-solid" name="capital" placeholder="e.g., Canberra, Kampala" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital Latitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="capital_lat" placeholder="-35.2809" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital Longitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="capital_lng" placeholder="149.1300" />
                        </div>
                    </div>
                    
                    <!-- Default Coordinates -->
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Default Latitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="default_lat" placeholder="-25.2744" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Default Longitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="default_lng" placeholder="133.7751" />
                        </div>
                    </div>
                    
                    <!-- Sort Order & Active -->
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
                    
                    <!-- SEO -->
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                        <input type="text" class="form-control form-control-solid" name="meta_title" placeholder="SEO Title (optional)" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" rows="2" placeholder="SEO Description (optional)"></textarea>
                    </div>
                    
                    <!-- Feature Flags Section -->
                    <div class="fv-row mb-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-semibold fs-6 mb-0">Feature Flags</label>
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="toggleAddFeatures()">
                                <i class="ki-duotone ki-eye fs-3 me-1">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Show All Features
                            </button>
                        </div>
                        
                        <div class="border rounded p-3 bg-light">
                            @php
                                $featureGroups = [
                                    'Job Seeker & Employer Services' => [
                                        'can_view_casual_workers' => 'View Casual Workers',
                                        'can_view_blue_collar_workers' => 'View Blue Collar Workers',
                                        'can_accept_cv_services' => 'CV Services',
                                        'can_offer_exam_services' => 'Exam Services',
                                        'can_view_salary_insights' => 'Salary Insights',
                                        'can_view_cost_of_living_tools' => 'Cost of Living Tools',
                                        'can_use_social_media_services' => 'Social Media Services',
                                        'can_view_employer_services' => 'Employer Services',
                                        'can_view_jobseeker_services' => 'Jobseeker Services',
                                        'can_access_subscription' => 'Subscription Access',
                                    ],
                                    'Traffic & Engagement Features' => [
                                        'can_view_company_profiles' => 'Company Profiles',
                                        'can_view_industry_insights' => 'Industry Insights',
                                        'can_access_career_advice' => 'Career Advice',
                                        'can_view_job_alerts' => 'Job Alerts',
                                        'can_use_resume_builder' => 'Resume Builder',
                                        'can_view_employer_reviews' => 'Employer Reviews',
                                        'can_access_skill_assessment' => 'Skill Assessment',
                                        'can_view_market_trends' => 'Market Trends',
                                        'can_use_job_comparison_tools' => 'Job Comparison Tools',
                                        'can_access_networking_events' => 'Networking Events',
                                        'can_view_training_courses' => 'Training Courses',
                                        'can_use_chat_support' => 'Chat Support',
                                    ],
                                    'Premium Features' => [
                                        'can_access_premium_content' => 'Premium Content',
                                        'can_view_verified_employers' => 'Verified Employers',
                                        'can_use_priority_application' => 'Priority Application',
                                        'can_view_exclusive_jobs' => 'Exclusive Jobs',
                                        'can_access_interview_coaching' => 'Interview Coaching',
                                        'can_view_salary_negotiation_tips' => 'Salary Negotiation Tips',
                                    ],
                                    'Job Posting Features' => [
                                        'can_post_jobs' => 'Post Jobs',
                                        'can_post_featured_jobs' => 'Post Featured Jobs',
                                        'can_post_urgent_jobs' => 'Post Urgent Jobs',
                                        'can_use_job_analytics' => 'Job Analytics',
                                        'can_manage_applications' => 'Manage Applications',
                                    ],
                                ];
                            @endphp
                            
                            @foreach($featureGroups as $groupName => $features)
                                <div class="mb-3">
                                    <span class="fw-bold fs-7 text-primary">{{ $groupName }}</span>
                                    <div class="row g-2 mt-1">
                                        @foreach($features as $field => $label)
                                            <div class="col-md-6 add-feature-item" style="display: none;">
                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" id="add_{{ $field }}" value="1" />
                                                    <label class="form-check-label fs-7" for="add_{{ $field }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                        <button type="submit" class="btn btn-primary" id="addCountryBtn">
                            <span class="indicator-label">Create Country</span>
                            <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT COUNTRY MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="kt_modal_edit_country" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Edit Country</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7">
                <form id="editCountryForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="country_id" id="edit_country_id">
                    
                    <!-- Basic Info -->
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country Code</label>
                            <input type="text" class="form-control form-control-solid" name="code" id="edit_code" maxlength="2" required />
                            <div class="text-muted fs-7 mt-1">ISO 3166-1 alpha-2 country code (2 letters)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Country Name</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="edit_name" required />
                        </div>
                    </div>
                    <!-- Basic Info -->
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Frontend URL</label>
                            <input type="text" class="form-control form-control-solid" name="frontend_url" id="edit_frontend_url" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Domain</label>
                            <input type="text" class="form-control form-control-solid" name="domain" id="edit_domain" required />
                        </div>
                    </div>
                    
                    <!-- Location Info -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Region</label>
                            <input type="text" class="form-control form-control-solid" name="region" id="edit_region" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Time Zone</label>
                            <input type="text" class="form-control form-control-solid" name="timezone" id="edit_timezone" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Phone Code</label>
                            <input type="text" class="form-control form-control-solid" name="phone_code" id="edit_phone_code" />
                        </div>
                    </div>
                    
                    <!-- Currency & Flag -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Currency</label>
                            <input type="text" class="form-control form-control-solid" name="currency" id="edit_currency" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Currency Symbol</label>
                            <input type="text" class="form-control form-control-solid" name="currency_symbol" id="edit_currency_symbol" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Flag Emoji</label>
                            <input type="text" class="form-control form-control-solid" name="flag" id="edit_flag" />
                        </div>
                    </div>
                    
                    <!-- Capital & Coordinates -->
                    <div class="row mb-7">
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital</label>
                            <input type="text" class="form-control form-control-solid" name="capital" id="edit_capital" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital Latitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="capital_lat" id="edit_capital_lat" />
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold fs-6 mb-2">Capital Longitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="capital_lng" id="edit_capital_lng" />
                        </div>
                    </div>
                    
                    <!-- Default Coordinates -->
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Default Latitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="default_lat" id="edit_default_lat" />
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold fs-6 mb-2">Default Longitude</label>
                            <input type="number" step="0.00000001" class="form-control form-control-solid" name="default_lng" id="edit_default_lng" />
                        </div>
                    </div>
                    
                    <!-- Sort Order & Active -->
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
                    
                    <!-- SEO -->
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Title</label>
                        <input type="text" class="form-control form-control-solid" name="meta_title" id="edit_meta_title" />
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="fw-semibold fs-6 mb-2">Meta Description</label>
                        <textarea class="form-control form-control-solid" name="meta_description" id="edit_meta_description" rows="2"></textarea>
                    </div>
                    
                    <!-- Feature Flags Section -->
                    <div class="fv-row mb-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="fw-semibold fs-6 mb-0">Feature Flags</label>
                            <button type="button" class="btn btn-sm btn-light-primary" onclick="toggleEditFeatures()">
                                <i class="ki-duotone ki-eye fs-3 me-1">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                Show All Features
                            </button>
                        </div>
                        
                        <div class="border rounded p-3 bg-light">
                            @foreach($featureGroups as $groupName => $features)
                                <div class="mb-3">
                                    <span class="fw-bold fs-7 text-primary">{{ $groupName }}</span>
                                    <div class="row g-2 mt-1">
                                        @foreach($features as $field => $label)
                                            <div class="col-md-6 edit-feature-item" style="display: none;">
                                                <div class="form-check form-switch form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" name="{{ $field }}" id="edit_{{ $field }}" value="1" />
                                                    <label class="form-check-label fs-7" for="edit_{{ $field }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editCountryBtn">
                            <span class="indicator-label">Update Country</span>
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
let currentStatus = '';
let addFeaturesVisible = false;
let editFeaturesVisible = false;

document.addEventListener('DOMContentLoaded', function() {
    loadCountries();
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
                loadCountries();
            }, 500);
        });
    }

    document.getElementById('statusFilter')?.addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadCountries();
    });
}

function loadCountries() {
    const spinner = document.getElementById('loadingSpinner');
    const table = document.getElementById('tableContainer');
    const noData = document.getElementById('noDataMessage');
    const pagination = document.getElementById('paginationContainer');
    
    spinner.classList.remove('d-none');
    table.classList.add('d-none');
    noData.classList.add('d-none');
    pagination.classList.add('d-none');
    
    let url = `/admin/countries/data?page=${currentPage}&per_page=20`;
    if (currentSearch) url += `&search=${encodeURIComponent(currentSearch)}`;
    if (currentStatus) url += `&status=${encodeURIComponent(currentStatus)}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            spinner.classList.add('d-none');
            if (data.data.length === 0) {
                noData.classList.remove('d-none');
            } else {
                table.classList.remove('d-none');
                renderCountriesTable(data.data);
                renderPagination(data);
                pagination.classList.remove('d-none');
            }
        })
        .catch(err => {
            spinner.classList.add('d-none');
            window.showToast('error', 'Failed to load countries');
        });
}

function renderCountriesTable(countries) {
    const tbody = document.getElementById('countriesTableBody');
    tbody.innerHTML = '';
    
    countries.forEach(country => {
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = `<span class="fw-bold">${country.id}</span>`;
        row.insertCell(1).innerHTML = `<span style="font-size: 28px;">${country.flag || '🌍'}</span>`;
        row.insertCell(2).innerHTML = `<div class="fw-bold">${escapeHtml(country.name)}</div>`;
        row.insertCell(3).innerHTML = `<span class="badge badge-light-primary">${escapeHtml(country.code)}</span>`;
        row.insertCell(4).innerHTML = country.capital ? escapeHtml(country.capital) : '<span class="text-muted">-</span>';
        row.insertCell(5).innerHTML = country.region ? escapeHtml(country.region) : '<span class="text-muted">-</span>';
        row.insertCell(6).innerHTML = country.currency ? escapeHtml(country.currency) : '<span class="text-muted">-</span>';
        row.insertCell(7).innerHTML = country.phone_code ? escapeHtml(country.phone_code) : '<span class="text-muted">-</span>';
        row.insertCell(8).innerHTML = country.status_badge;
        row.insertCell(9).innerHTML = country.sort_order || 0;
        row.insertCell(10).innerHTML = `
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-icon btn-light" onclick="toggleStatus(${country.id}, ${country.is_active})" title="${country.is_active ? 'Deactivate' : 'Activate'}">
                    <i class="ki-duotone ki-${country.is_active ? 'disconnect' : 'check'} fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="editCountry(${country.id})" title="Edit">
                    <i class="ki-duotone ki-setting-3 fs-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                </button>
                <button class="btn btn-sm btn-icon btn-light" onclick="deleteCountry(${country.id}, '${escapeHtml(country.name)}')" title="Delete">
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
    if (page !== currentPage && page > 0) { currentPage = page; loadCountries(); }
};

window.toggleStatus = function(id, current) {
    const action = current ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this country?`)) {
        fetch(`/admin/countries/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to toggle status'));
    }
};

// ================================================================
// EDIT COUNTRY - WITH FEATURE FLAGS
// ================================================================
window.editCountry = function(id) {
    // console.log('Fetching country with ID:', id);
    
    fetch(`/admin/countries/${id}`)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Failed to load country');
                });
            }
            return response.json();
        })
        .then(data => {
            // console.log('Country data received:', data);
            
            document.getElementById('edit_country_id').value = data.id;
            document.getElementById('edit_code').value = data.code;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_frontend_url').value = data.frontend_url;
            document.getElementById('edit_domain').value = data.domain;
            document.getElementById('edit_region').value = data.region || '';
            document.getElementById('edit_timezone').value = data.timezone || '';
            document.getElementById('edit_phone_code').value = data.phone_code || '';
            document.getElementById('edit_currency').value = data.currency || '';
            document.getElementById('edit_currency_symbol').value = data.currency_symbol || '';
            document.getElementById('edit_flag').value = data.flag || '';
            document.getElementById('edit_capital').value = data.capital || '';
            document.getElementById('edit_capital_lat').value = data.capital_lat || '';
            document.getElementById('edit_capital_lng').value = data.capital_lng || '';
            document.getElementById('edit_default_lat').value = data.default_lat || '';
            document.getElementById('edit_default_lng').value = data.default_lng || '';
            document.getElementById('edit_sort_order').value = data.sort_order || 0;
            document.getElementById('edit_meta_title').value = data.meta_title || '';
            document.getElementById('edit_meta_description').value = data.meta_description || '';
            document.getElementById('edit_is_active').checked = data.is_active;
            
            // Set feature flags
            const featureFields = [
                'can_view_casual_workers',
                'can_view_blue_collar_workers',
                'can_accept_cv_services',
                'can_offer_exam_services',
                'can_view_salary_insights',
                'can_view_cost_of_living_tools',
                'can_use_social_media_services',
                'can_view_employer_services',
                'can_view_jobseeker_services',
                'can_access_subscription',
                'can_view_company_profiles',
                'can_view_industry_insights',
                'can_access_career_advice',
                'can_view_job_alerts',
                'can_use_resume_builder',
                'can_view_employer_reviews',
                'can_access_skill_assessment',
                'can_view_market_trends',
                'can_use_job_comparison_tools',
                'can_access_networking_events',
                'can_view_training_courses',
                'can_use_chat_support',
                'can_access_premium_content',
                'can_view_verified_employers',
                'can_use_priority_application',
                'can_view_exclusive_jobs',
                'can_access_interview_coaching',
                'can_view_salary_negotiation_tips',
                'can_post_jobs',
                'can_post_featured_jobs',
                'can_post_urgent_jobs',
                'can_use_job_analytics',
                'can_manage_applications'
            ];
            
            featureFields.forEach(field => {
                const checkbox = document.getElementById(`edit_${field}`);
                if (checkbox) {
                    checkbox.checked = data[field] === true || data[field] === 1;
                }
            });
            
            // Reset features visibility (hide all features initially)
            editFeaturesVisible = false;
            document.querySelectorAll('.edit-feature-item').forEach(item => {
                item.style.display = 'none';
            });
            
            const toggleBtn = document.querySelector('#kt_modal_edit_country #toggleFeaturesBtn');
            if (toggleBtn) {
                toggleBtn.innerHTML = '<i class="ki-duotone ki-eye fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Show All Features';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('kt_modal_edit_country'));
            modal.show();
        })
        .catch(err => {
            console.error('Error loading country:', err);
            window.showToast('error', 'Failed to load country details: ' + err.message);
        });
};

// ================================================================
// TOGGLE FEATURES - ADD MODAL
// ================================================================
function toggleAddFeatures() {
    const btn = document.querySelector('#kt_modal_add_country #toggleFeaturesBtn');
    const items = document.querySelectorAll('.add-feature-item');
    
    addFeaturesVisible = !addFeaturesVisible;
    
    items.forEach(item => {
        item.style.display = addFeaturesVisible ? 'block' : 'none';
    });
    
    btn.innerHTML = addFeaturesVisible ? 
        '<i class="ki-duotone ki-eye-slash fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Hide Features' : 
        '<i class="ki-duotone ki-eye fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Show All Features';
}

// ================================================================
// TOGGLE FEATURES - EDIT MODAL
// ================================================================
function toggleEditFeatures() {
    const btn = document.querySelector('#kt_modal_edit_country #toggleFeaturesBtn');
    const items = document.querySelectorAll('.edit-feature-item');
    
    editFeaturesVisible = !editFeaturesVisible;
    
    items.forEach(item => {
        item.style.display = editFeaturesVisible ? 'block' : 'none';
    });
    
    btn.innerHTML = editFeaturesVisible ? 
        '<i class="ki-duotone ki-eye-slash fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Hide Features' : 
        '<i class="ki-duotone ki-eye fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Show All Features';
}

window.deleteCountry = function(id, name) {
    if (confirm(`Are you sure you want to delete country "${name}"? This action cannot be undone.`)) {
        fetch(`/admin/countries/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.showToast('success', data.message);
                loadCountries();
            } else {
                window.showToast('error', data.message);
            }
        })
        .catch(err => window.showToast('error', 'Failed to delete country'));
    }
};

// Add Country Form
document.getElementById('addCountryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('addCountryBtn');
    window.showButtonSpinner(btn);
    
    const formData = new FormData(this);
    
    const isActiveCheckbox = document.querySelector('#addCountryForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    fetch('/admin/countries', {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_add_country'));
            if (modal) modal.hide();
            this.reset();
            loadCountries();
        } else {
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join('\n');
                window.showToast('error', errorMessages);
            } else {
                window.showToast('error', data.message || 'Failed to create country');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        window.showToast('error', 'Failed to create country: ' + err.message);
    })
    .finally(() => window.hideButtonSpinner(btn));
});

// Edit Country Form
document.getElementById('editCountryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editCountryBtn');
    window.showButtonSpinner(btn);
    const id = document.getElementById('edit_country_id').value;
    
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    const isActiveCheckbox = document.querySelector('#editCountryForm input[name="is_active"]');
    if (isActiveCheckbox) {
        formData.set('is_active', isActiveCheckbox.checked ? '1' : '0');
    }
    
    fetch(`/admin/countries/${id}`, {
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_country'));
            if (modal) modal.hide();
            loadCountries();
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
        window.showToast('error', 'Failed to update country: ' + err.message);
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