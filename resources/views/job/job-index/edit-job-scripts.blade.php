@push('scripts')
<script>
// ================================================================
// EDIT JOB MODAL - SEPARATED SCRIPTS
// ================================================================

// ================================================================
// EDIT JOB
// ================================================================
window.editJob = function(id) {
    // Show loading state
    const modal = new bootstrap.Modal(document.getElementById('kt_modal_edit_job'));
    modal.show();
    
    // Show loading in form fields
    document.getElementById('edit_job_title').value = 'Loading...';
    
    fetch(`/admin/job-posts/${id}`)
        .then(res => res.json())
        .then(data => {
            // Store job data for rich editor population
            window._editingJobData = data;
            
            // Set basic fields
            document.getElementById('edit_job_post_id').value = data.id;
            document.getElementById('edit_job_title').value = data.job_title || '';
            
            if (data.deadline) {
                // If deadline is already in YYYY-MM-DD format
                if (data.deadline.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    document.getElementById('edit_deadline').value = data.deadline;
                } else {
                    // Try to parse and format
                    try {
                        const date = new Date(data.deadline);
                        if (!isNaN(date.getTime())) {
                            document.getElementById('edit_deadline').value = date.toISOString().split('T')[0];
                        }
                    } catch(e) {
                        document.getElementById('edit_deadline').value = data.deadline;
                    }
                }
            }
            document.getElementById('edit_salary_amount').value = data.salary_amount || '';
            document.getElementById('edit_currency').value = data.currency || 'AUD';
            document.getElementById('edit_duty_station').value = data.duty_station || '';
            document.getElementById('edit_location_type').value = data.location_type || 'on-site';
            document.getElementById('edit_job_source').value = data.job_source || 'competitor_website';
            document.getElementById('edit_employment_type').value = data.employment_type || 'full-time';
            document.getElementById('edit_work_hours').value = data.work_hours || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_telephone').value = data.telephone || '';
            document.getElementById('edit_job_reference').value = data.job_reference || '';
            document.getElementById('edit_duration').value = data.duration || '';
            
            // Set checkboxes
            document.getElementById('edit_is_active').checked = data.is_active || false;
            document.getElementById('edit_is_verified').checked = data.is_verified || false;
            document.getElementById('edit_is_urgent').checked = data.is_urgent || false;
            document.getElementById('edit_is_simple_job').checked = data.is_simple_job || false;
            document.getElementById('edit_is_quick_gig').checked = data.is_quick_gig || false;
            document.getElementById('edit_is_whatsapp_contact').checked = data.is_whatsapp_contact || false;
            document.getElementById('edit_is_telephone_call').checked = data.is_telephone_call || false;
            document.getElementById('edit_is_application_required').checked = data.is_application_required || false;
            document.getElementById('edit_is_resume_required').checked = data.is_resume_required !== undefined ? data.is_resume_required : true;
            document.getElementById('edit_is_cover_letter_required').checked = data.is_cover_letter_required || false;
            document.getElementById('edit_is_academic_documents_required').checked = data.is_academic_documents_required || false;
            
            // Set country
            document.getElementById('edit_job_country_code').value = data.country_code || 'AU';
            
            // Build preselected values for searchable selects
            const preselected = {
                company_id: data.company_id,
                job_category_id: data.job_category_id,
                industry_id: data.industry_id,
                job_location_id: data.job_location_id,
                job_type_id: data.job_type_id,
                experience_level_id: data.experience_level_id,
                education_level_id: data.education_level_id,
                salary_range_id: data.salary_range_id
            };
            
            // Load form data with preselected values
            loadFormData(data.country_code || 'AU', preselected).then(() => {
                // After form data is loaded, set rich editor content
                setTimeout(() => {
                    if (typeof richEditorSet === 'function') {
                        richEditorSet('edit_description_editor', data.job_description || '');
                        richEditorSet('edit_responsibilities_editor', data.responsibilities || '');
                        richEditorSet('edit_skills_editor', data.skills || '');
                        richEditorSet('edit_qualifications_editor', data.qualifications || '');
                        richEditorSet('edit_application_editor', data.application_procedure || '');
                    }
                }, 300);
            });
        })
        .catch(err => {
            console.error('Error loading job:', err);
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Failed to load job post details');
            }
        });
};

