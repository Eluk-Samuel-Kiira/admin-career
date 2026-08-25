
<script>
// ================================================================
// GLOBALS
// ================================================================
const searchableSelectData = {};
let extractedData = null;
let uploadedImages = [];
let selectedImageIndex = null;
const drops = {}; 
const API_BASE = '/admin/job-posts';
const AI_API_BASE = '/ai';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

// ================================================================
// DROP HANDLES - wires `drops[prefix]` to the searchable-select
// functions so autoSelectDropdowns() can actually select things.
// ================================================================
function makeDropHandle(prefix) {
    return {
        setByName(name, exact = true) {
            if (!name) return false;
            const items = searchableSelectData[prefix] || [];
            const term = String(name).trim().toLowerCase();

            let match = items.find(i => i.label.trim().toLowerCase() === term);

            if (!match && !exact) {
                match = items.find(i => i.label.toLowerCase().includes(term))
                     || items.find(i => term.includes(i.label.toLowerCase()));
            }

            if (match) {
                selectSearchableOption(prefix, match.id, match.label);
                return true;
            }
            return false;
        },
        reset() {
            // Clear selection but keep dropdown functional
            const hidden = document.getElementById(`${prefix}_id`);
            const search = document.getElementById(`${prefix}_search`);
            if (hidden) hidden.value = '';
            if (search) search.value = '';
            
            // Re-render dropdown with all items
            const items = searchableSelectData[prefix] || [];
            if (items.length > 0) {
                renderSearchableDropdown(prefix, items);
            }
            // Close dropdown
            closeSearchableDropdown(prefix);
        }
    };
}

['f_company', 'f_category', 'f_industry', 'f_location', 'f_jobtype', 'f_experience', 'f_education', 'f_salaryrange']
    .forEach(prefix => { drops[prefix] = makeDropHandle(prefix); });



// ================================================================
// DOM READY
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Load AI models for text
    loadAiModels();
    
    // Load AI models for image
    loadImageModels();
    
    // Initialize rich editors
    initRichEditors();
    
    // Load dropdowns
    loadDropdowns();
    
    // Initialize char counters
    initCharCounters();
    
    // Initialize source type toggle
    initSourceTypeToggle();
    
    // Set default deadline to 2 weeks from now
    const deadline = document.getElementById('f_deadline');
    if (deadline) {
        const date = new Date();
        date.setDate(date.getDate() + 14);
        deadline.value = date.toISOString().split('T')[0];
    }
    
    // Set currency based on country from database
    const countrySelect = document.getElementById('f_country_code');
    const currencyInput = document.getElementById('f_currency');
    
    if (countrySelect && currencyInput) {
        // Get currency from data attribute (set by Blade)
        const countryCurrency = JSON.parse(document.getElementById('countryCurrencyData')?.value || '{}');
        
        // Set initial currency
        const selectedCountry = countrySelect.value || 'AU';
        if (countryCurrency[selectedCountry]) {
            currencyInput.value = countryCurrency[selectedCountry];
        }
        
        // Update currency when country changes
        countrySelect.addEventListener('change', function() {
            const country = this.value;
            if (countryCurrency[country]) {
                currencyInput.value = countryCurrency[country];
            }
        });
    }
    
});

// ================================================================
// LOAD AI MODELS
// ================================================================
// ============================================================
// LOAD AI MODELS
// ============================================================
async function loadAiModels() {
    try {
        const response = await fetch('/ai/models', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            }
        });
        const result = await response.json();
        if (result.success) {
            renderModelSelector(result.data, result.default);
        }
    } catch (e) {
        console.error('Failed to load AI models:', e);
    }
}

async function loadImageModels() {
    try {
        const response = await fetch('/ai/models', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            }
        });
        const result = await response.json();
        if (result.success) {
            renderImageModelSelector(result.data, result.default);
        }
    } catch (e) {
        console.error('Failed to load image AI models:', e);
    }
}


