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

{{-- ============================================================== --}}
{{-- FILTER CARD                                                      --}}
{{-- ============================================================== --}}
<div class="card card-flush mb-5">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" id="searchInput"
                       class="form-control form-control-solid w-250px ps-12"
                       placeholder="Search name, email, skills..." />
            </div>
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
            <select id="countryFilter" class="form-select form-select-solid w-150px">
                <option value="">All Countries</option>
            </select>
            <select id="statusFilter" class="form-select form-select-solid w-150px">
                <option value="">Any Status</option>
                <option value="has_cv">Has CV</option>
                <option value="no_cv">No CV</option>
                <option value="has_applied">Has Applied</option>
            </select>
            <button type="button" class="btn btn-light-primary" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                <i class="ki-duotone ki-filter fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Advanced
            </button>

            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold">
                    <span class="text-gray-800">Results</span>
                </h3>
                <div class="card-toolbar d-flex gap-3">
                    <span class="badge badge-light-primary fs-7 py-3 px-5" id="totalBadge">0 Total</span>
                    <button type="button" class="btn btn-sm btn-primary" id="addSeekerBtn">
                        <i class="ki-duotone ki-plus fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Add Seeker
                    </button>
                </div>
            </div>
        </div>
    </div>



    {{-- Advanced filter grid --}}
    <div class="collapse" id="advancedFilters">
        <div class="card-body border-top pt-6">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Job Category</label>
                    <select id="categoryFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Categories">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Industry</label>
                    <select id="industryFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Industries">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Job Type</label>
                    <select id="jobTypeFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Job Types">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Location</label>
                    <select id="locationFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Locations">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Experience Level</label>
                    <select id="experienceLevelFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Experience Levels">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Education Level</label>
                    <select id="educationLevelFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Education Levels">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Salary Range</label>
                    <select id="salaryRangeFilter" class="form-select form-select-solid" data-control="select2" data-placeholder="All Salary Ranges">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Min Years Experience</label>
                    <input type="number" id="minExperienceFilter"
                           class="form-control form-control-solid"
                           placeholder="e.g. 3" min="0" max="60" />
                </div>
                <div class="col-md-3">
                    <label class="fw-semibold fs-7 mb-2">Profile Status</label>
                    <select id="profileCompleteFilter" class="form-select form-select-solid">
                        <option value="">Any</option>
                        <option value="1">Complete</option>
                        <option value="0">Incomplete</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" id="resetFiltersBtn" class="btn btn-light w-100">
                        <i class="ki-duotone ki-arrows-circle fs-2 me-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Reset Filters
                    </button>
                </div>
            </div>

            {{-- Active filter chips --}}
            <div id="activeFilterChips" class="d-flex flex-wrap gap-2 mt-5 d-none"></div>
        </div>
    </div>
</div>

