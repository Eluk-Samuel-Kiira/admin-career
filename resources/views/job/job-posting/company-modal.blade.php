{{-- ============================================================ --}}
{{-- QUICK ADD COMPANY MODAL (inline, from job posting page)      --}}
{{-- ============================================================ --}}
@can('create company')
<div class="modal fade" id="kt_modal_quick_add_company" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold mb-0">Quick Add Company</h2>
                    <span class="text-muted fs-7">Add a missing company without leaving this page.</span>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-3 my-4">
                <form id="quickAddCompanyForm" enctype="multipart/form-data">
                    @csrf

                    {{-- Company Name + Country --}}
                    <div class="row mb-5">
                        <div class="col-md-7">
                            <label class="required fw-semibold fs-6 mb-2">Company Name</label>
                            <input type="text" class="form-control form-control-solid"
                                   name="name" id="qac_name" placeholder="e.g. Acme Corp" required>
                        </div>
                        <div class="col-md-5">
                            <label class="required fw-semibold fs-6 mb-2">Country</label>
                            <select class="form-select form-select-solid"
                                    name="country_code" id="qac_country_code" required>
                                <option value="">Select Country</option>
                                @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                                    <option value="{{ $country['code'] }}">
                                        {{ $country['flag'] }} {{ $country['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Industry + Location --}}
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Industry</label>
                            <div class="searchable-select" id="qac_industry_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                       id="qac_industry_search" placeholder="Type to search industry..." autocomplete="off">
                                <input type="hidden" name="industry_id" id="qac_industry_id" value="">
                                <div class="searchable-select-dropdown" id="qac_industry_dropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="required fw-semibold fs-6 mb-2">Location</label>
                            <div class="searchable-select" id="qac_location_wrapper">
                                <input type="text" class="form-control form-control-solid searchable-select-input"
                                       id="qac_location_search" placeholder="Type to search location..." autocomplete="off">
                                <input type="hidden" name="location_id" id="qac_location_id" value="">
                                <div class="searchable-select-dropdown" id="qac_location_dropdown"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Website --}}
                    <div class="fv-row mb-5">
                        <label class="required fw-semibold fs-6 mb-2">Website</label>
                        <input type="url" class="form-control form-control-solid"
                               name="website" id="qac_website" placeholder="https://example.com" required>
                    </div>

                    {{-- Logo --}}
                    <div class="fv-row mb-5">
                        <label class="required fw-semibold fs-6 mb-2">Logo</label>
                        <div id="qac_logo_preview" class="mb-2" style="display:none;">
                            <img id="qac_logo_image" src="" alt="Logo Preview"
                                 style="max-width:90px;max-height:90px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;" />
                            <button type="button" class="btn btn-sm btn-light-danger ms-2" onclick="clearQacLogo()">
                                <i class="ki-duotone ki-cross fs-2"></i> Remove
                            </button>
                        </div>
                        <input type="file" class="form-control form-control-solid"
                               name="logo" id="qac_logo_input"
                               accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml,image/webp" required>
                        <div class="text-muted fs-7 mt-1">JPEG, PNG, JPG, GIF, SVG, WEBP — max 2MB.</div>
                    </div>

                    {{-- Hidden sensible defaults (kept so CompanyController@store validation passes) --}}
                    <input type="hidden" name="is_active" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="qac_submitBtn" onclick="submitQuickAddCompany()">
                    <span class="indicator-label">
                        <i class="ki-duotone ki-check fs-3 me-1"></i> Save &amp; Select
                    </span>
                    <span class="indicator-progress">
                        Saving...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endcan
<script>
// ================================================================
// QUICK ADD COMPANY (inline from job posting page)
// ================================================================

let qacIndustryOptions = [];
let qacLocationOptions = [];

function openQuickAddCompanyModal() {
    // Prefill country from the job form
    const jobCountry = document.getElementById('f_country_code')?.value || 'AU';
    const countrySelect = document.getElementById('qac_country_code');
    if (countrySelect) countrySelect.value = jobCountry;

    // Reset form + state
    const form = document.getElementById('quickAddCompanyForm');
    if (form) form.reset();
    if (countrySelect) countrySelect.value = jobCountry;

    // Wipe only the visible search text + hidden ids.
    // Do NOT wipe dropdown.innerHTML - we re-render right after fetch anyway.
    resetQacSelect('qac_industry');
    resetQacSelect('qac_location');

    document.getElementById('qac_logo_preview').style.display = 'none';
    document.getElementById('qac_logo_image').src = '';

    // Show the modal FIRST so Bootstrap finishes its animation,
    // then load options. This gives getBoundingClientRect() a stable
    // container to measure against when the dropdown opens.
    bsModal('kt_modal_quick_add_company').show();

    // Load options after the modal has begun rendering
    setTimeout(() => loadQacFormData(jobCountry), 250);
}

function resetQacSelect(prefix) {
    const hidden = document.getElementById(`${prefix}_id`);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) hidden.value = '';
    if (search) search.value = '';

    // Seed an empty list so the focus handler doesn't show stale data
    searchableSelectData[prefix] = [];
}