// ============================================================
// TEXT MODEL SELECTOR - COMPACT
// ============================================================
function renderModelSelector(models, defaultModel) {
    const container = document.getElementById('modelSelector');
    if (!container) {
        console.warn('Model selector container not found');
        return;
    }
    
    container.innerHTML = '';
    
    const entries = Object.entries(models);
    
    if (entries.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-3">
                <span class="text-muted">No models available</span>
            </div>
        `;
        return;
    }
    
    entries.forEach(([key, model]) => {
        const isDefault = key === defaultModel;
        const col = document.createElement('div');
        col.className = 'col-6 col-md-3';
        col.innerHTML = `
            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-3 w-100 ${isDefault ? 'active' : ''}" 
                   data-kt-button="true"
                   data-model="${key}"
                   onclick="selectModel(this,'${key}')"
                   style="cursor:pointer;transition:all .15s;border-width:2px;text-decoration:none;">
                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                    <input class="form-check-input" type="radio" name="ai_model" value="${key}" ${isDefault ? 'checked' : ''} />
                </span>
                <span class="ms-2 d-flex align-items-center gap-2">
                    <i class="ki-duotone ${model.icon} fs-2" style="color:${model.color}"></i>
                    <span class="fs-7 fw-bold text-gray-800">${model.name}</span>
                    ${isDefault ? '<i class="ki-duotone ki-check-circle fs-5 text-primary ms-auto"></i>' : ''}
                </span>
            </label>
        `;
        container.appendChild(col);
    });
    
    // Set default model
    document.getElementById('selectedModel').value = defaultModel;
    const display = document.getElementById('selectedModelDisplay');
    if (display) {
        display.textContent = models[defaultModel]?.name || 'Claude';
    }
    
    // Also update modal if it exists
    const modalDisplay = document.getElementById('modalSelectedModel');
    if (modalDisplay) {
        modalDisplay.textContent = models[defaultModel]?.name || 'Claude';
    }
}

function selectModel(el, modelId) {
    // Update radio button
    const radio = el.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    
    // Update active state
    document.querySelectorAll('#modelSelector .btn').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedModel').value = modelId;
    
    // Update display badge
    const modelName = el.querySelector('.fs-7.fw-bold.text-gray-800')?.textContent || modelId;
    const display = document.getElementById('selectedModelDisplay');
    if (display) display.textContent = modelName;
    
    // Also update modal if it exists
    const modalDisplay = document.getElementById('modalSelectedModel');
    if (modalDisplay) modalDisplay.textContent = modelName;
}

// ============================================================
// IMAGE MODEL SELECTOR - COMPACT
// ============================================================
function renderImageModelSelector(models, defaultModel) {
    const container = document.getElementById('imgModelSelector');
    if (!container) {
        console.warn('Image model selector container not found');
        return;
    }
    
    container.innerHTML = '';
    
    // Filter only models that support images
    const imageModels = Object.entries(models).filter(([key, model]) => 
        model.supports && model.supports.includes('image')
    );
    
    // console.log('Image models found:', imageModels.length);
    
    if (imageModels.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-3">
                <span class="text-muted">No vision models available</span>
            </div>
        `;
        return;
    }
    
    imageModels.forEach(([key, model]) => {
        const isDefault = key === defaultModel;
        const col = document.createElement('div');
        col.className = 'col-6 col-md-3';
        col.innerHTML = `
            <label class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-3 w-100 ${isDefault ? 'active' : ''}" 
                   data-kt-button="true"
                   data-model="${key}"
                   onclick="selectImageModel(this,'${key}')"
                   style="cursor:pointer;transition:all .15s;border-width:2px;text-decoration:none;">
                <span class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                    <input class="form-check-input" type="radio" name="img_ai_model" value="${key}" ${isDefault ? 'checked' : ''} />
                </span>
                <span class="ms-2 d-flex align-items-center gap-2">
                    <i class="ki-duotone ${model.icon} fs-2" style="color:${model.color}"></i>
                    <span class="fs-7 fw-bold text-gray-800">${model.name}</span>
                    ${isDefault ? '<i class="ki-duotone ki-check-circle fs-5 text-primary ms-auto"></i>' : ''}
                </span>
            </label>
        `;
        container.appendChild(col);
    });
    
    // Set default if available
    if (imageModels.length > 0) {
        const firstKey = imageModels[0][0];
        const isDefaultAvailable = imageModels.some(([key]) => key === defaultModel);
        const selectedKey = isDefaultAvailable ? defaultModel : firstKey;
        document.getElementById('imgSelectedModel').value = selectedKey;
        const display = document.getElementById('imgModelDisplay');
        if (display) {
            const model = imageModels.find(([key]) => key === selectedKey);
            if (model) display.textContent = model[1].name;
        }
    }
}