{{-- ============================================================== --}}
{{-- TABLE CARD                                                       --}}
{{-- ============================================================== --}}
<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <h3 class="card-title fw-bold">
            <span class="text-gray-800">Results</span>
        </h3>
        <div class="card-toolbar">
            <span class="badge badge-light-primary fs-7 py-3 px-5" id="totalBadge">0 Total</span>
        </div>
    </div>

    <div class="card-body pt-0">
        {{-- Skeleton loader --}}
        <div id="loadingSpinner" class="d-none">
            @for ($i = 0; $i < 5; $i++)
                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                    <div class="skeleton skeleton-circle" style="width:40px;height:40px;"></div>
                    <div class="flex-grow-1">
                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                    </div>
                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                </div>
            @endfor
        </div>

        <div id="tableContainer" class="d-none">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-4 mb-0">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-50px">ID</th>
                            <th class="min-w-220px">Seeker</th>
                            <th class="min-w-160px">Title</th>
                            <th class="min-w-140px">Country</th>
                            <th class="min-w-90px">Experience</th>
                            <th class="min-w-220px">Preferences</th>
                            <th class="min-w-110px">CV</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="seekersTableBody"></tbody>
                </table>
            </div>

            <div id="paginationContainer"
                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                <div id="paginationInfo" class="text-muted fs-7"></div>
                <nav>
                    <ul class="pagination pagination-outline m-0" id="pagination"></ul>
                </nav>
            </div>
        </div>

        {{-- Empty state --}}
        <div id="noDataMessage" class="text-center py-15 d-none">
            <i class="ki-duotone ki-search-list fs-5tx text-muted mb-4 d-block">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <h4 class="fw-bold text-gray-800 mb-2">No job seekers found</h4>
            <p class="text-muted mb-5">Try adjusting the filters or search query.</p>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('resetFiltersBtn').click();">
                Reset Filters
            </button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════ --}}
{{-- SEEKER DETAILS DRAWER                                     --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_drawer_view_seeker" style="width: 720px;">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h5 class="offcanvas-title mb-0">Seeker Details</h5>
            <span class="badge badge-light-primary d-none" id="drawerSeekerBadge"></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="seekerDetailsContainer">
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- APPLICATIONS DRAWER                                       --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_drawer_view_applications" style="width: 900px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title mb-0">Job Applications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="applicationsContainer">
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- CREATE / EDIT SEEKER DRAWER                                --}}
{{-- ══════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_drawer_edit_seeker" style="width: 900px;">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-3">
            <h5 class="offcanvas-title mb-0" id="editDrawerTitle">Add Job Seeker</h5>
            <span class="badge badge-light-info d-none" id="editDrawerMode"></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="editSeekerContainer">
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer border-top p-4 d-flex justify-content-end gap-2">
        <button class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
        <button class="btn btn-primary" id="saveSeekerBtn">
            <span class="indicator-label">
                <i class="ki-duotone ki-check-circle fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                Save Seeker
            </span>
            <span class="indicator-progress">Saving... <span class="spinner-border spinner-border-sm ms-2"></span></span>
        </button>
    </div>
</div>
@endcan
@endsection

@push('styles')
<style>
    .skeleton {
        display: inline-block;
        background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
        background-size: 200% 100%;
        animation: skeleton-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .skeleton-circle { border-radius: 50%; }
    .skeleton-text   { height: 12px; margin: 4px 0; display: block; }

    @keyframes skeleton-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--bs-gray-200);
        font-size: 0.8rem;
        font-weight: 500;
    }
    .filter-chip .chip-close {
        cursor: pointer;
        opacity: .6;
        transition: opacity .15s ease;
    }
    .filter-chip .chip-close:hover { opacity: 1; }

    .pref-cell .badge { font-size: 0.68rem; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    // ─────────────────────────────────────────────────────────────
    // State
    // ─────────────────────────────────────────────────────────────
    const state = {
        page: 1,
        perPage: 15,
        search: '',
        country: '',
        status: '',
        category: '',
        industry: '',
        jobType: '',
        location: '',
        experienceLevel: '',
        educationLevel: '',
        salaryRange: '',
        minExperience: '',
        profileComplete: '',
    };

    const seekerDrawerEl = document.getElementById('kt_drawer_view_seeker');
    const seekerDrawer   = bootstrap.Offcanvas.getOrCreateInstance(seekerDrawerEl);

    const appsDrawerEl = document.getElementById('kt_drawer_view_applications');
    const appsDrawer   = bootstrap.Offcanvas.getOrCreateInstance(appsDrawerEl);

    const editDrawerEl = document.getElementById('kt_drawer_edit_seeker');
    const editDrawer   = bootstrap.Offcanvas.getOrCreateInstance(editDrawerEl);

    let searchDebounce;

    // ─────────────────────────────────────────────────────────────
    // Bootstrap
    // ─────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        initSelect2();
        bindEvents();
        loadFilters();
        loadSeekers();
    });

    function initSelect2() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
        jQuery('#advancedFilters select[data-control="select2"]').each(function () {
            const $el = jQuery(this);
            if ($el.hasClass('select2-hidden-accessible')) return;
            $el.select2({
                placeholder: $el.data('placeholder') || 'Select...',
                allowClear: true,
                width: '100%',
                dropdownParent: jQuery('#advancedFilters'),
            });
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Event wiring
    // ─────────────────────────────────────────────────────────────
    function bindEvents() {
        const searchInput = document.getElementById('searchInput');
        searchInput?.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                state.search = this.value.trim();
                state.page = 1;
                loadSeekers();
            }, 400);
        });

        bindFilter('countryFilter',         v => state.country = v);
        bindFilter('statusFilter',          v => state.status = v);
        bindFilter('categoryFilter',        v => state.category = v);
        bindFilter('industryFilter',        v => state.industry = v);
        bindFilter('jobTypeFilter',         v => state.jobType = v);
        bindFilter('locationFilter',        v => state.location = v);
        bindFilter('experienceLevelFilter', v => state.experienceLevel = v);
        bindFilter('educationLevelFilter',  v => state.educationLevel = v);
        bindFilter('salaryRangeFilter',     v => state.salaryRange = v);
        bindFilter('profileCompleteFilter', v => state.profileComplete = v);

        const minExp = document.getElementById('minExperienceFilter');
        minExp?.addEventListener('input', function () {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => {
                state.minExperience = this.value;
                state.page = 1;
                loadSeekers();
            }, 400);
        });

        document.getElementById('resetFiltersBtn')?.addEventListener('click', resetFilters);
    }

    function bindFilter(id, setter) {
        const el = document.getElementById(id);
        if (!el) return;

        // Select2 fires 'change' too, so this works for both plain and Select2 selects
        el.addEventListener('change', function () {
            setter(this.value);
            state.page = 1;
            loadSeekers();
        });
    }

    function resetFilters() {
        state.page = 1;
        state.search = state.country = state.status = '';
        state.category = state.industry = state.jobType = state.location = '';
        state.experienceLevel = state.educationLevel = state.salaryRange = '';
        state.minExperience = state.profileComplete = '';

        ['searchInput', 'countryFilter', 'statusFilter', 'categoryFilter', 'industryFilter',
         'jobTypeFilter', 'locationFilter', 'experienceLevelFilter', 'educationLevelFilter',
         'salaryRangeFilter', 'profileCompleteFilter', 'minExperienceFilter']
            .forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (typeof jQuery !== 'undefined' && jQuery(el).hasClass('select2-hidden-accessible')) {
                    jQuery(el).val('').trigger('change.select2');
                } else {
                    el.value = '';
                }
            });

        loadSeekers();
    }

    // ─────────────────────────────────────────────────────────────
    // Filters loader
    // ─────────────────────────────────────────────────────────────
    function loadFilters() {
        fetch('/admin/seekers/filters', { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                populate('countryFilter',         data.countries,         'code', 'name',  'All Countries');
                populate('categoryFilter',        data.categories,        'id',   'label', 'All Categories');
                populate('industryFilter',        data.industries,        'id',   'label', 'All Industries');
                populate('jobTypeFilter',         data.job_types,         'id',   'label', 'All Job Types');
                populate('locationFilter',        data.locations,         'id',   'label', 'All Locations');
                populate('experienceLevelFilter', data.experience_levels, 'id',   'label', 'All Experience Levels');
                populate('educationLevelFilter',  data.education_levels,  'id',   'label', 'All Education Levels');
                populate('salaryRangeFilter',     data.salary_ranges,     'id',   'label', 'All Salary Ranges');

                // Re-init Select2 after populating
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery('#advancedFilters select[data-control="select2"]').trigger('change.select2');
                }
            })
            .catch(err => console.error('Failed to load filters:', err));
    }

    function populate(id, items, valueKey, labelKey, placeholder) {
        const el = document.getElementById(id);
        if (!el || !items) return;

        const parts = [`<option value="">${placeholder}</option>`];
        items.forEach(item => {
            parts.push(`<option value="${item[valueKey]}">${escapeHtml(item[labelKey])}</option>`);
        });
        el.innerHTML = parts.join('');

        // If Select2 was initialized, refresh its dropdown
        if (typeof jQuery !== 'undefined' && jQuery(el).hasClass('select2-hidden-accessible')) {
            jQuery(el).trigger('change.select2');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Seekers loader
    // ─────────────────────────────────────────────────────────────
    function loadSeekers() {
        showState('loading');
        updateActiveChips();

        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        Object.entries(state).forEach(([key, value]) => {
            if (key === 'page' || key === 'perPage' || value === '' || value === null) return;
            params.set(mapParamKey(key), value);
        });

        fetch(`/admin/seekers/data?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (!data.data || data.data.length === 0) {
                    showState('empty');
                    return;
                }
                showState('table');
                renderTable(data.data);
                renderPagination(data);
                document.getElementById('totalBadge').textContent = `${data.total} Total`;
            })
            .catch(err => {
                console.error(err);
                showState('empty');
                window.showToast?.('error', 'Failed to load seekers');
            });
    }

    function mapParamKey(key) {
        // Convert camelCase state keys to snake_case query params
        return {
            search: 'search',
            country: 'country',
            status: 'status',
            category: 'job_category_id',
            industry: 'industry_id',
            jobType: 'job_type_id',
            location: 'job_location_id',
            experienceLevel: 'experience_level_id',
            educationLevel: 'education_level_id',
            salaryRange: 'salary_range_id',
            minExperience: 'min_experience',
            profileComplete: 'profile_complete',
        }[key] || key;
    }

    function showState(which) {
        const loading = document.getElementById('loadingSpinner');
        const table   = document.getElementById('tableContainer');
        const empty   = document.getElementById('noDataMessage');
        const pager   = document.getElementById('paginationContainer');

        loading.classList.toggle('d-none', which !== 'loading');
        table.classList.toggle('d-none',   which !== 'table');
        empty.classList.toggle('d-none',   which !== 'empty');
        pager.classList.toggle('d-none',   which !== 'table');
    }

    // ─────────────────────────────────────────────────────────────
    // Active filter chips
    // ─────────────────────────────────────────────────────────────
    function updateActiveChips() {
        const wrapper = document.getElementById('activeFilterChips');
        if (!wrapper) return;

        const labels = {
            search: 'Search',
            country: 'Country',
            status: 'Status',
            category: 'Category',
            industry: 'Industry',
            jobType: 'Job Type',
            location: 'Location',
            experienceLevel: 'Experience',
            educationLevel: 'Education',
            salaryRange: 'Salary',
            minExperience: 'Min Years',
            profileComplete: 'Profile',
        };

        const chips = [];
        Object.entries(state).forEach(([key, value]) => {
            if (key === 'page' || key === 'perPage') return;
            if (value === '' || value === null) return;

            // Resolve display label from the select option text if possible
            let displayValue = value;
            const selectMap = {
                country: 'countryFilter',
                status: 'statusFilter',
                category: 'categoryFilter',
                industry: 'industryFilter',
                jobType: 'jobTypeFilter',
                location: 'locationFilter',
                experienceLevel: 'experienceLevelFilter',
                educationLevel: 'educationLevelFilter',
                salaryRange: 'salaryRangeFilter',
                profileComplete: 'profileCompleteFilter',
            };
            if (selectMap[key]) {
                const opt = document.querySelector(`#${selectMap[key]} option[value="${value}"]`);
                if (opt) displayValue = opt.textContent;
            }

            chips.push(`
                <span class="filter-chip">
                    <span class="text-muted">${labels[key]}:</span>
                    <span>${escapeHtml(displayValue)}</span>
                    <i class="ki-duotone ki-cross fs-5 chip-close" data-clear="${key}">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </span>
            `);
        });

        if (chips.length === 0) {
            wrapper.classList.add('d-none');
            wrapper.innerHTML = '';
            return;
        }

        wrapper.innerHTML = chips.join('');
        wrapper.classList.remove('d-none');

        wrapper.querySelectorAll('[data-clear]').forEach(el => {
            el.addEventListener('click', () => clearSingleFilter(el.getAttribute('data-clear')));
        });
    }

    function clearSingleFilter(stateKey) {
        state[stateKey] = '';
        state.page = 1;

        const fieldMap = {
            search: 'searchInput',
            country: 'countryFilter',
            status: 'statusFilter',
            category: 'categoryFilter',
            industry: 'industryFilter',
            jobType: 'jobTypeFilter',
            location: 'locationFilter',
            experienceLevel: 'experienceLevelFilter',
            educationLevel: 'educationLevelFilter',
            salaryRange: 'salaryRangeFilter',
            minExperience: 'minExperienceFilter',
            profileComplete: 'profileCompleteFilter',
        };
        const el = document.getElementById(fieldMap[stateKey]);
        if (el) {
            if (typeof jQuery !== 'undefined' && jQuery(el).hasClass('select2-hidden-accessible')) {
                jQuery(el).val('').trigger('change.select2');
            } else {
                el.value = '';
            }
        }
        loadSeekers();
    }

    // ─────────────────────────────────────────────────────────────
    // Table rendering
    // ─────────────────────────────────────────────────────────────
    function renderTable(seekers) {
        const tbody = document.getElementById('seekersTableBody');
        tbody.innerHTML = seekers.map(seeker => `
            <tr>
                <td><span class="fw-bold text-gray-800">${seeker.id}</span></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px symbol-circle me-3">
                            <img src="${escapeHtml(seeker.avatar)}" alt="${escapeHtml(seeker.full_name)}" />
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-gray-800 text-hover-primary mb-1">${escapeHtml(seeker.full_name)}</span>
                            <span class="text-muted fs-7">${escapeHtml(seeker.email)}</span>
                            ${seeker.phone ? `<span class="text-muted fs-7">${escapeHtml(seeker.phone)}</span>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    ${seeker.professional_title && seeker.professional_title !== 'N/A'
                        ? `<span class="fw-semibold text-gray-700">${escapeHtml(seeker.professional_title)}</span>`
                        : `<span class="text-muted">-</span>`}
                </td>
                <td>
                    ${seeker.country && seeker.country !== 'N/A'
                        ? `<span class="badge badge-light-info">${escapeHtml(seeker.flag || '')} ${escapeHtml(seeker.country)}</span>`
                        : `<span class="text-muted">-</span>`}
                </td>
                <td>
                    ${seeker.years_of_experience > 0
                        ? `<span class="fw-semibold">${seeker.years_of_experience} yrs</span>`
                        : `<span class="text-muted">-</span>`}
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1 pref-cell">
                        ${prefBadge(seeker.job_category, 'primary')}
                        ${prefBadge(seeker.industry, 'info')}
                        ${prefBadge(seeker.experience_level, 'warning')}
                        ${prefBadge(seeker.education_level, 'success')}
                        ${!seeker.job_category && !seeker.industry && !seeker.experience_level && !seeker.education_level
                            ? `<span class="text-muted fs-7">Not set</span>` : ''}
                    </div>
                </td>
                <td>${seeker.cv_badge}</td>
                <td>${seeker.status_badge}</td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <i class="ki-duotone ki-dots-vertical fs-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" onclick="editSeeker(${seeker.id})">
                                    <i class="ki-duotone ki-pencil fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    Edit Seeker
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="viewSeeker(${seeker.id})">
                                    <i class="ki-duotone ki-eye fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                    View Details
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item" onclick="viewApplications(${seeker.id})">
                                    <i class="ki-duotone ki-briefcase fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                    View Applications
                                    ${seeker.applied_count > 0 ? `<span class="badge badge-light-primary ms-2">${seeker.applied_count}</span>` : ''}
                                </button>
                            </li>
                            ${seeker.has_cv && seeker.cv_files && seeker.cv_files.length
                                ? `<li><hr class="dropdown-divider"></li>
                                   <li>
                                       <a class="dropdown-item" href="${escapeHtml(seeker.cv_files[0].url)}" target="_blank">
                                           <i class="ki-duotone ki-file fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                           Open Primary CV
                                       </a>
                                   </li>`
                                : ''}
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function prefBadge(value, color) {
        if (!value) return '';
        return `<span class="badge badge-light-${color}">${escapeHtml(value)}</span>`;
    }

    // ─────────────────────────────────────────────────────────────
    // Pagination
    // ─────────────────────────────────────────────────────────────
    function renderPagination(data) {
        const el = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        if (!el || !info) return;

        el.innerHTML = '';
        info.textContent = `Showing ${data.from || 0} to ${data.to || 0} of ${data.total} entries`;

        const makeItem = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', e => { e.preventDefault(); changePage(page); });
            } else {
                a.addEventListener('click', e => e.preventDefault());
            }
            li.appendChild(a);
            el.appendChild(li);
        };

        makeItem(data.current_page - 1, 'Prev', false, !data.prev_page_url);

        const start = Math.max(1, data.current_page - 2);
        const end   = Math.min(data.last_page, data.current_page + 2);

        if (start > 1) makeItem(1, '1');
        if (start > 2) el.insertAdjacentHTML('beforeend',
            '<li class="page-item disabled"><span class="page-link">...</span></li>');

        for (let i = start; i <= end; i++) makeItem(i, i, i === data.current_page);

        if (end < data.last_page - 1) el.insertAdjacentHTML('beforeend',
            '<li class="page-item disabled"><span class="page-link">...</span></li>');
        if (end < data.last_page) makeItem(data.last_page, data.last_page);

        makeItem(data.current_page + 1, 'Next', false, !data.next_page_url);
    }

    function changePage(page) {
        if (page === state.page || page < 1) return;
        state.page = page;
        loadSeekers();
    }

    

    window.viewSeeker = function (id) {
        const container = document.getElementById('seekerDetailsContainer');
        container.innerHTML = `<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>`;
        seekerDrawer.show();

        fetch(`/admin/seekers/${id}`, { headers: { 'Accept': 'text/html' } })
            .then(res => res.text())
            .then(html => { container.innerHTML = html; })
            .catch(() => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load seeker details.</div>`;
            });
    };

    window.viewApplications = function (id) {
        const container = document.getElementById('applicationsContainer');
        container.innerHTML = `<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>`;
        appsDrawer.show();

        fetch(`/admin/seekers/${id}/applications`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.data.length) {
                    container.innerHTML = `
                        <div class="text-center py-15">
                            <i class="ki-duotone ki-briefcase fs-5tx text-muted mb-4 d-block"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="fw-bold text-gray-800 mb-2">No applications</h4>
                            <p class="text-muted">This seeker has not applied to any jobs yet.</p>
                        </div>`;
                    return;
                }

                const statusMap = {
                    applied:      { color: 'primary', label: 'Applied' },
                    interviewing: { color: 'warning', label: 'Interviewing' },
                    hired:        { color: 'success', label: 'Hired' },
                    rejected:     { color: 'danger',  label: 'Rejected' },
                };

                const rows = data.data.map(app => {
                    const meta = statusMap[app.status] || { color: 'secondary', label: app.status };
                    const applied = app.applied_at
                        ? new Date(app.applied_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                        : '-';

                    return `
                        <tr>
                            <td class="fw-bold text-gray-800">${escapeHtml(app.job_title)}</td>
                            <td>${escapeHtml(app.company_name)}</td>
                            <td>${escapeHtml(app.location)}</td>
                            <td class="text-muted">${applied}</td>
                            <td><span class="badge badge-light-${meta.color}">${meta.label}</span></td>
                        </tr>`;
                }).join('');

                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase">
                                    <th>Job Title</th><th>Company</th><th>Location</th><th>Applied</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>`;
            })
            .catch(() => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load applications.</div>`;
            });
    };

    document.getElementById('addSeekerBtn')?.addEventListener('click', () => openEditDrawer(null));

    // ─────────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────────
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
</script>


<script>
    // ────────────────────────────────────────────────────────────
    // Create / Edit drawer  (self-contained — re-creates drawer ref)
    // ────────────────────────────────────────────────────────────
    const editDrawerEl = document.getElementById('kt_drawer_edit_seeker');
    const editDrawer   = bootstrap.Offcanvas.getOrCreateInstance(editDrawerEl);

    window.openEditDrawer = function (id) {
        const container = document.getElementById('editSeekerContainer');
        const titleEl   = document.getElementById('editDrawerTitle');
        const modeEl    = document.getElementById('editDrawerMode');

        container.innerHTML = `<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>`;
        titleEl.textContent = id ? 'Edit Job Seeker' : 'Add Job Seeker';
        modeEl.classList.toggle('d-none', !id);
        modeEl.textContent = id ? 'Edit' : '';
        editDrawer.show();

        const url = id
            ? `/admin/seekers/${id}/edit-form`
            : `/admin/seekers/create-form`;

        fetch(url, { headers: { 'Accept': 'text/html' } })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;

                // ★ re-wire the CV / Academic buttons after the DOM is in place ★
                window.wireSeekerFileManagers?.();
            })
            .catch(() => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load form.</div>`;
            });
    };

    window.editSeeker = function (id) { openEditDrawer(id); };

    document.getElementById('saveSeekerBtn')?.addEventListener('click', async function () {
        const container = document.getElementById('editSeekerContainer');
        const form      = container.querySelector('#seekerForm');
        if (!form) return;

        const formData = new FormData(form);
        const seekerId = formData.get('seeker_id');
        const isEdit   = !!seekerId;
        const url      = isEdit ? `/admin/seekers/${seekerId}` : `/admin/seekers`;

        // Checkbox → boolean (unchecked boxes don't submit)
        ['alert_all_jobs', 'alert_by_skill', 'alert_by_whatsapp', 'alert_by_email']
            .forEach(name => {
                if (!formData.has(name)) formData.append(name, '0');
            });

        const btn      = this;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        btn.disabled = true;
        label.classList.add('d-none');
        progress.classList.remove('d-none');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: formData,
            });
            const data = await res.json();

            if (data.success) {
                window.showToast?.('success', data.message || 'Saved.');

                if (!isEdit) {
                    // Just created — reload table so the new row appears.
                    editDrawer.hide();
                    (window.reloadSeekersFirstPage || window.reloadSeekers)?.();
                } else {
                    // Updated — keep drawer open so admin can move to the Files tab.
                    // Just reload the table in the background.
                    window.reloadSeekers?.();
                    window.showToast?.('info', 'You can now upload CVs from the Files tab.');
                }
            } else {
                const msg = data.message
                    || Object.values(data.errors || {}).flat().join('\n')
                    || 'Save failed.';
                window.showToast?.('error', msg);
            }
        } catch (err) {
            console.error(err);
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            label.classList.remove('d-none');
            progress.classList.add('d-none');
        }
    });

    function wireAlertCategories() {
        const container = document.getElementById('sfAlertCategories');
        if (!container || container.dataset.wired) return;
        container.dataset.wired = '1';

        // Local helper — this script block has no escapeHtml of its own
        const escapeHtml = (t) => {
            if (t === null || t === undefined) return '';
            const d = document.createElement('div');
            d.textContent = String(t);
            return d.innerHTML;
        };

        const seekerId = document.querySelector('#seekerForm input[name="seeker_id"]')?.value;
        if (!seekerId) {
            container.innerHTML = '<div class="col-12 text-muted fs-7">Save the seeker first, then set alerts.</div>';
            return;
        }

        const countryLabel = document.getElementById('sfAlertCountry');

        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

        fetch(`/admin/seekers/${seekerId}/categories`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="col-12 alert alert-warning mb-0">Could not load categories.</div>';
                    return;
                }

                if (countryLabel) countryLabel.textContent = data.country || '—';

                const cats = data.categories || [];
                const selected = new Set((data.selected || []).map(Number));

                if (cats.length === 0) {
                    container.innerHTML = '<div class="col-12 text-muted fs-7">No categories available for this country.</div>';
                    return;
                }

                container.innerHTML = cats.map(cat => `
                    <div class="col-md-4 col-lg-3">
                        <label class="d-flex align-items-center gap-2 p-3 bg-light rounded border border-gray-200 w-100"
                            style="cursor:pointer;">
                            <input type="checkbox"
                                class="form-check-input form-check-input-sm m-0"
                                name="alert_by_category[]"
                                value="${cat.id}"
                                ${selected.has(cat.id) ? 'checked' : ''}>
                            <span class="fs-7 fw-semibold">${escapeHtml(cat.label)}</span>
                        </label>
                    </div>
                `).join('');
            })
            .catch(err => {
                console.error('loadCategories failed', err);
                container.innerHTML = '<div class="col-12 alert alert-danger mb-0">Network error loading categories.</div>';
            });

        // Select-all / clear helpers
        document.getElementById('sfSelectAllCats')?.addEventListener('click', () => {
            container.querySelectorAll('input[name="alert_by_category[]"]').forEach(c => c.checked = true);
        });
        document.getElementById('sfClearAllCats')?.addEventListener('click', () => {
            container.querySelectorAll('input[name="alert_by_category[]"]').forEach(c => c.checked = false);
        });
    }

