@php
    $isEdit = $mode === 'edit';
    $p      = $seeker;

    $cvFiles     = $p && is_array($p->cv_files) ? $p->cv_files : [];
    $hasAcademic = $p?->academic_documents_path;

    $prefs = $p?->job_preferences ?? [];
    if (is_string($prefs)) $prefs = json_decode($prefs, true) ?: [];
@endphp

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- FORM (Account / Profile / Job Preferences / Alerts)        --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<form id="seekerForm">
    @if($isEdit)
        <input type="hidden" name="seeker_id" value="{{ $p->id }}">
    @endif

    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-5" id="seekerFormTabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#sf_account">Account</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sf_profile">Profile</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sf_prefs">Job Preferences</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sf_alerts">Alerts</a></li>
        @if($isEdit)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#sf_files">
                    CV &amp; Documents
                    @if(count($cvFiles) || $hasAcademic)
                        <span class="bullet bullet-dot bg-success ms-1"></span>
                    @endif
                </a>
            </li>
        @endif
    </ul>

    <div class="tab-content">

        {{-- ── ACCOUNT ────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="sf_account">
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="required fw-semibold fs-7 mb-1">Country</label>
                    <select name="country" id="sf_country" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select country…</option>
                        @foreach($filters['countries'] as $c)
                            <option value="{{ $c['code'] }}" @selected(($country ?? $p?->country) === $c['code'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="text-muted fs-8 mt-1">This scopes the Job Preferences options below.</div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-6"><label class="required fw-semibold fs-7 mb-1">First Name</label>
                    <input type="text" name="first_name" class="form-control form-control-solid" required value="{{ $p?->first_name }}"></div>
                <div class="col-md-6"><label class="required fw-semibold fs-7 mb-1">Last Name</label>
                    <input type="text" name="last_name" class="form-control form-control-solid" required value="{{ $p?->last_name }}"></div>
                <div class="col-md-6"><label class="required fw-semibold fs-7 mb-1">Email</label>
                    <input type="email" name="email" class="form-control form-control-solid" required value="{{ $p?->email }}"></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Phone</label>
                    <input type="text" name="phone" class="form-control form-control-solid" value="{{ $p?->phone }}"></div>
            </div>
        </div>

        {{-- ── PROFILE ────────────────────────────────────── --}}
        <div class="tab-pane fade" id="sf_profile">
            <div class="row g-4">
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Professional Title</label>
                    <input type="text" name="professional_title" class="form-control form-control-solid" value="{{ $p?->professional_title }}"></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Years of Experience</label>
                    <input type="number" name="years_of_experience" min="0" class="form-control form-control-solid" value="{{ $p?->years_of_experience }}"></div>
                <div class="col-12"><label class="fw-semibold fs-7 mb-1">Professional Summary</label>
                    <textarea name="professional_summary" rows="3" class="form-control form-control-solid">{{ $p?->professional_summary }}</textarea></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">City</label>
                    <input type="text" name="city" class="form-control form-control-solid" value="{{ $p?->city }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control form-control-solid" value="{{ $p?->postal_code }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control form-control-solid" value="{{ $p?->date_of_birth?->format('Y-m-d') }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Nationality</label>
                    <input type="text" name="nationality" class="form-control form-control-solid" value="{{ $p?->nationality }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">LinkedIn</label>
                    <input type="text" name="linkedin_url" class="form-control form-control-solid" value="{{ $p?->linkedin_url }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">GitHub</label>
                    <input type="text" name="github_url" class="form-control form-control-solid" value="{{ $p?->github_url }}"></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Portfolio</label>
                    <input type="text" name="portfolio_url" class="form-control form-control-solid" value="{{ $p?->portfolio_url }}"></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Skills</label>
                    <input type="text" name="skills" placeholder="PHP, Laravel, JS" class="form-control form-control-solid"
                           value="{{ is_array($p?->skills) ? implode(', ', $p->skills) : '' }}"></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Languages</label>
                    <input type="text" name="languages" placeholder="English, Swahili" class="form-control form-control-solid"
                           value="{{ is_array($p?->languages) ? implode(', ', $p->languages) : '' }}"></div>
            </div>
        </div>

        {{-- ── JOB PREFERENCES ────────────────────────────── --}}
        <div class="tab-pane fade" id="sf_prefs">
            <div class="alert alert-light-primary d-flex align-items-center gap-3 mb-5">
                <i class="ki-duotone ki-information-5 fs-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                <div class="fs-7">
                    The options below are filtered by <strong id="sfCountryLabel">{{ $country ?? 'UG' }}</strong>.
                    Change the country in the Account tab to load a different set.
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Job Category</label>
                    <select name="job_category_id" id="sf_category" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select category…</option>
                        @foreach($filters['categories'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->job_category_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Industry</label>
                    <select name="industry_id" id="sf_industry" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select industry…</option>
                        @foreach($filters['industries'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->industry_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Job Type</label>
                    <select name="job_type_id" id="sf_job_type" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select job type…</option>
                        @foreach($filters['job_types'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->job_type_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="fw-semibold fs-7 mb-1">Location</label>
                    <select name="job_location_id" id="sf_location" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select location…</option>
                        @foreach($filters['locations'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->job_location_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Experience Level</label>
                    <select name="experience_level_id" id="sf_experience_level" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select experience level…</option>
                        @foreach($filters['experience_levels'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->experience_level_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Education Level</label>
                    <select name="education_level_id" id="sf_education_level" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select education level…</option>
                        @foreach($filters['education_levels'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->education_level_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="fw-semibold fs-7 mb-1">Salary Range</label>
                    <select name="salary_range_id" id="sf_salary_range" class="form-select form-select-solid" data-control="select2">
                        <option value="">Select salary range…</option>
                        @foreach($filters['salary_ranges'] as $c)
                            <option value="{{ $c['id'] }}" @selected($p?->salary_range_id == $c['id'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select></div>
            </div>
        </div>

        {{-- ── ALERTS ─────────────────────────────────────── --}}
        <div class="tab-pane fade" id="sf_alerts">
            <div class="alert alert-light-primary d-flex align-items-center gap-3 mb-5">
                <i class="ki-duotone ki-information-5 fs-2 text-primary">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div class="fs-7">
                    Tick the job categories this seeker wants to be alerted about.
                    Categories are filtered to <strong id="sfAlertCountry">—</strong>.
                    These are the same categories the seeker sees on their own Alert Preferences page.
                </div>
            </div>

            {{-- Categories --}}
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="fw-semibold fs-6 mb-0">Alert me about jobs in these categories</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" id="sfSelectAllCats">Select all</button>
                        <button type="button" class="btn btn-sm btn-light" id="sfClearAllCats">Clear</button>
                    </div>
                </div>

                <div id="sfAlertCategories" class="row g-3">
                    <div class="col-12 text-muted fs-7">Loading categories…</div>
                </div>
            </div>

            {{-- Threshold --}}
            <div class="mb-5">
                <label class="fw-semibold fs-6 mb-1">Minimum skill match threshold</label>
                <div class="text-muted fs-8 mb-3">
                    Only alert this seeker when a job matches at least this much of their skills.
                </div>
                <select name="alert_threshold" id="sf_alert_threshold"
                        class="form-select form-select-solid w-150px">
                    @foreach([50, 60, 70, 80, 90] as $t)
                        <option value="{{ $t }}"
                            @selected((int) ($prefs['skill_match_threshold'] ?? 60) === $t)>
                            {{ $t }}%
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Channels --}}
            <div class="mb-3">
                <label class="fw-semibold fs-6 mb-3 d-block">How should we reach them?</label>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-check form-check-custom form-check-solid p-3 bg-light rounded w-100">
                            <input class="form-check-input" type="checkbox" name="alert_by_whatsapp" value="1"
                                @checked(!empty($prefs['send_alert_by_whatsapp']))>
                            <span class="form-check-label fw-semibold ms-3">WhatsApp</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check form-check-custom form-check-solid p-3 bg-light rounded w-100">
                            <input class="form-check-input" type="checkbox" name="alert_by_email" value="1"
                                @checked($prefs['send_alert_by_email'] ?? true)>
                            <span class="form-check-label fw-semibold ms-3">Email</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check form-check-custom form-check-solid p-3 bg-light rounded w-100">
                            <input class="form-check-input" type="checkbox" name="alert_by_telegram" value="1"
                                @checked(!empty($prefs['send_alert_by_telegram']))>
                            <span class="form-check-label fw-semibold ms-3">Telegram</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── FILES TAB (edit mode only, but content sits OUTSIDE the form) ── --}}
        @if($isEdit)
            <div class="tab-pane fade" id="sf_files">
                {{-- This entire block is rendered by an external partial to keep it out of <form> --}}
                @include('job-seeker.partials.files-manager', [
                    'seeker'      => $p,
                    'cvFiles'     => $cvFiles,
                    'hasAcademic' => $hasAcademic,
                ])
            </div>
        @endif
    </div>
</form>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- SELECT2 wiring (unchanged behaviour)                       --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
        console.warn('Select2 not loaded — dropdowns will render as plain selects.');
        return;
    }

    function pickDropdownParent() {
        const $form = jQuery('#seekerForm');
        const $drawer = jQuery('#kt_drawer_edit_seeker');
        if ($drawer.length && $drawer.is(':visible')) return $drawer;

        const $ancestor = $form.closest('.offcanvas, .modal, .card, .content');
        if ($ancestor.length) return $ancestor.first();

        return jQuery('body');
    }

    let $dropdownParent = pickDropdownParent();

    jQuery('#kt_drawer_edit_seeker').on('shown.bs.offcanvas', function () {
        $dropdownParent = jQuery(this);
    });

    function initSelect2($scope) {
        $scope.find('select[data-control="select2"]').each(function () {
            const $el = jQuery(this);
            if ($el.data('select2')) return;
            $el.select2({
                placeholder: $el.find('option[value=""]').text().trim() || 'Select…',
                allowClear: true,
                width: '100%',
                dropdownParent: $dropdownParent,
            });
        });
    }

    initSelect2(jQuery('#seekerForm'));

    function repopulate(selector, items, placeholder) {
        const $select = jQuery(selector);
        if (!$select.length) return;

        const current = $select.val();
        if ($select.data('select2')) $select.select2('destroy');

        const valueKey = items && items.length && 'code' in items[0] ? 'code' : 'id';
        const opts = [`<option value="">${placeholder}</option>`];
        (items || []).forEach(it => {
            const sel = String(it[valueKey]) === String(current) ? ' selected' : '';
            opts.push(`<option value="${it[valueKey]}"${sel}>${escapeHtml(it['label'])}</option>`);
        });
        $select.html(opts.join(''));

        $dropdownParent = pickDropdownParent();
        $select.select2({ placeholder, allowClear: true, width: '100%', dropdownParent: $dropdownParent });
    }

    jQuery('#sf_country').on('change', function () {
        const country = jQuery(this).val();
        jQuery('#sfCountryLabel').text(country || '—');
        if (!country) return;

        jQuery.ajax({
            url: '{{ route('admin.seekers.filters-by-country') }}',
            data: { country },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                if (!res.success) return;
                const f = res.filters;
                repopulate('#sf_category',         f.categories,        'Select category…');
                repopulate('#sf_industry',         f.industries,        'Select industry…');
                repopulate('#sf_job_type',         f.job_types,         'Select job type…');
                repopulate('#sf_location',         f.locations,         'Select location…');
                repopulate('#sf_experience_level', f.experience_levels, 'Select experience level…');
                repopulate('#sf_education_level',  f.education_levels,  'Select education level…');
                repopulate('#sf_salary_range',     f.salary_ranges,     'Select salary range…');
            },
            error: function () {
                window.showToast?.('error', 'Could not load country-specific options.');
            },
        });
    });

    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
})();
</script>