function selectImageModel(el, modelId) {
    // Update radio button
    const radio = el.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    
    // Update active state
    document.querySelectorAll('#imgModelSelector .btn').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('imgSelectedModel').value = modelId;
    
    // Update display badge
    const modelName = el.querySelector('.fs-7.fw-bold.text-gray-800')?.textContent || modelId;
    const display = document.getElementById('imgModelDisplay');
    if (display) display.textContent = modelName;
}



// ================================================================
// SEARCHABLE SELECT FUNCTIONS
// ================================================================
function setSearchableSelectOptions(prefix, items, selectedId = null) {
    searchableSelectData[prefix] = items || [];
    const hiddenInput = document.getElementById(`${prefix}_id`);  
    const searchInput = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    
    if (!hiddenInput || !searchInput || !dropdown) return;

    let match = null;
    if (selectedId) {
        match = (items || []).find(i => String(i.id) === String(selectedId));
    }
    
    if (match) {
        hiddenInput.value = match.id;
        searchInput.value = match.label;
    } else {
        hiddenInput.value = '';
        searchInput.value = '';
    }
    renderSearchableDropdown(prefix, items || []);
}

function renderSearchableDropdown(prefix, items) {
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!dropdown) return;
    
    if (!items || items.length === 0) {
        dropdown.innerHTML = '<div class="searchable-select-empty">No options available</div>';
        return;
    }
    
    dropdown.innerHTML = items.map(item => `
        <div class="searchable-select-option" 
             data-id="${escapeHtml(String(item.id))}" 
             data-label="${escapeHtml(item.label)}"
             onclick="selectSearchableOption('${prefix}', '${item.id}', '${escapeHtml(item.label)}')"
             style="cursor:pointer;padding:8px 14px;transition:background 0.15s ease;">
            ${escapeHtml(item.label)}
        </div>
    `).join('');
}

