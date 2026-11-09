<script>

    // ================================================================
    // CSRF + REQUEST HELPERS
    // ================================================================
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || window.CSRF_TOKEN
            || '';
    }

    function xsrfFromCookie() {
        const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'X-XSRF-TOKEN': xsrfFromCookie(),
            },
            body: JSON.stringify(body),
        });

        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch (_) {}

        if (res.status === 419) {
            return {
                ok: false,
                status: 419,
                json: {
                    success: false,
                    type: 'session_expired',
                    message: 'Your session has expired. Reload the page and try again.',
                    errors: {},
                },
                text,
            };
        }

        return { ok: res.ok, status: res.status, json, text };
    }

    // Shows the exact message, type and per-model errors from the server.
    // targetId / statusId let the same function serve both the text and image panels.
    function renderExtractionError(title, { status, json, text }, targetId = 'aiPreviewPanel', statusId = 'extractStatus') {
        const panel    = document.getElementById(targetId);
        const statusEl = document.getElementById(statusId);

        let message, type = 'unknown', errors = {}, debug = null;

        if (json) {
            message = json.message || 'Extraction failed.';
            type    = json.type || 'unknown';
            errors  = json.errors || {};
            debug   = json.debug || null;
        } else {
            const plain = (text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
            message = `Server returned HTTP ${status} with a non-JSON response.`;
            type    = 'server_error';
            debug   = plain.slice(0, 500);
        }

        const rows = Object.entries(errors).map(([model, err], i) => `
            <div class="d-flex gap-2 p-2 ${i % 2 === 0 ? 'bg-light' : ''} rounded">
                <strong>${escapeHtml(model)}:</strong>
                <span class="text-break">${escapeHtml(String(err))}</span>
            </div>`).join('');

        if (panel) {
            panel.innerHTML = `
                <div class="alert alert-danger m-2">
                    <strong class="d-block mb-1">${escapeHtml(title)}</strong>
                    <div class="mb-2">${escapeHtml(message)}</div>
                    <div class="small text-muted mb-2">Type: <code>${escapeHtml(type)}</code> &middot; HTTP ${status}</div>
                    ${rows ? `<div class="small">${rows}</div>` : ''}
                    ${debug ? `<details class="mt-2"><summary class="small">Technical detail</summary><pre class="small text-break mt-2 mb-0" style="white-space:pre-wrap">${escapeHtml(String(debug))}</pre></details>` : ''}
                </div>`;
        }

        if (statusEl) {
            statusEl.textContent = 'Failed';
            statusEl.className = 'badge badge-danger';
        }

        toast(message, 'danger');
    }

    // ================================================================
    // EXTRACT JOB DATA (text or URL)
    // ================================================================
    async function extractJobData() {
        const model      = document.getElementById('selectedModel')?.value || null;
        const sourceType = document.querySelector('input[name="sourceType"]:checked')?.value || 'text';

        let content = '';
        if (sourceType === 'text') {
            content = document.getElementById('aiSourceText')?.value?.trim() || '';
            if (!content) {
                toast('Please paste some job content first.', 'warning');
                return;
            }
        } else {
            content = document.getElementById('aiSourceUrl')?.value?.trim() || '';
            if (!content) {
                toast('Please enter a job URL.', 'warning');
                return;
            }
        }

        const btn       = document.getElementById('extractBtn');
        const spinner   = document.getElementById('extractBtnSpinner');
        const btnText   = document.getElementById('extractBtnText');
        const previewEl = document.getElementById('aiPreviewPanel');
        const statusEl  = document.getElementById('extractStatus');

        if (btn) btn.disabled = true;
        spinner?.classList.remove('d-none');
        if (btnText) btnText.innerHTML = '<i class="ki-duotone ki-loader fs-3 me-1"></i>Extracting...';
        if (statusEl) {
            statusEl.textContent = 'Processing...';
            statusEl.className = 'badge badge-warning';
        }
        if (previewEl) {
            previewEl.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">AI agent extracting job data...</p>
                </div>`;
        }

        try {
            let response = await postJson(`${AI_API_BASE}/extract-job`, {
                model,
                content,
                source_type: sourceType,
                country: document.getElementById('f_country_code')?.value || null,
            });

            if (response.status === 202 && response.json?.token) {
                response = await pollExtraction(response.json.token);
            }

            if (!response.ok || !response.json || response.json.success === false) {
                renderExtractionError('Extraction failed', response);
                return;
            }

            extractedData = response.json.data;
            renderExtractedPreview(extractedData);

            const applyBtn = document.getElementById('applyExtractedBtn');
            if (applyBtn) applyBtn.style.display = 'inline-flex';

            const tokenInfo = document.getElementById('aiTokenInfo');
            if (tokenInfo) tokenInfo.textContent = `${(model || 'AI').toUpperCase()} — extraction complete`;

            if (statusEl) {
                statusEl.textContent = 'Complete';
                statusEl.className = 'badge badge-success';
            }

            if (document.getElementById('autoApplyToggle')?.checked) {
                setTimeout(() => {
                    applyExtractedData();
                    bsModal('aiExtractModal')?.hide();
                }, 500);
            } else {
                toast('Data extracted. Click "Apply to Form" to fill the form.', 'info');
            }

        } catch (err) {
            // Reached only when no response arrived at all (network down, server unreachable)
            renderExtractionError('Extraction failed', {
                status: 0,
                json: {
                    success: false,
                    type: 'network',
                    message: 'Could not reach the server. Check your connection and try again.',
                    errors: {},
                },
                text: err?.message || '',
            });
        } finally {
            if (btn) btn.disabled = false;
            spinner?.classList.add('d-none');
            if (btnText) btnText.innerHTML = '<i class="ki-duotone ki-sparkle fs-3 me-1"></i>Extract Data';
        }
    }

    async function pollExtraction(token) {
        const url = `${AI_API_BASE}/extract-job/${token}`;

        for (let attempt = 0; attempt < 600; attempt++) {   // about 15 minutes at 1.5 seconds
            await new Promise(r => setTimeout(r, attempt === 0 ? 800 : 1500));

            let res, text, json = null;
            try {
                res  = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                text = await res.text();
                try { json = JSON.parse(text); } catch (_) {}
            } catch (_) {
                continue;   // a dropped request should not end the wait
            }

            if (!json) continue;
            if (json.status === 'done')  return { ok: true,  status: res.status, json, text };
            if (json.success === false)  return { ok: false, status: res.status, json, text };
        }

        return {
            ok: false, status: 0,
            json: { success: false, type: 'timeout', errors: {},
                    message: 'The extraction is still running. Please check again in a few minutes.' },
            text: '',
        };
    }

    // ================================================================
    // EXTRACT FROM MULTIPLE IMAGES
    // ================================================================
    async function extractFromMultipleImages() {
        if (uploadedImages.length === 0) {
            toast('Please upload at least one image first.', 'error');
            return;
        }

        const model    = document.getElementById('imgSelectedModel')?.value
                    || document.getElementById('selectedModel')?.value
                    || 'gemini';
        const preview  = document.getElementById('imgPreviewPanel');
        const btn      = document.getElementById('multiImgExtractBtn');
        const spinner  = document.getElementById('multiImgExtractBtnSpinner');
        const btnText  = document.getElementById('multiImgExtractBtnText');
        const statusEl = document.getElementById('imgExtractStatus');

        if (btn) btn.disabled = true;
        spinner?.classList.remove('d-none');
        if (btnText) btnText.innerHTML = 'Extracting...';
        if (statusEl) {
            statusEl.textContent = 'Processing...';
            statusEl.className = 'badge badge-warning';
        }

        try {
            let combinedData = {};

            for (let i = 0; i < uploadedImages.length; i++) {
                const img = uploadedImages[i];

                if (preview) {
                    preview.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="text-muted">Processing image ${i + 1} of ${uploadedImages.length}...</p>
                        </div>`;
                }

                const response = await postJson(`${AI_API_BASE}/extract-image`, {
                    model,
                    image_base64: img.base64,
                });

                if (!response.ok || !response.json || response.json.success === false) {
                    renderExtractionError(`Image ${i + 1} extraction failed`, response, 'imgPreviewPanel', 'imgExtractStatus');
                    return;
                }

                img.extractedData = response.json.data;
                combinedData = { ...combinedData, ...response.json.data };
            }

            extractedData = combinedData;
            renderExtractedPreview(combinedData);

            const sourcePanel = document.getElementById('aiPreviewPanel');
            if (sourcePanel && preview) {
                preview.innerHTML = sourcePanel.innerHTML;
            }

            document.getElementById('applyImgBtn')?.classList.remove('d-none');

            const imgTokenInfo = document.getElementById('imgTokenInfo');
            if (imgTokenInfo) {
                imgTokenInfo.textContent = `${model.toUpperCase()} — extracted from ${uploadedImages.length} image(s)`;
            }

            if (statusEl) {
                statusEl.textContent = 'Complete';
                statusEl.className = 'badge badge-success';
            }

            renderImageGallery();

            if (document.getElementById('imgAutoApplyToggle')?.checked) {
                applyImageData();
                bsModal('imageExtractModal')?.hide();
                toast('Job data extracted and applied to form!', 'success');
            } else {
                toast(`Extracted data from ${uploadedImages.length} image(s). Review then apply.`, 'success');
            }

        } catch (err) {
            renderExtractionError('Image extraction failed', {
                status: 0,
                json: {
                    success: false,
                    type: 'network',
                    message: 'Could not reach the server. Check your connection and try again.',
                    errors: {},
                },
                text: err?.message || '',
            }, 'imgPreviewPanel', 'imgExtractStatus');
        } finally {
            if (btn) btn.disabled = false;
            spinner?.classList.add('d-none');
            if (btnText) btnText.innerHTML = '<i class="ki-duotone ki-picture fs-3 me-1"></i>Extract from All Images';
        }
    }


    // ================================================================
    // APPLY EXTRACTED DATA - FIXED
    // ================================================================
    function applyExtractedData() {
        if (!extractedData) {
            toast('No extracted data. Please extract first.', 'warning');
            return;
        }
    
        const d = extractedData;
    
        // ---- Plain text fields ----
        const fieldMap = {
            job_title: 'f_job_title',
            duty_station: 'f_duty_station',
            email: 'f_email',
            telephone: 'f_telephone',
            salary_amount: 'f_salary_amount',
            currency: 'f_currency',
            meta_description: 'f_meta_description',
            keywords: 'f_keywords',
            work_hours: 'f_work_hours',
        };
        Object.entries(fieldMap).forEach(([key, id]) => {
            if (d[key] !== undefined && d[key] !== null && d[key] !== '') {
                const el = document.getElementById(id);
                if (el) el.value = d[key];
            }
        });
    
        // ---- Deadline ----
        if (d.deadline) {
            const el = document.getElementById('f_deadline');
            if (el) {
                el.value = /^\d{4}-\d{2}-\d{2}$/.test(d.deadline)
                    ? d.deadline
                    : (() => { const dt = new Date(d.deadline); return isNaN(dt) ? d.deadline : dt.toISOString().split('T')[0]; })();
            }
        }
    
        // ---- Native <select> dropdowns ----
        [['f_employment_type', d.employment_type], ['f_location_type', d.location_type], ['f_payment_period', d.payment_period]]
            .forEach(([id, value]) => {
                if (!value) return;
                const el = document.getElementById(id);
                if (!el) return;
                const match = Array.from(el.options).find(opt => opt.value.toLowerCase() === String(value).toLowerCase());
                if (match) el.value = match.value;
            });
    
        // ---- Rich text editors (Arial styling already applied server-side - don't re-wrap here) ----
        const richMap = {
            job_description: 'f_job_description_editor',
            responsibilities: 'f_responsibilities_editor',
            qualifications: 'f_qualifications_editor',
            skills: 'f_skills_editor',
            application_procedure: 'f_application_procedure_editor',
        };
        Object.entries(richMap).forEach(([key, editorId]) => {
            if (d[key] && typeof richEditorSet === 'function') {
                richEditorSet(editorId, d[key]);
            }
        });
    
        // ---- Country: f_country_code is a hidden input, not a <select> - no .options here.
        // Don't force-navigate to a different country (that would reload the page and lose
        // everything just filled in) - just tell the user if the AI thinks it's a different one.
        if (d.country_code) {
            const currentCountry = document.getElementById('f_country_code')?.value;
            if (currentCountry && d.country_code !== currentCountry) {
                toast(`Note: this content looks like it's for ${d.country_code}, but you're posting under ${currentCountry}. Switch country above if that's wrong.`, 'info');
            }
        }
    
        // ---- Searchable dropdowns (company, category, industry, location, job type, etc.) ----
        if (typeof autoSelectDropdowns === 'function') {
            autoSelectDropdowns(d);
        }
    
        // ---- Checkboxes ----
        const checkMap = {
            is_urgent: 'f_urgent', is_featured: 'f_featured', is_verified: 'f_verified',
            is_quick_gig: 'f_quickgig', is_resume_required: 'f_resume', is_cover_letter_required: 'f_cover',
            is_academic_documents_required: 'f_academic', is_application_required: 'f_appletter',
            is_whatsapp_contact: 'f_whatsapp', is_telephone_call: 'f_telcall',
        };
        Object.entries(checkMap).forEach(([key, id]) => {
            if (d[key] !== undefined) {
                const el = document.getElementById(id);
                if (el) el.checked = !!d[key];
            }
        });
    
        // ---- Draw attention to the top of the form ----
        const titleField = document.getElementById('f_job_title');
        if (titleField) {
            titleField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            titleField.style.transition = 'background 0.5s';
            titleField.style.background = '#e8f5e9';
            setTimeout(() => { titleField.style.background = ''; }, 2000);
        }
    
        toast('✅ Data applied to form successfully!', 'success');
    }


    // ================================================================
    // AUTO-SELECT DROPDOWNS - FIXED
    // ================================================================
    function autoSelectDropdowns(d) {
        const dropdownMap = {
            jobtype:     { dataKey: 'job_type_name',        fallback: null },
            experience:  { dataKey: 'experience_level_name', fallback: 'entry level' },
            education:   { dataKey: 'education_level_name',  fallback: 'Certificate' },
            category:    { dataKey: 'category_name',         fallback: null },
            industry:    { dataKey: 'industry_name',         fallback: null },
            location:    { dataKey: 'job_location_name',      fallback: null },
            salaryrange: { dataKey: 'salary_range_name',      fallback: null },
        };

        Object.entries(dropdownMap).forEach(([prefix, config]) => {
            const drop = drops[`f_${prefix}`];
            if (!drop) return;
            const value = d[config.dataKey] || config.fallback;
            if (value) {
                const matched = drop.setByName(value, false);
                if (!matched) {
                    // If no match, reset the search input but KEEP the dropdown data
                    // Just clear the selection without destroying the dropdown
                    const hidden = document.getElementById(`f_${prefix}_id`);
                    const search = document.getElementById(`f_${prefix}_search`);
                    if (hidden) hidden.value = '';
                    if (search) search.value = '';
                    
                    // Re-render the dropdown with all available options
                    const items = searchableSelectData[`f_${prefix}`] || [];
                    if (items.length > 0) {
                        renderSearchableDropdown(`f_${prefix}`, items);
                    }
                }
            } else {
                // No value provided - just ensure dropdown shows all options
                const items = searchableSelectData[`f_${prefix}`] || [];
                if (items.length > 0) {
                    renderSearchableDropdown(`f_${prefix}`, items);
                }
            }
        });

        // Company: not selected from a list - just populate the visible search
        // text with the AI's guess, leave it unselected so it's clearly "new".
        if (d.company_name) {
            const companySearch = document.getElementById('f_company_search');
            if (companySearch) companySearch.value = d.company_name;
            const companyHidden = document.getElementById('f_company_id');
            if (companyHidden) companyHidden.value = '';
        }
    }

    
    // ================================================================
    // RENDER EXTRACTED PREVIEW
    // ================================================================
    function renderExtractedPreview(data) {
        const panel = document.getElementById('aiPreviewPanel');
        if (!panel) {
            console.warn('aiPreviewPanel not found');
            return;
        }

        const fields = [
            { key: 'job_title', label: 'Job Title', icon: 'ki-briefcase' },
            { key: 'company_name', label: 'Company', icon: 'ki-building' },
            { key: 'employment_type', label: 'Employment Type', icon: 'ki-clock' },
            { key: 'location_type', label: 'Location Type', icon: 'ki-geolocation' },
            { key: 'duty_station', label: 'Duty Station', icon: 'ki-map' },
            { key: 'deadline', label: 'Deadline', icon: 'ki-calendar' },
            { key: 'salary_amount', label: 'Salary', icon: 'ki-coin' },
            { key: 'currency', label: 'Currency', icon: 'ki-dollar' },
            { key: 'payment_period', label: 'Pay Period', icon: 'ki-repeat' },
            { key: 'email', label: 'Email', icon: 'ki-message' },
            { key: 'telephone', label: 'Phone', icon: 'ki-call' },
            { key: 'experience_level_name', label: 'Experience Level', icon: 'ki-star' },
            { key: 'education_level_name', label: 'Education Level', icon: 'ki-school' },
            { key: 'industry_name', label: 'Industry', icon: 'ki-building-factory' },
            { key: 'category_name', label: 'Category', icon: 'ki-category' },
            { key: 'country_code', label: 'Country', icon: 'ki-flag' },
        ];

        const aiDefaulted = new Set(['employment_type','experience_level_name','education_level_name','deadline']);

        let html = `
            <div class="alert alert-success py-2 mb-2 d-flex align-items-center gap-2 small">
                <i class="ki-duotone ki-robot fs-3 flex-shrink-0"></i>
                <div>Content extracted. Fields marked <span class="badge badge-light-warning ms-1">default</span> were auto-generated.</div>
            </div>
            <div class="d-flex flex-column gap-2" style="font-family: Arial, sans-serif;">`;

        // Show fields
        fields.forEach(f => {
            const val = data[f.key];
            if (val === null || val === undefined || val === '') return;
            const badge = aiDefaulted.has(f.key)
                ? `<span class="badge badge-light-warning ms-1" style="font-size:10px">default</span>`
                : '';
                                            
            // For country code, show country name
            let displayVal = val;
            if (f.key === 'country_code' && val) {
                    const countryMap = {};
                    @foreach(\App\Helpers\CountryHelper::getCountriesWithFlags() as $country)
                        countryMap['{{ $country['code'] }}'] = '{{ $country['name'] }} {{ $country['flag'] }}';
                    @endforeach
                displayVal = countryMap[val] || val;
            }
            
            html += `
                <div class="field-item d-flex gap-2 align-items-start" style="font-family: Arial, sans-serif;">
                    <i class="ki-duotone ${f.icon} text-primary flex-shrink-0 mt-1"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="field-label">${escapeHtml(f.label)}${badge}</div>
                        <div class="field-value" style="font-family: Arial, sans-serif;">${escapeHtml(String(displayVal))}</div>
                    </div>
                </div>`;
        });

        // Show content fields with full HTML preservation
        const contentFields = [
            { key: 'job_description', label: 'Job Description', icon: 'ki-file-text' },
            { key: 'responsibilities', label: 'Responsibilities', icon: 'ki-check-circle' },
            { key: 'qualifications', label: 'Qualifications', icon: 'ki-shield-tick' },
            { key: 'skills', label: 'Skills', icon: 'ki-tools' },
            { key: 'application_procedure', label: 'Application Procedure', icon: 'ki-send' },
        ];

        contentFields.forEach(f => {
            const val = data[f.key];
            if (!val || val === '' || val === '<br>' || val === '<p><br></p>') return;
            
            html += `
                <div class="field-item d-flex gap-2 align-items-start" style="font-family: Arial, sans-serif;">
                    <i class="ki-duotone ${f.icon} text-primary flex-shrink-0 mt-1"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="field-label">${escapeHtml(f.label)}</div>
                        <div class="field-value" style="font-family: Arial, sans-serif; max-height:150px;overflow-y:auto;background:#f8f9fa;padding:8px;border-radius:4px;font-size:13px;">
                            ${val}
                        </div>
                    </div>
                </div>`;
        });

        html += '</div>';
        panel.innerHTML = html;
    }

</script>