function loadQacFormData(countryCode) {
    fetch(`/admin/companies/form-data?country=${countryCode}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        qacIndustryOptions = (data.industries || []).map(i => ({ id: i.id, label: i.name }));
        qacLocationOptions = (data.locations || []).map(l => ({
            id: l.id,
            label: l.city ? `${l.district} (${l.city})` : l.district
        }));

        // Register into the shared store so the global filter handlers see it
        searchableSelectData['qac_industry'] = qacIndustryOptions;
        searchableSelectData['qac_location'] = qacLocationOptions;

        // Render immediately so the dropdown has content the moment
        // the user clicks in (no need to wait for the focus event).
        renderQacDropdown('qac_industry', qacIndustryOptions);
        renderQacDropdown('qac_location', qacLocationOptions);

        // If a search box already has focus (user clicked fast), refresh it now
        const active = document.activeElement;
        if (active && active.classList.contains('searchable-select-input')) {
            const pfx = active.id.replace('_search', '');
            if (pfx === 'qac_industry' || pfx === 'qac_location') {
                const items = searchableSelectData[pfx] || [];
                const term  = active.value.trim().toLowerCase();
                const list  = term ? items.filter(i => i.label.toLowerCase().includes(term)) : items;
                renderQacDropdown(pfx, list);
            }
        }
    })
    .catch(err => console.error('Quick-add: failed to load form data', err));
}

// Dedicated renderer for the QAC dropdowns - avoids the inline-onclick
// escaping pitfalls of the shared renderer and makes option clicks
// data-driven instead of string-interpolated.
function renderQacDropdown(prefix, items) {
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!dropdown) return;

    if (!items || items.length === 0) {
        dropdown.innerHTML = '<div class="searchable-select-empty">No matches found</div>';
        return;
    }

    dropdown.innerHTML = items.map(item => `
        <div class="searchable-select-option"
             data-qac-id="${String(item.id).replace(/"/g,'&quot;')}"
             data-qac-label="${String(item.label).replace(/"/g,'&quot;')}">
            ${String(item.label).replace(/</g,'&lt;').replace(/>/g,'&gt;')}
        </div>
    `).join('');
}

// Delegated click for QAC dropdown options - works even if innerHTML
// was rebuilt several times, unlike inline onclick which gets wiped.
document.addEventListener('click', function (e) {
    const opt = e.target.closest('#qac_industry_dropdown .searchable-select-option, #qac_location_dropdown .searchable-select-option');
    if (!opt) return;

    const dropdown = opt.closest('.searchable-select-dropdown');
    const prefix   = dropdown.id.replace('_dropdown', '');

    const hidden = document.getElementById(`${prefix}_id`);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) hidden.value = opt.dataset.qacId;
    if (search) search.value = opt.dataset.qacLabel;

    dropdown.classList.remove('show');
});

// Filter QAC dropdowns as the user types, independent of the global handler
document.addEventListener('input', function (e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    if (prefix !== 'qac_industry' && prefix !== 'qac_location') return;

    const items = searchableSelectData[prefix] || [];
    const term  = e.target.value.trim().toLowerCase();
    const list  = term ? items.filter(i => i.label.toLowerCase().includes(term)) : items;

    renderQacDropdown(prefix, list);

    // If the user is typing, they've clearly invalidated any prior selection
    const hidden = document.getElementById(`${prefix}_id`);
    if (hidden) hidden.value = '';

    // Position + open
    positionQacDropdown(prefix);
    document.getElementById(`${prefix}_dropdown`)?.classList.add('show');
});

// Open QAC dropdown on focus
document.addEventListener('focus', function (e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    if (prefix !== 'qac_industry' && prefix !== 'qac_location') return;

    const items = searchableSelectData[prefix] || [];
    renderQacDropdown(prefix, items);
    positionQacDropdown(prefix);
    document.getElementById(`${prefix}_dropdown`)?.classList.add('show');
}, true);

function positionQacDropdown(prefix) {
    const input    = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!input || !dropdown) return;

    const rect = input.getBoundingClientRect();
    dropdown.style.top   = `${rect.bottom + 2}px`;
    dropdown.style.left  = `${rect.left}px`;
    dropdown.style.width = `${rect.width}px`;
}

// Country change inside the modal
document.addEventListener('DOMContentLoaded', () => {
    const qacCountry = document.getElementById('qac_country_code');
    if (qacCountry) {
        qacCountry.addEventListener('change', function () {
            resetQacSelect('qac_industry');
            resetQacSelect('qac_location');
            loadQacFormData(this.value);
        });
    }

    const logoInput = document.getElementById('qac_logo_input');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            const wrap = document.getElementById('qac_logo_preview');
            const img  = document.getElementById('qac_logo_image');
            if (file) {
                const reader = new FileReader();
                reader.onload = ev => {
                    img.src = ev.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                wrap.style.display = 'none';
                img.src = '';
            }
        });
    }
});

function clearQacLogo() {
    document.getElementById('qac_logo_input').value = '';
    document.getElementById('qac_logo_preview').style.display = 'none';
    document.getElementById('qac_logo_image').src = '';
}

async function submitQuickAddCompany() {
    const form = document.getElementById('quickAddCompanyForm');
    if (!form) return;

    const name     = document.getElementById('qac_name').value.trim();
    const country  = document.getElementById('qac_country_code').value;
    const website  = document.getElementById('qac_website').value.trim();
    const industry = document.getElementById('qac_industry_id').value;
    const location = document.getElementById('qac_location_id').value;
    const logoFile = document.getElementById('qac_logo_input').files[0];

    if (!name)     { toast('Company name is required.', 'warning'); return; }
    if (!country)  { toast('Please select a country.', 'warning'); return; }
    if (!industry) { toast('Please select an industry.', 'warning'); return; }
    if (!location) { toast('Please select a location.', 'warning'); return; }
    if (!website)  { toast('Website is required.', 'warning'); return; }
    if (!logoFile) { toast('Please upload a company logo.', 'warning'); return; }

    const btn = document.getElementById('qac_submitBtn');
    btn.setAttribute('data-kt-indicator', 'on');
    btn.disabled = true;

    try {
        const fd = new FormData(form);

        const res = await fetch('/admin/companies', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'),
                'Accept': 'application/json'
            },
            body: fd
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            const msg = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Failed to create company');
            throw new Error(msg);
        }

        const created = data.data;

        // Inject into the job-form company dropdown + auto-select
        const items = searchableSelectData['f_company'] || [];
        items.push({ id: created.id, label: created.name });
        searchableSelectData['f_company'] = items;

        selectSearchableOption('f_company', created.id, created.name);

        bsModal('kt_modal_quick_add_company').hide();
        toast(`✓ Company "${created.name}" created and selected.`, 'success');

    } catch (e) {
        toast('Quick-add failed: ' + (e.message || 'Unknown error'), 'danger');
    } finally {
        btn.removeAttribute('data-kt-indicator');
        btn.disabled = false;
    }
}
</script>