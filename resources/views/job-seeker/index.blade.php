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
                            <th class="min-w-90px">Applied</th>
                            <th class="min-w-90px">Saved</th>
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

{{-- ============================================================== --}}
{{-- SEEKER DETAILS MODAL                                             --}}
{{-- ============================================================== --}}
<div class="modal fade" id="kt_modal_view_seeker" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold mb-0">Seeker Details</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body" id="seekerDetailsContainer">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================== --}}
{{-- APPLICATIONS MODAL                                               --}}
{{-- ============================================================== --}}
<div class="modal fade" id="kt_modal_view_applications" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold mb-0">Job Applications</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            <div class="modal-body" id="applicationsContainer">
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
                <td>
                    ${seeker.applied_count > 0
                        ? `<span class="badge badge-light-primary">${seeker.applied_count}</span>`
                        : `<span class="text-muted">0</span>`}
                </td>
                <td>
                    ${seeker.saved_count > 0
                        ? `<span class="badge badge-light-warning">${seeker.saved_count}</span>`
                        : `<span class="text-muted">0</span>`}
                </td>
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

    // ─────────────────────────────────────────────────────────────
    // Modals
    // ─────────────────────────────────────────────────────────────
    window.viewSeeker = function (id) {
        const modalEl = document.getElementById('kt_modal_view_seeker');
        const container = document.getElementById('seekerDetailsContainer');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        container.innerHTML = `
            <div class="text-center py-10">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;
        modal.show();

        fetch(`/admin/seekers/${id}`, { headers: { 'Accept': 'text/html' } })
            .then(res => res.text())
            .then(html => { container.innerHTML = html; })
            .catch(() => {
                container.innerHTML = `<div class="alert alert-danger">Failed to load seeker details.</div>`;
            });
    };

    window.viewApplications = function (id) {
        const modalEl = document.getElementById('kt_modal_view_applications');
        const container = document.getElementById('applicationsContainer');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        container.innerHTML = `
            <div class="text-center py-10">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;
        modal.show();

        fetch(`/admin/seekers/${id}/applications`, { headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.data.length) {
                    container.innerHTML = `
                        <div class="text-center py-15">
                            <i class="ki-duotone ki-briefcase fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
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
                        ? new Date(app.applied_at).toLocaleDateString('en-US',
                            { month: 'short', day: 'numeric', year: 'numeric' })
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
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Applied</th>
                                    <th>Status</th>
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
@endpush