</script>


<script>
(function () {
    'use strict';

    const CSRF = '{{ csrf_token() }}';

    // ── CV upload ─────────────────────────────────────────────
    function wireCvManager() {
        const mgr = document.getElementById('cvManager');
        if (!mgr) return;

        const seekerId = mgr.dataset.seekerId;
        const input    = document.getElementById('cvUploadInput');
        const btn      = document.getElementById('cvUploadBtn');
        const badge    = document.getElementById('cvCountBadge');
        const statusEl = document.getElementById('cvAiStatus');
        const listEl   = document.getElementById('cvFileList');

        // Guard: prevent double-binding if this function runs twice
        if (!btn || btn._wired) return;
        btn._wired = true;

        btn.addEventListener('click', () => {
            input.value = '';
            input.click();
        });

        input.addEventListener('change', async function () {
            if (!this.files.length) return;
            const file = this.files[0];

            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading & extracting…';
            if (statusEl) statusEl.innerHTML = '';

            const fd = new FormData();
            fd.append('cv', file);
            fd.append('_token', CSRF);

            try {
                const res = await fetch(`/admin/seekers/${seekerId}/cv`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();

                if (data.success) {
                    window.showToast?.('success', data.message || 'CV uploaded.');

                    if (statusEl) {
                        const icon = data.ai_status === 'ok'
                            ? '<i class="ki-duotone ki-check-circle fs-2 text-success"><span class="path1"></span><span class="path2"></span></i>'
                            : '<i class="ki-duotone ki-information-5 fs-2 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
                        statusEl.innerHTML = `<div class="alert alert-light-${data.ai_status === 'ok' ? 'success' : 'warning'} d-flex align-items-center gap-3 mb-0">${icon}<div class="fs-7">${data.message}</div></div>`;
                    }

                    if (Array.isArray(data.cv_files)) {
                        if (badge) badge.textContent = `${data.cv_files.length}/2`;
                        if (listEl) {
                            if (data.cv_files.length === 0) {
                                listEl.innerHTML = '<div class="text-muted fs-7 py-4 text-center">No CV on file yet.</div>';
                            } else {
                                listEl.innerHTML = data.cv_files.map(cv => `
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded" data-cv-path="${cv.path}">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ki-duotone ki-file fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                            <div>
                                                <div class="fw-bold fs-7">${escapeHtml(cv.original_name || 'CV')}</div>
                                                <div class="text-muted fs-8">${cv.size ? (cv.size / 1024).toFixed(1) + ' KB' : ''}</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger delete-cv-btn" data-path="${cv.path}" title="Delete CV">
                                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                        </button>
                                    </div>`).join('');
                            }
                            wireDeleteCvButtons();
                        }
                    }

                    btn.disabled = data.cv_files && data.cv_files.length >= 2;

                    if (data.profile) refreshFormFromProfile(data.profile);
                } else {
                    window.showToast?.('error', data.message || 'Upload failed.');
                }
            } catch (err) {
                console.error(err);
                window.showToast?.('error', 'Network error.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    }

    // ── CV delete ─────────────────────────────────────────────
    function wireDeleteCvButtons() {
        document.querySelectorAll('.delete-cv-btn').forEach(btn => {
            if (btn._wired) return;
            btn._wired = true;

            btn.addEventListener('click', async function () {
                const mgr      = document.getElementById('cvManager');
                const seekerId = mgr?.dataset.seekerId;
                const path     = this.dataset.path;
                if (!seekerId || !path) return;
                if (!confirm('Delete this CV?')) return;

                this.disabled = true;
                try {
                    const res = await fetch(`/admin/seekers/${seekerId}/cv?path=${encodeURIComponent(path)}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.showToast?.('success', 'CV deleted.');
                        const badge  = document.getElementById('cvCountBadge');
                        const listEl = document.getElementById('cvFileList');
                        if (badge && Array.isArray(data.cv_files)) badge.textContent = `${data.cv_files.length}/2`;
                        if (listEl) {
                            if (!data.cv_files || data.cv_files.length === 0) {
                                listEl.innerHTML = '<div class="text-muted fs-7 py-4 text-center">No CV on file yet.</div>';
                            } else {
                                listEl.innerHTML = data.cv_files.map(cv => `
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded" data-cv-path="${cv.path}">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="ki-duotone ki-file fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                            <div>
                                                <div class="fw-bold fs-7">${escapeHtml(cv.original_name || 'CV')}</div>
                                                <div class="text-muted fs-8">${cv.size ? (cv.size / 1024).toFixed(1) + ' KB' : ''}</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger delete-cv-btn" data-path="${cv.path}" title="Delete CV">
                                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                        </button>
                                    </div>`).join('');
                            }
                            wireDeleteCvButtons();
                        }
                    } else {
                        window.showToast?.('error', data.message || 'Delete failed.');
                        this.disabled = false;
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast?.('error', 'Network error.');
                    this.disabled = false;
                }
            });
        });
    }

    // ── Re-extract ────────────────────────────────────────────
    async function handleReExtract() {
        const mgr      = document.getElementById('cvManager');
        const seekerId = mgr?.dataset.seekerId;
        if (!seekerId) return;
        if (!confirm('Re-run AI extraction on the stored CV? Blank profile fields will be filled — existing values stay.')) return;

        const btn = this;
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Extracting…';

        try {
            const res = await fetch(`/admin/seekers/${seekerId}/re-extract`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success) {
                window.showToast?.('error', data.message || 'Extraction failed.');
                return;
            }

            const form = document.getElementById('seekerForm');
            const ext  = data.extracted || {};
            const setIfEmpty = (name, value) => {
                if (!value) return;
                const el = form?.querySelector(`[name="${name}"]`);
                if (el && (!el.value || el.value === '')) el.value = value;
            };

            ['professional_title','professional_summary','phone','city','postal_code',
             'nationality','date_of_birth','years_of_experience','linkedin_url',
             'github_url','portfolio_url'].forEach(f => setIfEmpty(f, ext[f]));

            const skillsEl = form?.querySelector('[name="skills"]');
            if (skillsEl && !skillsEl.value && Array.isArray(ext.skills)) skillsEl.value = ext.skills.join(', ');

            const langsEl = form?.querySelector('[name="languages"]');
            if (langsEl && !langsEl.value && Array.isArray(ext.languages)) langsEl.value = ext.languages.join(', ');

            window.showToast?.('success', 'Extracted — review the form and click Save Seeker.');
        } catch (err) {
            console.error(err);
            window.showToast?.('error', 'Network error.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }

    // ── Academic upload/delete ────────────────────────────────
    function wireAcademicManager() {
        const mgr = document.getElementById('academicManager');
        if (!mgr) return;

        const seekerId = mgr.dataset.seekerId;
        const input    = document.getElementById('academicUploadInput');
        const btn      = document.getElementById('academicUploadBtn');
        const delBtn   = document.querySelector('.delete-academic-btn');

        if (btn && !btn._wired) {
            btn._wired = true;
            btn.addEventListener('click', () => { input.value = ''; input.click(); });

            input.addEventListener('change', async function () {
                if (!this.files.length) return;
                const file = this.files[0];

                btn.disabled = true;
                const original = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading…';

                const fd = new FormData();
                fd.append('academic_document', file);
                fd.append('_token', CSRF);

                try {
                    const res = await fetch(`/admin/seekers/${seekerId}/academic`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: fd,
                    });
                    const data = await res.json();

                    if (data.success) {
                        window.showToast?.('success', data.message || 'Uploaded.');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        window.showToast?.('error', data.message || 'Upload failed.');
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast?.('error', 'Network error.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            });
        }

        if (delBtn && !delBtn._wired) {
            delBtn._wired = true;
            delBtn.addEventListener('click', async function () {
                if (!confirm('Delete the academic document?')) return;
                this.disabled = true;

                try {
                    const res = await fetch(`/admin/seekers/${seekerId}/academic`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.showToast?.('success', 'Deleted.');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        window.showToast?.('error', data.message || 'Delete failed.');
                        this.disabled = false;
                    }
                } catch (err) {
                    console.error(err);
                    window.showToast?.('error', 'Network error.');
                    this.disabled = false;
                }
            });
        }
    }

    // ── Sync extracted fields back into the form ─────────────
    function refreshFormFromProfile(profile) {
        const form = document.getElementById('seekerForm');
        if (!form || !profile) return;

        const set = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (el && (!el.value || el.value === '')) el.value = value ?? '';
        };

        ['professional_title','professional_summary','phone','city','postal_code',
         'nationality','date_of_birth','years_of_experience','linkedin_url',
         'github_url','portfolio_url'].forEach(f => set(f, profile[f]));

        if (Array.isArray(profile.skills)) {
            const el = form.querySelector('[name="skills"]');
            if (el && !el.value) el.value = profile.skills.join(', ');
        }
        if (Array.isArray(profile.languages)) {
            const el = form.querySelector('[name="languages"]');
            if (el && !el.value) el.value = profile.languages.join(', ');
        }
    }

    // ── Helper ────────────────────────────────────────────────
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    // ── Public re-wire hook ───────────────────────────────────
    window.wireSeekerFileManagers = function () {
        wireCvManager();
        wireDeleteCvButtons();
        wireAcademicManager();
        wireAlertCategories();

        const reBtn = document.getElementById('reExtractCvBtn');
        if (reBtn && !reBtn._wired) {
            reBtn._wired = true;
            reBtn.addEventListener('click', handleReExtract);
        }
    };

    // Safe initial call (no-op if the drawer is closed)
    window.wireSeekerFileManagers();
    // ── Export for the drawer save handler ────────────────────
    window.reloadSeekers = loadSeekers;
    window.reloadSeekersFirstPage = () => { state.page = 1; loadSeekers(); };
})();
</script>

@endpush