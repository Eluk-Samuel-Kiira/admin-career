<script>

    // ================================================================
    // EXTRACT JOB DATA - USE RAW ERRORS FROM CONTROLLER
    // ================================================================
    async function extractJobData() {
        const model = document.getElementById('selectedModel').value;
        const sourceType = document.querySelector('input[name="sourceType"]:checked')?.value || 'text';
        let content = '';

        if (sourceType === 'text') {
            content = document.getElementById('aiSourceText')?.value?.trim();
            if (!content) {
                toast('Please paste some job content first.', 'warning');
                return;
            }
        } else {
            const url = document.getElementById('aiSourceUrl')?.value?.trim();
            if (!url) {
                toast('Please enter a job URL.', 'warning');
                return;
            }
            content = url;
        }

        const btn = document.getElementById('extractBtn');
        const spinner = document.getElementById('extractBtnSpinner');
        const btnText = document.getElementById('extractBtnText');
        const preview = document.getElementById('aiPreviewPanel');
        const status = document.getElementById('extractStatus');

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (btnText) btnText.innerHTML = '<i class="ki-duotone ki-loader fs-3 me-1"></i>Extracting...';
        if (status) {
            status.textContent = 'Processing...';
            status.className = 'badge badge-warning';
        }

        if (preview) {
            preview.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">AI agent extracting job data...</p>
                </div>`;
        }

        try {
            const result = await apiFetch(`${AI_API_BASE}/extract-job`, {
                method: 'POST',
                body: JSON.stringify({
                    model,
                    content,
                    source_type: sourceType,
                    country: document.getElementById('f_country_code')?.value || null,
                }),
            });

            if (result.success === false) {
                // Use structured errors from the controller
                if (result.errors && typeof result.errors === 'object') {
                    const error = new Error('Extraction failed');
                    error.errors = result.errors; // Pass raw errors to catch block
                    throw error;
                } else {
                    throw new Error(result.error || result.message || 'Extraction failed');
                }
            }

            extractedData = result.data;
            renderExtractedPreview(result.data);

            const applyBtn = document.getElementById('applyExtractedBtn');
            if (applyBtn) applyBtn.style.display = 'inline-flex';

            const tokenInfo = document.getElementById('aiTokenInfo');
            if (tokenInfo) tokenInfo.textContent = `${model.toUpperCase()} — extraction complete`;

            if (status) {
                status.textContent = 'Complete';
                status.className = 'badge badge-success';
            }

            if (document.getElementById('autoApplyToggle')?.checked) {
                setTimeout(() => {
                    applyExtractedData();
                    const modal = bsModal('aiExtractModal');
                    if (modal) modal.hide();
                }, 500);
            } else {
                toast('Data extracted. Click "Apply to Form" to fill the form.', 'info');
            }

        } catch (e) {
            // Get errors from the error object
            let modelErrors = [];
            
            if (e.errors && typeof e.errors === 'object') {
                // Use the raw errors from the controller - no modification
                Object.entries(e.errors).forEach(([model, error]) => {
                    modelErrors.push({ model, error });
                });
            } else {
                // Fallback: parse from message (should rarely happen)
                const rawMsg = e.message || 'Extraction failed';
                const parts = rawMsg.split('|');
                if (parts.length > 1) {
                    parts.forEach(part => {
                        const trimmedPart = part.trim();
                        const match = trimmedPart.match(/\[([^\]]+)\]\s*(.*)/);
                        if (match) {
                            modelErrors.push({ model: match[1], error: match[2] });
                        }
                    });
                }
                if (modelErrors.length === 0) {
                    modelErrors.push({ model: 'AI Service', error: rawMsg });
                }
            }

            // Build the error display HTML
            const getModelIcon = (model) => {
                const lower = model.toLowerCase();
                if (lower.includes('gemini')) return '🔴';
                if (lower.includes('openai')) return '🟢';
                if (lower.includes('claude')) return '🟣';
                if (lower.includes('grok')) return '🟠';
                if (lower.includes('cohere')) return '🟡';
                if (lower.includes('mistral')) return '🔵';
                return '⚠️';
            };
            
            let errorItemsHtml = modelErrors.map((item, index) => {
                const icon = getModelIcon(item.model);
                const color = index % 2 === 0 ? 'bg-light' : '';
                return `
                    <div class="d-flex align-items-start gap-2 p-2 ${color} rounded" style="border-bottom: 1px solid #f0f0f0;">
                        <span style="font-size: 16px;">${icon}</span>
                        <div class="flex-grow-1">
                            <strong>${escapeHtml(item.model)}:</strong>
                            <span class="text-break">${escapeHtml(item.error)}</span>
                        </div>
                    </div>`;
            }).join('');
            
            const errorHtml = `
                <div class="alert alert-danger m-2">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="ki-duotone ki-danger fs-3 text-danger flex-shrink-0 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-2">Extraction failed</strong>
                            <div class="small">
                                ${errorItemsHtml}
                            </div>
                            <div class="mt-3 pt-2 border-top border-danger border-opacity-25">
                                <small class="text-muted">
                                    <i class="ki-duotone ki-information-5 fs-7 me-1"></i>
                                    Please check your AI model API keys or try a different model.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (status) {
                status.textContent = 'Failed';
                status.className = 'badge badge-danger';
            }
            
            if (preview) {
                preview.innerHTML = errorHtml;
            }
            
            const firstError = modelErrors[0]?.error || 'Extraction failed';
            toast(firstError, 'danger');
            
        } finally {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (btnText) btnText.innerHTML = '<i class="ki-duotone ki-sparkle fs-3 me-1"></i>Extract Data';
        }
    }


    // ================================================================
    // EXTRACT FROM MULTIPLE IMAGES - USE RAW ERRORS
    // ================================================================
    async function extractFromMultipleImages() {
        if (uploadedImages.length === 0) {
            toast('Please upload at least one image first.', 'error');
            return;
        }

        const model = document.getElementById('imgSelectedModel').value || document.getElementById('selectedModel').value || 'gemini';
        const preview = document.getElementById('imgPreviewPanel');
        const btn = document.getElementById('multiImgExtractBtn');
        const spinner = document.getElementById('multiImgExtractBtnSpinner');
        const btnText = document.getElementById('multiImgExtractBtnText');
        const status = document.getElementById('imgExtractStatus');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        btnText.innerHTML = 'Extracting...';
        if (status) {
            status.textContent = 'Processing...';
            status.className = 'badge badge-warning';
        }
        preview.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="text-muted">Analyzing ${uploadedImages.length} image(s)...</p>
            </div>`;

        try {
            let combinedData = {};
            for (let i = 0; i < uploadedImages.length; i++) {
                const img = uploadedImages[i];
                preview.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p class="text-muted">Processing image ${i + 1} of ${uploadedImages.length}...</p>
                    </div>`;
                const result = await apiFetch(`/ai/extract-image`, {
                    method: 'POST',
                    body: JSON.stringify({ model, image_base64: img.base64 }),
                });
                
                if (result.success === false) {
                    if (result.errors && typeof result.errors === 'object') {
                        const error = new Error('Image extraction failed');
                        error.errors = result.errors;
                        throw error;
                    } else {
                        throw new Error(result.error || result.message || 'Image extraction failed');
                    }
                }
                
                img.extractedData = result.data;
                combinedData = { ...combinedData, ...result.data };
            }
            
            extractedData = combinedData;
            renderExtractedPreview(combinedData);
            
            const sourcePanel = document.getElementById('aiPreviewPanel');
            const targetPanel = document.getElementById('imgPreviewPanel');
            if (sourcePanel && targetPanel) {
                targetPanel.innerHTML = sourcePanel.innerHTML;
            }
            
            document.getElementById('applyImgBtn')?.classList.remove('d-none');
            document.getElementById('imgTokenInfo').textContent = `${model.toUpperCase()} — extracted from ${uploadedImages.length} image(s)`;
            
            if (status) {
                status.textContent = 'Complete';
                status.className = 'badge badge-success';
            }
            
            renderImageGallery();
            
            if (document.getElementById('imgAutoApplyToggle')?.checked) {
                applyImageData();
                bsModal('imageExtractModal').hide();
                toast('Job data extracted and applied to form!', 'success');
            } else {
                toast(`Extracted data from ${uploadedImages.length} image(s). Review then apply.`, 'success');
            }
            
        } catch (e) {
            let modelErrors = [];
            
            if (e.errors && typeof e.errors === 'object') {
                Object.entries(e.errors).forEach(([model, error]) => {
                    modelErrors.push({ model, error });
                });
            } else {
                const rawMsg = e.message || 'Image extraction failed';
                const parts = rawMsg.split('|');
                if (parts.length > 1) {
                    parts.forEach(part => {
                        const trimmedPart = part.trim();
                        const match = trimmedPart.match(/\[([^\]]+)\]\s*(.*)/);
                        if (match) {
                            modelErrors.push({ model: match[1], error: match[2] });
                        }
                    });
                }
                if (modelErrors.length === 0) {
                    modelErrors.push({ model: 'AI Service', error: rawMsg });
                }
            }

            const getModelIcon = (model) => {
                const lower = model.toLowerCase();
                if (lower.includes('gemini')) return '🔴';
                if (lower.includes('openai')) return '🟢';
                if (lower.includes('claude')) return '🟣';
                if (lower.includes('grok')) return '🟠';
                if (lower.includes('cohere')) return '🟡';
                if (lower.includes('mistral')) return '🔵';
                return '⚠️';
            };
            
            let errorItemsHtml = modelErrors.map((item, index) => {
                const icon = getModelIcon(item.model);
                const color = index % 2 === 0 ? 'bg-light' : '';
                return `
                    <div class="d-flex align-items-start gap-2 p-2 ${color} rounded" style="border-bottom: 1px solid #f0f0f0;">
                        <span style="font-size: 16px;">${icon}</span>
                        <div class="flex-grow-1">
                            <strong>${escapeHtml(item.model)}:</strong>
                            <span class="text-break">${escapeHtml(item.error)}</span>
                        </div>
                    </div>`;
            }).join('');
            
            const errorHtml = `
                <div class="alert alert-danger m-2">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="ki-duotone ki-danger fs-3 text-danger flex-shrink-0 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-2">Image extraction failed</strong>
                            <div class="small">
                                ${errorItemsHtml}
                            </div>
                            <div class="mt-3 pt-2 border-top border-danger border-opacity-25">
                                <small class="text-muted">
                                    <i class="ki-duotone ki-information-5 fs-7 me-1"></i>
                                    Please check your AI model API keys or try a different model.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (status) {
                status.textContent = 'Failed';
                status.className = 'badge badge-danger';
            }
            
            preview.innerHTML = errorHtml;
            
            const firstError = modelErrors[0]?.error || 'Image extraction failed';
            toast(firstError, 'danger');
            
        } finally {
            btn.disabled = false;
            spinner.classList.add('d-none');
            btnText.innerHTML = '<i class="ki-duotone ki-picture fs-3 me-1"></i>Extract from All Images';
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