// ================================================================
// EDIT FORM SUBMISSION
// ================================================================
document.getElementById('editJobPostForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('editJobBtn');
    if (typeof window.showButtonSpinner === 'function') window.showButtonSpinner(btn);
    const id = document.getElementById('edit_job_post_id').value;
    
    // Sync all rich editors
    ['edit_description_editor', 'edit_responsibilities_editor', 'edit_skills_editor', 'edit_qualifications_editor', 'edit_application_editor'].forEach(editorId => {
        const el = document.getElementById(editorId);
        const hiddenId = editorId.replace('_editor', '') + '_hidden';
        const hidden = document.getElementById(hiddenId);
        if (el && hidden) {
            hidden.value = el.innerHTML;
        }
    });
    
    const formData = new FormData(this);
    formData.append('_method', 'PUT');
    
    const booleanFields = ['is_active', 'is_verified', 'is_urgent', 'is_simple_job', 'is_quick_gig', 'is_whatsapp_contact', 'is_telephone_call', 'is_application_required', 'is_resume_required', 'is_cover_letter_required', 'is_academic_documents_required'];
    booleanFields.forEach(field => {
        const checkbox = document.querySelector(`#editJobPostForm input[name="${field}"]`);
        if (checkbox) {
            formData.set(field, checkbox.checked ? '1' : '0');
        }
    });
    
    // Ensure searchable select values are included
    const searchableFields = ['edit_company', 'edit_job_category', 'edit_industry', 'edit_job_location', 'edit_job_type', 'edit_experience_level', 'edit_education_level', 'edit_salary_range'];
    searchableFields.forEach(field => {
        const value = getSearchableValue(field);
        if (value) {
            const hidden = document.getElementById(field);
            if (hidden) {
                // Already set via the searchable select
            }
        }
    });
    
    fetch(`/admin/job-posts/${id}`, {
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
            if (typeof window.showToast === 'function') window.showToast('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_job'));
            if (modal) modal.hide();
            loadJobPosts();
        } else {
            let errorMsg = data.message;
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join('\n');
            }
            if (typeof window.showToast === 'function') window.showToast('error', errorMsg);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof window.showToast === 'function') window.showToast('error', 'Failed to update job post: ' + err.message);
    })
    .finally(() => {
        if (typeof window.hideButtonSpinner === 'function') window.hideButtonSpinner(btn);
    });
});

// ================================================================
// SEARCHABLE SELECT - FIXED SCROLLING
// ================================================================

// Override the position function to handle scrolling better
function positionSearchableDropdown(prefix) {
    const input = document.getElementById(`${prefix}_search`);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!input || !dropdown) return;
    const rect = input.getBoundingClientRect();
    dropdown.style.top = `${rect.bottom + window.scrollY + 2}px`;
    dropdown.style.left = `${rect.left + window.scrollX}px`;
    dropdown.style.width = `${rect.width}px`;
    dropdown.style.maxHeight = '220px';
    dropdown.style.overflowY = 'auto';
}

// Open dropdown with proper positioning
function openSearchableDropdown(prefix) {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        if (d.id !== `${prefix}_dropdown`) d.classList.remove('show');
    });
    positionSearchableDropdown(prefix);
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (dropdown) {
        dropdown.classList.add('show');
        // Ensure the dropdown scrolls with the page
        dropdown.style.position = 'fixed';
    }
}

// Close dropdown
function closeSearchableDropdown(prefix) {
    document.getElementById(`${prefix}_dropdown`)?.classList.remove('show');
}

// Select option and close dropdown
function selectSearchableOption(prefix, id, label) {
    const hidden = document.getElementById(prefix);
    const search = document.getElementById(`${prefix}_search`);
    if (hidden) hidden.value = id;
    if (search) search.value = label;
    closeSearchableDropdown(prefix);
}

// Render dropdown with options
function renderSearchableDropdown(prefix, items) {
    const dropdown = document.getElementById(`${prefix}_dropdown`);
    if (!dropdown) return;
    if (!items || items.length === 0) {
        dropdown.innerHTML = '<div class="searchable-select-empty">No matches found</div>';
        return;
    }
    dropdown.innerHTML = items.map(item => `
        <div class="searchable-select-option" data-id="${escapeHtml(String(item.id))}" data-label="${escapeHtml(item.label)}">
            ${escapeHtml(item.label)}
        </div>
    `).join('');
}

// Set searchable select options
function setSearchableSelectOptions(prefix, items, selectedId = null) {
    searchableSelectData[prefix] = items || [];
    const hiddenInput = document.getElementById(prefix);
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

// Get searchable value
function getSearchableValue(prefix) {
    return document.getElementById(prefix)?.value || '';
}

// ================================================================
// SEARCHABLE SELECT EVENT HANDLERS (FIXED)
// ================================================================

// Handle input for filtering
document.addEventListener('input', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    const items = searchableSelectData[prefix] || [];
    const term = e.target.value.trim().toLowerCase();
    const filtered = term ? items.filter(i => i.label.toLowerCase().includes(term)) : items;
    renderSearchableDropdown(prefix, filtered);
    openSearchableDropdown(prefix);

    const hidden = document.getElementById(prefix);
    if (hidden && hidden.value) {
        const current = items.find(i => String(i.id) === String(hidden.value));
        if (!current || current.label !== e.target.value) hidden.value = '';
    }
});