// ================================================================
// CLEAR FORM - FIXED
// ================================================================
function clearForm() {
    // Clear plain text fields
    const plainFieldIds = [
        'f_job_title', 'f_duty_station', 'f_deadline',
        'f_email', 'f_telephone', 'f_work_hours',
        'f_salary_amount', 'f_currency','f_job_source',
        'f_meta_title', 'f_meta_description', 'f_keywords',
    ];
    plainFieldIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    // Native <select> fields - reset to their first/default option.
    const selectDefaults = {
        f_employment_type: 'full-time',
        f_location_type: 'on-site',
        f_payment_period: 'monthly',
    };
    Object.entries(selectDefaults).forEach(([id, defaultValue]) => {
        const el = document.getElementById(id);
        if (el) el.value = defaultValue;
    });

    // Checkboxes - reset to their original checked state.
    const checkboxDefaults = {
        f_whatsapp: false, f_telcall: false,
        f_featured: false, f_urgent: false, f_quickgig: false, f_verified: false,
        f_resume: true, f_cover: false, f_academic: false, f_appletter: false,
    };
    Object.entries(checkboxDefaults).forEach(([id, defaultChecked]) => {
        const el = document.getElementById(id);
        if (el) el.checked = defaultChecked;
    });

    // Clear rich editors
    const editors = [
        'f_job_description_editor',
        'f_responsibilities_editor',
        'f_qualifications_editor',
        'f_skills_editor',
        'f_application_procedure_editor'
    ];
    editors.forEach(id => {
        if (typeof richEditorSet === 'function') {
            richEditorSet(id, '');
        }
    });

    // ========== CLEAR SEARCHABLE SELECTS ==========
    // Only clear the visible search text and the selected id.
    // Never touch dropdown.innerHTML or searchableSelectData - the loaded
    // options must survive a "Clear Form" click so the lists still work
    // immediately afterward without needing to reload from the server.
    const searchablePrefixes = [
        'f_company', 'f_category', 'f_industry', 'f_location',
        'f_jobtype', 'f_experience', 'f_education', 'f_salaryrange'
    ];

    searchablePrefixes.forEach(prefix => {
        const searchInput = document.getElementById(`${prefix}_search`);
        if (searchInput) searchInput.value = '';

        const hiddenInput = document.getElementById(`${prefix}_id`);
        if (hiddenInput) {
            hiddenInput.value = '';
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        closeSearchableDropdown(prefix);
    });

    // Clear form errors
    const errorDiv = document.getElementById('formErrors');
    if (errorDiv) {
        errorDiv.innerHTML = '';
    }

    extractedData = null;
    hideBanner();
    toast('Form cleared successfully', 'info');
}

function selectSearchableOption(prefix, id, label) {
    const hidden = document.getElementById(`${prefix}_id`);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) hidden.value = id;
    if (search) search.value = label;
    closeSearchableDropdown(prefix);
}

function getSearchableValue(prefix) {
    const hidden = document.getElementById(`${prefix}_id`);
    return hidden ? hidden.value : '';
}

function positionSearchableDropdown(prefix) {
    const input = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    const rect = input.getBoundingClientRect();   // viewport-relative coordinates
    dropdown.style.top = `${rect.bottom + 2}px`;
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.width = `${rect.width}px`;
}

function openSearchableDropdown(prefix) {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        if (d.id !== `${prefix}_dropdown`) d.classList.remove('show');
    });
    positionSearchableDropdown(prefix);
    document.getElementById(`${prefix}_dropdown`)?.classList.add('show');
}

function closeSearchableDropdown(prefix) {
    document.getElementById(`${prefix}_dropdown`)?.classList.remove('show');
}

// ================================================================
// SEARCHABLE SELECT EVENT HANDLERS
// ================================================================
document.addEventListener('input', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    const items = searchableSelectData[prefix] || [];
    const term = e.target.value.trim().toLowerCase();
    const filtered = term ? items.filter(i => i.label.toLowerCase().includes(term)) : items;
    renderSearchableDropdown(prefix, filtered);
    openSearchableDropdown(prefix);
    const hidden = document.getElementById(`${prefix}_id`)
    if (hidden && hidden.value) {
        const current = items.find(i => String(i.id) === String(hidden.value));
        if (!current || current.label !== e.target.value) hidden.value = '';
    }
});

document.addEventListener('focus', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    const items = searchableSelectData[prefix] || [];
    renderSearchableDropdown(prefix, items);
    openSearchableDropdown(prefix);
}, true);

document.addEventListener('focusout', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    setTimeout(() => {
        const hidden = document.getElementById(`${prefix}_id`); 
        const search = document.getElementById(`${prefix}_search`);
        if (hidden && search && !hidden.value) search.value = '';
    }, 150);
}, true);

document.addEventListener('click', function(e) {
    const option = e.target.closest('.searchable-select-option');
    if (option) {
        const dropdown = option.closest('.searchable-select-dropdown');
        if (dropdown) {
            const prefix = dropdown.id.replace('_dropdown', '');
            selectSearchableOption(prefix, option.dataset.id, option.dataset.label);
        }
        return;
    }
    if (!e.target.closest('.searchable-select')) {
        document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => d.classList.remove('show'));
    }
});