// Handle focus - open dropdown
document.addEventListener('focus', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    renderSearchableDropdown(prefix, searchableSelectData[prefix] || []);
    openSearchableDropdown(prefix);
}, true);

// Handle focus out - close dropdown after delay
document.addEventListener('focusout', function(e) {
    if (!e.target?.classList?.contains('searchable-select-input')) return;
    const prefix = e.target.id.replace('_search', '');
    setTimeout(() => {
        const hidden = document.getElementById(prefix);
        const search = document.getElementById(`${prefix}_search`);
        if (hidden && search && !hidden.value) search.value = '';
        closeSearchableDropdown(prefix);
    }, 200);
}, true);

// Handle click - select option or close
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
    // Close dropdowns when clicking outside
    if (!e.target.closest('.searchable-select')) {
        document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => d.classList.remove('show'));
    }
});

// Handle scroll - reposition dropdowns
document.addEventListener('scroll', function() {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        const prefix = d.id.replace('_dropdown', '');
        positionSearchableDropdown(prefix);
    });
}, true);

// Handle resize - reposition dropdowns
window.addEventListener('resize', function() {
    document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
        const prefix = d.id.replace('_dropdown', '');
        positionSearchableDropdown(prefix);
    });
});

// Handle modal scroll - reposition dropdowns inside modal
document.addEventListener('scroll', function(e) {
    const modal = document.querySelector('#kt_modal_edit_job .modal-body');
    if (modal && modal.contains(e.target)) {
        document.querySelectorAll('.searchable-select-dropdown.show').forEach(d => {
            const prefix = d.id.replace('_dropdown', '');
            positionSearchableDropdown(prefix);
        });
    }
}, true);

// ================================================================
// LOAD FORM DATA (UPDATED)
// ================================================================
function loadFormData(country, preselected = {}) {
    const countryCode = country || 'AU';
    const endpoint = `/admin/job-posts/form-data?country=${countryCode}`;

    return fetch(endpoint)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Map data to searchable select format
                const companies = (data.companies || []).map(c => ({ id: c.id, label: c.name }));
                const categories = (data.job_categories || []).map(c => ({ id: c.id, label: c.name }));
                const industries = (data.industries || []).map(i => ({ id: i.id, label: i.name }));
                const locations = (data.locations || []).map(l => ({ 
                    id: l.id, 
                    label: l.city ? `${l.district} (${l.city})` : l.district 
                }));
                const jobTypes = (data.job_types || []).map(t => ({ id: t.id, label: t.name }));
                const experienceLevels = (data.experience_levels || []).map(e => ({
                    id: e.id,
                    label: (e.name || '') + (e.min_years !== undefined && e.max_years !== undefined ? ` (${e.min_years || 0}-${e.max_years || '∞'} years)` : '')
                }));
                const educationLevels = (data.education_levels || []).map(e => ({ id: e.id, label: e.name }));
                const salaryRanges = (data.salary_ranges || []).map(s => ({ id: s.id, label: s.name }));

                // Set searchable select options
                setSearchableSelectOptions('edit_company', companies, preselected.company_id || getSearchableValue('edit_company'));
                setSearchableSelectOptions('edit_job_category', categories, preselected.job_category_id || getSearchableValue('edit_job_category'));
                setSearchableSelectOptions('edit_industry', industries, preselected.industry_id || getSearchableValue('edit_industry'));
                setSearchableSelectOptions('edit_job_location', locations, preselected.job_location_id || getSearchableValue('edit_job_location'));
                setSearchableSelectOptions('edit_job_type', jobTypes, preselected.job_type_id || getSearchableValue('edit_job_type'));
                setSearchableSelectOptions('edit_experience_level', experienceLevels, preselected.experience_level_id || getSearchableValue('edit_experience_level'));
                setSearchableSelectOptions('edit_education_level', educationLevels, preselected.education_level_id || getSearchableValue('edit_education_level'));
                setSearchableSelectOptions('edit_salary_range', salaryRanges, preselected.salary_range_id || getSearchableValue('edit_salary_range'));

                // Initialize rich editors after data is loaded
                initRichEditors();
            }
        })
        .catch(err => console.error('Error loading form data:', err));
}
</script>
@endpush