document.addEventListener('scroll', function(e) {
    const t = e.target;
    if (t && t.nodeType === 1 && t.closest('.searchable-select-dropdown')) return; // scrolling the list itself - keep it open
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => d.classList.remove('show'));
}, true);

window.addEventListener('resize', function() {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        const prefix = d.id.replace('_dropdown', '');
        positionSearchableDropdown(prefix);
    });
});

// ================================================================
// LOAD DROPDOWNS
// ================================================================
function loadDropdowns() {
    const country = document.getElementById('f_country_code')?.value || 'AU';
    loadFormData(country);
}

function loadFormData(country, preselected = {}) {
    const countryCode = country || 'AU';
    const endpoint = `/admin/job-posts/form-data?country=${countryCode}`;

    fetch(endpoint)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const companies = (data.companies || []).map(c => ({ id: c.id, label: c.name }));
                const categories = (data.job_categories || []).map(c => ({ id: c.id, label: c.name }));
                const industries = (data.industries || []).map(i => ({ id: i.id, label: i.name }));
                const locations = (data.locations || []).map(l => ({ 
                    id: l.id, label: l.city ? `${l.district} (${l.city})` : l.district 
                }));
                const jobTypes = (data.job_types || []).map(t => ({ id: t.id, label: t.name }));
                const experienceLevels = (data.experience_levels || []).map(e => ({
                    id: e.id,
                    label: (e.name || '') + (e.min_years !== undefined && e.max_years !== undefined ? ` (${e.min_years || 0}-${e.max_years || '∞'} years)` : '')
                }));
                const educationLevels = (data.education_levels || []).map(e => ({ id: e.id, label: e.name }));
                const salaryRanges = (data.salary_ranges || []).map(s => ({ id: s.id, label: s.name }));

                setSearchableSelectOptions('f_company', companies, preselected.company_id || getSearchableValue('f_company'));
                setSearchableSelectOptions('f_category', categories, preselected.job_category_id || getSearchableValue('f_category'));
                setSearchableSelectOptions('f_industry', industries, preselected.industry_id || getSearchableValue('f_industry'));
                setSearchableSelectOptions('f_location', locations, preselected.job_location_id || getSearchableValue('f_location'));
                setSearchableSelectOptions('f_jobtype', jobTypes, preselected.job_type_id || getSearchableValue('f_jobtype'));
                setSearchableSelectOptions('f_experience', experienceLevels, preselected.experience_level_id || getSearchableValue('f_experience'));
                setSearchableSelectOptions('f_education', educationLevels, preselected.education_level_id || getSearchableValue('f_education'));
                setSearchableSelectOptions('f_salaryrange', salaryRanges, preselected.salary_range_id || getSearchableValue('f_salaryrange'));
            }
        })
        .catch(err => console.error('Error loading form data:', err));
}

// ================================================================
// RICH EDITOR FUNCTIONS
// ================================================================
function initRichEditors() {
    const editors = [
        { id: 'f_job_description_editor', container: 'f_job_description_editor_container', hidden: 'f_job_description_hidden', placeholder: 'Enter job description...', height: 220 },
        { id: 'f_responsibilities_editor', container: 'f_responsibilities_editor_container', hidden: 'f_responsibilities_hidden', placeholder: 'Enter responsibilities...', height: 180 },
        { id: 'f_qualifications_editor', container: 'f_qualifications_editor_container', hidden: 'f_qualifications_hidden', placeholder: 'Enter qualifications...', height: 160 },
        { id: 'f_skills_editor', container: 'f_skills_editor_container', hidden: 'f_skills_hidden', placeholder: 'Enter skills...', height: 120 },
        { id: 'f_application_procedure_editor', container: 'f_application_procedure_editor_container', hidden: 'f_application_procedure_hidden', placeholder: 'Enter application procedure...', height: 100 },
    ];
    
    editors.forEach(editor => {
        const container = document.getElementById(editor.container);
        if (container && !document.getElementById(editor.id)) {
            container.innerHTML = buildRichEditor(editor.id, editor.id, editor.placeholder, editor.height);
        }
    });
}

function buildRichEditor(id, name, placeholder, height = 160) {
    const s = `fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"`;
    return `
    <div class="rich-editor-wrapper border rounded overflow-hidden" data-editor-id="${id}">
        <div class="rich-editor-toolbar d-flex flex-wrap align-items-center gap-1 px-2 py-1 border-bottom bg-light">
            <button type="button" class="re-btn" onclick="reFmt('${id}','undo')" title="Undo">
                <svg viewBox="0 0 24 24" ${s}><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.7"/></svg>
            </button>
            <button type="button" class="re-btn" onclick="reFmt('${id}','redo')" title="Redo">
                <svg viewBox="0 0 24 24" ${s}><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.7"/></svg>
            </button>
            <div class="re-sep"></div>
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
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h2')" title="Heading 2">H2</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','h3')" title="Heading 3">H3</button>
            <button type="button" class="re-btn re-btn-text" onclick="reFmt('${id}','formatBlock','p')"  title="Paragraph">P</button>
            <div class="re-sep"></div>
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
            <button type="button" class="re-btn" onclick="reFmt('${id}','removeFormat')" title="Clear formatting">
                <svg viewBox="0 0 24 24" ${s}><path d="M4 7l4-4 12 12-4 4"/><path d="M14.5 2.5l7 7"/><line x1="2" y1="22" x2="22" y2="22"/><path d="M3 17l4-4"/></svg>
            </button>
            <button type="button" class="re-btn re-btn-danger" onclick="richEditorClear('${id}')" title="Clear all content">
                <svg viewBox="0 0 24 24" ${s}><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
        </div>
        <div id="${id}" contenteditable="true" class="rich-editor-body p-3"
            style="min-height:${height}px;max-height:${height * 2}px;overflow-y:auto;outline:none;font-size:14px;line-height:1.7"
            data-placeholder="${placeholder}" oninput="richEditorSync('${id}'); updateStats('${id}')">
        </div>
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
    if (txt) document.execCommand('createLink', false, url);
    else document.execCommand('insertHTML', false, `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
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
    if (el && hidden) hidden.value = el.innerHTML;
    updateStats(id);
}

function richEditorGet(id) {
    const el = document.getElementById(id);
    return el ? el.innerHTML : '';
}

function richEditorSet(id, html) {
    const el = document.getElementById(id);
    const hidden = document.getElementById(id + '_hidden');
    if (el) el.innerHTML = html ?? '';
    if (hidden) hidden.value = html ?? '';
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

// Keyboard shortcuts
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
    document.querySelectorAll('[contenteditable="true"][id]').forEach(el => richEditorSync(el.id));
});

// ================================================================
// ESCAPE HTML
// ================================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toast(msg, type = 'success') {
    if (typeof showToast === 'function') {
        showToast(type, msg);
    } else {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = 9999;
        toast.style.maxWidth = '500px';
        toast.innerHTML = `${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }
}

function showBanner(text) {
    const b = document.getElementById('aiBanner');
    if (b) {
        document.getElementById('aiBannerText').textContent = text;
        b.classList.remove('d-none');
        b.classList.add('d-flex');
    }
}

function hideBanner() {
    const b = document.getElementById('aiBanner');
    if (b) { b.classList.add('d-none'); b.classList.remove('d-flex'); }
}

function bsModal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
}

function toggleSeo() {
    const body = document.getElementById('seoBody');
    const chevron = document.getElementById('seoChevron');
    if (!body || !chevron) return;
    const visible = body.style.display !== 'none';
    body.style.display = visible ? 'none' : 'block';
    chevron.className = visible ? 'ki-duotone ki-chevron-down fs-3' : 'ki-duotone ki-chevron-up fs-3';
}

function initCharCounters() {
    [['f_meta_title','metaTitleCount'], ['f_meta_description','metaDescCount']].forEach(([fId, cId]) => {
        const field = document.getElementById(fId);
        const count = document.getElementById(cId);
        if (field && count) {
            field.addEventListener('input', () => count.textContent = `${field.value.length}/${field.maxLength}`);
        }
    });
}

function initSourceTypeToggle() {
    function toggle() {
        const val = document.querySelector('input[name="sourceType"]:checked')?.value;
        const textPanel = document.getElementById('textSourcePanel');
        const urlPanel = document.getElementById('urlSourcePanel');
        if (textPanel && urlPanel) {
            textPanel.style.display = val === 'url' ? 'none' : 'block';
            urlPanel.style.display = val === 'url' ? 'block' : 'none';
        }
    }
    document.querySelectorAll('input[name="sourceType"]').forEach(r => r.addEventListener('change', toggle));
    toggle();
}

// ================================================================
// AI FUNCTIONS
// ================================================================
async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            ...(options.headers ?? {}),
        },
    });
    const data = await res.json();
    if (!res.ok) {
        const err = new Error(data.error || data.message || `Request failed (HTTP ${res.status})`);
        Object.assign(err, data); // keep .error, .errors, etc. available too
        throw err;
    }
    return data;
}


async function aiEnhanceField(fieldName, instruction) {
    const editorMap = {
        job_description: 'f_job_description_editor',
        responsibilities: 'f_responsibilities_editor',
        qualifications: 'f_qualifications_editor',
        skills: 'f_skills_editor',
        application_procedure: 'f_application_procedure_editor',
    };

    const editorId = editorMap[fieldName];
    let currentContent = editorId ? richEditorGet(editorId) : document.getElementById(`f_${fieldName}`)?.value || '';

    const isBlank = !currentContent || currentContent.replace(/<[^>]*>/g, '').trim() === '';
    const jobTitle = document.getElementById('f_job_title')?.value?.trim() || '';

    if (isBlank) {
        if (!jobTitle) { toast('Enter a job title first so AI knows what to generate.', 'warning'); return; }
        instruction = `Generate professional ${fieldName.replace(/_/g, ' ')} content for a "${jobTitle}" role.`;
        currentContent = `Job Title: ${jobTitle}`;
    }

    const model = document.getElementById('selectedModel')?.value || 'claude';
    const btn = document.getElementById(`btn-enhance-${fieldName}`);
    let origHtml = '';
    if (btn) { origHtml = btn.innerHTML; btn.disabled = true; btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Working…`; }

    showBanner(`AI is enhancing ${fieldName.replace(/_/g, ' ')}…`);

    try {
        const stripped = currentContent.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        const result = await apiFetch(`/ai/enhance-field`, {
            method: 'POST',
            body: JSON.stringify({ model, field_name: fieldName, content: stripped, instruction }),
        });
        let enhanced = result.enhanced?.replace(/```html\n?|```\n?/g, '').trim() || '';
        if (!enhanced) throw new Error('AI returned empty content. Please try again.');
        if (editorId) richEditorSet(editorId, enhanced);
        else document.getElementById(`f_${fieldName}`).value = enhanced.replace(/<[^>]*>/g, '');
        toast(`✓ ${fieldName.replace(/_/g, ' ')} improved by AI. Please review.`, 'success');
    } catch (e) {
        toast('Enhancement failed: ' + (e.message || 'Unknown error'), 'danger');
    } finally {
        hideBanner();
        if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
    }
}

async function aiGenerateFromUrl() {
    const modal = bsModal('aiExtractModal');
    modal.show();
    const urlRadio = document.getElementById('srcUrl');
    if (urlRadio) {
        urlRadio.checked = true;
        document.getElementById('textSourcePanel').style.display = 'none';
        document.getElementById('urlSourcePanel').style.display = 'block';
    }
    document.getElementById('aiSourceUrl')?.focus();
    toast('Paste a job URL in the modal and click "Extract Data"', 'info');
}

function openAiExtractModal() { bsModal('aiExtractModal').show(); }
function openImageExtractModal() { bsModal('imageExtractModal').show(); }

// ================================================================
// IMAGE EXTRACTION FUNCTIONS
// ================================================================

function handleMultiImgDrop(e) {
    e.preventDefault();
    processMultipleImages(Array.from(e.dataTransfer.files));
}

function handleMultiImgSelect(e) {
    processMultipleImages(Array.from(e.target.files));
}

function processMultipleImages(files) {
    const remainingSlots = 5 - uploadedImages.length;
    const validFiles = files.slice(0, remainingSlots).filter(file => {
        const isValidType = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type);
        const isValidSize = file.size <= 5 * 1024 * 1024;
        if (!isValidType) toast(`${file.name}: Invalid format`, 'error');
        if (!isValidSize) toast(`${file.name}: Exceeds 5MB`, 'error');
        return isValidType && isValidSize;
    });

    validFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const base64 = e.target.result.split(',')[1];
            uploadedImages.push({ file, base64, previewUrl: e.target.result, extractedData: null });
            renderImageGallery();
        };
        reader.readAsDataURL(file);
    });
    if (validFiles.length === 0 && files.length > 0) {
        toast('No valid images added. Max 5 images, 5MB each, JPG/PNG/GIF/WEBP only.', 'warning');
    }
}

function renderImageGallery() {
    const container = document.getElementById('galleryContainer');
    if (!container) return;
    if (uploadedImages.length === 0) {
        container.innerHTML = '<div class="text-muted small p-2">No images uploaded. Click or drag to add images.</div>';
        return;
    }
    container.innerHTML = uploadedImages.map((img, idx) => `
        <div class="gallery-image-item ${selectedImageIndex === idx ? 'selected' : ''}" onclick="selectGalleryImage(${idx})">
            <img src="${img.previewUrl}" alt="Image ${idx + 1}">
            <div class="remove-btn" onclick="event.stopPropagation(); removeImage(${idx})">
                <i class="ki-duotone ki-cross fs-1"></i>
            </div>
            ${img.extractedData ? '<div class="position-absolute bottom-0 end-0 m-1"><i class="ki-duotone ki-check-circle text-success fs-2"></i></div>' : ''}
        </div>
    `).join('');
}

function selectGalleryImage(index) {
    selectedImageIndex = index;
    renderImageGallery();
    const preview = document.getElementById('imgPreview');
    if (preview && uploadedImages[index]) {
        preview.src = uploadedImages[index].previewUrl;
        document.getElementById('imgPreviewWrap').style.display = 'block';
    }
}

function removeImage(index) {
    if (uploadedImages[index].previewUrl?.startsWith('blob:')) URL.revokeObjectURL(uploadedImages[index].previewUrl);
    uploadedImages.splice(index, 1);
    if (selectedImageIndex >= uploadedImages.length) selectedImageIndex = uploadedImages.length - 1;
    if (uploadedImages.length === 0) document.getElementById('imgPreviewWrap').style.display = 'none';
    else if (selectedImageIndex >= 0) selectGalleryImage(selectedImageIndex);
    renderImageGallery();
}

function clearAllImages() {
    uploadedImages.forEach(img => { if (img.previewUrl?.startsWith('blob:')) URL.revokeObjectURL(img.previewUrl); });
    uploadedImages = [];
    selectedImageIndex = null;
    renderImageGallery();
    document.getElementById('imgPreviewWrap').style.display = 'none';
    document.getElementById('multiImgFileInput').value = '';
}



function applyImageData() {
    if (!extractedData) { toast('No extracted data to apply.', 'warning'); return; }
    applyExtractedData();
}





// ================================================================
// SEARCHABLE SELECT - CLEAR FUNCTION
// ================================================================
function clearSearchableSelect(prefix) {
    const hidden = document.getElementById(`${prefix}_id`);  
    const search = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    
    if (hidden) {
        hidden.value = '';
        // Trigger change event to notify any listeners
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (search) {
        search.value = '';
    }
    if (dropdown) {
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
        dropdown.classList.remove('show');
    }
}
</script>